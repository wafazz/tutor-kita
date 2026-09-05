# TutorHUB

A tuition marketplace connecting parents with tutors across Malaysia. It covers the
whole lifecycle — requests and matching, five ways a lesson can be delivered, session
check-in with GPS and QR verification, collection through BayarCash FPX, and tutor
payouts.

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 12 (PHP 8.2+) |
| Frontend | React 19 + TypeScript + Tailwind CSS 4 |
| Bridge | Inertia.js 2.0 (server-driven SPA) |
| Database | MySQL |
| Auth | Laravel Breeze + role middleware |
| Collection | BayarCash FPX |
| Build | Vite 7 |
| Tests | Pest / PHPUnit — 208 tests, 790 assertions |

## Delivery modes

A lesson can happen in one of five ways, and the mode drives pricing, matching and
scheduling rather than being a label:

| Mode | Group | Who travels | Distance matters |
|------|-------|-------------|------------------|
| `home_student` | – | tutor, to the student's home | yes |
| `home_tutor` | – | student, to the tutor's home | yes |
| `centre_group` | **yes** | student, to a centre | yes |
| `online_solo` | – | – | no |
| `online_group` | **yes** | – | no |

`DeliveryMode` is a PHP enum over a plain string column, so adding a sixth mode is a
constant and a rate row rather than a migration. Each mode answers `isGroup()`,
`needsGeo()` and `traveller()` — the last decides which address a radius is measured
from, so matching asks the mode instead of branching on it everywhere.

## Money

### Pricing

Rates live in `subject_rates`, keyed by subject and delivery mode, with `max_students`
for group modes. Resolution walks explicit rates up a fallback chain and only then the
legacy two-column rates, so a partly configured subject prices rather than dropping to
zero. `Subject::hasOwnRateFor()` exposes an inherited rate, so a group class quietly
charging the one-to-one price is visible rather than hidden.

Approval refuses to raise a zero-amount payment, naming the subject at fault.

### Commission

Per tutor, defaulting to the platform rate in Admin → Settings
(`Setting::defaultCommissionRate()`). New tutors inherit it; changing it never reprices
existing ones.

### When a tutor is paid

Set per package, so different products can settle differently:

| Policy | Payable |
|--------|---------|
| `upfront` | as soon as the parent's payment succeeds |
| `per_session` | accrues 1/`total_sessions` per completed session |
| `on_completion` | only once every session is delivered |

Group classes additionally choose **how much**, per class:

| Model | Tutor earns |
|-------|-------------|
| `per_student` | their commission share of what the students paid |
| `flat` | a fixed amount, whatever the headcount |
| `flat_plus_head` | a floor, plus a per-head amount past a threshold |

A class paying out more than it collects is flagged before it runs, not at payout time.

### The ledger

One booking is one student is one payment — including group seats, so group revenue
flows through exactly the same path as one-to-one work. A class decides only the
tutor's total; that total is divided across the enrolled bookings.

`bookings.paid_out_amount` is the guard against paying the same money twice, and the
`booking_tutor_payout` pivot records which run paid which slice. Payout runs claim only
unpaid accrual under a transaction, so a booking is payable exactly once however the
periods are drawn.

Payouts are recorded, not sent: `markPaid` stores a reference and the transfer is made
in your bank. Tutors supply bank details in their profile; the account number is
encrypted at rest.

## Geography

Students, tutors and centres each carry an address and optional coordinates. Tutors also
set a `travel_radius_km`.

Coordinates can be set three ways:

1. **By the person themselves** — "use my current location" in the browser, or typing
   the coordinates. This is the default path and needs no external service.
2. **By geocoding** — a driver chosen in Admin → Settings (`manual`, `postcode` or
   `google`). Defaults to `manual`, so nothing depends on a paid service or billing
   account until you choose it.
3. **By backfill** — `php artisan geocode:backfill` for existing records.

A coordinate someone set is never replaced by geocoding: their own pin is more precise
than a postcode centroid. A record without coordinates is *excluded* from distance
results rather than matched wrongly, and the screens say so.

The `postcodes` table holds the Malaysian directory (2,931 entries, all 16 states). It
maps a postcode to a city and state and **carries no coordinates**, so it fills address
forms automatically but cannot place anyone on a map on its own.

### Matching and clashes

`TutorMatcher` narrows candidates by distance where the mode needs it, measured from
whichever address the traveller is heading to. An unplaced student falls back to every
tutor rather than none, so matching degrades to a manual pick instead of appearing
broken.

`ScheduleConflictDetector` refuses to double-book **either** party. It catches literal
overlaps and, for in-person work, journeys that cannot be made: a lesson in Petaling
Jaya ending at 12:00 and one in Klang starting at 12:00 do not overlap on a clock but
cannot both be taught. Travel time is estimated from the coordinates already stored.

## Getting Started

### Prerequisites

PHP 8.2+, Composer, MySQL, and Node.js 20.19+ or 22.12+ — the range Vite 7
requires. Older 20.x releases will fail to install.

### Installation

```bash
git clone https://github.com/wafazz/tutor-kita.git
cd tutor-kita

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Configure the database in `.env`, then:

```bash
php artisan migrate
php artisan db:seed
```

Seeding loads subjects, packages, demo accounts and the postcode directory.

### Running it

```bash
composer dev
```

That starts the server, queue worker, log viewer and Vite together on
`http://localhost:8000`. Ctrl+C stops all four. For a lighter setup, `php artisan serve`
and `npm run dev` in two terminals is enough for UI work.

### Default Accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@tutorhub.my` | `admin123` |
| Tutor | `tutor@tutorhub.my` | `tutor123` |
| Parent | `parent@tutorhub.my` | `parent123` |

### Checks

```bash
php artisan test        # 208 tests
vendor/bin/pint         # formatting
npx tsc --noEmit        # frontend types
```

## Roles

| Role | Capabilities |
|------|-------------|
| **Admin** | Dashboard, tutor verification, commission and platform settings, subjects and rates, packages and payout policies, centres, group classes, request matching, payments and payouts |
| **Tutor** | Profile with address, travel radius and bank details, accept or reject jobs, own group classes, session check-in/out with QR and GPS, proof photos, earnings, reviews |
| **Parent** | Students with addresses, tutor requests with package selection, browse and enrol in group classes by distance, bookings and sessions, pay by FPX, reviews, reports |

## Project Structure

```
app/
├── Enums/
│   ├── DeliveryMode.php        five ways a lesson happens
│   └── GroupPayoutModel.php    how a group tutor is paid
├── Support/
│   ├── TutorMatcher.php        candidates, narrowed by distance
│   ├── ScheduleConflictDetector.php   overlaps and unmakeable journeys
│   ├── SessionScheduler.php    lays a booking's weeks out
│   ├── ClassEnroller.php       seats, and the money that follows
│   └── Geocoding/              contract, manual/postcode/google drivers
├── Http/Controllers/           Admin (16), Tutor (9), ParentUser (9), Auth
├── Models/                     + Concerns/HasCoordinates
└── Http/Middleware/EnsureRole.php

database/
├── data/postcodes.csv          the Malaysian directory
└── seeders/
```

## Database

28 tables. The ones specific to how this platform works:

| Table | Holds |
|-------|-------|
| `subject_rates` | a rate per subject per delivery mode, with group capacity |
| `centres` | places students travel to; `owner_user_id` null means platform-run |
| `class_sessions` | a group class: schedule, seats, price, payout model |
| `class_enrolments` | a seat, linked to its own booking and payment |
| `booking_tutor_payout` | which payout run paid which slice of a booking |
| `postcodes` | the Malaysian postcode directory |
| `student_reports` | marks and progress |

Alongside the core: `users`, `tutor_profiles`, `students`, `subjects`, `packages`,
`tutor_requests`, `bookings`, `tutor_sessions`, `payments`, `tutor_payouts`, `reviews`,
`settings`.

## How money moves

```
Parent creates a request
  → Admin matches a tutor          (refused if either party is already busy)
    → Payment raised, pending      (priced by subject × delivery mode × package)
      → Parent pays by FPX         (or manual, when no gateway keys are set)
        → Booking created, sessions laid out across the weeks
          → Tutor delivers and checks in
            → Payout accrues per the package's policy
              → Admin runs a payout, which claims only unpaid accrual
                → Transfer made in the bank, marked paid with a reference
```

Group classes join at the booking step: enrolling creates that student's own booking and
payment, and everything downstream is identical.

## Notes

- **Geocoding is off by default.** Distance features work from coordinates people set
  themselves. Switch the driver in Admin → Settings to change that.
- **The postcode directory has no coordinates.** It fills city and state on address
  forms; it does not place anyone on a map.
- **Payouts are a ledger, not a payment rail.** The transfer is made by hand.
- **Group classes need a price of their own.** Without one they inherit the
  one-to-one rate, which is shown as inherited rather than hidden.

## License

All rights reserved.
