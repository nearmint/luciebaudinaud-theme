#!/bin/bash
#
# bin/check-headers.sh
#
# Quick sanity check post-deploy: HTTP status + critical headers.
#
# Usage:
#   bash bin/check-headers.sh https://luciebaudinaud.com
#

set -euo pipefail

URL="${1:-https://luciebaudinaud.com}"

echo "═══════════════════════════════════════════════════════════════"
echo "  Health check : $URL"
echo "═══════════════════════════════════════════════════════════════"

echo ""
echo "→ HTTP status"
curl -sI -o /dev/null -w "HTTP/%{http_version} %{http_code} (time: %{time_total}s)\n" "$URL"

echo ""
echo "→ Headers (subset)"
curl -sI "$URL" | grep -iE "^(content-type|content-security-policy|x-frame-options|x-content-type-options|referrer-policy|permissions-policy|strict-transport-security|cache-control):" || echo "(no security headers detected)"

echo ""
echo "→ HTML title"
curl -s "$URL" | grep -oE "<title>[^<]+</title>" | head -1 || echo "(no title found)"

echo ""
echo "✓ Health check done."
