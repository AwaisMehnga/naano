---
name: jsonb
description: Postgres JSON columns must be jsonb. Use $table->jsonb() in migrations, never $table->json(). Activate when writing migrations, schema, columns, JSON fields, jsonb, or searchable JSON.
---

# jsonb

This app runs Postgres. JSON columns must be `jsonb` so they stay fast to search.

- Use `$table->jsonb('column')`. Never `$table->json()`.
- Eloquent still casts them as `array`.
- SQLite tests map `jsonb` to text. That is fine.
