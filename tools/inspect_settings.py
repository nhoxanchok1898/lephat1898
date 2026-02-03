import importlib, pprint, sys, os

# Ensure project root is on sys.path so we can import local_settings_sqlite
proj_root = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
if proj_root not in sys.path:
	sys.path.insert(0, proj_root)

s = importlib.import_module('local_settings_sqlite')
print('BASE_DIR type:', type(s.BASE_DIR))
print('DEBUG:', getattr(s, 'DEBUG', None))
print('ALLOWED_HOSTS sample:', getattr(s, 'ALLOWED_HOSTS', [])[:3])
print('Has ROOT_URLCONF:', hasattr(s, 'ROOT_URLCONF'))
print('TEMPLATES:')
pprint.pprint(getattr(s, 'TEMPLATES', None))
