---
plan: queue-worker
status: implemented
branch: feature/queue-worker
pr: 17
implemented: 2026-08-08
---

# Feature: Queue Worker Readiness

## What & Why

The three booking notification jobs are the only asynchronous work in the app,
and they are how a customer learns their booking was requested, confirmed, or
cancelled. Nothing runs them on Laravel Cloud unless a worker process is
configured there, so bookings succeed while every notification silently queues
forever. The Docker stack has had a `queue` service all along, which is why this
has never bitten locally.

Configuring the worker is a dashboard action, not a repo change. What the repo
can fix is everything that makes an unattended worker safe to leave running:

- Nothing in `.env.example` says a worker has to exist at all, so the one piece
  of setup without which the feature silently does nothing is undocumented.
  (`QUEUE_CONNECTION` stays `database` there — it is the only value that works
  for `php artisan serve` without Redis — but now says why it differs from
  Docker and production.)
- The jobs set no retry policy. Retries currently come from the Docker worker's
  `--tries=3` flag, so the behaviour depends on how the worker happens to be
  invoked — and Cloud's worker is invoked separately. A transient SMTP failure
  drops a notification with no retry.
- `SerializesModels` throws `ModelNotFoundException` when the booking is gone by
  the time the job runs. The domain hard-deletes with cascading FKs, so deleting
  a user or hotel destroys their bookings and permanently fails any job still in
  flight.
- The `redis` queue connection has `after_commit => false`. Notification jobs are
  dispatched from `Booking::booted()`'s `updated` hook, which fires inside
  whatever transaction the caller opened.

## Scope

- `.env.example` documenting that a worker must exist.
- `$tries`, `$backoff`, and `$deleteWhenMissingModels` on all three jobs.
- `after_commit => true` on the `redis` queue connection.
- `CLAUDE.md` documents the worker command Cloud needs.

## Out of Scope

- Laravel Horizon. Three low-volume jobs on one queue do not justify it.
- A `failed_jobs` alerting or retry workflow.
- SMTP configuration — the notifications will queue and run correctly, but with
  `MAIL_MAILER=log` nothing is delivered. Separate change.
- Scheduled tasks. `routes/console.php` registers none, so Cloud needs no cron.

## Technical Approach

### Backend

- `app/Jobs/SendBookingRequestNotification.php`,
  `SendBookingConfirmationNotification.php`,
  `SendBookingCancelledNotification.php` — add the three properties. Putting
  them on the job rather than the worker flag makes the policy travel with the
  code instead of depending on how each environment starts its worker.
- `config/queue.php` — `after_commit => true` on `redis`.

### Frontend

None.

## Acceptance Criteria

- [x] All three jobs retry with a backoff
- [x] A job whose booking has been deleted is discarded, not failed
- [x] Redis dispatches wait for the surrounding transaction to commit
- [x] `.env.example` states that a worker is required
- [x] `composer test` passes, `vendor/bin/pint` clean

## Edge Cases

- **Retries must not duplicate notifications.** Each job's `handle()` sends one
  notification, so a retry after a genuine send failure is what we want; a retry
  after a partial success would double-notify. Only reachable if the notification
  channel succeeds and the job then fails, which it cannot — the notify call is
  the last statement.
- **`after_commit` with no open transaction** dispatches immediately, so the
  `BookingController@store` path, which already dispatches outside its
  transaction, is unaffected.

## Open Questions

None.
