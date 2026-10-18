# Repeated-code rules

These rules apply to everyday coding as well as to the `/dry-sweep` run.
PHP and JS cannot share code, so count copies on each side separately.

## 1. Rule of three
- 1 copy: write it.
- 2 copies: leave them.
- 3 copies: extract one shared home.

## 2. Count meaning, not looks
Two pieces of code are the same only if they would always change together,
for the same reason. Code that only looks alike stays separate. The booking
emails look alike but each can change on its own — do not merge them.

## 3. Business rules: one home from the second copy
A wrong second copy of these is a bug, so extract on the second copy:
- prices and money
- dates and times shown to users
- availability and capacity
- booking status changes
- security checks (owner scoping, throttles, policies)

## 4. Never extract
- Migrations — frozen history.
- Config files.
- Tests — a plain repeated setup reads better than a hidden helper. Extract a
  test helper only when the same setup appears 3+ times in one test file.

## 5. Kept on purpose: `dry-keep:`
Code that repeats for a reason carries a comment that starts with `dry-keep:`
and says why, in one line, at each copy:

```php
// dry-keep: the owner email words this differently on purpose; see BookingConfirmed.
```

```js
// dry-keep: twin of App\Support\Money — PHP is the source; change both together.
```

A sweep never touches marked code. Remove the marker when the reason goes away.

## 6. Where the shared home goes

| What it is | Backend | Frontend |
|---|---|---|
| Pure logic or formatting | `app/Support/` | `resources/js/*.js` (like `money.js`) |
| Query or model state | model method or scope | — |
| Labels or colours per status | enum method | — |
| Shared panel behaviour | trait in `app/Filament/**/Concerns/` | — |
| Validation | Form Request | — |
| Needs Vue reactivity | — | `resources/js/composables/useX.js` |
| Markup and style | — | `resources/js/Components/Ui/` |
| A colour, shadow or font value | `resources/css/tokens.css` | same |

## 7. Shape of the shared code
- Small, and named after the domain (`stayDates()`, not `formatHelper()`).
- No flags. If callers differ enough to need a boolean, they are not the same
  thing — keep the copies. One small option (like `weekday` in
  `useFormatDate`) is fine.
- If a helper later grows a flag per caller, inline it back. Repeated code is
  cheaper than the wrong shared code.

## 8. A rule that must exist in PHP and JS
PHP is the source. The JS file is a copy with a `dry-keep:` comment naming the
PHP source. A test on each side checks the same values, so drift fails a test.
