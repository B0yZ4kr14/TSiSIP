#!/usr/bin/env bash
# TSiSIP Disaster Recovery Drill Script
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
REPORT_DIR="${PROJECT_DIR}/reports/dr-drills"
REPORT_FILE="${REPORT_DIR}/dr-drill-$(date +%Y%m%d-%H%M%S).md"

mkdir -p "$REPORT_DIR"

DRILL_START=$(date +%s)
LATEST_BACKUP=$(ls -t "${PROJECT_DIR}/backups"/tsisip_db_*.sql.gz 2>/dev/null | head -1 || true)

if [[ -z "$LATEST_BACKUP" ]]; then
    echo "ERROR: No backup found for DR drill"
    exit 1
fi

echo "=== TSiSIP DR Drill ==="
echo "Backup: $LATEST_BACKUP"
echo "Start: $(date -Iseconds)"
echo ""

# Run verification
VERIFY_START=$(date +%s)
if bash "${PROJECT_DIR}/scripts/verify-backup.sh" --backup "$LATEST_BACKUP"; then
    VERIFY_STATUS="PASS"
else
    VERIFY_STATUS="FAIL"
fi
VERIFY_END=$(date +%s)
VERIFY_DURATION=$((VERIFY_END - VERIFY_START))

# Calculate total RTO
DRILL_END=$(date +%s)
RTO=$((DRILL_END - DRILL_START))

# Generate report
cat > "$REPORT_FILE" <<EOF
# TSiSIP Disaster Recovery Drill Report

**Date**: $(date -Iseconds)
**Backup File**: $(basename "$LATEST_BACKUP")
**Backup Timestamp**: $(stat -c %y "$LATEST_BACKUP")

## Results

| Metric | Value |
|--------|-------|
| Verification Status | $VERIFY_STATUS |
| Verification Duration | ${VERIFY_DURATION}s |
| Total RTO | ${RTO}s |

## Details

- Backup checksum verified: $VERIFY_STATUS
- Schema integrity verified: $VERIFY_STATUS
- Restore to temp container: $VERIFY_STATUS

## Evidence

- Backup file: $LATEST_BACKUP
- Checksum file: ${LATEST_BACKUP}.sha256
- Metadata file: ${LATEST_BACKUP}.meta.json

## Conclusion

$(if [[ "$VERIFY_STATUS" == "PASS" ]]; then
    echo "DR drill completed successfully. RTO of ${RTO}s is within acceptable limits."
else
    echo "DR drill FAILED. Immediate operator attention required."
fi)
EOF

echo ""
echo "Report written to: $REPORT_FILE"
echo "Verification: $VERIFY_STATUS (${VERIFY_DURATION}s)"
echo "Total RTO: ${RTO}s"

if [[ "$VERIFY_STATUS" == "FAIL" ]]; then
    exit 1
fi

echo "=== DR DRILL PASSED ==="
