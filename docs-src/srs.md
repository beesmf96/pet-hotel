---
title: Software Requirements Specification
description: Numbered functional and non-functional requirements, each traced to the module, route, and tests that satisfy it.
badges: Requirements
audience: stakeholder
order: 12
---

# Software Requirements Specification — Pet Hotel

| Field | Value |
|-------|-------|
| Document version | 1.2 |
| Date | 2026-10-10 |
| Status | Baselined against the delivered MVP |
| Supersedes | `docs/pet-hotel-boarding-mvp-v1.html` (retained as the original scope statement) |
| Related | [Project Initiation Document](pid.html) |

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
| Answer deadline | When the hotel must answer a booking request by (FR-26a): 48 hours after it was sent, or 12:00 noon (Malaysia time) on the day before check-in, whichever comes first. 48 hours is the most, not a fixed time |
| Spot | Room for one pet on one night. Spots left on a night = that night's capacity (`pet_hotels.capacity`, or a `hotel_availabilities.capacity` override) − confirmed and completed bookings covering it |
| Night | A date a pet sleeps over: a stay covers check-in up to, not including, check-out |
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
| FR-05 | A user shall register and sign in with a Google account. An account created this way is verified on creation, has no password, and shall remain usable without one. | S | `auth.google`, `auth.google.callback` · `tests/Feature/Auth/GoogleAuthTest.php` |
| FR-06 | Authentication endpoints shall be rate limited to 5 requests per minute per client. | M | `throttle:5,1` on auth routes · `tests/Feature/Auth/AuthTest.php` |

### 3.2 Profiles

| ID | Requirement | Pri | Traces to |
|----|-------------|-----|-----------|
| FR-07 | A customer shall view and update their name, phone, and preferred location. | M | `profile.edit`, `profile.update` · `tests/Feature/UserTest.php` |
| FR-07a | A customer shall change their password after confirming the current one. An account with no password (Google only) shall set one without a current-password check. The endpoint is rate limited to 5 requests per minute. | S | `profile.password.update` · `tests/Feature/Auth/ChangePasswordTest.php` |
| FR-08 | A customer shall create, edit, and delete pet profiles holding name, species, breed, age, and special needs. | M | `pets.*` · `tests/Feature/PetTest.php` |
| FR-09 | A customer shall upload a photo for a pet. Uploads shall be written to the configured photo disk. | S | `PetController` · `tests/Feature/UploadDiskTest.php` |
| FR-10 | A customer shall access and modify only their own pets. | M | `PetPolicy` · `tests/Unit/Policies/PetPolicyTest.php` |

### 3.3 Hotel Discovery

| ID | Requirement | Pri | Traces to |
|----|-------------|-----|-----------|
| FR-11 | A guest shall search hotels by city. | M | `GET /hotels` · `tests/Feature/HotelSearchTest.php` |
| FR-12 | A guest shall filter results by pet type, price range, and facilities. With both stay dates set and check-out after check-in, results shall hold only hotels with a spot on every night (FR-22); otherwise the dates filter nothing. Upcoming dates shall carry to the hotel page and pre-fill the booking form. | M | `HotelSearchController` · `tests/Feature/HotelSearchTest.php` |
| FR-13 | A guest shall sort results by newest, ascending price, or descending price. The backend also accepts a distance sort given coordinates, but no control exposes it — see OI-3. | S | `HotelSearchController` · `tests/Feature/HotelSearchTest.php` |
| FR-14 | Search results shall be paginated at 15 per page. | M | `HotelSearchController` · `tests/Feature/HotelSearchTest.php` |
| FR-15 | The system shall show an empty state when no hotel matches the criteria. | S | `SearchPage.vue` · Vitest component suite |
| FR-16 | A guest shall view a hotel profile by slug, showing description, photo gallery, facilities, policies, per-pet-type pricing, rating summary, and a map when the hotel has coordinates. | M | `GET /hotels/{slug}` · `tests/Feature/HotelTest.php` |
| FR-17 | Photo references shall be resolved to URLs on the disk they were written to before reaching the page. | M | `PhotoUrl` support class · `tests/Unit/Support/PhotoUrlTest.php`, `tests/Feature/HotelPhotoUrlTest.php` |
| FR-18 | A guest shall view a hotel's availability calendar for a date range, with each day marked available, limited (half or fewer of its spots left), full or closed, and the spots left on hover. Availability is served as JSON to the calendar widget. | M | `GET /hotels/{slug}/availability` · `tests/Feature/HotelAvailabilityTest.php` |
| FR-18a | The landing page shall show up to four active hotels ranked by average rating. | C | `GET /` · `tests/Feature/LandingPageTest.php` |

### 3.4 Booking

| ID | Requirement | Pri | Traces to |
|----|-------------|-----|-----------|
| FR-19 | A verified customer shall submit a booking request selecting a pet, check-in and check-out dates, and optional notes. | M | `bookings.create`, `bookings.store` · `tests/Feature/BookingTest.php` |
| FR-20 | A new booking shall be created with status `pending`; the system shall never confirm it automatically. | M | `BookingController@store` · `tests/Feature/BookingTest.php` |
| FR-21 | The system shall calculate total price from the hotel's per-night rate for the pet's type and the number of nights, summed in whole sen. A request for a pet type the hotel has no rate for shall be rejected. | M | `BookingController@store` · `tests/Feature/BookingTest.php` |
| FR-22 | The system shall reject a booking request, and refuse to confirm a booking, when any night of the stay is closed or has no spot left. The check-out day is not a night. | M | `Availability::fits()`, `BookingController@store`, `Booking::confirm()` · `tests/Feature/BookingTest.php`, `tests/Unit/Support/AvailabilityTest.php` |
| FR-22a | A pet shall stay in one place a night. The system shall reject a request for a pet that already has a pending, confirmed or completed booking sharing a night with it, at any hotel, and refuse to confirm a booking whose pet is confirmed elsewhere on a shared night. Back-to-back stays share no night. | M | `Booking::clashFor()`, `BookingController@store`, `Booking::confirm()` · `tests/Feature/BookingTest.php` |
| FR-23 | Spots left shall be computed from capacity and confirmed and completed bookings, never stored. Confirming takes a spot on each night; cancelling frees it. Pending requests hold no spot. | M | `App\Support\Availability` · `tests/Unit/Support/AvailabilityTest.php` |
| FR-23a | Check-in and check-out times, when the hotel has set them, shall appear on the booking form, confirmation page, booking detail page, and the request and confirmed emails. | S | `PetHotelPolicy::stayTimes()` · `tests/Feature/BookingTest.php`, `tests/Feature/NotificationTest.php` |
| FR-24 | A customer shall be told, before sending a request and on the confirmation screen and email after, that it is a request: the hotel confirms or declines it, and the pet has no place until it is confirmed. The hotel page's button shall read "Request to Book". | M | `bookings.confirmation` · `tests/Feature/BookingTest.php` |
| FR-25 | A customer shall list their bookings with status badges and open any one for detail. | M | `bookings.index`, `bookings.show` · `tests/Feature/BookingTest.php` |
| FR-26 | A customer shall cancel their own booking while it is still `pending`. Confirmed bookings are cancelled by the hotel or an administrator. | M | `bookings.cancel` · `tests/Feature/BookingTest.php` |
| FR-26a | A hotel shall answer a booking request within 48 hours of it being sent, or by noon (Malaysia time) the day before check-in if that is sooner; a request sent after that noon has until the end of its check-in day. A request still `pending` at its deadline shall be cancelled automatically and the customer emailed that it expired. Owners are not notified. The deadline shall be shown to the customer on the confirmation and detail pages and in the request email, and to owners in the bookings list, the booking pop-up and the new-request email. | M | `Booking::answerBy()`, `bookings:expire-pending` (every five minutes) · `tests/Unit/Models/BookingAnswerByTest.php`, `tests/Feature/ExpirePendingBookingsTest.php` |
| FR-27 | A customer shall access only their own bookings. | M | `BookingPolicy` · `tests/Unit/Policies/BookingPolicyTest.php` |

FR-26a examples, for a stay with check-in on Sun 1 Nov (Malaysia time):

| Request sent | Answer deadline | Why |
|---|---|---|
| Sun 18 Oct, 10:00 AM | Tue 20 Oct, 10:00 AM | 48 hours comes first |
| Fri 30 Oct, 10:00 AM | Sat 31 Oct, 12:00 PM | Noon the day before check-in comes first |
| Sat 31 Oct, 3:00 PM | Sun 1 Nov, 11:59 PM (end of the day) | Noon the day before has already passed |

### 3.5 Reviews

| ID | Requirement | Pri | Traces to |
|----|-------------|-----|-----------|
| FR-28 | A customer shall submit one review per booking, with a 1–5 star rating and an optional comment, only once the booking status is `completed`. | M | `reviews.store` · `tests/Feature/ReviewTest.php` |
| FR-29 | Review submission shall be rate limited to 5 requests per 10 minutes. | S | `throttle:5,10` · `tests/Feature/ReviewTest.php` |
| FR-30 | A guest shall read a hotel's visible reviews, paginated. Hidden reviews shall never appear publicly. | M | `reviews.index` · `tests/Feature/ReviewTest.php` |
| FR-31 | A hotel's average rating shall be derived from its visible reviews. | M | `PetHotel` model · `tests/Unit/Models/PetHotelTest.php` |

### 3.6 Notifications

| ID | Requirement | Pri | Traces to |
|----|-------------|-----|-----------|
| FR-32 | The system shall email the customer when a booking request is submitted, when it is confirmed, and when it is cancelled. The cancellation email shall say whether the customer cancelled, the hotel declined the request, the hotel cancelled a confirmed stay, or the request expired. | M | Notification jobs · `tests/Feature/NotificationTest.php` |
| FR-32a | The system shall email every owner of the hotel, and add a notification to the owner panel, when a guest submits a booking request and when a guest cancels their pending request. | M | `NotifyOwnersOfBookingRequest`, `NotifyOwnersOfGuestCancellation` · `tests/Feature/OwnerNotificationTest.php` |
| FR-33 | Notification emails shall be dispatched through the queue, not sent inline during the web request. Each is queued once, by its job. | M | `Booking::confirm()`, `Booking::cancel()`, `BookingController@store` · `tests/Feature/Jobs/NotificationJobPolicyTest.php` |
| FR-34 | A queued notification job shall fail safely and shall not be retried indefinitely when its subject no longer exists. | M | Job policy · `tests/Feature/Jobs/NotificationJobPolicyTest.php` |
| FR-35 | A customer shall see an unread notification count and read their recent in-app notifications. Owner-panel notifications are not included. | M | `notifications.index` · `tests/Feature/NotificationTest.php` |
| FR-36 | A customer shall mark one notification, or all of them, as read. | M | `notifications.read`, `notifications.read-all` · `tests/Feature/NotificationTest.php` |

### 3.7 Administration

| ID | Requirement | Pri | Traces to |
|----|-------------|-----|-----------|
| FR-37 | Only users with `is_admin` shall reach the admin panel at `/admin`. | M | `User::canAccessPanel()` · `tests/Feature/Filament/PanelRoutingTest.php` |
| FR-38 | An administrator shall create, edit, and delete hotels, including photos, pricing tiers, policies, and owner assignment. | M | `HotelResource` · `tests/Feature/Filament/Admin/HotelResourceTest.php` |
| FR-39 | An administrator shall list and filter bookings by status, view any booking, and confirm or cancel any booking. Both actions fire the corresponding notification. Prices are shown in MYR. | M | `BookingResource` · `tests/Feature/Filament/Admin/BookingResourceTest.php` |
| FR-40 | An administrator shall view a read-only list of users, and open any user to see their pets. | S | `UserResource` · `tests/Feature/Filament/Admin/UserResourceTest.php` |
| FR-41 | An administrator shall toggle a review's visibility and delete reviews. | M | `ReviewResource` · `tests/Feature/Filament/Admin/ReviewResourceTest.php` |
| FR-42 | The admin dashboard shall show bookings checking in today, pending bookings, and users created in the last 7 days. | C | Widgets · `tests/Feature/Filament/Admin/WidgetsTest.php` |

### 3.8 Hotel Owner

| ID | Requirement | Pri | Traces to |
|----|-------------|-----|-----------|
| FR-43 | Only users owning at least one hotel shall reach the owner panel at `/owner`. | M | `HotelOwnerPanelProvider` · `tests/Feature/Filament/PanelRoutingTest.php` |
| FR-44 | A hotel owner shall see bookings for their own hotels only, filterable by status. | M | `HotelOwner\BookingResource` · `tests/Feature/HotelOwnerBookingTest.php` |
| FR-45 | A hotel owner shall confirm or decline a booking for their own hotel, and see the number of pending requests on the Bookings menu item. | M | `HotelOwner\BookingResource` · `tests/Feature/Filament/HotelOwner/BookingResourceTest.php` |
| FR-45a | A hotel owner shall open a booking's details — stay with the hotel's times, nights, total, guest's notes, pet details and guest contact — from its row or straight from the owner notification (bell or email), and confirm or decline a pending booking there. | S | `HotelOwner\BookingResource::infolist()`, `OwnerBookingNotification::bookingUrl()` · `tests/Feature/Filament/HotelOwner/BookingResourceTest.php` |
| FR-46 | A hotel owner shall set their hotel's normal capacity and check-in and check-out times. | M | `HotelOwner\Pages\HotelSettings` · `tests/Feature/Filament/HotelOwner/HotelSettingsTest.php` |
| FR-47 | A hotel owner shall close a range of dates, set a different capacity for a range, and reset a range to normal. | M | `HotelOwner\AvailabilityResource` · `tests/Feature/Filament/HotelOwner/AvailabilityResourceTest.php` |
| FR-48 | A hotel owner shall see, for each night of a chosen month, the capacity, confirmed bookings, spots left and status. | S | `HotelOwner\Pages\DailyOverview` · `tests/Feature/Filament/HotelOwner/DailyOverviewTest.php` |

---

## 4. Non-Functional Requirements

### 4.1 Security

| ID | Requirement | Pri | Traces to |
|----|-------------|-----|-----------|
| NFR-01 | All state-changing requests shall be CSRF protected, except the documented CSP report endpoint. | M | `bootstrap/app.php` · `tests/Feature/CspReportTest.php` |
| NFR-02 | The system shall send security headers including a Content Security Policy on every response. The policy shall permit only the configured photo storage host and the OpenStreetMap tile hosts as external origins. | M | `SecurityHeaders` middleware · `tests/Feature/SecurityHeadersTest.php` |
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
| NFR-14 | Backend line coverage shall not fall below the CI floor (currently 98%). Frontend line coverage shall not fall below its floor (currently 90%). | M | `.github/workflows/ci.yml` |
| NFR-15 | PHP shall be formatted with Pint and JavaScript linted with ESLint; both are enforced in CI. | M | `.github/workflows/ci.yml` |
| NFR-16 | Pages shall be delivered through Inertia. JSON responses are permitted only for the hotel availability and notification widgets. | M | `CLAUDE.md` |
| NFR-17 | Spots left shall be computed in exactly one place, `App\Support\Availability`, and a booking shall be confirmed only through `Booking::confirm()`, which locks the hotel row. | M | `tests/Unit/Support/AvailabilityTest.php` |

### 4.5 Portability and Operations

| ID | Requirement | Pri |
|----|-------------|-----|
| NFR-18 | The full stack shall start from a clean checkout with `docker compose up -d`. | M |
| NFR-19 | Photo storage shall be selectable by configuration; on ephemeral hosting it shall be S3-compatible. | M |
| NFR-20 | The database shall be PostgreSQL in Docker and production, and SQLite for tests. | M |
| NFR-21 | Go-live steps that cannot be enforced from the repository shall be documented in the deployment checklist (`docs-src/deployment-checklist.md`, coder-facing). | M |

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

Entities and relationships are defined in `CLAUDE.md`. Requirements that
constrain the data layer:

| ID | Requirement |
|----|-------------|
| DR-01 | Deletion shall cascade through foreign keys; soft deletes shall not be used. |
| DR-02 | `users.google_id` shall be nullable and unique; `users.password` shall be nullable. |
| DR-03 | Each hotel shall store a normal `capacity`. A `hotel_availabilities` row is an owner's change to one date: `is_blocked`, and a nullable `capacity` (null keeps the hotel's). Dates without a row use the hotel's capacity. |
| DR-04 | Pricing shall be stored per hotel per pet type. |
| DR-05 | A review shall belong to exactly one booking. |

---

## 7. Traceability to the Original MVP Statement

| MVP requirement | Covered by |
|-----------------|------------|
| 1. Search pet hotels by location | FR-11, FR-14 |
| 2. View available dates and times | FR-18 |
| 3. View pet hotel profiles | FR-16, FR-17 |
| 4. Book a pet hotel (manual process) | FR-19 – FR-24 (with FR-22a), FR-39, FR-45 |
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
sign-in), FR-07a (password change), FR-33/FR-34 (queue hardening), FR-37/FR-43 – FR-45 (panels and owner
role), FR-17 (photo URL resolution), NFR-02/NFR-03 (security headers and CSP),
NFR-14/NFR-15 (CI quality gates).

---

## 8. Open Items

| ID | Item | Owner |
|----|------|-------|
| OI-1 | The hotel `cancellation_policy` field is free text shown on the profile and is not enforced; customer cancellation is governed only by booking status (FR-26). Decide whether a machine-readable rule is wanted. | *TBC* |
| OI-2 | No requirement covers what happens to reviews when a hotel is deleted beyond the cascade in DR-01. | *TBC* |
| OI-3 | Distance sorting exists in the backend but has no UI control, and depends on coordinates that admins enter manually with no validation. Decide whether to expose it or remove it. | *TBC* |
| OI-4 | ~~FR-22 is unimplemented.~~ Resolved 2026-10-10: spots are computed from capacity (FR-23), requests and confirms are checked (FR-22), and owners manage dates (FR-46, FR-47). | Closed |
| OI-5 | ~~A booking for a pet type with no pricing row is created at RM 0.~~ Resolved 2026-10-10: the request is rejected and the form cannot be submitted (FR-21). | Closed |
| OI-6 | ~~Check-in and check-out dates in the search bar do not narrow results by availability (FR-12); they are only passed through.~~ Resolved 2026-10-11: both dates filter results to hotels with a spot every night (FR-12). | Closed |
