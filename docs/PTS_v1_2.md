# AgentLog.gg – Platform Technical Specification v1.2 (Condensed)

**Stack**
- Backend: Laravel 11 (PHP 8.3)
- DB: PostgreSQL 15+
- Queue: Redis + Laravel Queue + Scheduler
- Email: Postmark (double opt-in)
- Frontend: Nuxt 3 PWA
- Video: Cloudflare Stream / Vimeo Pro

**Core entities**
Series → Book → Season → Mission (+ mission_videos/videos)
Household → Agent → AgentSeasonState → AgentProgress → MissionAttempts
Rewards, ParentContacts (email)
Teachers → Classes → ClassAgents → TeacherAlerts

**Auth**
- MVP: Sanctum bearer tokens.
- `/pair` issues `agent` token.
- `/parent/pin/verify` upgrades token to `agent,parent`.
- Teacher login uses Sanctum bearer tokens with `teacher` ability.

**Routing**
Public QR route:
`/{series_slug}/{book_slug}/s/{season_no}/m/{mission_no}`

**MVP endpoints (v1)**
- GET /api/v1/content/{series}/{book}/s/{seasonNo}
- POST /api/v1/pair
- GET /api/v1/agent/status
- POST /api/v1/missions/{missionId}/attempt
- POST /api/v1/parent/pin/set
- POST /api/v1/parent/pin/verify
- POST /api/v1/household/email/setup
- POST /api/v1/household/email/verify
- POST /api/v1/household/email/unsubscribe
- POST /api/v1/teacher/auth/login
- POST /api/v1/teacher/classes
- GET  /api/v1/teacher/classes/{classId}/analytics
- POST /api/v1/teacher/alerts/{alertId}/ack
