# TutorHUB

A full-stack **Tutor Supply & Demand Marketplace Platform** that connects parents seeking tutors with qualified tutors. Manages the complete lifecycle — from tutor requests and matching, to session check-in/check-out with GPS & QR verification, payments via BayarCash FPX, and monthly tutor payouts.

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 12 (PHP 8.2+) |
| Frontend | React 19 + TypeScript + Tailwind CSS 4 |
| Bridge | Inertia.js 2.0 (server-driven SPA) |
| Database | MySQL |
| Auth | Laravel Breeze + custom role middleware |
| Payment | BayarCash FPX integration |
| Build | Vite 7 |
| PWA | Service Worker + Web App Manifest |

## Features

### Role-Based Access

| Role | Capabilities |
|------|-------------|
| **Admin** | Dashboard analytics, tutor verification, commission settings, subject & package management, request matching, session oversight, payment & payout processing, platform settings |
| **Tutor** | Profile management, accept/reject job offers, session check-in/out (QR + GPS), proof photo uploads, earnings dashboard, view reviews |
| **Parent** | Manage student profiles, create tutor requests with package selection, track bookings & sessions, pay via BayarCash FPX, leave reviews |

### Core Modules

- **Tutor Verification** — Admin reviews and approves/rejects tutor applications
- **Request & Matching** — Parents create requests → Admin matches with suitable tutors → Tutors accept/reject
- **Package System** — Dynamic packages with subject associations and session bundles
- **Session Check-In/Out** — QR code + GPS location capture for fraud prevention, with proof photo requirement before check-out
- **Payment Flow** — Auto-created pending payments → Parent pays via BayarCash FPX → Booking confirmed
- **Commission & Payouts** — Configurable commission per tutor (default 20%), monthly batch payout processing
- **Reviews** — 1–5 star ratings with comments, auto-updates tutor average rating
- **PWA** — Offline support, installable as a mobile app

## Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL

### Installation

```bash
# Clone the repository
git clone https://github.com/wafazz/tutor-kita.git
cd tutor-kita

# Install dependencies
composer install
npm install --legacy-peer-deps

# Environment setup
cp .env.example .env
php artisan key:generate
```

### Configure `.env`

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tutorhub
DB_USERNAME=root
DB_PASSWORD=
```

### Database Setup

```bash
php artisan migrate
php artisan db:seed
```

### Run Development Server

```bash
# Terminal 1 — Laravel
php artisan serve

# Terminal 2 — Vite (frontend assets)
npm run dev
```

Visit `http://localhost:8000`

### Default Accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@tutorhub.my` | `admin123` |
| Tutor | `tutor@tutorhub.my` | `tutor123` |
| Parent | `parent@tutorhub.my` | `parent123` |

## Project Structure

```
app/
├── Http/Controllers/
│   ├── Admin/          # 14 controllers (dashboard, tutors, subjects, requests, etc.)
│   ├── Tutor/          # 7 controllers (profile, jobs, sessions, earnings, etc.)
│   ├── ParentUser/     # 7 controllers (students, requests, payments, etc.)
│   └── Auth/           # Registration & login
├── Models/             # User, TutorProfile, Student, Subject, Package, Booking,
│                       # TutorRequest, TutorSession, Payment, TutorPayout, Review
└── Http/Middleware/
    └── EnsureRole.php  # Role-based access control

resources/js/Pages/
├── Admin/              # 13 module directories
├── Tutor/              # 6 module directories
├── Parent/             # 6 module directories
├── Auth/               # Login, Register, RegisterParent
└── Tutors/             # Public tutor browse page
```

## Database Schema

12 core tables covering the full platform lifecycle:

- `users` — All platform users with role field (admin/tutor/parent)
- `tutor_profiles` — Verification status, subjects, rates, GPS coordinates, commission rate
- `students` — Parent's children profiles
- `subjects` — Available subjects with categories and hourly rates
- `packages` — Tutoring packages with session bundles (many-to-many with subjects)
- `tutor_requests` — Parent requests with grouping, matching, and tutor acceptance
- `bookings` — Confirmed tutor-parent engagements
- `tutor_sessions` — Individual sessions with QR check-in tokens, GPS, proof photos
- `payments` — BayarCash FPX transactions with commission breakdown
- `tutor_payouts` — Monthly payout batch records
- `reviews` — Star ratings and comments
- `settings` — Platform configuration key-value store

## Payment Flow

```
Parent creates request
    → Admin matches tutor
        → Tutor accepts offer
            → Payment record created (pending)
                → Parent pays via BayarCash FPX
                    → Booking auto-created on success
                        → Sessions auto-generated from schedule
```

## License

All rights reserved.
