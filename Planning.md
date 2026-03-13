# TutorHUB — Planning Document
> Tutor Supply & Demand Marketplace Platform

## Stack
- Laravel 12 + Inertia.js + React 19 + TypeScript + Tailwind CSS 4
- MySQL (XAMPP port 3307), database: tutorhub
- Payment: Bayarcash FPX
- Auth: Laravel Breeze (Inertia React variant) + custom role middleware

## Roles
| Role | Access |
|------|--------|
| `admin` | Platform management, tutor approval, matching, revenue dashboard |
| `tutor` | Profile, availability, accept/reject jobs, schedule, earnings, check-in/out |
| `parent` | Browse tutors, request booking, pay, rate tutors, track child progress |

## Database Schema

### users
- id, name, email, password, phone, role (admin/tutor/parent), avatar, is_active, email_verified_at, timestamps

### tutor_profiles
- id, user_id (FK users), ic_number, subjects (JSON), education_level, experience_years, bio, hourly_rate, location_area, location_state, latitude, longitude, availability (JSON), verification_status (pending/verified/rejected), verified_at, documents (JSON — cert uploads), rating_avg, total_sessions, timestamps

### students
- id, parent_id (FK users), name, age, school, education_level, notes, timestamps

### subjects
- id, name, category (academic/language/quran/music/etc), education_level, is_active, timestamps

### tutor_requests
- id, parent_id (FK users), student_id (FK students), subject_id (FK subjects), preferred_area, preferred_schedule, budget_min, budget_max, notes, status (open/matched/closed/cancelled), matched_tutor_id (FK users nullable), matched_at, timestamps

### bookings
- id, tutor_request_id (FK nullable), tutor_id (FK users), parent_id (FK users), student_id (FK students), subject_id (FK subjects), schedule_day, schedule_time, duration_hours, hourly_rate, commission_rate, location_type (home/online/center), location_address, status (pending/confirmed/active/completed/cancelled), notes, timestamps

### sessions
- id, booking_id (FK bookings), session_date, start_time, end_time, check_in_token (unique), checked_in_at, checked_out_at, check_in_lat, check_in_lng, check_out_lat, check_out_lng, check_in_method (qr/manual), duration_minutes (calculated), status (scheduled/checked_in/completed/missed/cancelled), tutor_notes, parent_confirmed, timestamps

### payments
- id, booking_id (FK bookings), session_id (FK sessions nullable), parent_id (FK users), amount, commission_amount, tutor_payout, payment_method (fpx/manual), gateway (bayarcash), transaction_id, status (pending/success/failed/refunded), paid_at, timestamps

### tutor_payouts
- id, tutor_id (FK users), amount, sessions_count, period_start, period_end, status (pending/processing/paid), paid_at, reference, timestamps

### reviews
- id, booking_id (FK bookings), parent_id (FK users), tutor_id (FK users), rating (1-5), comment, timestamps

### settings
- id, key (unique), value (text nullable), timestamps

## Features by Phase

### Phase 1 — Foundation (MVP)
- [x] Laravel scaffold + auth (Breeze)
- [ ] Role-based middleware & redirects
- [ ] Admin dashboard (stats overview)
- [ ] Tutor registration + profile management
- [ ] Tutor verification (admin approves/rejects)
- [ ] Subject management (admin CRUD)
- [ ] Parent registration + student profiles

### Phase 2 — Matching & Booking
- [ ] Parent creates tutor request (subject, area, budget)
- [ ] Admin matching dashboard — view requests, assign tutors
- [ ] Tutor receives job offer → accept/reject
- [ ] Booking created on acceptance
- [ ] Booking calendar view (tutor & parent)

### Phase 3 — Check-In/Check-Out
- [ ] QR code generated per session (unique token)
- [ ] Tutor check-in via QR scan (with GPS coords)
- [ ] Tutor check-out via QR scan
- [ ] Auto-calculate session duration
- [ ] Parent confirmation (verify session happened)
- [ ] Manual check-in fallback (admin override)

### Phase 4 — Payment & Earnings
- [ ] Bayarcash FPX integration
- [ ] Parent pays per session or monthly
- [ ] Commission calculation (admin %)
- [ ] Tutor earnings dashboard
- [ ] Payout management (admin)

### Phase 5 — Reviews & Polish
- [ ] Rating & review system
- [ ] Tutor leaderboard / search
- [ ] WhatsApp notifications
- [ ] Parent dashboard (child progress)
- [ ] PWA support

## Route Plan

### Admin Routes (prefix: /admin)
- GET /admin/dashboard
- Resource: /admin/tutors (list, show, verify)
- Resource: /admin/subjects
- Resource: /admin/requests (matching dashboard)
- Resource: /admin/bookings
- Resource: /admin/payments
- Resource: /admin/payouts
- GET /admin/settings

### Tutor Routes (prefix: /tutor)
- GET /tutor/dashboard
- GET|PUT /tutor/profile
- GET /tutor/jobs (incoming requests)
- POST /tutor/jobs/{id}/accept | /reject
- GET /tutor/bookings
- GET /tutor/sessions
- POST /tutor/sessions/{id}/check-in
- POST /tutor/sessions/{id}/check-out
- GET /tutor/earnings

### Parent Routes (prefix: /parent)
- GET /parent/dashboard
- Resource: /parent/students (manage children)
- Resource: /parent/requests (create tutor requests)
- GET /parent/bookings
- GET /parent/sessions/{id} (confirm session)
- POST /parent/sessions/{id}/confirm
- POST /parent/reviews
- GET /parent/payments

### Public
- GET / (landing page)
- GET /tutors (browse tutors)
- Auth routes (login/register)

## Key Decisions
- Commission model: Admin sets % per booking (default 20%)
- Check-in uses QR + GPS for location verification
- Sessions generated from booking schedule (weekly recurring)
- Tutor payout is monthly batch (admin triggers)
