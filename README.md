# AgentLog.gg – Codex Starter Pack

This pack is meant to be dropped into a new Git repo and used with OpenAI Codex (cloud/CLI/IDE) to scaffold the project quickly.

It contains:
- Platform Technical Specification (PTS) summary
- Laravel 11 backend file skeletons (migrations/models/controllers/services/jobs/etc.)
- OpenAPI v3 spec for v1 API
- Suggested AGENTS.md for Codex instructions

Recommended workflow:
1) Create a new repo (e.g. `agentlog`).
2) Unzip this pack into the repo root.
3) Ask Codex to:
   - Create a new Laravel 11 app in `backend/`
   - Copy/merge the provided files into that app
   - Run migrations and seeders
   - Add missing glue (config, auth, sanctum, tests)

See AGENTS.md for exact Codex commands/prompts.
