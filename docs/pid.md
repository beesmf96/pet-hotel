# Project Initiation Document — Pet Hotel

| Field | Value |
|-------|-------|
| Project name | Pet Hotel — Pet Boarding Marketplace |
| Document version | 1.0 |
| Date | 2026-08-08 |
| Status | Baselined |
| Author | *TBC* |
| Approver / Sponsor | *TBC* |
| Repository | `beesmf96/pet-hotel` |

> Fields marked *TBC* need input from the project owner before this document is signed off.

---

## 1. Purpose of This Document

This PID defines what the Pet Hotel project is, what it will and will not deliver,
who is involved, how it is being run, and what "done" means. It is the reference
point for scope decisions and the baseline against which delivery is measured.

Related documents:

| Document | Location | Covers |
|----------|----------|--------|
| MVP requirements | `docs/pet-hotel-boarding-mvp-v1.html` | Original functional / non-functional requirements |
| Task list | `docs/tasks.md` | Work breakdown, Modules 0–9 |
| User guide | `docs/user_guide.html` | End-user instructions (pet owner, admin, hotel owner) |
| Booking flow | `docs/booking-flow.html` | Step-by-step booking journey |
| Deployment checklist | `docs/deployment-checklist.md` | Release / go-live steps |
| Stack & domain reference | `.claude/CLAUDE.md` | Architecture and domain model |

---

## 2. Background

Pet owners looking for boarding currently rely on phone calls, social media, and
word of mouth. There is no single place to compare hotels on price, facilities,
location, and other owners' experience, and no reliable way to see whether a hotel
has space on the dates needed.

Pet Hotel is a marketplace that brings that into one system: customers search and
compare hotels, submit booking requests, and leave reviews; hotel owners and
administrators manage listings, availability, and bookings through dedicated admin
panels. Booking confirmation is deliberately a **manual** step — a human confirms
each request — which keeps the MVP simple and avoids payment integration.

---

## 3. Objectives

| # | Objective | Success measure |
|---|-----------|-----------------|
| O1 | Let customers find suitable pet hotels | Search with location, date, pet-type, price filters and rating/price/distance sorting |
| O2 | Let customers book without phone calls | Booking request submitted online, confirmed by an operator, both parties notified by email and in-app |
| O3 | Give operators control without developer help | Admin and hotel-owner panels cover hotels, bookings, users, and review moderation |
| O4 | Build trust between strangers | Reviews restricted to customers with a completed booking; moderation available |
| O5 | Ship something maintainable | ≥95% backend line coverage enforced in CI; linting and formatting gates on every PR |

---

## 4. Scope

### 4.1 In scope

**Customer (pet owner)**

- Registration, email verification, login, password reset, Google sign-in
- Profile management (contact details, preferred location)
- Pet profiles with photo upload
- Hotel search with filters and sorting; paginated results
- Hotel profile pages — description, photos, pricing per pet type, facilities, policies
- Availability calendar per hotel
- Booking request submission, booking history, booking detail, cancellation
- Reviews and ratings after a completed stay
- In-app notification bell and email notifications

**Administrator**

- Filament admin panel at `admin.pet-hotel.local`, restricted to `is_admin` users
- Hotel CRUD including photos, pricing tiers, policies, and owner assignment
- Booking management — filter, confirm, cancel
- Read-only user list with pets
- Review moderation (visibility toggle, delete)
- Dashboard widgets — bookings today, pending bookings, new users this week

**Hotel owner**

- Filament panel at `owner.pet-hotel.local`, restricted to users who own a hotel
- View own bookings, filter by status, confirm or decline

**Platform**

- Dockerised local environment (app, nginx, PostgreSQL, Redis, Mailpit, Vite)
- Redis-backed queue for notification jobs
- Configurable photo storage disk (local or S3-compatible)
- Security hardening — security headers, CSP, dependency advisory clearance
- CI pipeline on every PR and push to `main`

### 4.2 Out of scope

| Excluded | Rationale |
|----------|-----------|
| Online payment / deposits | MVP confirms bookings manually; payments are a later phase |
| Public API (`routes/api.php`) | No third-party or mobile consumer exists yet |
| Native mobile apps | Responsive web is sufficient for MVP |
| Real-time chat between customer and hotel | Email and notifications cover the MVP need |
| Additional OAuth providers | Google only; others add support cost without demand |
| Full hotel-owner self-service listing management | Owner panel covers bookings only; hotels are managed by admins |
| Multi-language / multi-currency | Single market at launch |
| Automated availability sync with external calendars | No integration partners identified |

### 4.3 Assumptions

- Operators check and confirm pending bookings promptly; the system does not auto-confirm.
- Hotel content (photos, descriptions, pricing) is supplied by hotels and entered by admins.
- Email delivery is available in production; Mailpit is local-only.
- Object storage (`PHOTO_DISK=s3`) is provisioned before deploying to ephemeral hosting.
- Users have a modern browser; no legacy browser support is required.

### 4.4 Constraints

- Fixed technology stack (see §7); substitutions require a change request.
- Bun is the only package manager; npm and pnpm are not used.
- Leaflet is the only mapping library.
- No TypeScript, no component library, no Pinia/Vuex.
- Pages are served through Inertia; JSON responses are permitted only for the two
  documented XHR widgets (hotel availability, notifications).
- Hard deletes with cascading foreign keys throughout — soft deletes are not used.

---

## 5. Deliverables

| # | Deliverable | Acceptance criteria |
|---|-------------|---------------------|
| D1 | Customer web application | All in-scope customer journeys work end to end |
| D2 | Admin panel | All in-scope admin functions available and access-restricted |
| D3 | Hotel-owner panel | Owner can view and act on their own hotel's bookings only |
| D4 | Notification system | Request, confirmation, and cancellation emails plus in-app notifications fire reliably via the queue |
| D5 | Dockerised environment | `docker compose up -d` yields a working stack from a clean checkout |
| D6 | Automated test suite | PHPUnit and Vitest green; backend line coverage ≥95% |
| D7 | CI pipeline | Formatting, linting, tests, and coverage gate enforced on every PR |
| D8 | Documentation set | PID, requirements, user guide, booking flow, deployment checklist |

---

## 6. Stakeholders and Roles

| Role | Responsibility | Who |
|------|----------------|-----|
| Project sponsor | Funds the project, approves scope changes | *TBC* |
| Product owner | Prioritises the backlog, accepts deliverables | *TBC* |
| Developer | Implementation, testing, documentation | *TBC* |
| Operations / admin user | Confirms bookings, maintains hotel listings, moderates reviews | *TBC* |
| Hotel owner | Supplies listing content, acts on their bookings | External partner hotels |
| Customer | End user — searches, books, reviews | Public |

---

## 7. Approach

### 7.1 Technology

| Layer | Choice |
|-------|--------|
| Backend | Laravel 13.8 (PHP 8.3+) |
| Frontend | Vue 3, Vite 8, Tailwind CSS v4 |
| SPA bridge | Inertia.js 3.1 |
| Admin UI | Filament v4 (two panels) |
| Auth | Sanctum cookie SPA + Google OAuth via Socialite |
| Database | PostgreSQL 16 (Docker); SQLite locally and in tests |
| Queue | Redis |
| Maps | Leaflet |
| Package manager | Bun |
| Tests | PHPUnit 12, Vitest 4 |
| Formatters | Laravel Pint, ESLint + Prettier |

### 7.2 Delivery method

Work is broken into modules (see `docs/tasks.md`) delivered incrementally. Each
change is developed on a `feature/{name}` branch, opened as a pull request to
`main`, and merged only after human review. Larger pieces of work carry a plan file
in `.claude/plans/`.

### 7.3 Quality gates

Every pull request must pass:

1. `vendor/bin/pint --test` — PHP formatting
2. PHPUnit with pcov coverage, failing below `MIN_COVERAGE` (currently 95)
3. `bun run lint` — ESLint
4. `bun run test --run` — Vitest

The coverage floor is raised as coverage improves and is never lowered to turn a
build green.

---

## 8. Work Breakdown and Milestones

| # | Milestone | Modules | Status |
|---|-----------|---------|--------|
| 1 | Docker + Laravel scaffold running | 0 | Complete |
| 2 | Authentication working end to end | 1 | Complete |
| 3 | Profiles and pets manageable | 2 | Complete |
| 4 | Hotels searchable and viewable | 3, 4 | Complete |
| 5 | Booking flow complete (pending state) | 5, 6 | Complete |
| 6 | Reviews visible and submittable | 7 | Complete |
| 7 | Email notifications firing | 8 | Complete |
| 8 | Admin can confirm bookings | 9a–9e | Complete |

Post-MVP work delivered outside the original module list: Google OAuth, hotel-owner
panel, object storage for photos, OWASP security hardening, and test-coverage
raising. Dates for each milestone are *TBC* — reconstruct from git history if a
dated schedule is required.

---

## 9. Risks

| ID | Risk | Likelihood | Impact | Mitigation |
|----|------|-----------|--------|------------|
| R1 | Manual booking confirmation does not scale as volume grows | Medium | High | Monitor pending-booking volume via the dashboard widget; automate confirmation in a later phase |
| R2 | Photos lost on redeploy if `PHOTO_DISK` is left on the local disk | Medium | High | Deployment checklist mandates `s3` on ephemeral hosting; documented in the stack reference |
| R3 | Availability drift — spots not adjusting correctly | Low | High | Availability side-effects are centralised in `Booking::booted()` and covered by tests; never duplicated elsewhere |
| R4 | Dependency vulnerabilities accumulate | Medium | Medium | Advisory review and dependency bumps; OWASP hardening plan completed |
| R5 | Email deliverability in production (spam filtering) | Medium | Medium | Use a reputable transactional provider; verify sending domain before go-live |
| R6 | Coverage gate blocks urgent fixes | Low | Low | Add tests with the fix; the floor is not lowered |
| R7 | Fake or abusive reviews damage hotel trust | Low | Medium | Reviews limited to completed bookings; admin moderation with visibility toggle |
| R8 | Single-developer knowledge concentration | Medium | Medium | Conventions and architecture documented in `CLAUDE.md`, plans, and this document set |

---

## 10. Dependencies

- Google Cloud OAuth credentials (`GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`)
- S3-compatible object storage bucket for production photo uploads
- Transactional email provider for production mail
- Managed PostgreSQL and Redis instances in production
- Hotel partners willing to list and to act on booking requests

---

## 11. Acceptance Criteria

The project is accepted when:

1. All in-scope journeys in §4.1 work end to end in a production-like environment.
2. CI is green on `main`, with backend line coverage at or above the configured floor.
3. The deployment checklist has been executed against the target environment.
4. Admin and hotel-owner panels enforce their access rules — non-admins cannot
   reach the admin panel, and owners see only their own hotel's bookings.
5. The documentation set in §5/D8 is complete and current.

---

## 12. Change Control

Changes to scope, technology choices, or the constraints in §4.4 require the
product owner's approval. Approved changes are recorded as a new version of this
document and, where implementation work follows, a plan file in `.claude/plans/`.

| Version | Date | Change | Author |
|---------|------|--------|--------|
| 1.0 | 2026-08-08 | Initial baseline | *TBC* |
