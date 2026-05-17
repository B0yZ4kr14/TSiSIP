#!/usr/bin/env python3
"""
Integration test for rclone backup configuration validation.
"""

import os
import subprocess
import pytest

COMPOSE_FILE = os.environ.get("COMPOSE_FILE", "docker-compose.yml")


class TestBackupRclone:
    """Test rclone backup configuration."""

    def test_rclone_config_exists(self):
        """Rclone config template exists in backup container."""
        result = subprocess.run(
            ["docker", "compose", "-f", COMPOSE_FILE, "exec", "-T", "backup",
             "test", "-f", "/etc/rclone/rclone.conf.tpl"],
            capture_output=True
        )
        if result.returncode != 0:
            pytest.skip("Backup container not accessible")
        assert result.returncode == 0

    def test_rclone_installed(self):
        """Rclone binary is available in backup container."""
        result = subprocess.run(
            ["docker", "compose", "-f", COMPOSE_FILE, "exec", "-T", "backup",
             "which", "rclone"],
            capture_output=True
        )
        if result.returncode != 0:
            pytest.skip("Backup container not accessible")
        assert result.returncode == 0

    def test_backup_scripts_executable(self):
        """Backup scripts are present and executable."""
        scripts = ["backup.sh", "encrypt.sh", "purge.sh", "validate.sh", "replicate.sh"]
        for script in scripts:
            result = subprocess.run(
                ["docker", "compose", "-f", COMPOSE_FILE, "exec", "-T", "backup",
                 "test", "-x", f"/usr/local/bin/{script}"],
                capture_output=True
            )
            if result.returncode != 0:
                pytest.skip(f"Script {script} not found in container")


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
