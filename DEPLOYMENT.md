Deployment options
==================

This document describes quick deployment options for this repository.

1) Render (recommended quick deploy)
-------------------------------------
- Create a new Web Service on Render and connect your GitHub repo `nhoxanchok1898/lephat1898`.
- Prefer Render auto-deploy from the connected GitHub repo or use the `render.yaml` Blueprint in this repository.
- Set production environment variables in the Render dashboard (`SECRET_KEY`, `SITE_URL`, email/payment keys, etc.).
- Render will redeploy automatically when `main` changes, so no GitHub-hosted Render API key is required for the default flow.

2) Railway
-----------
- Railway supports GitHub integration; after connecting, Railway will build and deploy automatically.
- Alternatively use the `railway` CLI to provision services and deploy from CI. Railway requires an API key secret in GitHub.

3) VPS / Docker
----------------
- This repo includes a `Dockerfile`. On a VPS you can build and run with:

```
docker build -t lephat1898:latest .
docker run -d -p 8000:8000 --name lephat1898 lephat1898:latest
```

- For production you should use `docker-compose` or systemd to manage the container and configure environment variables (SECRET_KEY, DB, ALLOWED_HOSTS, etc.).

Notes
-----
- If a Render API key is ever exposed, revoke it in the Render dashboard and create a new one there. Do not keep long-lived Render deploy keys in GitHub unless you explicitly need an API-driven deploy flow.
