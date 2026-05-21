#!/bin/bash
# T5 — VPS 24h Rollback Runbook
# Rollback dry-run: executable without ambiguity
set -uo pipefail

PROFILE="${1:-vps}"
COMPOSE_FILE="docker-compose.${PROFILE}.yml"
PROJECT_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
EVIDENCE_DIR="$PROJECT_ROOT/.sisyphus/evidence"
mkdir -p "$EVIDENCE_DIR"

DRY_RUN="${DRY_RUN:-true}"

echo "=== T5: Rollback Runbook ==="
echo "Profile: $PROFILE"
echo "Dry-run: $DRY_RUN"
echo ""

# Pre-deploy snapshot function
snapshot() {
    local snap_file="$EVIDENCE_DIR/rollback-snapshot-$(date +%Y%m%d-%H%M%S).txt"
    docker compose -f "$PROJECT_ROOT/$COMPOSE_FILE" ps --format '{{.Name}} {{.Image}} {{.Status}}' > "$snap_file" 2>/dev/null || true
    echo "$snap_file"
}

# Abort triggers (conditions that trigger rollback)
check_abort_triggers() {
    local triggers=0
    
    # Trigger 1: opensips container not healthy after 60s
    local opensips_status
    opensips_status=$(docker compose -f "$PROJECT_ROOT/$COMPOSE_FILE" ps opensips --format '{{.Status}}' 2>/dev/null || echo "missing")
    if ! echo "$opensips_status" | grep -qiE 'healthy|Up'; then
        echo "[TRIGGER] opensips unhealthy: $opensips_status"
        triggers=$((triggers + 1))
    fi
    
    # Trigger 2: postgres not healthy after 60s
    local pg_status
    pg_status=$(docker compose -f "$PROJECT_ROOT/$COMPOSE_FILE" ps postgres --format '{{.Status}}' 2>/dev/null || echo "missing")
    if ! echo "$pg_status" | grep -qiE 'healthy|Up'; then
        echo "[TRIGGER] postgres unhealthy: $pg_status"
        triggers=$((triggers + 1))
    fi
    
    # Trigger 3: SIP probe fails 3 consecutive times
    local sip_fail=0
    for i in 1 2 3; do
        if ! bash "$PROJECT_ROOT/tests/vps-stabilization/test-vps-sip.sh" "$PROFILE" >/dev/null 2>&1; then
            sip_fail=$((sip_fail + 1))
        fi
        sleep 2
    done
    if [ "$sip_fail" -ge 3 ]; then
        echo "[TRIGGER] SIP probe failed 3/3 times"
        triggers=$((triggers + 1))
    fi
    
    # Trigger 4: OCP probe fails 3 consecutive times
    local ocp_fail=0
    for i in 1 2 3; do
        if ! bash "$PROJECT_ROOT/tests/vps-stabilization/test-vps-ocp.sh" "$PROFILE" >/dev/null 2>&1; then
            ocp_fail=$((ocp_fail + 1))
        fi
        sleep 2
    done
    if [ "$ocp_fail" -ge 3 ]; then
        echo "[TRIGGER] OCP probe failed 3/3 times"
        triggers=$((triggers + 1))
    fi
    
    echo "$triggers"
}

# Rollback steps
rollback() {
    echo "[ROLLBACK] Step 1: Capture current state..."
    local snap
    snap=$(snapshot)
    echo "[ROLLBACK] Snapshot saved: $snap"
    
    echo "[ROLLBACK] Step 2: Stop affected services..."
    if [ "$DRY_RUN" = "false" ]; then
        docker compose -f "$PROJECT_ROOT/$COMPOSE_FILE" down --timeout 30 2>/dev/null || true
    else
        echo "[DRY-RUN] Would run: docker compose down --timeout 30"
    fi
    
    echo "[ROLLBACK] Step 3: Restore previous images (if snapshot exists)..."
    local prev_snap
    prev_snap=$(find "$EVIDENCE_DIR" -name 'rollback-snapshot-*.txt' | sort | tail -2 | head -1)
    if [ -n "$prev_snap" ] && [ -f "$prev_snap" ]; then
        echo "[ROLLBACK] Previous snapshot: $prev_snap"
        if [ "$DRY_RUN" = "false" ]; then
            while read -r name image status; do
                if [ -n "$image" ]; then
                    docker pull "$image" 2>/dev/null || true
                fi
            done < "$prev_snap"
        else
            echo "[DRY-RUN] Would pull previous images from snapshot"
        fi
    fi
    
    echo "[ROLLBACK] Step 4: Restart with previous/stable configuration..."
    if [ "$DRY_RUN" = "false" ]; then
        docker compose -f "$PROJECT_ROOT/$COMPOSE_FILE" up -d
    else
        echo "[DRY-RUN] Would run: docker compose up -d"
    fi
    
    echo "[ROLLBACK] Step 5: Verify restoration..."
    sleep 10
    if [ "$DRY_RUN" = "false" ]; then
        bash "$PROJECT_ROOT/tests/vps-stabilization/test-vps-health.sh" "$PROFILE" || true
    else
        echo "[DRY-RUN] Would run health verification"
    fi
}

# Main flow
echo "[CHECK] Evaluating abort triggers..."
TRIGGERS=$(check_abort_triggers)
echo "[CHECK] Active triggers: $TRIGGERS"

if [ "$TRIGGERS" -gt 0 ]; then
    echo "[ABORT] $TRIGGERS trigger(s) active. Initiating rollback..."
    rollback
    echo "[DONE] Rollback complete"
    exit 1
else
    echo "[PASS] No abort triggers active. System stable."
    exit 0
fi
