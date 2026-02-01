# Legacy Code - Not In Use

This directory contains **legacy/duplicate code** that is **NOT actively used** by the application.

## Current Active Implementation

The active Django app is in `/store/` directory:
- Models: `/store/models.py`
- Views: `/store/views.py`
- URLs: `/store/urls.py`
- Templates: `/templates/store/`

## What's Here (Legacy)

This `paint_store/paint_store/` subdirectory contains:
- `views.py` - Old view functions (imports from `paint_store.models` which doesn't exist)
- `urls.py` - Old URL patterns
- `fixtures/` - Old fixture data

## Why It Exists

This appears to be from an earlier project structure before the app was refactored to use the `store` app.

## Important

**DO NOT modify files in this directory.** All development should happen in the `/store/` app.

The main project settings in `/paint_store/settings.py` correctly points to `'store.apps.StoreConfig'` in `INSTALLED_APPS`.

## To Clean Up (Future)

This directory could be safely deleted in a future cleanup task, but it's kept for now to preserve any historical context.
