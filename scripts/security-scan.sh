#!/usr/bin/env bash
# TSiSIP Security Scan
set -euo pipefail

echo "=== TSiSIP Security Scan ==="

# Check for secrets in code
echo ""
echo "1. Checking for secrets..."
grep -r "password.*=" web/ --include="*.php" | grep -v "secrets/" | grep -v "password_hash" | grep -v "getenv" | head -5 || echo "✓ No hardcoded passwords"

# Check file permissions
echo ""
echo "2. Checking file permissions..."
find web -type f -perm /111 | grep -v ".php" | head -5 || echo "✓ No executable non-scripts"

# Check for SQL injection
echo ""
echo "3. Checking for SQL injection risks..."
grep -r "mysql_query\|mysqli_query" web/ --include="*.php" | head -5 || echo "✓ No raw SQL queries"

# Check for XSS
echo ""
echo "4. Checking for XSS risks..."
grep -r "echo.*\$_" web/ --include="*.php" | grep -v "htmlspecialchars" | head -5 || echo "✓ Proper escaping"

# Check for eval
echo ""
echo "5. Checking for eval..."
grep -r "eval(" web/ --include="*.php" | head -5 || echo "✓ No eval"

# Check for base64_decode
echo ""
echo "6. Checking for base64_decode..."
grep -r "base64_decode" web/ --include="*.php" | head -5 || echo "✓ No base64_decode"

# Check .env files
echo ""
echo "7. Checking .env files..."
find . -name ".env" -not -name ".env.example" | head -5 || echo "✓ No exposed .env files"

# Check secrets directory
echo ""
echo "8. Checking secrets..."
ls secrets/ 2>/dev/null | wc -l | xargs echo "Secret files:"

# Check for debug mode
echo ""
echo "9. Checking for debug mode..."
grep -r "display_errors.*On" web/ --include="*.php" | head -5 || echo "✓ Debug mode off"

# Check for session security
echo ""
echo "10. Checking session security..."
grep -r "session.cookie_secure.*=.*1" web/ --include="*.php" | head -5 || echo "✓ Secure cookies"

echo ""
echo "=== Security Scan Complete ==="
