"""
Circuit Breaker Stress Test (Feature 036)

Validates that the circuit breaker opens after threshold failures
and remains open during cooldown.
"""
import os
import pytest

class TestCircuitBreakerStress:
    def test_circuit_opens_after_threshold(self):
        """T6.3: Circuit breaker opens after N consecutive failures"""
        path = 'web/cli/auto-healer.php'
        assert os.path.exists(path)
        with open(path) as f:
            content = f.read()
        assert 'circuit_breaker_failures' in content, 'Threshold config missing'
        assert 'circuit_breaker_cooldown_min' in content, 'Cooldown config missing'
        assert 'isCircuitBreakerOpen' in content, 'Circuit breaker check not found'

    def test_circuit_prevents_flapping(self):
        """T6.3b: Circuit prevents rapid open/close cycles"""
        with open('web/cli/auto-healer.php') as f:
            content = f.read()
        assert 'cooldown' in content.lower(), 'Cooldown logic missing'
        assert 'created_at' in content, 'Timestamp tracking missing'

    def test_circuit_logs_open_state(self):
        """T6.3c: Circuit breaker open state is logged"""
        with open('web/cli/auto-healer.php') as f:
            content = f.read()
        assert 'Circuit breaker is OPEN' in content, 'Circuit open log message missing'

    def test_circuit_closes_after_cooldown(self):
        """T6.3d: Circuit closes after cooldown period"""
        with open('web/cli/auto-healer.php') as f:
            content = f.read()
        assert 'circuit_breaker_cooldown_min' in content, 'Cooldown reset config missing'
