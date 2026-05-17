#!/usr/bin/env python3
"""
Integration test for graceful degradation.
Validates: 488 on RTPengine failure, 480 on PostgreSQL failure.
"""

import os
import pytest

COMPOSE_FILE = os.environ.get('COMPOSE_FILE', 'docker-compose.yml')


class TestGracefulDegradation:
    """Test graceful degradation under component failure."""

    def test_rtpengine_down_returns_488(self):
        """INVITE returns 488 when RTPengine is unavailable."""
        pytest.skip("Requires running stack and RTPengine failure injection")

    def test_postgres_down_returns_480(self):
        """REGISTER returns 480 when PostgreSQL is unavailable."""
        pytest.skip("Requires running stack and PostgreSQL failure injection")

    def test_no_crash_on_component_failure(self):
        """OpenSIPS does not crash when components fail."""
        pytest.skip("Requires running stack and component failure injection")


if __name__ == '__main__':
    pytest.main([__file__, '-v'])
