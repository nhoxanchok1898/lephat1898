#!/bin/bash

# Cleanup script to delete old and unused branches

# Fetch all branches
git fetch --prune

# List all branches and filter out current branch
branches=$(git branch | grep -v "*" | grep -v "main")

# Loop through branches and delete them if needed
for branch in $branches; do
    echo "Deleting branch: $branch"
    git branch -d $branch
done
