import os
import zipfile

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
OUT = os.path.join(ROOT, 'repo_snapshot.zip')
EXCLUDE_DIRS = {'media', '.git', '__pycache__'}
EXCLUDE_FILES = {'db.sqlite3', 'repo_snapshot.zip'}

with zipfile.ZipFile(OUT, 'w', zipfile.ZIP_DEFLATED) as z:
    for dirpath, dirnames, filenames in os.walk(ROOT):
        # skip excluded directories
        rel_dir = os.path.relpath(dirpath, ROOT)
        if rel_dir == '.':
            rel_dir = ''
        parts = set(rel_dir.split(os.sep)) if rel_dir else set()
        if parts & EXCLUDE_DIRS:
            continue
        for fn in filenames:
            if fn in EXCLUDE_FILES:
                continue
            fp = os.path.join(dirpath, fn)
            arcname = os.path.relpath(fp, ROOT)
            z.write(fp, arcname)

print('Created', OUT)
