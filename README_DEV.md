# Developer quickstart

## Backend (Laravel)
1. Install PHP deps if needed, then run database + redis (docker compose if available).
2. Serve the API:
   ```bash
   cd backend
   php artisan serve --port=8000
   ```

## Frontend (Nuxt 3 PWA)
1. Install dependencies:
   ```bash
   cd frontend
   pnpm install
   ```
2. Start the dev server:
   ```bash
   pnpm dev --port 3000
   ```

Dev start URL example:
```
http://localhost:3000/start?series=agent-academy&book=mission-math&season=1&token=BOOK-TOKEN-1
```

If you encounter CORS errors, allow `http://localhost:3000` in the Laravel CORS config or set up a proxy during development.
