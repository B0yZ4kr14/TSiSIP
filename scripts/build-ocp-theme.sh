#!/bin/bash
# TSiSIP OCP Theme Build Script
# Orchestrates asset generation, i18n compilation, validation, and Docker build.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
WEB_DIR="$PROJECT_ROOT/web/tsisip"
BUILD_DIR="$PROJECT_ROOT/build"
TESTS_DIR="$PROJECT_ROOT/tests"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

info() { echo -e "${GREEN}[INFO]${NC} $*"; }
warn() { echo -e "${YELLOW}[WARN]${NC} $*"; }
error() { echo -e "${RED}[ERROR]${NC} $*" >&2; }

cd "$PROJECT_ROOT"

# 1. Dependency check
info "Checking dependencies..."
command -v node >/dev/null 2>&1 || { error "Node.js is required"; exit 1; }
command -v msgfmt >/dev/null 2>&1 || { error "gettext (msgfmt) is required"; exit 1; }
command -v docker >/dev/null 2>&1 || { warn "Docker not found; skipping container build"; DOCKER_SKIP=1; }

# 2. Generate CSS variables
info "Generating CSS custom properties from theme.json..."
node "$BUILD_DIR/generate-css-variables.js"

# 3. Build theme CSS (copy source to output for now; PostCSS can be added later)
info "Building theme CSS..."
cp "$BUILD_DIR/tsisip-theme.src.css" "$WEB_DIR/css/tsisip-theme.css"

# 4. Generate asset manifest with hashes
info "Generating asset manifest..."
node "$BUILD_DIR/generate-manifest.js"

# 5. Compile i18n .po -> .mo
info "Compiling i18n locale files..."
mkdir -p "$WEB_DIR/locale/en_US/LC_MESSAGES"
mkdir -p "$WEB_DIR/locale/es_ES/LC_MESSAGES"
mkdir -p "$WEB_DIR/locale/pt_BR/LC_MESSAGES"
msgfmt "$WEB_DIR/locale/tsisip-en.po" -o "$WEB_DIR/locale/en_US/LC_MESSAGES/tsisip.mo"
msgfmt "$WEB_DIR/locale/tsisip-es.po" -o "$WEB_DIR/locale/es_ES/LC_MESSAGES/tsisip.mo"
msgfmt "$WEB_DIR/locale/tsisip-pt.po" -o "$WEB_DIR/locale/pt_BR/LC_MESSAGES/tsisip.mo"

# 6. Validate asset payload size (base theme without D3.js)
info "Validating asset payload size..."
BASE_PAYLOAD=$(find "$WEB_DIR" -type f \( -name '*.css' -o -name '*.js' -o -name '*.svg' -o -name '*.mo' \) -not -name 'd3.v7.min.js' -not -name 'd3.v7.min.*.js' | xargs -I{} stat -c%s {} | awk '{s+=$1} END {print s}')
MAX_PAYLOAD=153600  # 150KB
if [ "$BASE_PAYLOAD" -gt "$MAX_PAYLOAD" ]; then
    error "Base asset payload (${BASE_PAYLOAD} bytes) exceeds budget (${MAX_PAYLOAD} bytes)"
    exit 1
fi
info "Base asset payload: ${BASE_PAYLOAD} bytes (budget: ${MAX_PAYLOAD} bytes)"

# 7. CSS specificity audit
info "Running CSS specificity audit..."
IMPORTANT_COUNT=$(grep -c '!important' "$WEB_DIR/css/tsisip-theme.css" || true)
TOTAL_RULES=$(grep -cE '^\s*[^/\s].*\{' "$WEB_DIR/css/tsisip-theme.css" || true)
if [ "$TOTAL_RULES" -gt 0 ]; then
    IMPORTANT_PCT=$(( IMPORTANT_COUNT * 100 / TOTAL_RULES ))
    info "CSS !important usage: ${IMPORTANT_COUNT}/${TOTAL_RULES} (${IMPORTANT_PCT}%)"
    if [ "$IMPORTANT_PCT" -gt 20 ]; then
        error "!important usage (${IMPORTANT_PCT}%) exceeds 20% threshold"
        exit 1
    fi
fi

# 8. Run automated tests
info "Running automated tests..."
node "$TESTS_DIR/d3-jquery-coexistence.test.js"
node "$TESTS_DIR/accessibility-audit.test.js"

# 9. Docker build (optional)
if [ "${DOCKER_SKIP:-0}" != "1" ]; then
    info "Building OCP Docker image..."
    docker build -t tsisip/ocp:latest -f docker/ocp/Dockerfile .
    info "Docker image built: tsisip/ocp:latest"
fi

info "Build completed successfully."
