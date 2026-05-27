"""Feature 033: Performance Benchmarking & Load Testing"""
import subprocess
import os
import json
import pytest


PROJECT_DIR = os.path.dirname(os.path.dirname(os.path.dirname(__file__)))


class TestBenchmarkScriptsExist:
    """T001-T004: Benchmark scripts exist and are executable"""

    def test_benchmark_sip_exists(self):
        script = os.path.join(PROJECT_DIR, 'scripts', 'benchmark-sip.sh')
        assert os.path.exists(script), 'benchmark-sip.sh not found'
        assert os.access(script, os.X_OK), 'benchmark-sip.sh not executable'

    def test_benchmark_pgsql_exists(self):
        script = os.path.join(PROJECT_DIR, 'scripts', 'benchmark-pgsql.sh')
        assert os.path.exists(script), 'benchmark-pgsql.sh not found'
        assert os.access(script, os.X_OK), 'benchmark-pgsql.sh not executable'

    def test_benchmark_report_exists(self):
        script = os.path.join(PROJECT_DIR, 'scripts', 'benchmark-report.sh')
        assert os.path.exists(script), 'benchmark-report.sh not found'
        assert os.access(script, os.X_OK), 'benchmark-report.sh not executable'


class TestBenchmarkOutputFormat:
    """T001-T004: Benchmark scripts produce valid JSON output"""

    def test_sip_benchmark_json_output(self):
        script = os.path.join(PROJECT_DIR, 'scripts', 'benchmark-sip.sh')
        result = subprocess.run(
            ['bash', script],
            capture_output=True, text=True, cwd=PROJECT_DIR, timeout=60
        )
        # Script may fail if OpenSIPS is not running; check for JSON in output
        lines = result.stdout.strip().split('\n')
        for line in reversed(lines):
            if line.startswith('{'):
                try:
                    data = json.loads(line)
                    assert 'benchmark' in data
                    assert data['benchmark'] == 'sip'
                    return
                except json.JSONDecodeError:
                    continue
        pytest.skip('No valid JSON output from SIP benchmark (OpenSIPS may not be running)')

    def test_pgsql_benchmark_json_output(self):
        script = os.path.join(PROJECT_DIR, 'scripts', 'benchmark-pgsql.sh')
        result = subprocess.run(
            ['bash', script],
            capture_output=True, text=True, cwd=PROJECT_DIR, timeout=60
        )
        lines = result.stdout.strip().split('\n')
        for line in reversed(lines):
            if line.startswith('{'):
                try:
                    data = json.loads(line)
                    assert 'benchmark' in data
                    assert data['benchmark'] == 'pgsql'
                    return
                except json.JSONDecodeError:
                    continue
        pytest.skip('No valid JSON output from PostgreSQL benchmark (DB may not be running)')


class TestBenchmarkReport:
    """T004-T005: Report generation"""

    def test_report_generates_markdown(self):
        script = os.path.join(PROJECT_DIR, 'scripts', 'benchmark-report.sh')
        result = subprocess.run(
            ['bash', script],
            capture_output=True, text=True, cwd=PROJECT_DIR, timeout=120
        )
        # Check that report was created
        report_dir = os.path.join(PROJECT_DIR, 'reports', 'benchmarks')
        if os.path.exists(report_dir):
            reports = sorted([
                f for f in os.listdir(report_dir)
                if f.startswith('benchmark-') and f.endswith('.md')
            ])
            if reports:
                latest = os.path.join(report_dir, reports[-1])
                with open(latest) as f:
                    content = f.read()
                assert '# TSiSIP Performance Benchmark Report' in content
                return
        pytest.skip('No benchmark report generated (services may not be running)')
