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
- [ ] Enable Sanctum SPA authentication (cookie-based)
- [ ] `POST /register` — email, password, name
- [ ] `POST /login` — email + password
- [ ] `POST /logout`
- [ ] `GET /user` — return authenticated user
- [ ] Email verification on registration (`MustVerifyEmail`)
- [ ] Password reset flow (built-in Laravel)

### Frontend
- [ ] `RegisterPage.vue` — form with validation errors via Inertia
- [ ] `LoginPage.vue`
- [ ] `ForgotPasswordPage.vue`
- [ ] `ResetPasswordPage.vue`
- [ ] Auth middleware redirect (guest → login, auth → dashboard)

---

## Module 2 — User & Pet Profile Management

**Covers:** FR #5 (User and Pet Profile Management)

### Backend
- [ ] `users` table: name, email, phone, preferred_location
- [ ] `pets` table: user_id, name, breed, species, age, special_needs, photo
- [ ] `UserController@update` — update contact details
- [ ] `PetController` — CRUD for user's pets
- [ ] Pet photo upload (local disk or S3-compatible)
- [ ] Form Request validation for pet and user updates

### Frontend
- [ ] `ProfilePage.vue` — edit name, phone, preferred location
- [ ] `PetsPage.vue` — list user's pets
- [ ] `PetFormModal.vue` — add/edit pet (inline modal)

---

## Module 3 — Pet Hotel Profiles

**Covers:** FR #3 (View Pet Hotel Profiles)

### Backend
- [ ] `pet_hotels` table: name, slug, description, address, city, lat, lng, cover_photo
- [ ] `pet_hotel_facilities` table: hotel_id, type (enum: grooming, play_area, vet_care, …)
- [ ] `pet_hotel_photos` table: hotel_id, url, sort_order
- [ ] `pet_hotel_policies` table: hotel_id, check_in_time, check_out_time, cancellation_policy
- [ ] `pet_hotel_pricing` table: hotel_id, pet_type, price_per_night
- [ ] `HotelController@show` — public profile endpoint
- [ ] Slug-based routing (`/hotels/{slug}`)

### Frontend
- [ ] `HotelProfilePage.vue` — full profile layout
  - [ ] Photo gallery carousel
  - [ ] Pricing table per pet type
  - [ ] Facilities chips/icons
  - [ ] Policies section
  - [ ] Ratings summary (feeds from Module 6)

---

## Module 4 — Search & Filter

**Covers:** FR #1 (Search by Location), FR #7 (Filter and Sort)

### Backend
- [ ] `HotelSearchController@index` — query builder with filters
- [ ] Filter params: `city`, `pet_type`, `price_min`, `price_max`, `check_in`, `check_out`
- [ ] Sort params: `rating`, `price_asc`, `price_desc`, `distance` (requires lat/lng)
- [ ] Pagination (15 per page)

### Frontend
- [ ] `SearchPage.vue` — search results grid
- [ ] `SearchBar.vue` — city input + date range picker + pet type selector
- [ ] `FilterSidebar.vue` — price range slider, pet type, facilities checkboxes
- [ ] `HotelCard.vue` — card component (name, city, price from, rating, cover photo)
- [ ] Sort dropdown (rating, price)
- [ ] Empty state illustration

---

## Module 5 — Availability & Calendar

**Covers:** FR #2 (View Available Dates and Times)

### Backend
- [ ] `hotel_availabilities` table: hotel_id, date, available_spots, is_blocked
- [ ] `HotelAvailabilityController@index` — return available dates for a hotel in a range
- [ ] Block dates when bookings are confirmed (Module 6 triggers this)
- [ ] Seed dummy availability data for development

### Frontend
- [ ] `AvailabilityCalendar.vue` — month calendar with available/unavailable day states
- [ ] Integrate into `HotelProfilePage.vue` — show calendar before booking CTA

---

## Module 6 — Booking System

**Covers:** FR #4 (Book a Pet Hotel), FR #10 (Booking Confirmation)

### Backend
- [ ] `bookings` table: user_id, hotel_id, pet_id, check_in, check_out, status (pending/confirmed/cancelled), notes, total_price
- [ ] `BookingController@store` — create booking with status=pending
- [ ] `BookingController@index` — user's booking history
- [ ] `BookingController@cancel` — user cancel (if policy allows)
- [ ] Queue job: `SendBookingRequestNotification` — email to user + admin on new booking
- [ ] Queue job: `SendBookingConfirmationNotification` — email to user when confirmed

### Frontend
- [ ] `BookingFormPage.vue` — select pet, dates, add notes, price summary
- [ ] `BookingConfirmationPage.vue` — "Your request is pending" screen
- [ ] `MyBookingsPage.vue` — list of bookings with status badges
- [ ] `BookingDetailPage.vue` — booking details + cancel button

---

## Module 7 — Ratings & Reviews

**Covers:** FR #6 (View Ratings and Reviews), FR #8 (Leave Feedback)

### Backend
- [ ] `reviews` table: user_id, hotel_id, booking_id, rating (1–5), comment, is_visible
- [ ] Only allow review after a confirmed + completed booking
- [ ] `ReviewController@store` — submit review
- [ ] `ReviewController@index` — public list per hotel (paginated)
- [ ] Compute average rating on `pet_hotels` table (via DB view or accessor)

### Frontend
- [ ] `ReviewList.vue` — paginated reviews with star display
- [ ] `LeaveReviewModal.vue` — star picker + comment textarea
- [ ] Show "Leave a review" prompt on `MyBookingsPage` for completed bookings

---

## Module 8 — Notifications

**Covers:** FR #10 (Booking Confirmation / email or in-app)

### Backend
- [ ] Set up Laravel queue worker (`redis` driver)
- [ ] `BookingRequestedMail` — sent to user on booking submission
- [ ] `BookingConfirmedMail` — sent to user when admin confirms
- [ ] `BookingCancelledMail` — sent to user on cancellation
- [ ] `in_app_notifications` table (or use Laravel's built-in notifications table)
- [ ] `NotificationController@index` — return unread notifications for auth user
- [ ] Mark-as-read endpoint

### Frontend
- [ ] `NotificationBell.vue` — navbar bell icon with unread count badge
- [ ] `NotificationsDropdown.vue` — last 10 notifications list

---

## Module 9 — Admin Panel (Filament)

**Manages:** Hotels, Bookings, Users, Reviews

### Setup
- [ ] Install and configure Filament v3
- [ ] Create `AdminUser` seeder
- [ ] Restrict `/admin` to users with `is_admin` flag

### Resources
- [ ] `HotelResource` — CRUD for pet hotels, photos, facilities, pricing, policies
- [ ] `BookingResource` — list bookings, filter by status, confirm/cancel action buttons
- [ ] `UserResource` — read-only user list, view pets
- [ ] `ReviewResource` — moderate reviews (toggle `is_visible`)

### Dashboard Widgets
- [ ] Bookings today (stat widget)
- [ ] Pending bookings count (stat widget)
- [ ] New users this week (stat widget)

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
