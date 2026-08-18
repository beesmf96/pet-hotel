---
title: Task List
description: Work breakdown across Modules 0–9, with delivery status per item.
badges: Planning
order: 40
---

# Pet Hotel App — Task List

**Stack:** Laravel 13 · Vue 3 · Inertia.js · Filament v4 · Laravel Sanctum · PostgreSQL · Redis

---

## Module 0 — Infrastructure & Docker

### Docker Compose
- [x] Create `docker-compose.yml` with services: `app`, `nginx`, `postgres`, `redis`, `node`
- [x] Create `docker/nginx/default.conf` for Laravel
- [x] Create `docker/php/Dockerfile` (PHP 8.4 + extensions incl. intl, redis)
- [x] Create `.env.docker` example file
- [x] Verify all services spin up: `docker compose up -d`

### Infrastructure Patch (2026-05-20)
- [x] Add `mailpit` service (`axllent/mailpit`) for local email catching
- [x] Replace single `docker/nginx/default.conf` with `docker/nginx/conf.d/` directory
- [x] Add nginx virtual host `web.pet-hotel.local` → Laravel app
- [x] Add nginx virtual host `mailpit.local` → Mailpit web UI (WebSocket-aware proxy)
- [x] Switch nginx port binding `8080 → 80`
- [x] Update `.env.docker`: `MAIL_MAILER=smtp`, `MAIL_HOST=mailpit`, `MAIL_PORT=1025`
- [x] Fix missing CSRF meta tag in `resources/views/app.blade.php` (caused 419 on logout)

### Infrastructure Patch (2026-05-20 — admin subdomain)
- [x] Add nginx virtual host `admin.pet-hotel.local` → Laravel app (PHP-FPM, same pattern as `web.conf`)
- [x] Configure Filament `AdminPanelProvider` with `->domain('admin.pet-hotel.local')->path('')`
- [x] Redirect root route `/` to `/hotels` (removes default Laravel welcome page)
- [x] Update `CLAUDE.md` and `README.md` to document new host entries and admin URL

### Laravel Project Bootstrap
- [x] Scaffold Laravel project inside repo root (Laravel 13)
- [x] Install frontend deps: `bun install` (Vite + Vue 3 + `@inertiajs/vue3` + `@vitejs/plugin-vue`)
- [x] Install Inertia.js server-side adapter (`inertiajs/inertia-laravel` v3.1)
- [x] Install Inertia.js client-side adapter (`@inertiajs/vue3` v3.1)
- [x] Install Filament v4 (`filament/filament` v4.11 — v3 does not support Laravel 13)
- [x] Install Sanctum (`laravel/sanctum` v4.3)
- [x] Configure PostgreSQL connection in `.env`
- [x] Configure Redis for session, cache, and queue
- [x] Set up base layout: `resources/js/Layouts/AppLayout.vue`
- [x] Run initial migrations

---

## Module 1 — User Authentication

**Covers:** FR #9 (Basic User Authentication)

### Backend
- [x] Enable Sanctum SPA authentication (cookie-based)
- [x] `POST /register` — email, password, name
- [x] `POST /login` — email + password
- [x] `POST /logout`
- [x] `GET /user` — return authenticated user
- [x] Email verification on registration (`MustVerifyEmail`)
- [x] Password reset flow (built-in Laravel)

### Frontend
- [x] `RegisterPage.vue` — form with validation errors via Inertia
- [x] `LoginPage.vue`
- [x] `ForgotPasswordPage.vue`
- [x] `ResetPasswordPage.vue`
- [x] Auth middleware redirect (guest → login, auth → dashboard)

---

## Module 2 — User & Pet Profile Management

**Covers:** FR #5 (User and Pet Profile Management)

### Backend
- [x] `users` table: name, email, phone, preferred_location
- [x] `pets` table: user_id, name, breed, species, age, special_needs, photo
- [x] `UserController@update` — update contact details
- [x] `PetController` — CRUD for user's pets
- [x] Pet photo upload (local disk or S3-compatible)
- [x] Form Request validation for pet and user updates

### Frontend
- [x] `ProfilePage.vue` — edit name, phone, preferred location
- [x] `PetsPage.vue` — list user's pets
- [x] `PetFormModal.vue` — add/edit pet (inline modal)

---

## Module 3 — Pet Hotel Profiles

**Covers:** FR #3 (View Pet Hotel Profiles)

### Backend
- [x] `pet_hotels` table: name, slug, description, address, city, lat, lng, cover_photo
- [x] `pet_hotel_facilities` table: hotel_id, type (enum: grooming, play_area, vet_care, …)
- [x] `pet_hotel_photos` table: hotel_id, url, sort_order
- [x] `pet_hotel_policies` table: hotel_id, check_in_time, check_out_time, cancellation_policy
- [x] `pet_hotel_pricing` table: hotel_id, pet_type, price_per_night
- [x] `HotelController@show` — public profile endpoint
- [x] Slug-based routing (`/hotels/{slug}`)

### Frontend
- [x] `HotelProfilePage.vue` — full profile layout
  - [x] Photo gallery carousel
  - [x] Pricing table per pet type
  - [x] Facilities chips/icons
  - [x] Policies section
  - [x] Ratings summary (feeds from Module 6)

---

## Module 4 — Search & Filter

**Covers:** FR #1 (Search by Location), FR #7 (Filter and Sort)

### Backend
- [x] `HotelSearchController@index` — query builder with filters
- [x] Filter params: `city`, `pet_type`, `price_min`, `price_max`, `check_in`, `check_out`
- [x] Sort params: `rating`, `price_asc`, `price_desc`, `distance` (requires lat/lng)
- [x] Pagination (15 per page)

### Frontend
- [x] `SearchPage.vue` — search results grid
- [x] `SearchBar.vue` — city input + date range picker + pet type selector
- [x] `FilterSidebar.vue` — price range slider, pet type, facilities checkboxes
- [x] `HotelCard.vue` — card component (name, city, price from, rating, cover photo)
- [x] Sort dropdown (rating, price)
- [x] Empty state illustration

---

## Module 5 — Availability & Calendar

**Covers:** FR #2 (View Available Dates and Times)

### Backend
- [x] `hotel_availabilities` table: hotel_id, date, available_spots, is_blocked
- [x] `HotelAvailabilityController@index` — return available dates for a hotel in a range
- [x] Block dates when bookings are confirmed (Module 6 triggers this)
- [x] Seed dummy availability data for development

### Frontend
- [x] `AvailabilityCalendar.vue` — month calendar with available/unavailable day states
- [x] Integrate into `HotelProfilePage.vue` — show calendar before booking CTA

---

## Module 6 — Booking System

**Covers:** FR #4 (Book a Pet Hotel), FR #10 (Booking Confirmation)

### Backend
- [x] `bookings` table: user_id, hotel_id, pet_id, check_in, check_out, status (pending/confirmed/cancelled), notes, total_price
- [x] `BookingController@store` — create booking with status=pending
- [x] `BookingController@index` — user's booking history
- [x] `BookingController@cancel` — user cancel (if policy allows)
- [x] Queue job: `SendBookingRequestNotification` — email to user + admin on new booking
- [x] Queue job: `SendBookingConfirmationNotification` — email to user when confirmed

### Frontend
- [x] `BookingFormPage.vue` — select pet, dates, add notes, price summary
- [x] `BookingConfirmationPage.vue` — "Your request is pending" screen
- [x] `MyBookingsPage.vue` — list of bookings with status badges
- [x] `BookingDetailPage.vue` — booking details + cancel button

---

## Module 7 — Ratings & Reviews

**Covers:** FR #6 (View Ratings and Reviews), FR #8 (Leave Feedback)

### Backend
- [x] `reviews` table: user_id, hotel_id, booking_id, rating (1–5), comment, is_visible
- [x] Only allow review after a confirmed + completed booking
- [x] `ReviewController@store` — submit review
- [x] `ReviewController@index` — public list per hotel (paginated)
- [x] Compute average rating on `pet_hotels` table (via DB view or accessor)

### Frontend
- [x] `ReviewList.vue` — paginated reviews with star display
- [x] `LeaveReviewModal.vue` — star picker + comment textarea
- [x] Show "Leave a review" prompt on `MyBookingsPage` for completed bookings

---

## Module 8 — Notifications

**Covers:** FR #10 (Booking Confirmation / email or in-app)

### Backend
- [x] Set up Laravel queue worker (`redis` driver)
- [x] `BookingRequestedMail` — sent to user on booking submission
- [x] `BookingConfirmedMail` — sent to user when admin confirms
- [x] `BookingCancelledMail` — sent to user on cancellation
- [x] `in_app_notifications` table (or use Laravel's built-in notifications table)
- [x] `NotificationController@index` — return unread notifications for auth user
- [x] Mark-as-read endpoint

### Frontend
- [x] `NotificationBell.vue` — navbar bell icon with unread count badge
- [x] `NotificationsDropdown.vue` — last 10 notifications list

---

## Module 9a — Admin Panel Setup

**Goal:** Get Filament installed, secured, and seeded with an admin user.

- [x] Add `is_admin` boolean column migration to `users` table
- [x] Add `canAccessPanel()` to `User` model to restrict `/admin` to admins
- [x] Create `AdminUserSeeder` — seeds one admin account
- [x] Update `DatabaseSeeder` to call `AdminUserSeeder`
- [x] Verify `/admin` login works and non-admins are rejected

---

## Module 9b — Manage Hotels (Admin)

**Goal:** Full CRUD for pet hotels from the admin panel.

- [x] Generate `HotelResource` with table (name, city, status) and form
- [x] Form includes: name, description, address, city, price fields
- [x] Form includes repeater or relation manager for photos
- [x] Form includes repeater or relation manager for policies
- [x] Form includes repeater or relation manager for pricing tiers

---

## Module 9c — Manage Bookings (Admin)

**Goal:** Let admins view, filter, confirm, and cancel bookings.

- [x] Generate `BookingResource` with table (pet, hotel, dates, status)
- [x] Filter by status (pending / confirmed / cancelled)
- [x] Add `Confirm` action button (sets status → confirmed, fires notification)
- [x] Add `Cancel` action button (sets status → cancelled, fires notification)

---

## Module 9d — Manage Users & Reviews (Admin)

**Goal:** Read-only user list with pets, and review moderation.

- [x] Generate `UserResource` — read-only table (name, email, created_at)
- [x] Add relation manager to show pets per user
- [x] Generate `ReviewResource` — table with rating, body, visibility toggle
- [x] Add `Toggle Visibility` action (flips `is_visible`)

---

## Module 9e — Dashboard Widgets

**Goal:** At-a-glance stats on the Filament dashboard.

- [x] `BookingsTodayWidget` — stat widget: count of bookings with check-in today
- [x] `PendingBookingsWidget` — stat widget: count of bookings with status = pending
- [x] `NewUsersThisWeekWidget` — stat widget: users created in the last 7 days
- [x] Register all three widgets in `AdminPanelProvider`

---

## Milestone Checklist

| # | Milestone | Modules |
|---|-----------|---------|
| 1 | Docker + Laravel scaffold running | 0 |
| 2 | Auth working end-to-end | 1 |
| 3 | Profiles + pets manageable | 2 |
| 4 | Hotels searchable and viewable | 3, 4 |
| 5 | Booking flow complete (pending state) | 5, 6 |
| 6 | Reviews visible and submittable | 7 |
| 7 | Email notifications firing | 8 |
| 8 | Admin can confirm bookings | 9 |
