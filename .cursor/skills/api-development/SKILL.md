---
name: api-development
description: Dashboard APIs use Laravel controllers and services. Controllers return AjaxResponse. Route files only declare routes. Use when adding or changing API routes, controllers, services, or JSON responses.
---

# API development

We need to use APIs and for the api response from controller we use AjaxResponse helper for consistent responses. The routes file only have the routes nothing else not logic running.

- Put dashboard APIs in `routes/api.php` (`/api/company`, `/api/creator`). Auth middleware stays on the group.
- Routes declare `[Controller::class, 'method']` only. No closures, no queries, no response building.
- Controllers call services, then return `AjaxResponse::success()`, `AjaxResponse::error()`, or `AjaxResponse::failure()`.
- Services own the work. Do not return `AjaxResponse` from a service.
