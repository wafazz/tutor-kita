# Tutor Kita — Business Logic Audit

**Repository:** `wafazz/tutor-kita`  
**Audited branch:** `main`  
**Audit focus:** Business logic, state consistency, marketplace workflow, payment, booking, scheduling, matching, group classes and tutor payout.

## Executive Summary

The repository has a solid foundation for a tuition marketplace. The README describes a lifecycle covering requests, tutor matching, five delivery modes, session verification, BayarCash FPX collection and tutor payouts. The codebase also has a substantial automated test suite.

However, the business rules should be tightened before treating the system as production-ready. The main risk is not the overall architecture; it is **state integrity across multiple entities** and ensuring that every state-changing action re-validates the rules that were true when the action was initiated.

### Overall Assessment

| Area | Assessment |
|---|---:|
| Core architecture | 🟢 8.5/10 |
| Pricing | 🟢 8/10 |
| Group classes | 🟢 8/10 |
| Tutor payout ledger | 🟢 8.5/10 |
| Session scheduling | 🟢 8/10 |
| Travel conflict | 🟢 9/10 |
| Payment lifecycle | 🟠 6/10 |
| Booking lifecycle | 🟠 6/10 |
| Cancellation/refund | 🔴 4/10 |
| Tutor matching | 🟠 6/10 |
| Schedule enforcement | 🟠 6/10 |
| Marketplace readiness | 🟠 6.5/10 |

> **Verdict:** Good foundation, but P0/P1 business-state issues should be resolved before production launch.

---

# 1. Business Model Validation

The repository's documented business model is coherent:

```text
Parent
  ↓
Tutor Request
  ↓
Matching
  ↓
Tutor Acceptance
  ↓
Payment
  ↓
Booking
  ↓
Sessions
  ↓
Completion
  ↓
Tutor Payout
```

The five delivery modes are also modelled as actual business behaviour rather than a display-only label:

```text
home_student
home_tutor
centre_group
online_solo
online_group
```

The repository documents that delivery mode drives pricing, matching and scheduling, which is the correct business abstraction.

The pricing model is also reasonably well structured around subject + delivery mode + group capacity, with commission and tutor payout policies separated from the base lesson pricing.

---

# 2. P0 — Critical Business Logic Issues

## 2.1 Payment Callback Must Be Strongly Verified

### Risk

A payment callback must never be treated as authoritative solely because the incoming status says the transaction succeeded.

For payment systems, the application should verify the gateway response before changing the internal payment state. General Laravel payment integrations follow the same principle: validate the gateway response/signature and expected order before treating the order as paid.

### Required validation

Before setting a payment to `success`, validate:

- Gateway authenticity/signature or equivalent verification mechanism
- Internal order/payment reference
- Expected amount
- Merchant/reference identifiers
- Current payment state
- Gateway transaction status

### Recommended flow

```text
Payment Callback
      ↓
Verify gateway response
      ↓
Validate order/reference
      ↓
Validate expected amount
      ↓
Validate merchant/reference
      ↓
Confirm transaction status
      ↓
Mark Payment SUCCESS
      ↓
Run Payment Completion
```

### Priority

**P0 — Must fix before production**

---

## 2.2 Admin "Mark Paid" Must Use the Same Payment Completion Flow

### Risk

Changing a payment record to `success` is not the same as completing the business transaction.

The normal successful-payment lifecycle should result in:

```text
Payment SUCCESS
      ↓
Booking Created
      ↓
Sessions Created
      ↓
Request Confirmed/Completed
```

If an admin manually marks a payment as paid but does not execute the same completion process, the database can enter an inconsistent state:

```text
Payment = SUCCESS
Booking = NONE
Sessions = NONE
Request = MATCHED / ACCEPTED
```

### Recommended solution

Create one application/domain service:

```php
PaymentCompletionService::complete($payment);
```

Both payment paths should call it:

```text
Gateway Callback ───────┐
                        ├──> PaymentCompletionService
Admin Mark Paid ────────┘
```

Do not duplicate the payment-completion logic in controllers.

### Priority

**P0 — Must fix**

---

## 2.3 Payment Completion Must Be Idempotent

The payment gateway may retry callbacks. Admins may also retry actions.

The same payment must never create duplicate:

- Bookings
- Sessions
- Payout accruals
- Financial records

### Required rule

```text
If payment is already SUCCESS:
    do not execute completion again
```

Use database transactions and row locking where required.

### Priority

**P0**

---

## 2.4 Revalidate Schedule During Tutor Acceptance

Matching is an earlier point in time. Tutor acceptance happens later.

The tutor's schedule may have changed between those events.

Example:

```text
Existing booking:
Monday 10:00–12:00

New job:
Monday 11:00–13:00
```

The tutor acceptance operation must reject the new job.

### Acceptance must validate

- Request is still eligible for acceptance
- Tutor is still verified/eligible
- Tutor availability
- Existing bookings
- Existing sessions
- Time overlap
- Travel time
- Delivery mode
- Location

### Principle

> Never rely on an earlier validation for a later state-changing operation.

### Priority

**P0**

---

## 2.5 Cancellation Must Be a Cross-Entity Business Transaction

Changing only the request status to `cancelled` is not enough after payment/booking has occurred.

Cancellation must define what happens to:

```text
Request
Payment
Booking
Sessions
Refund
Tutor entitlement
Payout eligibility
```

Example:

```text
RM500 paid
     ↓
Booking created
     ↓
5 sessions created
     ↓
Parent cancels
```

The system needs a deterministic result rather than leaving:

```text
Request = CANCELLED
Payment = SUCCESS
Booking = CONFIRMED
Sessions = SCHEDULED
```

unless that exact state is intentionally supported by the business policy.

### Priority

**P0**

---

# 3. P1 — Tutor Matching Business Logic

## 3.1 Separate Eligibility From Ranking

The matching engine should have two distinct concepts:

### Eligibility

A tutor either satisfies a mandatory requirement or does not.

### Ranking

Among eligible tutors, determine who is the best match.

Recommended pipeline:

```text
Tutor Request
      ↓
Eligibility Filter
      ├── Subject
      ├── Qualification
      ├── Verification
      ├── Gender preference
      ├── Budget
      ├── Availability
      ├── Education level
      └── Delivery mode
      ↓
Schedule Validation
      ↓
Travel Validation
      ↓
Ranking
      ↓
Recommended Tutors
```

A tutor who fails a mandatory requirement should be excluded, not merely ranked lower.

### Priority

**P1**

---

## 3.2 Subject Matching Must Be Explicit

A tutor should not be presented as an eligible match unless the tutor can teach the requested subject.

Business rule:

```text
requested_subject ∈ tutor_teachable_subjects
```

This should be enforced server-side and not only through UI filtering.

### Priority

**P1**

---

## 3.3 Budget Must Be Enforced According to Business Policy

If the parent specifies a budget range, matching should determine whether the tutor/rate combination is compatible with that budget.

Example:

```text
Parent budget: RM30–RM40/hour
Tutor rate:    RM80/hour
```

The system must have a documented rule for whether this tutor is:

- excluded, or
- allowed only for manual override.

It should not be ambiguous.

### Priority

**P1**

---

## 3.4 Tutor Gender Preference Must Be Treated as a Matching Rule

If the parent specifies a tutor gender preference and the product/business policy treats it as mandatory, it must be part of eligibility.

If it is only a preference, it should affect ranking instead.

This distinction should be explicit:

```text
MANDATORY → eligibility filter
PREFERENCE → ranking score
```

### Priority

**P1**

---

## 3.5 Tutor Availability Must Be Checked

A tutor should not be considered an eligible candidate when the requested recurring schedule falls outside the tutor's availability.

Example:

```text
Tutor availability:
Saturday 09:00–12:00

Request:
Saturday 14:00–16:00
```

This must fail eligibility.

Availability should also be checked again during final acceptance.

### Priority

**P1**

---

# 4. Package and Subject Integrity

If a request contains both a package and subject, the application should validate that the subject belongs to the selected package.

Example:

```text
Package A
 ├── Mathematics
 └── Science

Request:
Package A
Subject = History
```

This should be rejected.

### Required rule

```text
selected_subject belongs_to selected_package
```

Do not rely on the frontend to maintain this relationship.

### Priority

**P1**

---

# 5. Centre Group Matching

For `centre_group`, geographic matching should use the **specific centre associated with the request/class**.

Recommended relationship:

```text
TutorRequest
     ↓
centre_id
     ↓
Centre
     ↓
latitude / longitude
```

Then calculate tutor distance from that exact centre.

Do not use an arbitrary active centre as the geographic origin.

### Priority

**P1**

---

# 6. Geography and Missing Coordinates

The current design has a sensible principle: postcode data can populate address forms, but postcode alone should not be treated as a precise map coordinate.

Recommended hierarchy:

```text
User-provided coordinates
        ↓
Geocoded coordinates
        ↓
Postcode only for address assistance
```

If exact coordinates are unavailable, the system should make the matching fallback explicit.

For physical lessons, avoid silently treating a missing coordinate as a real distance.

---

# 7. Marketplace Expiry Rules

The marketplace needs explicit expiry rules.

## Tutor acceptance expiry

Example:

```text
MATCHED
   ↓
24 hours
   ↓
EXPIRED
```

## Payment expiry

Example:

```text
ACCEPTED
   ↓
2 hours
   ↓
PAYMENT EXPIRED
```

## Re-matching

A sensible recovery flow is:

```text
Payment expired
      ↓
Release tutor
      ↓
Reopen request
      ↓
Match another tutor
```

Without expiry rules, tutor availability can remain blocked indefinitely.

### Priority

**P1**

---

# 8. Group Class Business Logic

The group-class architecture is conceptually strong.

The model can represent individual student bookings/payments while calculating tutor payout at class level:

```text
Group Class
   ├── Student A → Booking A → Payment A
   ├── Student B → Booking B → Payment B
   └── Student C → Booking C → Payment C
```

The payout attribution mechanism and `paid_out_amount` concept provide a useful accounting guard against paying the same amount twice.

## Missing business policy: headcount changes

The business must explicitly decide what happens when a student joins or leaves after the class has started.

Example:

```text
3 students
Tutor payout = RM300

Student C cancels
```

Possible policies:

- Tutor still receives RM300
- Tutor payout becomes RM200
- Remaining students pay more
- Student receives a partial refund
- Cancellation fee is retained
- Combination of the above

This is a business policy and should be documented before implementation.

### Priority

**P1**

---

# 9. Pricing and Commission

The pricing architecture is one of the stronger parts of the system.

The repository documents:

- Rates by subject and delivery mode
- Group `max_students`
- Fallback pricing
- Platform commission
- Tutor-specific commission inheritance
- Package payout policies

The important business rule is that changing the current commission rate should not silently reprice historical bookings.

Recommended principle:

```text
Booking created
      ↓
Snapshot applicable commercial terms
      ↓
Historical transaction never changes
```

This should apply to:

- Lesson price
- Commission rate
- Tutor payout rate
- Group payout configuration

---

# 10. Session Scheduling

The recurring session scheduler is appropriate for the MVP model.

However, real tuition operations eventually require:

- Public holidays
- School holidays
- Tutor leave
- Student leave
- Replacement classes
- Makeup classes
- Rescheduling
- Missed sessions
- Cancelled sessions

These features do not all need to be implemented immediately, but the data model and state model should allow them later.

---

# 11. Travel Conflict Detection

This is one of the strongest business rules in the repository.

The system considers travel time for physical lessons rather than checking only clock overlap.

Example:

```text
Booking A
11:00–12:00
Petaling Jaya

Booking B
12:00–13:00
Klang
```

There is no direct clock overlap, but the tutor may not physically be able to travel between the locations.

This is a good marketplace-specific rule.

### Recommendation

Keep this logic centralized in a dedicated domain/support service rather than duplicating it across controllers.

---

# 12. Tutor Payout Ledger

The payout architecture is another strong area.

The basic accounting principle is sound:

```text
Accrued Payout
      -
Already Paid
      =
Currently Payable
```

The use of `paid_out_amount` plus payout attribution provides historical traceability.

## Important rule

Payout eligibility should be tied to a completed business event.

For example:

```text
Session completed
      ↓
Payout accrued
      ↓
Payout eligible
      ↓
Payout run
      ↓
Ledger settlement
```

Do not allow a booking's existence alone to imply that the tutor has earned the money.

---

# 13. Recommended State Machine

The system would benefit from an explicit state machine or equivalent transition layer.

## Request

```text
OPEN
  ↓
MATCHED
  ↓
ACCEPTED
  ↓
PAYMENT_PENDING
  ↓
CONFIRMED
  ↓
IN_PROGRESS
  ↓
COMPLETED
```

Possible cancellation/expiry transitions:

```text
OPEN              → CANCELLED
MATCHED           → EXPIRED
MATCHED           → CANCELLED
ACCEPTED          → PAYMENT_EXPIRED
ACCEPTED          → CANCELLED
CONFIRMED         → CANCELLED
```

## Payment

```text
PENDING
   ↓
SUCCESS
   ↓
REFUNDED / PARTIALLY_REFUNDED
```

Failure:

```text
PENDING → FAILED
```

## Booking

```text
PENDING
   ↓
CONFIRMED
   ↓
IN_PROGRESS
   ↓
COMPLETED
```

Cancellation:

```text
PENDING   → CANCELLED
CONFIRMED → CANCELLED
```

## Session

```text
SCHEDULED
   ↓
CHECKED_IN
   ↓
COMPLETED
```

Alternative outcomes:

```text
SCHEDULED → MISSED
SCHEDULED → CANCELLED
```

---

# 14. Business Transition Guards

Every state-changing operation should enforce its own business guards.

## Tutor acceptance

```text
acceptJob()
 ├── Request must be MATCHED
 ├── Tutor must still be eligible
 ├── Tutor must be verified
 ├── Subject must match
 ├── Availability must permit
 ├── Schedule must not conflict
 ├── Travel time must be possible
 ├── Delivery mode must be valid
 └── Location must be valid
```

## Payment completion

```text
completePayment()
 ├── Payment must be PENDING
 ├── Gateway transaction must be verified
 ├── Amount must match
 ├── Order reference must match
 ├── Request must still be valid
 ├── Booking must not already exist
 └── Execute transaction
       ├── Payment SUCCESS
       ├── Booking creation
       ├── Session creation
       └── Request confirmation
```

## Cancellation

```text
cancel()
 ├── Validate current state
 ├── Determine cancellation policy
 ├── Calculate refund
 ├── Calculate tutor entitlement
 ├── Update booking
 ├── Update sessions
 ├── Update payment/refund
 └── Record cancellation reason
```

---

# 15. Recommended Implementation Architecture

Avoid putting the entire business process inside controllers.

Prefer application/domain services for cross-entity operations.

Suggested structure:

```text
app/
├── Services/
│   ├── TutorMatchingService.php
│   ├── TutorAcceptanceService.php
│   ├── PaymentCompletionService.php
│   ├── BookingCreationService.php
│   ├── CancellationService.php
│   ├── RefundService.php
│   ├── SessionSchedulingService.php
│   └── TutorPayoutService.php
│
├── Rules/
│   ├── TutorEligibilityRule.php
│   ├── ScheduleConflictRule.php
│   ├── BudgetRule.php
│   ├── SubjectRule.php
│   ├── AvailabilityRule.php
│   └── DeliveryModeRule.php
│
└── States/
    ├── RequestState.php
    ├── PaymentState.php
    ├── BookingState.php
    └── SessionState.php
```

Controllers should orchestrate the operation, not independently mutate several business entities without a shared transaction/service.

---

# 16. Transaction Boundaries

The following operations should be treated as atomic business transactions wherever possible.

## Payment completion

```text
BEGIN TRANSACTION
    verify payment
    mark payment success
    create booking
    create sessions
    update request
COMMIT
```

## Tutor acceptance

```text
BEGIN TRANSACTION
    lock relevant request/tutor state
    revalidate availability
    revalidate schedule
    revalidate travel
    accept tutor
COMMIT
```

## Cancellation

```text
BEGIN TRANSACTION
    validate cancellation
    calculate refund
    calculate tutor entitlement
    cancel booking/session(s)
    update payment/refund
    update request
COMMIT
```

This prevents partial state changes.

---

# 17. P0/P1/P2 Roadmap

## P0 — Before Production

- [ ] Secure payment callback verification
- [ ] Make payment completion idempotent
- [ ] Fix Admin "Mark Paid" to execute the full payment completion lifecycle
- [ ] Revalidate schedule during tutor acceptance
- [ ] Prevent duplicate booking/session creation
- [ ] Define cancellation/refund behaviour
- [ ] Ensure request/payment/booking/session states remain consistent
- [ ] Wrap multi-entity state transitions in database transactions

## P1 — Before Marketplace Launch

- [ ] Separate tutor eligibility from ranking
- [ ] Enforce subject matching
- [ ] Enforce tutor availability
- [ ] Enforce budget rules
- [ ] Define tutor gender preference as mandatory vs ranking preference
- [ ] Validate package ↔ subject relationship
- [ ] Use the exact centre for centre-group geographic matching
- [ ] Add tutor acceptance expiry
- [ ] Add payment expiry
- [ ] Define re-matching after expiry
- [ ] Define group-class headcount/cancellation policy

## P2 — Post-MVP

- [ ] Holiday handling
- [ ] Makeup classes
- [ ] Rescheduling
- [ ] Tutor/student leave
- [ ] Advanced group-class cancellation rules
- [ ] Partial refunds
- [ ] Advanced payout rules
- [ ] Automated marketplace re-matching

---

# 18. Recommended Acceptance Criteria

The business logic should be considered ready for production only when the following scenarios are covered by automated tests.

## Payment

- [ ] Valid payment callback succeeds
- [ ] Invalid callback is rejected
- [ ] Wrong amount is rejected
- [ ] Wrong order reference is rejected
- [ ] Duplicate callback does not duplicate booking
- [ ] Admin manual payment creates the same downstream records as gateway payment
- [ ] Failed payment never creates a confirmed booking

## Matching

- [ ] Wrong subject is excluded
- [ ] Unverified tutor is excluded
- [ ] Tutor outside required budget is handled according to policy
- [ ] Tutor outside availability is excluded
- [ ] Mandatory gender preference is respected
- [ ] Delivery mode is respected
- [ ] Centre location is correct

## Scheduling

- [ ] Direct time overlap is rejected
- [ ] Travel-time conflict is rejected
- [ ] Acceptance rechecks schedule after matching
- [ ] Student double booking is rejected
- [ ] Tutor double booking is rejected

## Cancellation

- [ ] Unpaid request cancellation works
- [ ] Paid booking cancellation follows refund policy
- [ ] Future sessions are handled correctly
- [ ] Completed sessions are not incorrectly refunded
- [ ] Tutor payout entitlement remains correct
- [ ] Repeated cancellation is idempotent

## Payout

- [ ] Completed session accrues payout correctly
- [ ] Same payout cannot be claimed twice
- [ ] Group payout is divided correctly
- [ ] Headcount changes follow documented policy
- [ ] Historical payout records remain immutable

---

# 19. Final Verdict

## Current State

**Tutor Kita has a good technical/business foundation, but it should not yet be considered fully production-ready from a business-logic perspective.**

The strongest areas are:

- Delivery-mode abstraction
- Pricing model
- Group-class financial model
- Travel-aware schedule conflict detection
- Session scheduling
- Tutor payout ledger
- Separation of reusable business/support logic

The biggest weakness is **cross-entity state integrity**.

The application should ensure that a business event is completed as one coherent operation instead of allowing individual controllers to update related records independently.

## Core Principle

```text
Validate
   ↓
Authorize
   ↓
Lock relevant state
   ↓
Transition state
   ↓
Execute side effects
   ↓
Persist atomically
```

## Final Priority

```text
P0  Payment + Booking + Cancellation + State Integrity
        ↓
P1  Matching + Availability + Schedule Integrity
        ↓
P2  Advanced Marketplace Features
```

Once the P0 and P1 items are resolved and covered by automated tests, Tutor Kita will have a much stronger foundation for a real Malaysian tuition marketplace.

---

## Source

Repository reviewed:

- `https://github.com/wafazz/tutor-kita`

The repository README currently describes Laravel 12, React 19 + TypeScript, Inertia.js 2, MySQL, BayarCash FPX, five delivery modes, pricing/payout policies, geographic matching, session scheduling and an automated test suite. The current GitHub repository shows 53 commits and the `main` branch. 
