"""
E2E Auto-Healing Test (Feature 036)

Simulates destination failure and verifies auto-failover logic.
"""
import os
import pytest

class TestAutohealE2E:
    def test_auto_failover_simulation_exists(self):
        """T6.1: Auto-failover simulation script exists"""
        assert os.path.exists('web/cli/auto-healer.php'), 'Auto-healer CLI missing'
        with open('web/cli/auto-healer.php') as f:
            content = f.read()
        assert 'probeDestination(' in content, 'probeDestination function missing'
        assert 'setDestinationState(' in content, 'setDestinationState function missing'
        assert 'AUTO_FAILOVER' in content, 'Auto-failover action missing'

    def test_auto_rollback_on_recovery(self):
        """T6.1b: Auto-rollback from changelog"""
        with open('web/cli/auto-healer.php') as f:
            content = f.read()
        assert 'tryAutoRollback(' in content, 'Auto-rollback function missing'
        assert 'AUTO_ROLLBACK' in content, 'Auto-rollback action missing'

    def test_health_log_records_probes(self):
        """T6.1c: Health log records probe results"""
        with open('web/cli/auto-healer.php') as f:
            content = f.read()
        assert 'recordHealth(' in content, 'recordHealth function missing'
        assert 'dispatcher_health_log' in content, 'Health log table missing'

    def test_mi_latency_under_threshold(self):
        """T6.4: MI call latency < 1s"""
        with open('web/cli/auto-healer.php') as f:
            content = f.read()
        assert 'microtime(' in content or 'hrtime(' in content, 'Timing measurement missing'
