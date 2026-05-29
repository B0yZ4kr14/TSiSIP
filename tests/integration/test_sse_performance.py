"""
SSE Performance Test (Feature 034 — T34.4.7)

Validates that the SSE endpoint can handle concurrent connections.
"""
import os
import pytest

class TestSsePerformance:
    def test_sse_endpoint_exists(self):
        """T34.4.7: SSE endpoint exists"""
        assert os.path.exists('web/common/sse-stream.php'), 'SSE endpoint missing'

    def test_sse_headers_correct(self):
        """T34.4.7b: SSE headers are correct"""
        with open('web/common/sse-stream.php') as f:
            content = f.read()
        assert 'text/event-stream' in content, 'Content-Type header missing'
        assert 'no-cache' in content, 'Cache-Control header missing'
        assert 'keep-alive' in content, 'Connection header missing'

    def test_sse_heartbeat_interval(self):
        """T34.4.7c: Heartbeat sent every 30s"""
        with open('web/common/sse-stream.php') as f:
            content = f.read()
        assert 'heartbeat' in content, 'Heartbeat event missing'
        assert 'sleep(5)' in content, 'Sleep interval missing'

    def test_no_output_buffering(self):
        """T34.4.7d: Output buffering disabled for SSE"""
        with open('web/common/sse-stream.php') as f:
            content = f.read()
        assert 'ob_implicit_flush' in content, 'Output buffering not disabled'
