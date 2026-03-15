# Session Memory - TutorHUB
> Last updated: 2026-03-15

## Session Context
- **Project**: TutorHUB
- **Profile**: ~/Desktop/MemoryCore Project/Projects/10-tutorhub.md
- **Branch**: main
- **Status**: feature-complete + enhancements
- **Focus**: UI polish, payment flow, packages, proof photos, parent registration

## Current Tasks
- [x] Phase 1-5: All complete
- [x] Admin Settings + HQ Profile
- [x] ProfileController fix (auth()->user() type hint)
- [x] Tutor sidebar: Settings → My Profile (link to /tutor/profile)
- [x] /profile restricted to admin only
- [x] 403/404/500/503 error page (Inertia)
- [x] Tutor dashboard commission cards (total/available/paid)
- [x] All stat cards → gradient backgrounds (admin/tutor/parent)
- [x] Parent registration page (/register/parent) — shareable URL
- [x] Admin assign any tutor to any request (search, reassign)
- [x] Tutor job show page redesign (hero card, icon toggles)
- [x] Dynamic packages system (admin CRUD, parent selects on request)
- [x] Payment flow: request → admin assign → parent pay → booking created
- [x] Proof photos: tutor must upload ≥1 photo before check-out

## Working Memory
### Active Context
- Stack: Laravel 12 + Inertia + React 19 + TS + Tailwind 4
- DB: MySQL 3307, database: tutorhub
- Admin: admin@tutorhub.my / admin123
- Tutor: tutor@tutorhub.my / tutor123
- Parent: parent@tutorhub.my / parent123
- NPM needs --legacy-peer-deps

### Key Patterns
- Parent controllers namespace: `ParentUser`
- MySQL decimals → `Number()` before `.toFixed()` in TS
- Payment auto-created on admin match (not on check-out anymore for package flow)
- Reviews auto-update rating_avg on tutor_profiles
- Settings table for API keys + HQ company info
- Back buttons: pill style `bg-indigo-50 text-indigo-600`
- Packages table: name, description, total_sessions, duration_hours, price, is_active, sort_order
- Payment flow: admin assigns tutor → pending payment auto-created → parent pays via BayarCash → booking auto-created
- Proof photos stored as JSON array in tutor_sessions, uploaded to storage/proof-photos/{session_id}/
- Error pages: bootstrap/app.php exceptions->respond() renders Inertia Error page
- /profile route restricted to admin via role:admin middleware
- /register/parent — public shareable parent registration

## Session Recap
### What Was Done
- Fixed ProfileController type hint for auth()->user()
- Tutor sidebar: changed Settings to My Profile with /tutor/profile URL
- Restricted /profile to admin only
- Created 403/404/500/503 error pages with Inertia
- Added commission cards to tutor dashboard (total/available/paid)
- Converted all stat cards to gradient backgrounds across all 3 dashboards
- Created parent registration page at /register/parent (shareable URL, split-screen design)
- Admin can now assign any verified tutor to any request with search
- Redesigned tutor job show page with hero card, icon location toggles
- Built dynamic packages system (admin CRUD + parent selection on request)
- Implemented full payment flow: admin assigns → payment auto-created → parent pays → booking auto-created
- Added proof photo upload requirement before tutor check-out

### Where We Left Off
- All features working. Payment flow with BayarCash FPX integrated (fallback to manual if API not configured).
