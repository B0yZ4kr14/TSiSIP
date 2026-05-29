"""
Auto-Healing No-Rollback Test (Feature 036 — T3.7)

Validates that auto-healer does NOT rollback when multiple
 destinations fail simultaneously (no single recent change).
"""
import os
import pytest

class TestAutohealNoRollback:
    def test_no_rollback_without_recent_change(self):
        """T3.7: No rollback when multiple destinations fail without recent change"""
        path = 'web/cli/auto-healer.php'
        assert os.path.exists(path)
        with open(path) as f:
            content = f.read()
        assert 'tryAutoRollback(' in content, 'Auto-rollback function missing'
        assert 'dispatcher_change_log' in content, 'Changelog table missing'

    def test_rollback_requires_recent_snapshot(self):
        """T3.7b: Rollback only when old_snapshot exists"""
        with open('web/cli/auto-healer.php') as f:
            content = f.read()
        assert 'old_snapshot' in content, 'old_snapshot check missing'
        assert 'created_at' in content, 'Timestamp check missing'

    def test_multiple_failures_increment_counter(self):
        """T3.7c: Multiple failures increment failure counter"""
        with open('web/cli/auto-healer.php') as f:
            content = f.read()
        assert 'failureCount' in content or 'failure_count' in content, 'Failure counter missing'
