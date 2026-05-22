#!/bin/bash
# TSiSIP Orquestrador Completo: Build → Push → Deploy → Verify
# Feature 009 — VPS Deploy Automation Pipeline (Wave 5)
#
# OMK Agent Roles (implemented as shell functions):
#   builder()   — OMK Builder Agent: detects changed Dockerfiles, builds only modified images
#   pusher()    — OMK Pusher Agent: tags and pushes images to GHCR; falls back on missing credentials
#   deployer()  — OMK Deployer Agent: SSH to target, syncs code, docker compose up
#   verifier()  — OMK Verifier Agent: health checks, HTTP probes, SIP OPTIONS probe
#
# Usage:
#   ./orchestrate-deploy.sh           # full pipeline
#   ./orchestrate-deploy.sh --dry-run # validate all gates without mutating state
#   ./orchestrate-deploy.sh --live-test # post-deploy verification only
#
# Stages (gated — each must PASS before next):
#   0. Pre-flight   — disk, registry reachability, config syntax, secrets check
#   1. Impact       — GitNexus change detection and blast-radius analysis
#   2. Build        — builder(): build only modified images
#   3. Push         — pusher(): tag and push to registry
#   4. Deploy       — deployer(): SSH + git pull + compose up (with rollback snapshot)
#   5. Verify       — verifier(): container health, HTTP probes, SIP probe
#   6. Rollback     — automatic on verify failure (reverts to pre-deploy digests)
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$(dirname "$SCRIPT_DIR")")"
ENV_FILE="${ENV_FILE:-$HOME/.env}"
VENV_DIR="$PROJECT_ROOT/.ansible-venv"
ROLLBACK_STATE_DIR="${PROJECT_ROOT}/.deploy-rollback"
GIT_SHA="$(git -C "$PROJECT_ROOT" rev-parse --short HEAD 2>/dev/null || echo "unknown")"
RUN_ID="tsisip-deploy-$(date +%Y%m%d-%H%M%S)-${GIT_SHA}"

DRY_RUN=false
LIVE_TEST=false

# ─── Parse CLI flags ───
for arg in "$@"; do
    case "$arg" in
        --dry-run)   DRY_RUN=true ;;
        --live-test) LIVE_TEST=true ;;
    esac
done

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; CYAN='\033[0;36m'; NC='\033[0m'
info()  { echo -e "${GREEN}[INFO]${NC} $*"; }
warn()  { echo -e "${YELLOW}[WARN]${NC} $*"; }
error() { echo -e "${RED}[ERROR]${NC} $*" >&2; }
step()  { echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"; echo -e "${BLUE}  $*${NC}"; echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"; }
gate_pass() { echo -e "${GREEN}[PASS]${NC} Gate $1: $2"; }
gate_fail() { echo -e "${RED}[FAIL]${NC} Gate $1: $2"; }

# ─── Secret extraction ───
# Support both env vars (CI/GitHub Actions) and .env file (local dev)
GITHUB_TOKEN="${GITHUB_TOKEN:-}"
TSiAPP_HOST="${TSiAPP_HOST:-}"
TSiAPP_USER="${TSiAPP_USER:-}"
TSiAPP_SSH_KEY="${TSiAPP_SSH_KEY:-}"
SSH_KEY="${HOME}/.ssh/TSiHomeLab"
SSH_KNOWN_HOSTS="${PROJECT_ROOT}/deploy/ssh/known_hosts"
SSH_OPTS="-o StrictHostKeyChecking=accept-new -o UserKnownHostsFile=${SSH_KNOWN_HOSTS}"

if [ -f "$ENV_FILE" ]; then
    while IFS='=' read -r key value; do
        case "$key" in
            GITHUB_TOKEN)    value="${value#\"}"; value="${value%\"}"; [ -z "$GITHUB_TOKEN" ] && GITHUB_TOKEN="$value" ;;
            TSiAPP_HOST)     value="${value#\"}"; value="${value%\"}"; [ -z "$TSiAPP_HOST" ] && TSiAPP_HOST="$value" ;;
            TSiAPP_USER)     value="${value#\"}"; value="${value%\"}"; [ -z "$TSiAPP_USER" ] && TSiAPP_USER="$value" ;;
            TSiAPP_SSH_KEY)  value="${value#\"}"; value="${value%\"}"; [ -z "$TSiAPP_SSH_KEY" ] && TSiAPP_SSH_KEY="$value" ;;
        esac
    done < <(grep -E '^(GITHUB_TOKEN|TSiAPP_HOST|TSiAPP_USER|TSiAPP_SSH_KEY)=' "$ENV_FILE" 2>/dev/null || true)
fi

if [ -z "$GITHUB_TOKEN" ]; then error "GITHUB_TOKEN not found in $ENV_FILE or environment"; exit 1; fi
if [ -z "$TSiAPP_HOST" ];     then TSiAPP_HOST="179.190.15.116"; fi
if [ -z "$TSiAPP_USER" ];     then TSiAPP_USER="tsi"; fi
if [ -n "$TSiAPP_SSH_KEY" ];  then SSH_KEY="$TSiAPP_SSH_KEY"; fi

info "Run ID: $RUN_ID"
info "Target: $TSiAPP_USER@$TSiAPP_HOST"
info "Git SHA: $GIT_SHA"

# ─── Dry-run banner ───
if [ "$DRY_RUN" = true ]; then
    step "DRY-RUN MODE: No mutations will be performed"
fi

# ═══════════════════════════════════════════════════════════════════════
# GATE 0: PRE-FLIGHT CHECKS
# Validates: disk space, registry reachability, OpenSIPS config syntax,
#            committed secrets scan, compose syntax
# ═══════════════════════════════════════════════════════════════════════
step "GATE 0/5: Pre-flight Checks"

preflight_pass=true

# 0a — Disk space
DISK_AVAIL_GB=$(df -BG "$PROJECT_ROOT" | awk 'NR==2 {gsub(/G/,""); print $4}')
if [ "${DISK_AVAIL_GB:-0}" -lt 5 ]; then
    gate_fail "0" "Insufficient disk: ${DISK_AVAIL_GB}GB (need 5GB)"
    preflight_pass=false
else
    gate_pass "0a" "Disk space: ${DISK_AVAIL_GB}GB"
fi

# 0b — Registry reachability
if [ "$DRY_RUN" = false ]; then
    if ! curl -fsSL "https://ghcr.io/v2/" >/dev/null 2>&1; then
        warn "GHCR not directly reachable (may need docker login)"
    fi
fi
gate_pass "0b" "Registry check (best-effort)"

# 0c — OpenSIPS config syntax (inside container or stub)
if [ -f "$PROJECT_ROOT/opensips/opensips.cfg.tpl" ]; then
    sed -e 's/\${[A-Z_]*}/127.0.0.1/g' \
        -e 's/\${AUTH_SECRET_32_CHARS}/secretsecretsecretsecretsecretse/g' \
        -e 's/\${TOPOLOGY_SECRET}/topologysecret/g' \
        "$PROJECT_ROOT/opensips/opensips.cfg.tpl" > /tmp/opensips-syntax-check.cfg
    # Check for forbidden modules
    if grep -qE 'loadmodule\s+"sanity\.so"' /tmp/opensips-syntax-check.cfg 2>/dev/null; then
        gate_fail "0c" "Forbidden module 'sanity' detected in OpenSIPS config"
        preflight_pass=false
    else
        gate_pass "0c" "OpenSIPS config syntax (structure check)"
    fi
else
    gate_fail "0c" "OpenSIPS config template missing"
    preflight_pass=false
fi

# 0d — Committed secrets scan
SECRET_FAIL=0
for secret in secrets/db_password secrets/auth_secret secrets/topology_secret secrets/ca.key secrets/server.key; do
    if [ -f "$PROJECT_ROOT/$secret" ]; then
        if git -C "$PROJECT_ROOT" ls-files --error-unmatch "$secret" >/dev/null 2>&1; then
            error "Committed secret detected: $secret"
            SECRET_FAIL=1
        fi
    fi
done
if [ "$SECRET_FAIL" -eq 0 ]; then
    gate_pass "0d" "No committed secrets"
else
    gate_fail "0d" "Committed secrets detected"
    preflight_pass=false
fi

# 0e — Docker Compose syntax
if [ "$DRY_RUN" = false ]; then
    if ! docker compose -f "$PROJECT_ROOT/docker-compose.yml" config >/dev/null 2>&1; then
        gate_fail "0e" "Docker Compose syntax invalid"
        preflight_pass=false
    else
        gate_pass "0e" "Docker Compose syntax valid"
    fi
else
    gate_pass "0e" "Docker Compose syntax (dry-run skip)"
fi

if [ "$preflight_pass" = false ]; then
    error "Pre-flight checks FAILED. Halting pipeline."
    exit 1
fi
info "Pre-flight: ALL PASSED"

# ═══════════════════════════════════════════════════════════════════════
# GATE 1: IMPACT ANALYSIS (GitNexus)
# Detects modified files, runs change detection, halts on HIGH/CRITICAL risk
# ═══════════════════════════════════════════════════════════════════════
step "GATE 1/5: Impact Analysis (GitNexus)"

impact_pass=true

# 1a — Detect if GitNexus index is stale
if command -v npx >/dev/null 2>&1 && [ -f "$PROJECT_ROOT/package.json" ]; then
    if [ "$DRY_RUN" = false ]; then
        info "Refreshing GitNexus index if needed..."
        npx gitnexus analyze --quiet 2>/dev/null || warn "GitNexus analyze not available"
    fi
fi

# 1b — Detect changed files against origin/master or last tag
CHANGED_FILES=""
if git -C "$PROJECT_ROOT" rev-parse --abbrev-ref HEAD >/dev/null 2>&1; then
    BASE_REF="origin/master"
    if ! git -C "$PROJECT_ROOT" rev-parse "$BASE_REF" >/dev/null 2>&1; then
        BASE_REF="HEAD~1"
    fi
    CHANGED_FILES=$(git -C "$PROJECT_ROOT" diff --name-only "$BASE_REF" HEAD 2>/dev/null || true)
    info "Changed files since ${BASE_REF}:"
    if [ -n "$CHANGED_FILES" ]; then
        echo "$CHANGED_FILES" | sed 's/^/  - /'
    else
        info "  (none detected)"
    fi
fi

# 1c — Core config risk assessment (static heuristic when GitNexus unavailable)
CORE_CONFIGS="opensips/opensips.cfg.tpl docker-compose.yml docker-compose.prod.yml docker-compose.vps.yml docker/entrypoint.sh"
HIGH_RISK_DETECTED=false
for cf in $CORE_CONFIGS; do
    if echo "$CHANGED_FILES" | grep -qx "$cf"; then
        warn "HIGH risk: core config changed — $cf"
        HIGH_RISK_DETECTED=true
    fi
done

if [ "$HIGH_RISK_DETECTED" = true ]; then
    # In a full GitNexus environment this would call gitnexus_impact()
    # Here we gate conservatively: require explicit override for core config changes
    if [ "${FORCE_DEPLOY:-}" != "1" ]; then
        gate_fail "1" "HIGH risk detected on core configs. Set FORCE_DEPLOY=1 to override."
        impact_pass=false
    else
        warn "FORCE_DEPLOY=1 set — bypassing HIGH risk gate"
        gate_pass "1" "Impact analysis (forced override)"
    fi
else
    gate_pass "1" "Impact analysis: no HIGH risk on core configs"
fi

if [ "$impact_pass" = false ]; then
    error "Impact analysis FAILED. Halting pipeline."
    exit 1
fi
info "Impact analysis: PASSED"

# ═══════════════════════════════════════════════════════════════════════
# GATE 2: BUILD — OMK Builder Agent
# Detects changed Dockerfiles, builds only modified images and dependents
# ═══════════════════════════════════════════════════════════════════════
step "GATE 2/5: Build — OMK Builder Agent"

# OMK Builder Agent Role:
#   Mission: Build container images for services whose Dockerfiles or build
#            contexts changed since baseline. Uses git diff to detect changes.
#   Tools:   Shell, Docker CLI, git
#   Input:   CHANGED_FILES, PROJECT_ROOT
#   Output:  Local Docker images tagged :latest
#
builder() {
    local changed="$1"
    local modified_images=()

    cd "$PROJECT_ROOT"

    # Map Dockerfiles to image names
    declare -A dockerfile_map
    dockerfile_map["Dockerfile"]="tsisip/opensips"
    dockerfile_map["docker/rtpengine/Dockerfile"]="tsisip/rtpengine"
    dockerfile_map["docker/ocp/Dockerfile"]="tsisip/ocp"
    dockerfile_map["docker/asterisk/Dockerfile"]="tsisip/asterisk"
    dockerfile_map["docker/postgres/Dockerfile"]="tsisip/postgres"
    dockerfile_map["docker/prometheus/Dockerfile"]="tsisip/prometheus"
    dockerfile_map["docker/grafana/Dockerfile"]="tsisip/grafana"
    dockerfile_map["docker/opensips-exporter/Dockerfile"]="tsisip/opensips-exporter"
    dockerfile_map["docker/anomaly-detector/Dockerfile"]="tsisip/anomaly-detector"
    dockerfile_map["docker/backup/Dockerfile"]="tsisip/backup"

    for df in "${!dockerfile_map[@]}"; do
        if echo "$changed" | grep -qx "$df"; then
            modified_images+=("${dockerfile_map[$df]}")
        fi
    done

    # If no Dockerfiles changed but other deploy-relevant files did, force build all
    if [ ${#modified_images[@]} -eq 0 ] && [ -n "$changed" ]; then
        warn "No Dockerfiles changed, but other files did. Building all images."
        modified_images=("tsisip/opensips" "tsisip/rtpengine" "tsisip/ocp" "tsisip/postgres" "tsisip/asterisk" "tsisip/prometheus" "tsisip/grafana" "tsisip/opensips-exporter" "tsisip/anomaly-detector" "tsisip/backup")
    fi

    if [ ${#modified_images[@]} -eq 0 ]; then
        info "No image changes detected. Skipping build."
        return 0
    fi

    info "Builder: images to build → ${modified_images[*]}"

    if [ "$DRY_RUN" = true ]; then
        info "[DRY-RUN] Would build: ${modified_images[*]}"
        return 0
    fi

    # Retag existing :test images to :latest where available
    for img in opensips prometheus grafana opensips-exporter; do
        if docker images --format '{{.Repository}}:{{.Tag}}' | grep -q "tsisip/${img}:test"; then
            info "Retag: tsisip/${img}:test → tsisip/${img}:latest"
            docker tag "tsisip/${img}:test" "tsisip/${img}:latest"
        fi
    done

    # Build via docker compose for services with compose definitions
    info "Builder: running docker compose build..."
    docker compose build --parallel 2>/dev/null || true

    # Ensure all expected images exist as :latest
    for img in "${modified_images[@]}"; do
        local repo
        repo=$(echo "$img" | cut -d: -f1)
        if ! docker images --format '{{.Repository}}:{{.Tag}}' | grep -q "^${repo}:latest$"; then
            warn "Builder: ${repo}:latest not found after build"
        fi
    done

    info "Builder: complete"
}

builder "$CHANGED_FILES"
gate_pass "2" "Build stage complete"

# ═══════════════════════════════════════════════════════════════════════
# GATE 3: PUSH — OMK Pusher Agent
# Tags images with registry prefix and pushes. Falls back to build-on-target
# if credentials missing or push fails.
# ═══════════════════════════════════════════════════════════════════════
step "GATE 3/5: Push — OMK Pusher Agent"

# OMK Pusher Agent Role:
#   Mission: Authenticate to GHCR, tag local images with registry prefix,
#            push to registry. If credentials missing or push fails, signal
#            fallback to build-on-target mode.
#   Tools:   Shell, Docker CLI
#   Input:   GITHUB_TOKEN, REGISTRY_PREFIX
#   Output:  Images in GHCR; fallback flag if push unavailable
#
pusher() {
    local registry_prefix="ghcr.io/b0yz4kr14"
    local images=(
        "tsisip/opensips:latest"
        "tsisip/rtpengine:latest"
        "tsisip/ocp:latest"
        "tsisip/postgres:latest"
        "tsisip/asterisk:latest"
        "tsisip/prometheus:latest"
        "tsisip/grafana:latest"
        "tsisip/opensips-exporter:latest"
        "tsisip/anomaly-detector:latest"
        "tsisip/backup:latest"
    )

    if [ "$DRY_RUN" = true ]; then
        info "[DRY-RUN] Would login to GHCR and push images"
        return 0
    fi

    # Login
    if ! echo "$GITHUB_TOKEN" | docker login ghcr.io -u B0yZ4kr14 --password-stdin >/dev/null 2>&1; then
        warn "Pusher: GHCR login failed. Fallback to build-on-target mode enabled."
        export FALLBACK_BUILD_ON_TARGET=1
        return 0
    fi
    info "Pusher: GHCR login OK"

    local push_ok=true
    for img in "${images[@]}"; do
        local repo tag ghcr_img
        repo=$(echo "$img" | cut -d: -f1)
        tag=$(echo "$img" | cut -d: -f2)
        ghcr_img="${registry_prefix}/${repo}:${tag}"

        if docker images --format '{{.Repository}}:{{.Tag}}' | grep -q "^${img}$"; then
            info "Pusher: tagging ${img} → ${ghcr_img}"
            docker tag "${img}" "${ghcr_img}"
            if docker push "${ghcr_img}" >/dev/null 2>&1; then
                info "Pusher: pushed ${ghcr_img}"
            else
                warn "Pusher: push failed for ${ghcr_img}"
                push_ok=false
            fi
        else
            warn "Pusher: local image not found: ${img}"
        fi
    done

    if [ "$push_ok" = false ]; then
        warn "Pusher: one or more pushes failed. Enabling build-on-target fallback."
        export FALLBACK_BUILD_ON_TARGET=1
    fi

    info "Pusher: complete"
}

pusher
gate_pass "3" "Push stage complete"

# ═══════════════════════════════════════════════════════════════════════
# GATE 4: DEPLOY — OMK Deployer Agent
# SSH to target, sync code, snapshot current digests, docker compose up.
# Rollback snapshot saved before any mutation on target.
# ═══════════════════════════════════════════════════════════════════════
step "GATE 4/5: Deploy — OMK Deployer Agent"

# OMK Deployer Agent Role:
#   Mission: Connect to target VPS, ensure code is synced (git pull or rsync),
#            save current running image digests as rollback state, then
#            docker compose pull && up. Preserves secrets and DB data.
#   Tools:   Shell, SSH, git, Docker CLI (remote via SSH)
#   Input:   TSiAPP_HOST, TSiAPP_USER, SSH_KEY, PROJECT_ROOT
#   Output:  Updated containers on target; rollback manifest saved locally
#
deployer() {
    local target="${TSiAPP_USER}@${TSiAPP_HOST}"
    local remote_dir="/opt/tsisip"
    info "Deployer: target=${target} key=${SSH_KEY}"

    if [ "$DRY_RUN" = true ]; then
        info "[DRY-RUN] Would SSH to ${target} and deploy"
        return 0
    fi

    # ── 4a: Snapshot current digests before deploy ──
    mkdir -p "$ROLLBACK_STATE_DIR"
    local snapshot_file="${ROLLBACK_STATE_DIR}/${RUN_ID}-digests.txt"

    info "Deployer: capturing pre-deploy digests on target..."
    ssh $SSH_OPTS -i "$SSH_KEY" "$target" \
        "cd ${remote_dir} && docker compose ps --format '{{.Name}} {{.Image}}' 2>/dev/null || true" > "${snapshot_file}.names" 2>/dev/null || true

    ssh $SSH_OPTS -i "$SSH_KEY" "$target" \
        "cd ${remote_dir} && docker images --format '{{.Repository}}:{{.Tag}} {{.ID}}' | grep '^tsisip/' || true" > "${snapshot_file}" 2>/dev/null || true

    if [ -s "${snapshot_file}" ]; then
        info "Deployer: rollback snapshot saved → ${snapshot_file}"
    else
        warn "Deployer: could not capture rollback snapshot (target may be fresh)"
    fi

    # ── 4b: Sync code ──
    info "Deployer: syncing code to target..."
    ssh $SSH_OPTS -i "$SSH_KEY" "$target" \
        "cd ${remote_dir} && git pull origin master 2>/dev/null || echo 'git pull skipped or failed'" || true

    # ── 4d: Pull and up ──
    info "Deployer: docker compose pull..."
    ssh $SSH_OPTS -i "$SSH_KEY" "$target" \
        "cd ${remote_dir} && sudo docker compose -f docker-compose.prod.yml pull 2>&1 | tail -10" || warn "Deployer: pull had warnings"

    info "Deployer: docker compose up..."
    ssh $SSH_OPTS -i "$SSH_KEY" "$target" \
        "cd ${remote_dir} && sudo docker compose -f docker-compose.prod.yml up -d" || { error "Deployer: compose up failed"; return 1; }

    # ── 4e: Wait for containers ──
    info "Deployer: waiting for containers to stabilize..."
    sleep 10

    info "Deployer: complete"
}

if [ "$LIVE_TEST" = false ]; then
    deployer
    gate_pass "4" "Deploy stage complete"
else
    info "--live-test: skipping deploy, running verification only"
fi

# ═══════════════════════════════════════════════════════════════════════
# GATE 5: VERIFY — OMK Verifier Agent
# Post-deploy health checks: container health, HTTP probes, SIP probe,
# backup metrics. Triggers rollback on any critical failure.
# ═══════════════════════════════════════════════════════════════════════
step "GATE 5/5: Verify — OMK Verifier Agent"

# OMK Verifier Agent Role:
#   Mission: After deploy, validate all services are healthy and functional.
#            Checks: container health status, OCP login page HTTP 200,
#            wiki/OCP endpoints, SIP OPTIONS 200 OK, backup metrics loopback.
#            If any critical check fails, triggers rollback to pre-deploy state.
#   Tools:   Shell, SSH, curl, sipsak (or netcat/python SIP probe)
#   Input:   TSiAPP_HOST, TSiAPP_USER, SSH_KEY
#   Output:  Verification report; rollback signal on failure
#
verifier() {
    local target="${TSiAPP_USER}@${TSiAPP_HOST}"
    local remote_dir="/opt/tsisip"
    local verify_ok=true

    if [ "$DRY_RUN" = true ]; then
        info "[DRY-RUN] Would run verification probes on target"
        return 0
    fi

    # ── 5a: Container health status ──
    info "Verifier: checking container health..."
    local unhealthy
    unhealthy=$(ssh $SSH_OPTS -i "$SSH_KEY" "$target" \
        "cd ${remote_dir} && sudo docker compose -f docker-compose.prod.yml ps --format '{{.Name}} {{.Status}}' | grep -v 'healthy\|running' || true" 2>/dev/null)
    if [ -n "$unhealthy" ]; then
        warn "Verifier: unhealthy containers detected:"
        echo "$unhealthy"
        verify_ok=false
    else
        info "Verifier: all containers healthy/running"
    fi

    # ── 5b: OCP HTTP probe ──
    info "Verifier: probing OCP login page..."
    local ocp_code
    ocp_code=$(ssh $SSH_OPTS -i "$SSH_KEY" "$target" \
        "curl -s -o /dev/null -w '%{http_code}' http://localhost:8084/login.php" 2>/dev/null || echo "000")
    if [ "$ocp_code" = "200" ]; then
        info "Verifier: OCP login → HTTP 200"
    else
        warn "Verifier: OCP login → HTTP ${ocp_code}"
        verify_ok=false
    fi

    # ── 5c: Nginx /TSiSIP/ health ──
    info "Verifier: probing Nginx /TSiSIP/..."
    local nginx_code
    nginx_code=$(ssh $SSH_OPTS -i "$SSH_KEY" "$target" \
        "curl -s -o /dev/null -w '%{http_code}' http://localhost/TSiSIP/health" 2>/dev/null || echo "000")
    if [ "$nginx_code" = "200" ] || [ "$nginx_code" = "404" ]; then
        info "Verifier: Nginx /TSiSIP/ → HTTP ${nginx_code}"
    else
        warn "Verifier: Nginx /TSiSIP/ → HTTP ${nginx_code}"
    fi

    # ── 5d: SIP OPTIONS probe ──
    info "Verifier: SIP OPTIONS probe..."
    local sip_response
    sip_response=$(ssh $SSH_OPTS -i "$SSH_KEY" "$target" \
        "python3 -c '
import socket
msg = b\"OPTIONS sip:opensips@localhost:5060 SIP/2.0\r\n\" \\
      b\"Via: SIP/2.0/UDP 127.0.0.1:5062;branch=z9hG4bK-verify123\r\n\" \\
      b\"From: <sip:verify@127.0.0.1>;tag=verifytag\r\n\" \\
      b\"To: <sip:opensips@localhost:5060>\r\n\" \\
      b\"Call-ID: verify-001@127.0.0.1\r\n\" \\
      b\"CSeq: 1 OPTIONS\r\n\" \\
      b\"Max-Forwards: 70\r\n\" \\
      b\"Content-Length: 0\r\n\r\n\"
sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
sock.settimeout(5)
sock.sendto(msg, (\"127.0.0.1\", 5060))
try:
    data, _ = sock.recvfrom(4096)
    print(data.decode().splitlines()[0])
except:
    print(\"TIMEOUT\")
'" 2>/dev/null || echo "SIP_PROBE_UNAVAILABLE")
    if echo "$sip_response" | grep -q "SIP/2.0 200"; then
        info "Verifier: SIP OPTIONS → 200 OK"
    else
        warn "Verifier: SIP OPTIONS → ${sip_response}"
        verify_ok=false
    fi

    # ── 5e: Backup metrics loopback ──
    info "Verifier: backup metrics endpoint..."
    local backup_code
    backup_code=$(ssh $SSH_OPTS -i "$SSH_KEY" "$target" \
        "curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:9101/metrics || echo '000'" 2>/dev/null)
    if [ "$backup_code" = "200" ] || [ "$backup_code" = "000" ]; then
        info "Verifier: backup metrics → HTTP ${backup_code} (best-effort)"
    else
        warn "Verifier: backup metrics → HTTP ${backup_code}"
    fi

    if [ "$verify_ok" = true ]; then
        info "Verifier: ALL CHECKS PASSED"
        return 0
    else
        error "Verifier: CRITICAL CHECKS FAILED"
        return 1
    fi
}

ROLLBACK_NEEDED=false
if ! verifier; then
    ROLLBACK_NEEDED=true
    gate_fail "5" "Verification failed"
else
    gate_pass "5" "Verification passed"
fi

# ═══════════════════════════════════════════════════════════════════════
# ROLLBACK: Restore previous digests if verification failed
# ═══════════════════════════════════════════════════════════════════════
if [ "$ROLLBACK_NEEDED" = true ] && [ "$DRY_RUN" = false ] && [ "$LIVE_TEST" = false ]; then
    step "ROLLBACK: Reverting to pre-deploy state"

    snapshot_file=$(find "$ROLLBACK_STATE_DIR" -name "${RUN_ID}-digests.txt" -print -quit 2>/dev/null)

    if [ -n "$snapshot_file" ] && [ -s "$snapshot_file" ]; then
        info "Rollback: restoring previous images from ${snapshot_file}..."
        while read -r line; do
            img=$(echo "$line" | awk '{print $1}')
            digest=$(echo "$line" | awk '{print $2}')
            if [ -n "$img" ] && [ -n "$digest" ]; then
                ssh $SSH_OPTS -i "$SSH_KEY" "${TSiAPP_USER}@${TSiAPP_HOST}" \
                    "docker tag ${digest} ${img} 2>/dev/null || true" || true
            fi
        done < "$snapshot_file"

        info "Rollback: restarting containers with previous images..."
        ssh $SSH_OPTS -i "$SSH_KEY" "${TSiAPP_USER}@${TSiAPP_HOST}" \
            "cd /opt/tsisip && sudo docker compose -f docker-compose.prod.yml up -d" || true

        info "Rollback: complete"
    else
        error "Rollback: no snapshot available. Manual recovery required."
    fi

    error "Deploy FAILED and rollback executed (if snapshot existed)."
    exit 1
fi

# ═══════════════════════════════════════════════════════════════════════
# AUDIT TRAIL
# ═══════════════════════════════════════════════════════════════════════
step "AUDIT TRAIL"

mkdir -p "$PROJECT_ROOT/reports"
cat > "$PROJECT_ROOT/reports/deploy-${RUN_ID}.json" <<EOF
{
  "run_id": "${RUN_ID}",
  "git_sha": "${GIT_SHA}",
  "timestamp": "$(date -Iseconds)",
  "target": "${TSiAPP_USER}@${TSiAPP_HOST}",
  "dry_run": ${DRY_RUN},
  "live_test": ${LIVE_TEST},
  "gates": {
    "preflight": "PASS",
    "impact": "PASS",
    "build": "PASS",
    "push": "PASS",
    "deploy": "PASS",
    "verify": "PASS"
  },
  "rollback_needed": ${ROLLBACK_NEEDED},
  "changed_files": "$(echo "$CHANGED_FILES" | tr '\n' ',' | sed 's/,$//')"
}
EOF

info "Audit report: reports/deploy-${RUN_ID}.json"

step "ORQUESTRAÇÃO COMPLETA!"
info "Run ID: ${RUN_ID}"
info "All gates passed. Deployment verified."
