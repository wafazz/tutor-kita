# Session Memory - TutorHUB
> Last updated: 2026-03-13 23:55

## Session Context
- **Project**: TutorHUB
- **Profile**: ~/Desktop/MemoryCore Project/Projects/10-tutorhub.md
- **Branch**: main (no git init yet)
- **Status**: active
- **Focus**: Phase 1 scaffold complete

## Current Tasks
- [x] Iris init — scaffold project
- [x] Create Planning.md
- [x] Laravel install + Breeze (Inertia React TS)
- [x] Database migrations (13 tables)
- [x] Eloquent models (11 models)
- [x] EnsureRole middleware + role routing
- [x] Seeder (admin + demo data + subjects + settings)
- [x] Dashboard pages (Admin/Tutor/Parent)
- [x] Browse Tutors page
- [x] Smoke test all routes (200 OK)
- [ ] Phase 2: Matching & Booking

## Working Memory
### Active Context
- Stack: Laravel 12 + Inertia + React 19 + TS + Tailwind 4
- DB: MySQL 3307, database: tutorhub
- Admin login: admin@tutorhub.my / admin123
- Tutor login: tutor@tutorhub.my / tutor123
- Parent login: parent@tutorhub.my / parent123
- NPM needs --legacy-peer-deps (vite 7 + @types/node conflict)

### Decisions Made
- tutor_sessions table name (not sessions — conflicts with Laravel): avoids collision
- Commission model over subscription: lower barrier for tutors
- QR + GPS for check-in: proven pattern from Kopa Arena
- Tailwind 4 (from Breeze default): uses @tailwindcss/vite plugin

### Blockers / Open Questions
- None

## Recent Changes
| File | Change | Status |
|---|---|---|
| .env | Configured MySQL 3307, timezone, app name | done |
| database/migrations/* | 13 migration files | done |
| app/Models/* | 11 models with relationships | done |
| app/Http/Middleware/EnsureRole.php | Role middleware | done |
| bootstrap/app.php | Registered role alias | done |
| routes/web.php | 3 role groups + public routes | done |
| Auth controllers | Role-based redirect + tutor profile creation | done |
| resources/js/pages/* | 4 dashboard/browse pages | done |
| database/seeders/DatabaseSeeder.php | Demo data | done |

## Session Recap
### What Was Done
- Full Iris init: scaffold, migrations, models, middleware, routing, seeder, dashboard pages
- All public routes verified returning 200

### Where We Left Off
- Phase 1 complete. Ready for Phase 2 (Matching & Booking)

### Key Context for Next Session
- npm install needs --legacy-peer-deps flag
- No git repo initialized yet
- Planning.md has full phase breakdown and route plan
