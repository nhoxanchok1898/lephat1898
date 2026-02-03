import os
files=[]
for root,dirs,filenames in os.walk('.', topdown=True):
    for f in filenames:
        try:
            p=os.path.join(root,f)
            s=os.path.getsize(p)
            files.append((s,p))
        except Exception:
            pass
files.sort(reverse=True)
for s,p in files[:40]:
    if s>1024*1024:
        size = f"{s/1024/1024:.1f} MB"
    elif s>1024:
        size = f"{s/1024:.1f} KB"
    else:
        size = f"{s} B"
    print(f"{size}\t{p}")
