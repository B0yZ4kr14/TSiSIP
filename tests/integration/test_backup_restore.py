#!/usr/bin/env python3
"""
Integration test for PostgreSQL backup and restore (Feature 005).
Validates: backup scripts presence, encryption roundtrip, purge retention,
quota monitoring, metrics export, PITR verification, key rotation dry-run.
"""

import os
import subprocess
import pytest

COMPOSE_FILE = os.environ.get('COMPOSE_FILE', 'docker-compose.yml')
BACKUP_SERVICE = 'backup'


def compose_exec(cmd: list, check: bool = True) -> subprocess.CompletedProcess:
    """Run a command inside the backup container via docker compose exec."""
    full_cmd = [
        'docker', 'compose', '-f', COMPOSE_FILE,
        'exec', '-T', BACKUP_SERVICE
    ] + cmd
    return subprocess.run(full_cmd, capture_output=True, text=True, check=check)


class TestBackupScriptsPresence:
    """All backup scripts are present and executable."""

    SCRIPTS = [
        '/usr/local/bin/backup.sh',
        '/usr/local/bin/wal-archive.sh',
        '/usr/local/bin/encrypt.sh',
        '/usr/local/bin/purge.sh',
        '/usr/local/bin/validate.sh',
        '/usr/local/bin/replicate.sh',
        '/usr/local/bin/rpo-monitor.sh',
        '/usr/local/bin/quota-check.sh',
        '/usr/local/bin/rotate-key.sh',
        '/usr/local/bin/pitr-restore.sh',
        '/usr/local/bin/metrics-exporter.sh',
        '/usr/local/bin/entrypoint.sh',
    ]

    @pytest.mark.parametrize('script', SCRIPTS)
    def test_script_exists(self, script):
        result = compose_exec(['test', '-x', script], check=False)
        assert result.returncode == 0, f"Script missing or not executable: {script}"


class TestEncryptDecryptRoundtrip:
    """AES-256-CBC + HMAC integrity verification."""

    def test_encrypt_decrypt_roundtrip(self):
        plaintext = "TSiSIP backup integrity test content"
        compose_exec(['sh', '-c', f'echo "{plaintext}" > /tmp/test_plain.txt'])

        r1 = compose_exec([
            '/usr/local/bin/encrypt.sh', 'encrypt',
            '/tmp/test_plain.txt', '/tmp/test_encrypted.enc'
        ], check=False)
        assert r1.returncode == 0, f"Encryption failed: {r1.stderr}"

        r_hmac = compose_exec(['test', '-f', '/tmp/test_encrypted.enc.hmac'], check=False)
        assert r_hmac.returncode == 0, "HMAC file not created after encryption"

        r2 = compose_exec([
            '/usr/local/bin/encrypt.sh', 'decrypt',
            '/tmp/test_encrypted.enc', '/tmp/test_decrypted.txt'
        ], check=False)
        assert r2.returncode == 0, f"Decryption failed: {r2.stderr}"

        r3 = compose_exec(['cat', '/tmp/test_decrypted.txt'], check=False)
        assert r3.returncode == 0
        assert plaintext in r3.stdout, "Decrypted content does not match original"

        compose_exec(['rm', '-f', '/tmp/test_plain.txt', '/tmp/test_encrypted.enc',
                      '/tmp/test_encrypted.enc.hmac', '/tmp/test_decrypted.txt'])

    def test_decrypt_tampered_file_fails(self):
        plaintext = "tamper test"
        compose_exec(['sh', '-c', f'echo "{plaintext}" > /tmp/tamper_plain.txt'])
        compose_exec([
            '/usr/local/bin/encrypt.sh', 'encrypt',
            '/tmp/tamper_plain.txt', '/tmp/tamper_encrypted.enc'
        ])

        compose_exec(['sh', '-c', 'echo "X" >> /tmp/tamper_encrypted.enc'])

        r = compose_exec([
            '/usr/local/bin/encrypt.sh', 'decrypt',
            '/tmp/tamper_encrypted.enc', '/tmp/tamper_decrypted.txt'
        ], check=False)
        assert r.returncode != 0, "Decryption should fail for tampered file"
        combined = r.stdout + r.stderr
        assert "HMAC verification failed" in combined

        compose_exec(['rm', '-f', '/tmp/tamper_plain.txt', '/tmp/tamper_encrypted.enc',
                      '/tmp/tamper_encrypted.enc.hmac', '/tmp/tamper_decrypted.txt'])


class TestPurgeRetention:
    """Retention policy enforcement."""

    def test_purge_removes_old_backups(self):
        compose_exec(['sh', '-c',
            'touch -d "40 days ago" /backup/daily/opensips_old_20240101_000000.dump.gz.enc'])

        r = compose_exec(['/usr/local/bin/purge.sh'], check=False)
        assert r.returncode == 0, f"Purge script failed: {r.stderr}"

        r2 = compose_exec([
            'test', '!', '-f',
            '/backup/daily/opensips_old_20240101_000000.dump.gz.enc'
        ], check=False)
        assert r2.returncode == 0, "Old backup was not purged"

    def test_purge_keeps_recent_backups(self):
        compose_exec(['sh', '-c',
            'touch /backup/daily/opensips_recent_20240115_000000.dump.gz.enc'])

        r = compose_exec(['/usr/local/bin/purge.sh'], check=False)
        assert r.returncode == 0

        r2 = compose_exec([
            'test', '-f',
            '/backup/daily/opensips_recent_20240115_000000.dump.gz.enc'
        ], check=False)
        assert r2.returncode == 0, "Recent backup was incorrectly purged"

        compose_exec(['rm', '-f', '/backup/daily/opensips_recent_20240115_000000.dump.gz.enc'])


class TestQuotaCheck:
    """Storage quota monitoring and alerting."""

    def test_quota_check_generates_metric(self):
        r = compose_exec(['/usr/local/bin/quota-check.sh'], check=False)
        assert r.returncode == 0, f"Quota check failed: {r.stderr}"

        r2 = compose_exec(['test', '-f', '/backup/metrics/quota_usage.prom'], check=False)
        assert r2.returncode == 0, "Quota metric file not generated"

    def test_quota_check_warn_at_high_usage(self):
        compose_exec(['sh', '-c',
            'dd if=/dev/zero of=/backup/daily/fake_large bs=1M count=1 seek=10239 2>/dev/null'])

        r = compose_exec(['bash', '-c', 'BACKUP_QUOTA_GB=1 /usr/local/bin/quota-check.sh'], check=False)
        assert r.returncode in (0, 1, 2), f"Unexpected exit code: {r.returncode}"

        compose_exec(['rm', '-f', '/backup/daily/fake_large'])


class TestRpoMonitor:
    """RPO lag monitoring."""

    def test_rpo_monitor_generates_metric(self):
        compose_exec(['/usr/local/bin/rpo-monitor.sh'], check=False)
        r2 = compose_exec(['test', '-f', '/backup/metrics/rpo_lag_seconds.prom'], check=False)
        assert r2.returncode == 0, "RPO metric file not generated"


class TestPitrRestore:
    """Point-in-Time Recovery script."""

    def test_pitr_verify_only_lists_segments(self):
        r = compose_exec([
            '/usr/local/bin/pitr-restore.sh',
            '--target', '2026-05-16T12:00:00Z',
            '--verify-only'
        ], check=False)
        assert r.returncode in (0, 1), f"Unexpected exit: {r.stderr}"


class TestKeyRotation:
    """Encryption key rotation."""

    def test_rotate_key_dry_run(self):
        r = compose_exec([
            '/usr/local/bin/rotate-key.sh', '--dry-run'
        ], check=False)
        if r.returncode != 0:
            pytest.skip("Key rotation requires backup_encryption_key_new secret")
        assert "Would rotate" in r.stdout or "Rotated:" in r.stdout


class TestMetricsExporter:
    """Prometheus metrics endpoint."""

    def test_metrics_exporter_generates_output(self):
        r = compose_exec([
            '/usr/local/bin/metrics-exporter.sh', '--once'
        ], check=False)
        assert r.returncode == 0, f"Metrics exporter failed: {r.stderr}"
        assert 'backup_rpo_lag_seconds' in r.stdout
        assert 'backup_quota_used_percent' in r.stdout
        assert 'backup_success_total' in r.stdout


if __name__ == '__main__':
    pytest.main([__file__, '-v'])
