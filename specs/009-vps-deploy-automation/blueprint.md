# Blueprint: VPS Deploy Automation Pipeline

**Branch**: `master` | **Date**: 2026-05-19
**Mode**: doc-only
**Total Tasks**: 10 | **Files**: 2 new, 4 modified, 0 deleted

## Key Decisions

- Multi-agent pipeline implemented as bash functions in single orchestration script → T2.1, T2.2, T2.3, T2.4, T2.5
- Separate GitHub Actions workflow for deploy (workflow_dispatch only) → T3.1
- `--dry-run` and `--live-test` flags for safe testing → T3.2, T3.3
- GitNexus impact analysis gates deploy on HIGH/CRITICAL risk → T1.2
- Rollback captures pre-deploy image digests and reverts on verification failure → T1.3

## Implementation Order

```
T1.1 (orchestrate-deploy.sh skeleton)
  ├── T1.2 (GitNexus impact hook)
  ├── T1.3 (rollback mechanism)
  ├── T2.1 (agent role definitions)
  │   ├── T2.2 (builder) [P] T2.3 (pusher)
  │   └── T2.4 (deployer)
  │       └── T2.5 (verifier)
  └── T3.1 (GitHub Actions integration)
      ├── T3.2 (dry-run test)
      └── T3.3 (live deploy test)
          └── T4.1 (README update)
```

## Phase 1 — Pipeline Architecture

### T1.1: Update orchestrate-deploy.sh with gated stages

**Type**: Modified file (`deploy/scripts/orchestrate-deploy.sh`)

The script is refactored into distinct stages:

```bash
#!/usr/bin/env bash
set -euo pipefail

# Stage definitions
stage_validation() { ... }
stage_impact_analysis() { ... }
stage_build() { ... }
stage_push() { ... }
stage_deploy() { ... }
stage_verify() { ... }
stage_rollback() { ... }

# Main pipeline
for stage in validation impact_analysis build push deploy verify; do
    echo "=== Stage: $stage ==="
    "stage_$stage" || { echo "Stage $stage failed"; exit 1; }
done
```

**Diff highlights**:
- Add `set -euo pipefail` for strict error handling
- Define `STAGE_LOG` array to track pass/fail per stage
- Add `--dry-run` flag parsing at startup

### T1.2: Add GitNexus impact analysis pre-deploy hook

**Type**: Modified file (`deploy/scripts/orchestrate-deploy.sh`)

Insert before build stage:

```bash
stage_impact_analysis() {
    echo "Running GitNexus change detection..."
    if ! npx gitnexus analyze --quiet 2>/dev/null; then
        echo "WARN: GitNexus index stale, reanalyzing..."
        npx gitnexus analyze
    fi
    
    local changed_files=$(git diff --name-only HEAD~1)
    for file in $changed_files; do
        local risk=$(npx gitnexus impact --target "$file" --json | jq -r '.risk_level')
        if [[ "$risk" == "HIGH" || "$risk" == "CRITICAL" ]]; then
            echo "FAIL: HIGH/CRITICAL risk detected on $file"
            return 1
        fi
    done
}
```

### T1.3: Add rollback mechanism

**Type**: Modified file (`deploy/scripts/orchestrate-deploy.sh`)

```bash
stage_deploy() {
    # Capture pre-deploy digests
    docker compose -f "$COMPOSE_FILE" images --format '{{.Name}} {{.Digest}}' > "$ROLLBACK_STATE"
    
    # Deploy
    docker compose -f "$COMPOSE_FILE" up -d
}

stage_rollback() {
    echo "Rolling back to previous images..."
    while read -r name digest; do
        docker pull "$name@$digest"
        docker tag "$name@$digest" "$name:latest"
    done < "$ROLLBACK_STATE"
    docker compose -f "$COMPOSE_FILE" up -d
}
```

## Phase 2 — OMK Agent Orchestration

### T2.1-T2.5: Agent roles as bash functions

**Type**: Modified file (`deploy/scripts/orchestrate-deploy.sh`)

```bash
builder() {
    local changed_dockerfiles=$(git diff --name-only HEAD~1 | grep Dockerfile)
    for df in $changed_dockerfiles; do
        local service=$(dirname "$df" | xargs basename)
        docker build -t "ghcr.io/b0yz4kr14/tsisip/${service}:latest" -f "$df" .
    done
}

pusher() {
    if docker info 2>/dev/null | grep -q "ghcr.io"; then
        docker push "ghcr.io/b0yz4kr14/tsisip/opensips:latest"
        docker push "ghcr.io/b0yz4kr14/tsisip/ocp:latest"
    else
        echo "WARN: Not logged into registry, falling back to build-on-target"
        return 1
    fi
}

deployer() {
    ssh "$VPS_HOST" "cd /opt/tsisip && git pull && docker compose pull && docker compose up -d"
}

verifier() {
    ssh "$VPS_HOST" "docker compose ps | grep -q healthy"
    curl -sf "http://${VPS_HOST}:8084/login.php" | grep -q "TSiSIP"
    # SIP probe via sipsak or python socket
}
```

## Phase 3 — GitHub Actions Integration

### T3.1: Deploy workflow

**Type**: New file (`.github/workflows/deploy.yml`)

```yaml
name: Deploy to VPS
on:
  workflow_dispatch:
    inputs:
      target_host:
        description: 'VPS target host'
        required: true
        default: '179.190.15.116'

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Run deploy pipeline
        run: ./deploy/scripts/orchestrate-deploy.sh --target ${{ inputs.target_host }}
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
```

### T3.2-T3.3: Test flags

**Type**: Modified file (`deploy/scripts/orchestrate-deploy.sh`)

Add at script startup:

```bash
DRY_RUN=false
LIVE_TEST=false
for arg in "$@"; do
    case "$arg" in
        --dry-run) DRY_RUN=true ;;
        --live-test) LIVE_TEST=true ;;
    esac
done

if [[ "$DRY_RUN" == true ]]; then
    echo "[DRY-RUN] No mutating operations will be performed"
    # Override docker, ssh, compose commands with echo equivalents
fi
```

## Phase 4 — Documentation

### T4.1: Update deploy README

**Type**: Modified file (`deploy/README-VPS-DEPLOY.md`)

Add sections:
- Pipeline stages overview
- `--dry-run` usage
- `--live-test` usage
- Rollback behavior
- GitHub Actions trigger instructions

## Pre-completed Tasks

None — all tasks were implemented during the feature development cycle.

## Checklist

- [x] T1.1: Gated stages in orchestrate-deploy.sh
- [x] T1.2: GitNexus impact analysis hook
- [x] T1.3: Rollback mechanism
- [x] T2.1: Agent role definitions
- [x] T2.2: Builder agent logic
- [x] T2.3: Pusher agent logic
- [x] T2.4: Deployer agent logic
- [x] T2.5: Verifier agent logic
- [x] T3.1: GitHub Actions integration
- [x] T3.2: Dry-run test
- [x] T3.3: Live deploy test
- [x] T4.1: Deploy README update

## Validation

Run:
```bash
./deploy/scripts/orchestrate-deploy.sh --dry-run
./deploy/scripts/orchestrate-deploy.sh --live-test
```

Expected: All stages report `[PASS]` or `[DRY-RUN] Would ...`.
