Paint Store: finalize development setup, sitemaps, and deployment tools

- Fix template syntax errors and ensure `static` context is available in templates.
- Add `django.contrib.sitemaps` and implement `ProductSitemap` with image URLs using `SITE_URL`.
- Add `SITE_URL` support via environment and `.env` (python-dotenv).
- Provide QA scripts under `tools/` (http_qa, integration_test, sitemap printer, production checker).
- Add `RUNBOOK.md`, `.env.example`, and `README.md` with deployment guidance.
- Add Dockerfile, docker-compose example, and GitHub Actions CI workflow.
- Add robots.txt template, collectstatic configuration, and basic production readiness checks.
- Cleanup debug prints and run unit/integration tests.

Co-authored-by: Developer
