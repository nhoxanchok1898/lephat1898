Offline install workflow

1) On a machine that HAS internet access, run (bash):

```bash
# download wheels for black and flake8 and their dependencies
# this creates a 'wheels/' directory with .whl files
bash tools/download_wheels.sh wheels black flake8
# (optional) create a text file listing packages
ls wheels/*.whl > wheels/packages.txt
# zip the wheels folder and copy to your dev machine
zip -r wheels.zip wheels
```

2) Copy `wheels/` (or `wheels.zip`) to your dev machine (the Windows machine without network).

3) On the dev machine, unpack (if zipped) and run PowerShell installer:

```powershell
# unzip wheels.zip if needed then:
.\	ools\install_wheels.ps1 -WheelsPath .\wheels -UpgradePip
# Afterwards run:
py -m flake8 .
py -m black .
```

Notes:
- If some wheels cannot be used (platform mismatch), re-run the download step on an appropriate platform (e.g., download on a manylinux-compatible Linux for Linux hosts or on Windows for Windows wheels).
- If a package needs dependencies compiled from source, prefer to download wheels for those dependencies as well.
- The bash `download_wheels.sh` uses `pip download` which fetches wheels and sdists; copy everything in `wheels/` to the offline host.
