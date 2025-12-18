# AgentLog Frontend (Nuxt 3 PWA)

This Nuxt 3 PWA provides the AgentLog mission experience against the Laravel API.

## Quick start

```bash
pnpm install
pnpm dev --port 3000
```

Open the seeded dev flow:
```
http://localhost:3000/start?series=agent-academy&book=mission-math&season=1&token=BOOK-TOKEN-1
```

## Notes
- API base URL comes from `NUXT_PUBLIC_API_BASE_URL` (see `.env.example`).
- The PWA is configured with @vite-pwa/nuxt and a dark sci-fi theme using TailwindCSS.
- Pinia stores persist the session token + agent pairing in `localStorage`.
- Developer tools on `/start` are hidden by default; set `NUXT_PUBLIC_SHOW_DEV_TOOLS=true` locally to reveal them. They should remain off in production unless explicitly enabled.

## Start server
- Gem en start-kommando i repo-roden (så du altid kan starte begge):
- Backend (terminal 1): cd backend && php artisan serve --port=8000
- Frontend (terminal 2): cd frontend && npm run dev -- --port 3000
- Test hele loopet i UI:
- Gå til /start?...token=BOOK-TOKEN-1
- Start → gå til mission 1 → svar 1/2 → se XP stige og mission 2 blive aktiv.