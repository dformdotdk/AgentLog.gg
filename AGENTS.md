# Codex Instructions (AgentLog.gg)

## Goal
Scaffold AgentLog.gg backend (Laravel 11) using the provided skeleton files in `spec/` and `openapi/`.

## Repo layout (target)
- backend/ (Laravel 11 app)
- openapi/agentlog.v1.yaml
- spec/ (reference skeleton files; can be deleted later)
- docs/

## Environment
- PHP 8.3
- Composer
- PostgreSQL
- Redis

## Commands
### Backend bootstrap
1) Create Laravel app:
   - `composer create-project laravel/laravel backend`
2) Install Sanctum:
   - `cd backend && composer require laravel/sanctum`
   - `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`
3) Copy/merge files from `spec/backend/` into `backend/` preserving paths.
4) Ensure Teacher model is auth-capable:
   - Change `teachers.password_hash` to `teachers.password` (or add migration and update model accordingly).
5) Run:
   - `php artisan migrate:fresh --seed`

### Verification
- `php artisan test` (create minimal tests if missing)
- Hit endpoints with curl using Sanctum Bearer token

## Coding rules
- Prefer small, testable services.
- Use FormRequest validation on all write endpoints.
- Return error responses as `{error_code,message,...}`.

## Notes
- `book_tokens` table is included for Mode A pairing.
- Teacher dashboard is adult auth; show pseudonyms only.
