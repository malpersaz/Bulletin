#!/usr/bin/env bash
# ==============================================================================
# Bulletin CMS Production Deployment Script
# Deploys code to ssh-w02232db@w02232db.kasserver.com with a clean web root.
# ==============================================================================
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

SERVER_USER="ssh-w02232db"
SERVER_HOST="w02232db.kasserver.com"
REMOTE_DOCROOT="/www/htdocs/w02232db/amedbulten.com/"

DRY_RUN=false
if [[ "${1:-}" == "--dry-run" ]]; then
    DRY_RUN=true
    echo "[INFO] Running in DRY-RUN mode. No changes will be written."
fi

echo "=================================================="
echo " Bulletin Production Deployment"
echo " Target: ${SERVER_USER}@${SERVER_HOST}:${REMOTE_DOCROOT}"
echo " Source: ${ROOT_DIR}"
echo "=================================================="

# Check SSH connectivity
echo "[1/4] Verifying SSH connection to server..."
ssh -o BatchMode=yes -o ConnectTimeout=8 "${SERVER_USER}@${SERVER_HOST}" "true" || {
    echo "[ERROR] Cannot connect via SSH to ${SERVER_USER}@${SERVER_HOST}. Aborting."
    exit 1
}

# Rsync options
RSYNC_OPTS=(
    -avz
    --human-readable
    --delete
    --chmod=Du=rwx,Dg=rx,Do=rx,Fu=rw,Fg=r,Fo=r
    --include='/cache/'
    --include='/cache/.htaccess'
    --include='/templates_c/'
    --include='/templates_c/.htaccess'
    --include='/logs/'
    --include='/logs/.htaccess'
    --exclude-from="${ROOT_DIR}/.deployignore"
    --filter='protect config/*.local.php'
    --filter='protect data/*.json'
    --filter='protect data/*.sqlite'
    --filter='protect data/sqlite/*'
    --filter='protect static/mod_*/**'
    --filter='protect cache/**'
    --filter='protect templates_c/**'
    --filter='protect logs/**'
)

if [[ "${DRY_RUN}" == "true" ]]; then
    RSYNC_OPTS+=(--dry-run)
fi

echo "[2/4] Synchronizing files to production server..."
rsync "${RSYNC_OPTS[@]}" "${ROOT_DIR}/" "${SERVER_USER}@${SERVER_HOST}:${REMOTE_DOCROOT}"

if [[ "${DRY_RUN}" == "false" ]]; then
    echo "[3/4] Purging server-side Twig cache, bumping PHP mtimes (OPcache revalidation) & securing local configs..."
    ssh -o BatchMode=yes "${SERVER_USER}@${SERVER_HOST}" "rm -rf ${REMOTE_DOCROOT}cache/twig/* 2>/dev/null || true; find ${REMOTE_DOCROOT}lib ${REMOTE_DOCROOT}mod ${REMOTE_DOCROOT}tasks ${REMOTE_DOCROOT}index.php ${REMOTE_DOCROOT}init.php -name '*.php' -exec touch {} + 2>/dev/null || true; chmod 640 ${REMOTE_DOCROOT}config/*.local.php ${REMOTE_DOCROOT}data/*.json ${REMOTE_DOCROOT}data/*.sqlite 2>/dev/null || true"

    echo "[4/4] Verifying site health..."
    HTTP_STATUS=$(curl -s -L -o /dev/null -w "%{http_code}" https://amedbulten.com/ || echo "000")
    if [[ "${HTTP_STATUS}" == "200" ]]; then
        echo "==> [SUCCESS] Deployment completed successfully! HTTP Status: ${HTTP_STATUS}"
    else
        echo "==> [WARNING] Deployment completed but HTTP Status is ${HTTP_STATUS}"
    fi
    HOMEPAGE_HEADERS=$(curl -sI https://amedbulten.com/tr/ | tr -d '\r')
    if echo "${HOMEPAGE_HEADERS}" | grep -qiE '^cache-control:.*no-store|^pragma: no-cache|^expires: Thu, 01 Jan 1970'; then
        echo "==> [ERROR] Homepage /tr/ still emits administrative no-store/epoch-1970 cache headers. Failing deploy."
        exit 1
    fi
    echo "==> [OK] Homepage /tr/ cache headers clean (no no-store, no epoch Expires, no Pragma)."
else
    echo "[3/4] Dry run complete. Twig cache was not purged."
    echo "[4/4] Dry run completed successfully."
fi
