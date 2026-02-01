Deployment options
==================

This document describes quick deployment options for this repository.

1) Render (recommended quick deploy)
-------------------------------------
- Create a new Web Service on Render and connect your GitHub repo `nhoxanchok1898/lephat1898`.
- In Render service settings get the Service ID.
- In GitHub repository settings, add Secrets:
  - `RENDER_SERVICE_ID` — the Render service id (for example `srv-...`)
  - `RENDER_API_KEY` — a Render API key with "deploy" permission
- The workflow `.github/workflows/deploy-render.yml` will trigger on pushes to `main` and call the Render API to create a deploy.

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
- I cannot run the deploy automatically without the required secrets or credentials. If you add `RENDER_SERVICE_ID` and `RENDER_API_KEY` as GitHub secrets I can trigger a deploy by pushing to `main` or manually creating a workflow dispatch.
