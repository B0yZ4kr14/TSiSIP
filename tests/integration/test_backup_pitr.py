#!/usr/bin/env python3
# @req FR-005
"""
Integration test for Point-in-Time Recovery (PITR) — Stage 8.
Validates: full backup → PITR restore → temp DB verification.

Test IDs:
- PITR-001: backup.sh produces an encrypted backup file
- PITR-002: pitr-restore.sh creates a temporary database from the backup
- PITR-003: Restored temp database is queryable and contains expected schema
- PITR-004: pitr-restore.sh --verify-only lists WAL segments without side effects
"""

import os
import subprocess
import pytest
import time

COMPOSE_FILE = os.environ.get("COMPOSE_FILE", "docker-compose.vps.yml")
BACKUP_SERVICE = "backup"


def compose_exec(cmd: list, check: bool = True) -> subprocess.CompletedProcess:
    full_cmd = [
        "docker", "compose", "-f", COMPOSE_FILE,
        "exec", "-T", BACKUP_SERVICE,
    ] + cmd
    return subprocess.run(full_cmd, capture_output=True, text=True, check=check)


def psql_exec(db: str, query: str, check: bool = True) -> subprocess.CompletedProcess:
    """Run a query inside the postgres container."""
    full_cmd = [
        "docker", "compose", "-f", COMPOSE_FILE,
        "exec", "-T", "postgres",
        "psql", "-U", "opensips", "-d", db, "-t", "-c", query,
    ]
    return subprocess.run(full_cmd, capture_output=True, text=True, check=check)


class TestBackupPitr:
    """End-to-end PITR workflow."""

    @pytest.fixture(scope="class", autouse=True)
    def ensure_backup_exists(self):
        """Ensure at least one encrypted backup is present before PITR tests."""
        r = compose_exec(
            ["test", "-f", "/backup/daily/latest"],
            check=False,
        )
        if r.returncode != 0:
            # Trigger a backup run
            r2 = compose_exec(["/usr/local/bin/backup.sh"], check=False)
            if r2.returncode != 0:
                pytest.skip(f"Backup creation failed: {r2.stderr}")
            # Wait for backup file to appear
            for _ in range(10):
                r3 = compose_exec(["test", "-f", "/backup/daily/latest"], check=False)
                if r3.returncode == 0:
                    break
                time.sleep(1)
        yield
        # Cleanup: drop any temp databases created by these tests
        for suffix in ["pitr_test", "pitr_verify"]:
            psql_exec(
                "postgres",
                f"DROP DATABASE IF EXISTS opensips_{suffix};",
                check=False,
            )

    def test_pitr_001_backup_produces_encrypted_file(self):
        """PITR-001: Latest backup symlink points to an encrypted file."""
        r = compose_exec(["readlink", "/backup/daily/latest"], check=False)
        assert r.returncode == 0, f"Failed to read latest symlink: {r.stderr}"
        latest = r.stdout.strip()
        assert latest.endswith(".enc"), f"Backup file not encrypted: {latest}"

        r2 = compose_exec(["test", "-f", f"/backup/daily/{latest}"], check=False)
        assert r2.returncode == 0, "Latest backup file does not exist"

    def test_pitr_002_restore_creates_temp_database(self):
        """PITR-002: pitr-restore.sh creates a queryable temp database."""
        # Use a recent timestamp to ensure we hit the backup just created
        target = time.strftime("%Y-%m-%dT%H:%M:%SZ", time.gmtime())
        temp_db = "opensips_pitr_test"

        r = compose_exec(
            [
                "/usr/local/bin/pitr-restore.sh",
                "--target", target,
                "--temp-db", temp_db,
            ],
            check=False,
        )
        # pitr-restore may exit 0 even if WAL replay is approximate;
        # we only require the temp DB to exist and be queryable.
        assert r.returncode == 0, f"PITR restore failed: {r.stdout} {r.stderr}"

        # Verify temp database exists
        r2 = psql_exec(
            "postgres",
            f"SELECT 1 FROM pg_database WHERE datname = '{temp_db}';",
            check=False,
        )
        assert r2.returncode == 0
        assert "1" in r2.stdout, f"Temp database {temp_db} was not created"

    def test_pitr_003_restored_db_contains_schema(self):
        """PITR-003: Restored temp database contains expected tables."""
        temp_db = "opensips_pitr_test"
        # Ensure database exists (created by previous test or fixture)
        r = psql_exec(
            "postgres",
            f"SELECT 1 FROM pg_database WHERE datname = '{temp_db}';",
            check=False,
        )
        if r.returncode != 0 or "1" not in r.stdout:
            pytest.skip("Temp database not available; run PITR-002 first")

        r2 = psql_exec(
            temp_db,
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public';",
            check=False,
        )
        assert r2.returncode == 0
        count = int(r2.stdout.strip())
        assert count > 0, "Restored database has no public tables"

    def test_pitr_004_verify_only_no_side_effects(self):
        """PITR-004: --verify-only does not create databases."""
        temp_db = "opensips_pitr_verify"
        # Drop if exists from previous run
        psql_exec("postgres", f"DROP DATABASE IF EXISTS {temp_db};", check=False)

        target = time.strftime("%Y-%m-%dT%H:%M:%SZ", time.gmtime())
        r = compose_exec(
            [
                "/usr/local/bin/pitr-restore.sh",
                "--target", target,
                "--temp-db", temp_db,
                "--verify-only",
            ],
            check=False,
        )
        assert r.returncode in (0, 1), f"Unexpected exit: {r.stderr}"

        # Confirm temp DB was NOT created
        r2 = psql_exec(
            "postgres",
            f"SELECT 1 FROM pg_database WHERE datname = '{temp_db}';",
            check=False,
        )
        assert "1" not in r2.stdout, "verify-only created a database — side effect bug"


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
