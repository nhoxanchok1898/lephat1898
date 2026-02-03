#!/bin/bash
#
# Branch Cleanup Script for lephat1898 Repository
# This script helps delete old and abandoned branches
#
# Usage: ./cleanup_branches.sh [--dry-run]
#
# Use --dry-run to see what would be deleted without actually deleting

set -e

DRY_RUN=false
if [[ "$1" == "--dry-run" ]]; then
    DRY_RUN=true
    echo "DRY RUN MODE - No branches will be deleted"
    echo "============================================"
fi

# Branches to delete (old/abandoned)
BRANCHES_TO_DELETE=(
    "ci/actions-healthcheck"
    "ci/auto-issue-on-fail"
    "ci/debug-render-payload"
    "ci/fix-render-json"
    "ci/fix-render-payload"
    "ci/print-curl-stdout"
    "ci/render-curl-trace"
    "ci/render-logs-artifacts"
    "ci/robust-render-deploy"
    "copilot/complete-code-review-fixes"
    "copilot/fix-critical-issues-paint-store"
    "copilot/fix-yaml-syntax-error"
    "copilot/fix-yaml-syntax-error-again"
    "nhoxanchok1898-patch-1"
    "revert-16-copilot/fix-critical-issues-paint-store"
    "revert-13-nhoxanchok1898-patch-1"
)

# Branches to keep (active/essential)
BRANCHES_TO_KEEP=(
    "main"
    "dev"
    "feature/initial"
    "feature/payments"
    "fix/pin-django-4.2"
    "copilot/implement-ecommerce-website"
    "copilot/add-advanced-ecommerce-features"
    "copilot/add-advanced-search-system"
    "copilot/implement-advanced-features"
    "copilot/merge-phase1-and-phase2-prs"
)

echo "Branch Cleanup Script"
echo "====================="
echo ""
echo "This script will delete ${#BRANCHES_TO_DELETE[@]} old/abandoned branches"
echo ""

# Show branches to be deleted
echo "Branches to DELETE:"
echo "-------------------"
for branch in "${BRANCHES_TO_DELETE[@]}"; do
    echo "  - $branch"
done
echo ""

# Show branches to keep
echo "Branches to KEEP:"
echo "-----------------"
for branch in "${BRANCHES_TO_KEEP[@]}"; do
    echo "  - $branch"
done
echo ""

if [ "$DRY_RUN" = false ]; then
    read -p "Do you want to proceed with deletion? (yes/no): " confirm
    if [[ "$confirm" != "yes" ]]; then
        echo "Aborted."
        exit 0
    fi
fi

# Delete branches
echo ""
echo "Deleting branches..."
echo "--------------------"

for branch in "${BRANCHES_TO_DELETE[@]}"; do
    # Check if branch exists remotely
    if git ls-remote --heads origin "$branch" | grep -q "$branch"; then
        if [ "$DRY_RUN" = true ]; then
            echo "[DRY RUN] Would delete remote branch: $branch"
        else
            echo "Deleting remote branch: $branch"
            git push origin --delete "$branch" || echo "  Failed to delete $branch (may not exist or lack permissions)"
        fi
    else
        echo "Branch $branch does not exist remotely, skipping"
    fi
    
    # Check if branch exists locally
    if git show-ref --verify --quiet "refs/heads/$branch"; then
        if [ "$DRY_RUN" = true ]; then
            echo "[DRY RUN] Would delete local branch: $branch"
        else
            echo "Deleting local branch: $branch"
            git branch -D "$branch" || echo "  Failed to delete local $branch"
        fi
    fi
done

echo ""
if [ "$DRY_RUN" = true ]; then
    echo "DRY RUN COMPLETE - No changes were made"
    echo "Run without --dry-run to actually delete branches"
else
    echo "Branch cleanup complete!"
fi
echo ""
echo "Remaining branches:"
git branch -a | grep -E "main|dev|feature|fix|copilot" | head -20
