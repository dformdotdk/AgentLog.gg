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
