# Pet Hotel — User Guide

This guide covers everything you can do on Pet Hotel, split by user type.

---

## Table of Contents

**Part 1 — Pet Owner**
1. [Getting Started](#1-getting-started)
2. [Profile Management](#2-profile-management)
3. [Managing Your Pets](#3-managing-your-pets)
4. [Browsing Hotels](#4-browsing-hotels)
5. [Making a Booking](#5-making-a-booking)
6. [Managing Your Bookings](#6-managing-your-bookings)
7. [Leaving a Review](#7-leaving-a-review)
8. [Notifications](#8-notifications)

**Part 2 — Admin**
1. [Accessing the Admin Panel](#1-accessing-the-admin-panel)
2. [Dashboard](#2-dashboard)
3. [Managing Hotels](#3-managing-hotels)
4. [Managing Bookings](#4-managing-bookings)
5. [Managing Users](#5-managing-users)
6. [Managing Reviews](#6-managing-reviews)

---

# Part 1 — Pet Owner

## 1. Getting Started

### Register

1. Go to `/register`.
2. Fill in your **name**, **email**, and **password** (confirm password required).
3. Submit — you will be redirected to a verification notice.

### Verify Your Email

After registering you must verify your email before accessing protected pages.

1. Open the verification link sent to your inbox.
2. If the email didn't arrive, go to `/email/verify` and click **Resend Verification Email**.

### Log In

1. Go to `/login`.
2. Enter your **email** and **password**.
3. Tick **Remember Me** to stay logged in across browser sessions.

### Forgot Password

1. Go to `/forgot-password`.
2. Enter your email — a reset link will be sent.
3. Follow the link to `/reset-password/{token}` and choose a new password.

### Log Out

Click the logout control in the navigation. Your session will be invalidated immediately.

---

## 2. Profile Management

**Route**: `/profile`

You can update:

| Field | Notes |
|---|---|
| Name | Required |
| Phone | Optional |
| Preferred Location | Optional — used to pre-fill hotel searches |

Your **email** is shown for reference but cannot be changed from this page.

---

## 3. Managing Your Pets

**Route**: `/pets`

All your pets are listed here. Each pet card shows its photo (if uploaded), species, breed, age, and any special needs.

### Add a Pet

Click **Add Pet** and fill in:

| Field | Required | Notes |
|---|---|---|
| Name | Yes | |
| Species | Yes | Dog, Cat, Rabbit, Bird, Other |
| Breed | No | |
| Age | No | In years |
| Special Needs | No | Dietary, medical, or behavioural notes |
| Photo | No | Image upload |

### Edit a Pet

Click the edit icon on any pet card. All fields can be changed, including replacing the photo.

### Delete a Pet

Click the delete icon on the pet card and confirm. Deleting a pet does not affect past bookings that referenced it.

> **Note:** You must have at least one pet registered before you can create a booking.

---

## 4. Browsing Hotels

### Search (public — no login required)

**Route**: `/hotels`

Use the search bar and filters to find hotels:

| Filter | Options |
|---|---|
| City | Free-text city name |
| Pet Type | Dog, Cat, Rabbit, Bird, Other |
| Price Range | Min and max price per night |
| Facilities | Grooming, Play Area, Vet Care, Swimming Pool, Training, Outdoor Walks, Webcam, 24h Care |
| Sort By | Newest, Price Low→High, Price High→Low, Distance |

Results are paginated (15 per page).

### Hotel Detail Page

**Route**: `/hotels/{slug}`

Each hotel page shows:

- **Photo gallery** — cover photo plus additional gallery images with navigation arrows
- **Name, address, city**
- **Description**
- **Facilities** offered
- **Pricing** by pet type (e.g. RM 50/night for dogs, RM 40/night for cats)
- **Policies** — check-in time, check-out time, cancellation policy
- **Review summary** — average star rating and total review count
- **Latest 5 reviews** with link to view all

### Availability Calendar

Accessible from the hotel detail page. Shows a month-by-month calendar with:

- **Available** dates (spots remaining)
- **Blocked** dates (hotel closed or unavailable)
- **Fully booked** dates

Navigate between months with the previous/next controls.

### All Reviews

**Route**: `/hotels/{slug}/reviews`

Full paginated list of customer reviews (10 per page). Each review shows the star rating, written comment, reviewer name, and date posted.

---

## 5. Making a Booking

**Route**: `/hotels/{slug}/book`

1. Navigate to a hotel and click **Book Now**.
2. Fill in the booking form:

| Field | Required | Notes |
|---|---|---|
| Pet | Yes | Select from your registered pets |
| Check-in Date | Yes | |
| Check-out Date | Yes | Must be after check-in |
| Notes / Special Requests | No | Any information for the hotel |

3. The **total price** is calculated automatically (nightly rate × number of nights) and shown before you confirm.
4. Submit — you will be redirected to the **Booking Confirmation** page.

### Booking Confirmation Page

**Route**: `/bookings/{booking}/confirmation`

Shows a summary of your booking details and a **Pending** status badge, meaning the hotel has yet to confirm your stay.

---

## 6. Managing Your Bookings

**Route**: `/bookings`

Lists all your bookings — past and upcoming — with:

- Hotel name
- Pet name
- Check-in and check-out dates
- Number of nights
- Total price
- Status badge

### Booking Statuses

| Status | Meaning |
|---|---|
| Pending | Submitted, awaiting hotel confirmation |
| Confirmed | Hotel has accepted your booking |
| Completed | Stay is finished |
| Cancelled | Booking was cancelled |

### View Booking Details

**Route**: `/bookings/{booking}`

Click any booking to see the full details: hotel address, pet, dates, nights, status, your notes, price, and when the booking was created.

### Cancel a Booking

On the booking detail page, a **Cancel** button appears when the booking status is **Pending**. Confirm the cancellation to remove the booking. Confirmed or completed bookings cannot be cancelled from this screen.

---

## 7. Leaving a Review

After a booking reaches **Completed** status, you can leave a review for that hotel.

1. Go to `/bookings` and open the completed booking.
2. Click **Leave a Review** (only visible if the booking is completed and not yet reviewed).
3. Enter a **star rating** and a written **comment**.
4. Submit — the review is posted to the hotel's profile immediately (subject to admin visibility settings).

Each completed booking can only be reviewed once. Reviews are rate-limited to 5 per 10 minutes.

---

## 8. Notifications

In-app notifications keep you informed about your booking activity.

### View Notifications

Your latest 10 notifications are accessible from the notification bell in the navigation bar. Each notification shows:

- Type and message
- Timestamp
- Read / unread status

You receive notifications when a booking is **confirmed** or **cancelled** by the hotel.

### Mark as Read

- Click an individual notification to mark it read.
- Use **Mark All as Read** to clear the unread count in one action.

---

# Part 2 — Admin

## 1. Accessing the Admin Panel

**Route**: `/admin`

The admin panel uses a **separate login** from the customer-facing site. Log in at `/admin/login` with an account that has admin privileges.

> Regular customer accounts cannot access the admin panel even if they know the URL.

---

## 2. Dashboard

**Route**: `/admin`

The dashboard displays three live stat widgets at a glance:

| Widget | What It Shows |
|---|---|
| Bookings Today | Number of bookings with a check-in date of today |
| Pending Bookings | Number of bookings currently awaiting confirmation |
| New Users This Week | Number of customer accounts created in the last 7 days |

An account widget shows your logged-in admin profile.

---

## 3. Managing Hotels

**Route**: `/admin/hotels`

Full CRUD for hotel listings.

### Hotel List

The table shows **Name**, **City**, and **Active** status. Filter by active/inactive with the filter sidebar.

### Create a Hotel

Click **New Hotel** and complete the form:

**Basic Information**

| Field | Required | Notes |
|---|---|---|
| Name | Yes | Slug auto-generates on blur |
| Slug | Yes | URL-friendly identifier, must be unique |
| Description | No | |
| Address | Yes | |
| City | Yes | |
| Active | — | Toggle on/off (defaults to active) |
| Latitude / Longitude | No | For distance-based sorting |
| Cover Photo | No | Main image shown in search results |

**Policy**

| Field | Required | Notes |
|---|---|---|
| Check-in Time | Yes | |
| Check-out Time | Yes | |
| Cancellation Policy | No | Free-text description |

### Edit a Hotel

Click a hotel row to open the edit form. All fields above are editable.

### Photos

Within the hotel edit page, the **Photos** tab lets you manage the gallery:

- **Upload** new photos (images only)
- Set a **sort order** to control gallery sequence
- **Drag-and-drop** to reorder
- **Delete** individual photos or use **Bulk Delete**

### Pricing

Within the hotel edit page, the **Pricing** tab lets you set per-night rates by pet type:

| Field | Options |
|---|---|
| Pet Type | Dog, Cat, Rabbit, Bird, Other |
| Price Per Night | Numeric, displayed in MYR |

Create one pricing row per pet type you want to support. Edit or delete rows at any time.

### Delete Hotels

Bulk-select rows on the list and use **Bulk Delete** to remove multiple hotels at once.

---

## 4. Managing Bookings

**Route**: `/admin/bookings`

All customer bookings across all hotels, sorted newest first.

### Table Columns

| Column | Notes |
|---|---|
| ID | |
| Pet | Pet's name |
| Hotel | Hotel name |
| Customer | Customer's name (toggleable) |
| Check In / Check Out | Formatted dates |
| Total Price | |
| Status | Colour-coded badge |

### Filter by Status

Use the **Status** filter to show only: Pending, Confirmed, Cancelled, or Completed bookings.

### Confirm a Booking

For any **Pending** booking, click the green **Confirm** action. A confirmation modal appears. On confirm:

- Booking status changes to **Confirmed**
- The customer receives an in-app notification

### Cancel a Booking

For any booking that is not already cancelled, click the red **Cancel** action. A confirmation modal appears. On confirm:

- Booking status changes to **Cancelled**
- The customer receives an in-app notification

### View Booking Details

Click **View** on any row to see the full booking record.

---

## 5. Managing Users

**Route**: `/admin/users`

A **read-only** view of all registered customer accounts, sorted by registration date.

### Table Columns

| Column | Notes |
|---|---|
| Name | Searchable |
| Email | Searchable |
| Phone | Toggleable (hidden by default) |
| Admin | Indicates if the account has admin access |
| Pets | Count of registered pets |
| Registered | Date |

### View a User

Click **View** on any row to open the user's detail page. The **Pets** tab shows a read-only list of their registered pets including species, breed, age, and special needs.

> User accounts cannot be created, edited, or deleted from the admin panel — account management is self-service for customers.

---

## 6. Managing Reviews

**Route**: `/admin/reviews`

All customer reviews across all hotels, sorted newest first.

### Table Columns

| Column | Notes |
|---|---|
| Customer | Searchable |
| Hotel | Searchable |
| Rating | Star display with colour coding (green = 4–5 ★, yellow = 3 ★, red = 1–2 ★) |
| Comment | Truncated preview |
| Visible | Whether the review is publicly shown |
| Date | |

### Filters

- **Visible** — show only visible, hidden, or all reviews
- **Rating** — filter by specific star rating (1–5)

### Toggle Review Visibility

Each review has a **Show** or **Hide** action depending on its current state:

- **Hide** — removes the review from the public hotel page (review is preserved in the database)
- **Show** — makes a hidden review publicly visible again

Use this to moderate reviews without deleting them.

### Delete Reviews

Bulk-select rows and use **Bulk Delete** to permanently remove multiple reviews. Individual view pages also support deletion.
