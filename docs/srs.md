---
title: Software Requirements Specification
description: Numbered functional and non-functional requirements, each traced to the module, route, and tests that satisfy it.
badges: Requirements
order: 12
---

# Software Requirements Specification — Pet Hotel

| Field | Value |
|-------|-------|
| Document version | 1.0 |
| Date | 2026-08-09 |
| Status | Baselined against the delivered MVP |
| Supersedes | `docs/pet-hotel-boarding-mvp-v1.html` (retained as the original scope statement) |
| Related | [Project Initiation Document](pid.html) · [Task List](tasks.html) |

---

## 1. Introduction

### 1.1 Purpose

This document states, in numbered and testable form, what the Pet Hotel system
does. Each requirement carries a stable identifier so that design, tests, and
change requests can cite it precisely.

It describes the system **as delivered**. Where the original MVP statement was
vague ("basic authentication"), this document records the behaviour that was
actually built.

### 1.2 Scope

Pet Hotel is a web-based pet boarding marketplace. Customers search for hotels,
view availability, submit booking requests, and leave reviews after a stay.
Administrators manage listings and confirm bookings. Hotel owners act on
bookings for their own hotel.

Booking confirmation is a manual, human step. The system takes no payment.
Exclusions are listed in §4.2 of the [PID](pid.html).

### 1.3 Definitions

| Term | Meaning |
|------|---------|
| Customer / pet owner | An end user who books boarding for their pet |
| Hotel owner | A user linked to a `pet_hotel` through the `hotel_owner` pivot |
| Administrator | A user with `is_admin = true` |
| Booking request | A booking in `pending` status, not yet confirmed |
| Spot | One unit of capacity for one hotel on one date (`hotel_availabilities.available_spots`) |
| Completed stay | A confirmed booking whose check-out date has passed |
| Panel | A Filament admin interface — `/admin` or `/owner` |

### 1.4 Requirement notation

- **FR-nn** — functional requirement; **NFR-nn** — non-functional requirement.
- Priority: **M** must have, **S** should have, **C** could have.
- "Shall" denotes a mandatory behaviour.
- Traceability columns cite the implementing route or class and the automated
  tests that verify it.

---

## 2. Actors

| Actor | Description | Authentication |
|-------|-------------|----------------|
| Guest | Unauthenticated visitor | None |
| Customer | Registered, email-verified user | Session (Sanctum cookie) or Google OAuth |
| Hotel owner | Customer who also owns at least one hotel | As above, plus `ownedHotels()->exists()` |
| Administrator | Staff operating the platform | As above, plus `is_admin` |
| System | Queue worker sending notifications | n/a |

---

## 3. Functional Requirements

### 3.1 Authentication and Accounts

| ID | Requirement | Pri | Traces to |
|----|-------------|-----|-----------|
| FR-01 | A guest shall register with name, email, and password. Registration is rate limited to 5 attempts per minute. | M | `POST /register` · `tests/Feature/Auth/AuthTest.php` |
| FR-02 | The system shall send a verification email on registration and shall deny access to customer features until the address is verified. | M | `verification.*` routes, `verified` middleware · `tests/Feature/Auth/EmailVerificationTest.php` |
| FR-03 | A registered user shall log in with email and password, and shall log out. | M | `POST /login`, `POST /logout` · `tests/Feature/Auth/AuthTest.php` |
| FR-04 | A user shall reset a forgotten password through an emailed, signed link. | M | `password.request`, `password.reset`, `password.update` · `tests/Feature/Auth/PasswordResetTest.php` |
| FR-05 | A user shall register and sign in with a Google account. An account created this way has no password and shall remain usable without one. | S | `auth.google`, `auth.google.callback` · `tests/Feature/Auth/GoogleAuthTest.php` |
| FR-06 | Authentication endpoints shall be rate limited to 5 requests per minute per client. | M | `throttle:5,1` on auth routes · `tests/Feature/Auth/AuthTest.php` |

### 3.2 Profiles

| ID | Requirement | Pri | Traces to |
|----|-------------|-----|-----------|
| FR-07 | A customer shall view and update their name, phone, and preferred location. | M | `profile.edit`, `profile.update` · `tests/Feature/UserTest.php` |
| FR-08 | A customer shall create, edit, and delete pet profiles holding name, species, breed, age, and special needs. | M | `pets.*` · `tests/Feature/PetTest.php` |
| FR-09 | A customer shall upload a photo for a pet. Uploads shall be written to the configured photo disk. | S | `PetController` · `tests/Feature/UploadDiskTest.php` |
| FR-10 | A customer shall access and modify only their own pets. | M | `PetPolicy` · `tests/Unit/Policies/PetPolicyTest.php` |

### 3.3 Hotel Discovery

| ID | Requirement | Pri | Traces to |
|----|-------------|-----|-----------|
| FR-11 | A guest shall search hotels by city. | M | `GET /hotels` · `tests/Feature/HotelSearchTest.php` |
| FR-12 | A guest shall filter results by pet type, price range, facilities, and stay dates. | M | `HotelSearchController` · `tests/Feature/HotelSearchTest.php` |
| FR-13 | A guest shall sort results by rating, ascending price, descending price, or distance. Distance sorting requires coordinates. | S | `HotelSearchController` · `tests/Feature/HotelSearchTest.php` |
| FR-14 | Search results shall be paginated at 15 per page. | M | `HotelSearchController` · `tests/Feature/HotelSearchTest.php` |
| FR-15 | The system shall show an empty state when no hotel matches the criteria. | S | `SearchPage.vue` · Vitest component suite |
| FR-16 | A guest shall view a hotel profile by slug, showing description, photo gallery, facilities, policies, per-pet-type pricing, and rating summary. | M | `GET /hotels/{slug}` · `tests/Feature/HotelTest.php` |
| FR-17 | Photo references shall be resolved to URLs on the disk they were written to before reaching the page. | M | `PhotoUrl` support class · `tests/Unit/Support/PhotoUrlTest.php`, `tests/Feature/HotelPhotoUrlTest.php` |
| FR-18 | A guest shall view a hotel's availability calendar for a date range. Availability is served as JSON to the calendar widget. | M | `GET /hotels/{slug}/availability` · `tests/Feature/HotelAvailabilityTest.php` |

### 3.4 Booking

| ID | Requirement | Pri | Traces to |
|----|-------------|-----|-----------|
| FR-19 | A verified customer shall submit a booking request selecting a pet, check-in and check-out dates, and optional notes. | M | `bookings.create`, `bookings.store` · `tests/Feature/BookingTest.php` |
| FR-20 | A new booking shall be created with status `pending`; the system shall never confirm it automatically. | M | `BookingController@store` · `tests/Feature/BookingTest.php` |
| FR-21 | The system shall calculate total price from the hotel's per-night rate for the pet's type and the number of nights. Where the hotel has no rate for that pet type the total is currently 0 — see OI-5. | M | `BookingController@store` · `tests/Feature/BookingTest.php` |
| FR-22 | The system shall reject a booking whose dates exceed available spots or fall on blocked dates. **Not implemented** — `BookingController@store` locks the availability rows for update but never checks `available_spots` or `is_blocked`. See OI-4. | M | *gap* |
| FR-23 | Available spots shall be adjusted as a side effect of a booking status change, in the `Booking` model only. | M | `Booking::booted()` · `tests/Feature/BookingTest.php` |
| FR-24 | A customer shall view a confirmation screen stating the request is pending. | M | `bookings.confirmation` · `tests/Feature/BookingTest.php` |
| FR-25 | A customer shall list their bookings with status badges and open any one for detail. | M | `bookings.index`, `bookings.show` · `tests/Feature/BookingTest.php` |
| FR-26 | A customer shall cancel their own booking where the hotel's policy allows. | M | `bookings.cancel` · `tests/Feature/BookingTest.php` |
| FR-27 | A customer shall access only their own bookings. | M | `BookingPolicy` · `tests/Unit/Policies/BookingPolicyTest.php` |

### 3.5 Reviews

| ID | Requirement | Pri | Traces to |
|----|-------------|-----|-----------|
| FR-28 | A customer shall submit one review per booking, with a 1–5 star rating and a comment, only after a confirmed and completed stay. | M | `reviews.store` · `tests/Feature/ReviewTest.php` |
| FR-29 | Review submission shall be rate limited to 5 requests per 10 minutes. | S | `throttle:5,10` · `tests/Feature/ReviewTest.php` |
| FR-30 | A guest shall read a hotel's visible reviews, paginated. Hidden reviews shall never appear publicly. | M | `reviews.index` · `tests/Feature/ReviewTest.php` |
| FR-31 | A hotel's average rating shall be derived from its visible reviews. | M | `PetHotel` model · `tests/Unit/Models/PetHotelTest.php` |

### 3.6 Notifications

| ID | Requirement | Pri | Traces to |
|----|-------------|-----|-----------|
| FR-32 | The system shall email the customer when a booking request is submitted, when it is confirmed, and when it is cancelled. | M | Notification jobs · `tests/Feature/NotificationTest.php` |
| FR-33 | Notification emails shall be dispatched through the queue, not sent inline during the web request. | M | `Booking::booted()` dispatch · `tests/Feature/Jobs/NotificationJobPolicyTest.php` |
| FR-34 | A queued notification job shall fail safely and shall not be retried indefinitely when its subject no longer exists. | M | Job policy · `tests/Feature/Jobs/NotificationJobPolicyTest.php` |
| FR-35 | A customer shall see an unread notification count and read their recent in-app notifications. | M | `notifications.index` · `tests/Feature/NotificationTest.php` |
| FR-36 | A customer shall mark one notification, or all of them, as read. | M | `notifications.read`, `notifications.read-all` · `tests/Feature/NotificationTest.php` |

### 3.7 Administration

| ID | Requirement | Pri | Traces to |
|----|-------------|-----|-----------|
| FR-37 | Only users with `is_admin` shall reach the admin panel at `/admin`. | M | `User::canAccessPanel()` · `tests/Feature/Filament/PanelRoutingTest.php` |
| FR-38 | An administrator shall create, edit, and delete hotels, including photos, pricing tiers, policies, and owner assignment. | M | `HotelResource` · `tests/Feature/Filament/Admin/HotelResourceTest.php` |
| FR-39 | An administrator shall list and filter bookings by status, and confirm or cancel any booking. Both actions fire the corresponding notification. | M | `BookingResource` · `tests/Feature/Filament/Admin/BookingResourceTest.php` |
| FR-40 | An administrator shall view a read-only list of users with their pets. | S | `UserResource` · `tests/Feature/Filament/Admin/UserResourceTest.php` |
| FR-41 | An administrator shall toggle a review's visibility and delete reviews. | M | `ReviewResource` · `tests/Feature/Filament/Admin/ReviewResourceTest.php` |
| FR-42 | The admin dashboard shall show bookings checking in today, pending bookings, and users created in the last 7 days. | C | Widgets · `tests/Feature/Filament/Admin/WidgetsTest.php` |

### 3.8 Hotel Owner

| ID | Requirement | Pri | Traces to |
|----|-------------|-----|-----------|
| FR-43 | Only users owning at least one hotel shall reach the owner panel at `/owner`. | M | `HotelOwnerPanelProvider` · `tests/Feature/Filament/PanelRoutingTest.php` |
| FR-44 | A hotel owner shall see bookings for their own hotels only, filterable by status. | M | `HotelOwner\BookingResource` · `tests/Feature/HotelOwnerBookingTest.php` |
| FR-45 | A hotel owner shall confirm or decline a booking for their own hotel. | M | `HotelOwner\BookingResource` · `tests/Feature/Filament/HotelOwner/BookingResourceTest.php` |

---

## 4. Non-Functional Requirements

### 4.1 Security

| ID | Requirement | Pri | Traces to |
|----|-------------|-----|-----------|
| NFR-01 | All state-changing requests shall be CSRF protected, except the documented CSP report endpoint. | M | `bootstrap/app.php` · `tests/Feature/CspReportTest.php` |
| NFR-02 | The system shall send security headers including a Content Security Policy on every response. The policy shall permit the configured photo storage host and no other external origin. | M | `SecurityHeaders` middleware · `tests/Feature/SecurityHeadersTest.php` |
| NFR-03 | CSP violations shall be reportable to a throttled endpoint that writes to the application log. | C | `csp.report` · `tests/Feature/CspReportTest.php` |
| NFR-04 | Passwords shall be stored hashed; a null password shall never authenticate a user. | M | Laravel hashing · `tests/Feature/Auth/GoogleAuthTest.php` |
| NFR-05 | Authorization shall be enforced by policy classes, not by view-level hiding alone. | M | `tests/Unit/Policies/*` |
| NFR-06 | The application shall be free of known high-severity dependency advisories at release. | M | Dependency review; see `.claude/plans/plan-owasp-hardening.md` |

### 4.2 Usability

| ID | Requirement | Pri |
|----|-------------|-----|
| NFR-07 | Validation errors shall be shown inline against the field they concern, without losing entered data. | M |
| NFR-08 | The interface shall be usable at mobile, tablet, and desktop widths. | M |
| NFR-09 | Booking status shall be visible at a glance wherever a booking is listed. | M |
| NFR-10 | End-user instructions shall be maintained in the [user guide](user_guide.html). | S |

### 4.3 Performance and Capacity

| ID | Requirement | Pri |
|----|-------------|-----|
| NFR-11 | Search result pages shall be paginated so that response size stays bounded regardless of catalogue size. | M |
| NFR-12 | Email delivery shall not block a web request; all mail goes through the queue. | M |
| NFR-13 | Performance targets beyond "functional" are explicitly deferred; scalability work waits for evidence of traction. | M |

### 4.4 Maintainability

| ID | Requirement | Pri | Traces to |
|----|-------------|-----|-----------|
| NFR-14 | Backend line coverage shall not fall below the CI floor (currently 95%). | M | `.github/workflows/ci.yml` |
| NFR-15 | PHP shall be formatted with Pint and JavaScript linted with ESLint; both are enforced in CI. | M | `.github/workflows/ci.yml` |
| NFR-16 | Pages shall be delivered through Inertia. JSON responses are permitted only for the hotel availability and notification widgets. | M | `.claude/CLAUDE.md` |
| NFR-17 | Availability side effects shall exist in exactly one place, `Booking::booted()`. | M | `tests/Feature/BookingTest.php` |

### 4.5 Portability and Operations

| ID | Requirement | Pri |
|----|-------------|-----|
| NFR-18 | The full stack shall start from a clean checkout with `docker compose up -d`. | M |
| NFR-19 | Photo storage shall be selectable by configuration; on ephemeral hosting it shall be S3-compatible. | M |
| NFR-20 | The database shall be PostgreSQL in Docker and production, and SQLite for tests. | M |
| NFR-21 | Go-live steps that cannot be enforced from the repository shall be documented in the [deployment checklist](deployment-checklist.html). | M |

---

## 5. External Interfaces

| Interface | Direction | Purpose |
|-----------|-----------|---------|
| Google OAuth 2.0 | Outbound | Sign-in and registration (`laravel/socialite`) |
| SMTP / transactional mail | Outbound | Booking notification emails; Mailpit locally |
| S3-compatible object storage | Outbound | Photo uploads when `PHOTO_DISK=s3` |
| Leaflet tiles | Outbound (browser) | Map rendering on hotel pages |
| Redis | Internal | Queue, cache, sessions |

The system exposes no public API. `routes/api.php` does not exist.

---

## 6. Data Requirements

Entities and relationships are defined in `.claude/CLAUDE.md`. Requirements that
constrain the data layer:

| ID | Requirement |
|----|-------------|
| DR-01 | Deletion shall cascade through foreign keys; soft deletes shall not be used. |
| DR-02 | `users.google_id` shall be nullable and unique; `users.password` shall be nullable. |
| DR-03 | Availability shall be stored as one row per hotel per date, holding `available_spots` and `is_blocked`. |
| DR-04 | Pricing shall be stored per hotel per pet type. |
| DR-05 | A review shall belong to exactly one booking. |

---

## 7. Traceability to the Original MVP Statement

| MVP requirement | Covered by |
|-----------------|------------|
| 1. Search pet hotels by location | FR-11, FR-14 |
| 2. View available dates and times | FR-18 |
| 3. View pet hotel profiles | FR-16, FR-17 |
| 4. Book a pet hotel (manual process) | FR-19 – FR-24, FR-39, FR-45 |
| 5. User and pet profile management | FR-07 – FR-10 |
| 6. View ratings and reviews | FR-30, FR-31 |
| 7. Filter and sort search results | FR-12, FR-13, FR-15 |
| 8. Leave feedback | FR-28, FR-29 |
| 9. Basic user authentication | FR-01 – FR-06 |
| 10. Booking confirmation | FR-24, FR-32, FR-35, FR-36 |
| A. Security and basic usability | NFR-01 – NFR-10 |
| B. Keep performance simple | NFR-11 – NFR-13 |
| C. Defer scalability | NFR-13 |

Requirements with no MVP ancestor — delivered as post-MVP scope: FR-05 (Google
sign-in), FR-33/FR-34 (queue hardening), FR-37/FR-43 – FR-45 (panels and owner
role), FR-17 (photo URL resolution), NFR-02/NFR-03 (security headers and CSP),
NFR-14/NFR-15 (CI quality gates).

---

## 8. Open Items

| ID | Item | Owner |
|----|------|-------|
| OI-1 | Cancellation policy is enforced per hotel but the exact rule is not specified here; needs a written rule per policy field. | *TBC* |
| OI-2 | No requirement covers what happens to reviews when a hotel is deleted beyond the cascade in DR-01. | *TBC* |
| OI-3 | Distance sorting (FR-13) depends on coordinates that admins enter manually; no validation requirement exists for them. | *TBC* |
| OI-4 | **FR-22 is unimplemented.** A customer can book dates that are fully booked or blocked; spots go negative through `Booking::booted()`. The transaction in `BookingController@store` already takes `lockForUpdate()` on the availability rows, so the capacity check was intended — only the assertion is missing. Needs a fix and a regression test. | *TBC* |
| OI-5 | A booking for a pet type the hotel has no pricing row for is created with `total_price = 0` rather than being rejected (FR-21). | *TBC* |
