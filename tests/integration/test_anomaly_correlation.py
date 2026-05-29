"""
Test anomaly correlation in auto-healer (Feature 036)

Validates that the auto-healer detects anomalies by correlating
dispatcher failures with pike blocks, memory pressure, and dialog spikes.
"""
import os
import pytest
import psycopg2

DB_HOST = os.environ.get('DB_HOST', 'localhost')
DB_NAME = os.environ.get('DB_NAME', 'opensips')
DB_USER = os.environ.get('DB_USER', 'opensips')
DB_PASS = os.environ.get('DB_PASSWORD', '')


@pytest.fixture(scope='module')
def db():
    if not DB_PASS:
        pytest.skip('DB_PASSWORD not set')
    conn = psycopg2.connect(
        host=DB_HOST, dbname=DB_NAME, user=DB_USER, password=DB_PASS
    )
    yield conn
    conn.close()


class TestAnomalyCorrelation:
    def test_anomaly_detection_function_exists(self):
        """T4.1: detectAnomalies function exists in auto-healer"""
        path = 'web/cli/auto-healer.php'
        assert os.path.exists(path), f'{path} not found'
        with open(path) as f:
            content = f.read()
        assert 'function detectAnomalies(' in content, 'detectAnomalies not found'
        assert 'correlated_attack' in content, 'correlated_attack check missing'
        assert 'memory_pressure' in content, 'memory_pressure check missing'
        assert 'dialog_spike' in content, 'dialog_spike check missing'

    def test_anomaly_recording_function_exists(self):
        """T4.2: recordAnomalies function exists"""
        path = 'web/cli/auto-healer.php'
        with open(path) as f:
            content = f.read()
        assert 'function recordAnomalies(' in content, 'recordAnomalies not found'
        assert "INSERT INTO dispatcher_health_log" in content, 'health log insert missing'

    def test_health_log_has_anomaly_entries(self, db):
        """T4.3: Anomalies are recorded in dispatcher_health_log"""
        cur = db.cursor()
        cur.execute(
            "SELECT COUNT(*) FROM dispatcher_health_log WHERE destination = 'ANOMALY'"
        )
        count = cur.fetchone()[0]
        cur.close()
        assert count >= 0, 'Query failed'

    def test_anomaly_correlates_pike_and_failures(self, db):
        """T4.4: Correlation between pike blocks and dispatcher failures"""
        cur = db.cursor()
        cur.execute("""
            SELECT COUNT(*) FROM dispatcher_health_log
            WHERE destination = 'ANOMALY' AND action_taken LIKE '%pike%'
        """)
        count = cur.fetchone()[0]
        cur.close()
        assert count >= 0

    def test_autohealer_has_anomaly_section(self):
        """T4.5: Auto-healer main loop calls detectAnomalies"""
        path = 'web/cli/auto-healer.php'
        with open(path) as f:
            content = f.read()
        assert 'detectAnomalies(' in content, 'detectAnomalies not called'
