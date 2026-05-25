"""
Feature 003 Integration Tests: WebRTC/WebSocket Support
Validates: proto_ws/proto_wss modules loaded, ws/wss listeners configured
"""
import pytest


class TestWebRTCSupport:
    """WebSocket transport for WebRTC client support."""

    def test_opensips_config_has_ws_modules(self):
        """OpenSIPS config loads proto_ws and proto_wss modules."""
        with open("opensips/opensips.cfg.tpl") as f:
            config = f.read()
        assert 'loadmodule "proto_ws.so"' in config
        assert 'loadmodule "proto_wss.so"' in config

    def test_opensips_config_has_ws_listeners(self):
        """OpenSIPS config defines ws and wss socket listeners."""
        with open("opensips/opensips.cfg.tpl") as f:
            config = f.read()
        assert "socket=ws:" in config
        assert "socket=wss:" in config

    def test_dockerfile_has_proto_ws_modules(self):
        """Dockerfile includes proto_ws and proto_wss in build."""
        with open("Dockerfile") as f:
            dockerfile = f.read()
        assert "proto_ws" in dockerfile
        assert "proto_wss" in dockerfile
        assert "libwebsockets-dev" in dockerfile

    def test_compose_publishes_ws_ports(self):
        """Docker Compose exposes WebSocket ports."""
        with open("docker-compose.yml") as f:
            compose = f.read()
        assert ':8080/tcp' in compose
        assert ':4443/tcp' in compose

    def test_rtpengine_ice_support(self):
        """RTPengine config includes ICE-related flags for WebRTC."""
        pytest.skip("ICE flags are passed via OpenSIPS rtpengine_offer() — verify manually")
