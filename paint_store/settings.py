"""
Compatibility settings module.

Historically some entrypoints reference `paint_store.settings`. The real,
maintained settings live in `ecommerce.settings`, so we import and re-export
everything here to keep those entrypoints working.
"""

from ecommerce.settings import *  # noqa: F401,F403
