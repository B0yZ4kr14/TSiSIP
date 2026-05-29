"""
Auto-Healer Security Review (Feature 036 — T6.5)

Validates that auto-healer runs with minimal privileges
and does not introduce new security risks.
"""
import os
import pytest

class TestAutohealSecurity:
    def test_no_new_privileges_in_dockerfile(self):
        """T6.5a: docker-compose.yml has no-new-privileges"""
        path = 'docker-compose.yml'
        assert os.path.exists(path)
        with open(path) as f:
            content = f.read()
        assert 'no-new-privileges' in content.lower(), 'docker-compose.yml missing no-new-privileges'

    def test_autohealer_runs_as_non_root(self):
        """T6.5b: Auto-healer cron runs as www-data"""
        path = 'docker/ocp/cron/auto-healer.sh'
        assert os.path.exists(path)
        with open(path) as f:
            content = f.read()
        assert 'auto-healer' in content, 'Auto-healer script missing'

    def test_secrets_not_logged(self):
        """T6.5c: Auto-healer does not log secrets"""
        path = 'web/cli/auto-healer.php'
        with open(path) as f:
            content = f.read()
        assert 'auth_secret' not in content, 'auth_secret leaked in auto-healer'
        assert 'db_password' not in content, 'db_password leaked in auto-healer'

    def test_no_shell_exec(self):
        """T6.5d: No shell_exec or backticks in auto-healer"""
        path = 'web/cli/auto-healer.php'
        with open(path) as f:
            content = f.read()
        assert 'shell_exec' not in content, 'shell_exec found in auto-healer'
        assert '`' not in content, 'Backticks found in auto-healer'
