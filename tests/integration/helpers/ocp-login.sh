#!/usr/bin/env bash
# OCP Login Helper — Handles CSRF token extraction for integration tests
# Usage: source tests/integration/helpers/ocp-login.sh
#        ocp_login "http://localhost/TSiSIP" "username" "/tmp/cookies.txt"
#
# Password is read from TSISIP_OCP_ADMIN_PASSWORD env var.
# Returns 0 on success, 1 on failure.

set -euo pipefail

ocp_login() {
    local base_url="${1:-${TSISIP_BASE_URL:-http://localhost}}"
    local username="${2:-testadmin}"
    local cookie_jar="${3:-/tmp/ocp_test_cookies.$$}"
    local host_header="${TSISIP_HOST_HEADER:-}"
    local password="${TSISIP_OCP_ADMIN_PASSWORD:-}"
    if [ -z "$password" ]; then
        echo "[FAIL] TSISIP_OCP_ADMIN_PASSWORD must be set" >&2
        return 1
    fi

    local curl_host=""
    if [ -n "$host_header" ]; then
        curl_host="-H Host:${host_header}"
    fi

    local curl_insecure=""
    if [ "${CURL_INSECURE:-}" = "true" ] || [ "${CURL_INSECURE:-}" = "1" ]; then
        curl_insecure="-k"
    fi

    local login_page
    login_page=$(curl -fsSL ${curl_insecure} ${curl_host} -c "$cookie_jar" -b "$cookie_jar" "${base_url}/login.php" 2>/dev/null)

    local csrf_token
    csrf_token=$(echo "$login_page" | grep -oE 'name="csrf_token" value="[^"]+"' | sed 's/.*value="\([^"]*\)".*/\1/' | head -1)

    if [ -z "$csrf_token" ]; then
        echo "[FAIL] Could not extract CSRF token from login page" >&2
        return 1
    fi

    local response
    response=$(curl -fsSL ${curl_insecure} ${curl_host} -c "$cookie_jar" -b "$cookie_jar" \
        -X POST "${base_url}/login.php" \
        -d "username=${username}&pass=${password}&csrf_token=${csrf_token}" \
        -L 2>/dev/null)

    if [[ "$response" == *[Dd]ashboard* ]] || [[ "$response" == *[Oo]verview* ]] || [[ "$response" == *[Ss]ign[[:space:]]out* ]] || [[ "$response" == *[Ll]ogout* ]]; then
        echo "[PASS] Login successful"
        return 0
    elif [[ "$response" == *[Ii]nvalid[[:space:]]credentials* ]] || [[ "$response" == *[Aa]uthentication[[:space:]]failed* ]]; then
        echo "[FAIL] Invalid credentials" >&2
        return 1
    elif [[ "$response" == *[Mm][Ff][Aa]*verify* ]] || [[ "$response" == *[Tt]wo[[:space:]]factor* ]] || [[ "$response" == *2[Ff][Aa]* ]]; then
        echo "[PASS] Login successful (MFA required)"
        return 0
    else
        echo "[FAIL] Login response did not indicate success" >&2
        return 1
    fi
}

if [ "${BASH_SOURCE[0]}" = "${0}" ]; then
    echo "Usage: source ${BASH_SOURCE[0]} && ocp_login [base_url] [username] [cookie_jar]"
    exit 1
fi
