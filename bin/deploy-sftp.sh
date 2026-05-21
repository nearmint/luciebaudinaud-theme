#!/bin/bash
#
# bin/deploy-sftp.sh
#
# Deploy WordPress theme to OVH SFTP via lftp mirror.
#
# Usage:
#   bash bin/deploy-sftp.sh                # Full sync with confirmation
#   bash bin/deploy-sftp.sh --dry-run      # Preview without uploading
#   bash bin/deploy-sftp.sh --connect-test # Test SFTP connection
#

set -euo pipefail

# ═══════════════════════════════════════════════════════════════════
# 1. Load .env
# ═══════════════════════════════════════════════════════════════════

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
THEME_DIR="$( dirname "$SCRIPT_DIR" )"
ENV_FILE="$THEME_DIR/.env"

if [ ! -f "$ENV_FILE" ]; then
    echo "ERROR: $ENV_FILE not found."
    echo "Copy .env.example to .env and fill in the SFTP credentials."
    exit 1
fi

set -a
# shellcheck disable=SC1090
source "$ENV_FILE"
set +a

# ═══════════════════════════════════════════════════════════════════
# 2. Validate required env vars
# ═══════════════════════════════════════════════════════════════════

REQUIRED_VARS=("SFTP_HOST" "SFTP_USER" "SFTP_PORT" "SFTP_PASSWORD" "SFTP_REMOTE_PATH")
for VAR in "${REQUIRED_VARS[@]}"; do
    if [ -z "${!VAR:-}" ] || [ "${!VAR}" = "your_password_here" ] || [ "${!VAR}" = "your_user_here" ]; then
        echo "ERROR: $VAR is not set in .env (or still has placeholder value)."
        exit 1
    fi
done

# ═══════════════════════════════════════════════════════════════════
# 3. Validate lftp is installed
# ═══════════════════════════════════════════════════════════════════

if ! command -v lftp &> /dev/null; then
    echo "ERROR: lftp is not installed."
    echo "Install with: brew install lftp"
    exit 1
fi

# ═══════════════════════════════════════════════════════════════════
# 3.bis. Auto-add SFTP host key to known_hosts if missing
# ═══════════════════════════════════════════════════════════════════

if ! ssh-keygen -F "$SFTP_HOST" > /dev/null 2>&1; then
    echo "→ Adding $SFTP_HOST to ~/.ssh/known_hosts (first connection)..."
    mkdir -p ~/.ssh
    chmod 700 ~/.ssh
    ssh-keyscan -p "$SFTP_PORT" -H "$SFTP_HOST" >> ~/.ssh/known_hosts 2>/dev/null
    echo "✓ Host key added."
fi

# ═══════════════════════════════════════════════════════════════════
# 4. Parse mode argument
# ═══════════════════════════════════════════════════════════════════

MODE="full"
case "${1:-}" in
    --dry-run)
        MODE="dry-run"
        ;;
    --connect-test)
        MODE="connect-test"
        ;;
    "")
        MODE="full"
        ;;
    *)
        echo "Unknown argument: $1"
        echo "Usage: $0 [--dry-run | --connect-test]"
        exit 1
        ;;
esac

# ═══════════════════════════════════════════════════════════════════
# 5. Display deploy info
# ═══════════════════════════════════════════════════════════════════

echo "═══════════════════════════════════════════════════════════════"
echo "  SFTP DEPLOY — lb3 (luciebaudinaud.com)"
echo "═══════════════════════════════════════════════════════════════"
echo "Mode          : $MODE"
echo "Source local  : $THEME_DIR"
echo "Target server : $SFTP_USER@$SFTP_HOST:$SFTP_PORT"
echo "Target path   : $SFTP_REMOTE_PATH"
echo "Timestamp     : $(date '+%Y-%m-%d %H:%M:%S')"
echo "═══════════════════════════════════════════════════════════════"

# ═══════════════════════════════════════════════════════════════════
# 6. Build lftp exclude list
# ═══════════════════════════════════════════════════════════════════

EXCLUDES=(
    "--exclude-glob=.git/"
    "--exclude-glob=.github/"
    "--exclude-glob=node_modules/"
    "--exclude-glob=vendor/"
    "--exclude-glob=docs/"
    "--exclude-glob=src/"
    "--exclude-glob=bin/"
    "--exclude-glob=.env*"
    "--exclude-glob=.gitignore"
    "--exclude-glob=.editorconfig"
    "--exclude-glob=.husky/"
    "--exclude-glob=composer.json"
    "--exclude-glob=composer.lock"
    "--exclude-glob=composer.phar"
    "--exclude-glob=phpcs.xml.dist"
    "--exclude-glob=package.json"
    "--exclude-glob=package-lock.json"
    "--exclude-glob=vite.config.js"
    "--exclude-glob=eslint.config.js"
    "--exclude-glob=README.md"
    "--exclude-glob=CHANGELOG.md"
    "--exclude-glob=LICENSE"
    "--exclude-glob=CLAUDE.md"
    "--exclude-glob=.claude/"
    "--exclude-glob=*.log"
    "--exclude-glob=*.tmp"
    "--exclude-glob=.DS_Store"
)

# ═══════════════════════════════════════════════════════════════════
# 7. Execute mode
# ═══════════════════════════════════════════════════════════════════

case "$MODE" in
    connect-test)
        echo "Testing SFTP connection..."
        lftp -u "$SFTP_USER,$SFTP_PASSWORD" -p "$SFTP_PORT" "sftp://$SFTP_HOST" <<EOF
set ssl:verify-certificate no
cd $SFTP_REMOTE_PATH
ls -l | head -10
bye
EOF
        echo ""
        echo "✓ Connection test successful."
        ;;

    dry-run)
        echo "DRY-RUN — no files will be uploaded."
        echo ""
        lftp -u "$SFTP_USER,$SFTP_PASSWORD" -p "$SFTP_PORT" "sftp://$SFTP_HOST" <<EOF
set ssl:verify-certificate no
mirror --reverse --delete --dry-run \\
    ${EXCLUDES[@]} \\
    "$THEME_DIR" "$SFTP_REMOTE_PATH"
bye
EOF
        echo ""
        echo "✓ Dry-run complete. Review the output above before running a real deploy."
        ;;

    full)
        echo "FULL SYNC — files will be uploaded to OVH production."
        echo ""
        read -p "Type 'yes' to confirm deployment: " CONFIRM
        if [ "$CONFIRM" != "yes" ]; then
            echo "Deployment cancelled."
            exit 0
        fi

        echo ""
        echo "Starting deploy..."
        lftp -u "$SFTP_USER,$SFTP_PASSWORD" -p "$SFTP_PORT" "sftp://$SFTP_HOST" <<EOF
set ssl:verify-certificate no
mirror --reverse --delete \\
    ${EXCLUDES[@]} \\
    "$THEME_DIR" "$SFTP_REMOTE_PATH"
bye
EOF
        echo ""
        echo "✓ Deploy complete."
        echo ""
        echo "Next steps:"
        echo "  1. Verify the site visually: https://luciebaudinaud.com"
        echo "  2. Run sanity check: bash bin/check-headers.sh https://luciebaudinaud.com"
        echo "  3. Purge Cloudflare cache if needed (manager.cloudflare.com)"
        ;;
esac

echo ""
echo "Done."
