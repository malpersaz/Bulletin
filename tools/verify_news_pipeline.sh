#!/usr/bin/env bash
# ==============================================================================
# verify_news_pipeline.sh
# End-to-End Editorial Pipeline Verification Suite (Amed Framework / Bulletin)
#
# Runs the 4-stage automated validation chain:
#   Stage 1: kevtemen  - Same-day freshness & anti-laundering gate
#   Stage 2: kevseo    - SEO, headline/deck length, and semantic HTML audit
#   Stage 3: kevrast   - Fact-checking, link health, and --explain diagnostics
#   Stage 4: audit_seo - (Optional) Post-staging rubric check with --record-json
# ==============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WORKSPACE_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

DRAFT_FILE=""
DOSSIER_FILE=""
HEADLINE=""
SUMMARY=""
SEO_SLUG=""
RECORD_JSON=""
PROFILE="editorial"
LANG_STATUS="y"
NO_NETWORK=""

usage() {
    cat <<EOF
Usage: $(basename "$0") [options]

Required Options:
  --draft <PATH>        Path to drafted HTML article file
  --dossier <PATH>      Path to research dossier JSON
  --headline <STR>      Article headline (40 to 70 characters)
  --summary <STR>       Article deck summary (90 to 120 characters)

Optional Options:
  --seo <STR>           Wikipedia-style SEO slug (with underscores)
  --record-json <PATH>  Post-staging JSON envelope for audit_content_seo
  --profile <NAME>      Audit rubric profile: editorial (default) or curator
  --lang-status <y|n>   Target language status (default: y)
  --no-network          Run offline without live HTTP verification
  -h, --help            Show this help message
EOF
    exit 2
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --draft)
            DRAFT_FILE="$2"
            shift 2
            ;;
        --dossier)
            DOSSIER_FILE="$2"
            shift 2
            ;;
        --headline)
            HEADLINE="$2"
            shift 2
            ;;
        --summary)
            SUMMARY="$2"
            shift 2
            ;;
        --seo)
            SEO_SLUG="$2"
            shift 2
            ;;
        --record-json)
            RECORD_JSON="$2"
            shift 2
            ;;
        --profile)
            PROFILE="$2"
            shift 2
            ;;
        --lang-status)
            LANG_STATUS="$2"
            shift 2
            ;;
        --no-network)
            NO_NETWORK="--no-network"
            shift 1
            ;;
        -h|--help)
            usage
            ;;
        *)
            echo "Error: Unknown argument '$1'" >&2
            usage
            ;;
    esac
done

if [[ -z "$DRAFT_FILE" || -z "$DOSSIER_FILE" || -z "$HEADLINE" || -z "$SUMMARY" ]]; then
    echo "Error: Missing required arguments (--draft, --dossier, --headline, and --summary are mandatory)" >&2
    usage
fi

if [[ ! -f "$DRAFT_FILE" ]]; then
    echo "Error: Draft file '$DRAFT_FILE' not found" >&2
    exit 2
fi

if [[ ! -f "$DOSSIER_FILE" ]]; then
    echo "Error: Dossier file '$DOSSIER_FILE' not found" >&2
    exit 2
fi

echo "============================================================"
echo "VERIFY NEWS PIPELINE: Running 4-Stage Editorial Verification"
echo "============================================================"
echo "Draft:    $DRAFT_FILE"
echo "Dossier:  $DOSSIER_FILE"
echo "Headline: $HEADLINE (${#HEADLINE} chars)"
echo "Summary:  $SUMMARY (${#SUMMARY} chars)"
echo "============================================================"

# Stage 1: kevtemen Freshness Sentinel
echo -e "\n[STAGE 1/4] Running kevtemen Freshness Sentinel..."
KEVTEMEN_CMD=(python3 "$WORKSPACE_ROOT/.agents/skills/kevtemen/scripts/check_freshness.py" --file "$DRAFT_FILE" --dossier "$DOSSIER_FILE")
if [[ -n "$NO_NETWORK" ]]; then
    KEVTEMEN_CMD+=(--no-network)
fi
"${KEVTEMEN_CMD[@]}"
echo "-> Stage 1 PASSED: Freshness and anti-laundering verified."

# Stage 2: kevseo SEO & Semantic HTML Audit
echo -e "\n[STAGE 2/4] Running kevseo Google News & Semantic HTML Audit..."
KEVSEO_CMD=(python3 "$WORKSPACE_ROOT/.agents/skills/kevseo/scripts/audit_seo.py" --file "$DRAFT_FILE" --headline "$HEADLINE" --summary "$SUMMARY")
if [[ -n "$SEO_SLUG" ]]; then
    KEVSEO_CMD+=(--seo "$SEO_SLUG")
fi
"${KEVSEO_CMD[@]}"
echo "-> Stage 2 PASSED: SEO and Semantic HTML verified."

# Stage 3: kevrast Fact-Checking & Provenance Audit
echo -e "\n[STAGE 3/4] Running kevrast Fact-Checking & Link Provenance Audit..."
KEVRAST_CMD=(python3 "$WORKSPACE_ROOT/.agents/skills/kevrast/scripts/verify_facts_and_links.py" --file "$DRAFT_FILE" --dossier "$DOSSIER_FILE" --explain)
if [[ -n "$NO_NETWORK" ]]; then
    KEVRAST_CMD+=(--no-network)
fi
"${KEVRAST_CMD[@]}"
echo "-> Stage 3 PASSED: Facts, links, and provenance verified."

# Stage 4: audit_content_seo Post-Staging Rubric Check (Optional)
if [[ -n "$RECORD_JSON" ]]; then
    if [[ ! -f "$RECORD_JSON" ]]; then
        echo "Error: Record JSON file '$RECORD_JSON' not found" >&2
        exit 2
    fi
    echo -e "\n[STAGE 4/4] Running audit_content_seo Post-Staging Rubric..."
    python3 "$WORKSPACE_ROOT/tools/audit_content_seo.py" \
        --module mod_news \
        --profile "$PROFILE" \
        --record-json "$RECORD_JSON" \
        --lang-status "$LANG_STATUS"
    echo "-> Stage 4 PASSED: CMS record audit rubric passed."
else
    echo -e "\n[STAGE 4/4] Post-staging record check skipped (no --record-json supplied)."
fi

echo -e "\n============================================================"
echo "ALL PIPELINE VERIFICATION STAGES PASSED SUCCESSFULLY"
echo "============================================================"
exit 0
