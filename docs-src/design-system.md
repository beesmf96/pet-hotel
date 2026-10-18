---
title: Design System
description: How the customer pages look and why — tokens, shapes, type, layout, components, page patterns, states and wording — drawn from the current code.
---

# Design System

The written rules for the customer UI (the Vue pages). The code holds the
parts — `resources/css/tokens.css`, the utilities in `resources/css/app.css`,
the components in `resources/js/Components/Ui/` and the two layouts. This file
says **which part to use when**, so every page looks like the same app.

Use it when building a page, and as the reference a design review checks
pages against. A rule here is the default; break it only with a reason, and
write the reason down (a comment, or a change to this file).

The Filament panels share the tokens but are styled only in
`resources/css/filament/theme.css`. They are out of scope here.

Sections 1–8 are the rules. Section 9 lists where pages do not match them
yet — the first design review fixes those.

## 1. Direction: "playful bold"

A cream page, ink outlines, flat bright fills and hard offset shadows — like
a printed sticker. No gradients, no soft blurred shadows, no thin grey borders.
Headings are loud (display font); body text is quiet (sans, moss for the
less important parts).

## 2. Colour

Every colour comes from `tokens.css`. Never write a hex value anywhere else.

| Token | Role | Used for |
|---|---|---|
| `cream` | Page | Page background, nav, header |
| `white` | Surface | Cards, fields, secondary buttons |
| `ink` | Line and text | All outlines, shadows, main text |
| `moss` | Quiet text | Subtitles, meta lines, hints, empty-state text |
| `teal` | Primary | Primary buttons, links, brand mark, "confirmed" |
| `teal-light` | Calm fill | Info notices, "completed" |
| `mustard` | Attention | Warning notices, "pending", the one call to action that should stand out (Register, Add pet) |
| `coral` | Danger | Errors, destructive actions (Cancel booking, Remove) |

Rules:
- **One meaning per colour.** Coral is never decoration; teal is never a warning.
- Text on `teal` is `cream`; text on every other fill is `ink`.
- Status colours come only from `StatusPill`.

## 3. Shape: borders, corners, shadows

| Element | Border | Corner | Shadow |
|---|---|---|---|
| Card (`card-hard`) | 3px ink | `rounded-2xl` | `shadow-hard` |
| Big card: auth box, hero, search box (`card-hard-lg`) | 3px ink | `rounded-2xl` | `shadow-hard-lg` |
| Button (`UiButton`) | 3px ink | `rounded-xl` | `shadow-hard`, drops to none on hover |
| Field (`field-hard`) | 2px ink | `rounded-xl` | none; teal ring on focus |
| Notice, pill, small inner box | 2px ink | `rounded-xl` / `rounded-full` | none |
| Round icon button, avatar | 3px ink | `rounded-full` | none |
| Feature card (teal fill: price summary, landing "owners" block) | 3px ink | `rounded-2xl` | `shadow-hard-lg` |

Rules:
- **3px for things you press or that hold a section; 2px for things inside them.**
- Corners stay on this scale: `lg` (small things under about 40px: checkboxes,
  calendar days, icon tiles), `xl`, `2xl`, `full`.
- Only ink-coloured hard shadows: `shadow-hard-sm`, `shadow-hard`, `shadow-hard-lg`.
- Use `card-hard` / `card-hard-lg` rather than rebuilding a card from classes.

## 4. Type

Fonts: `font-display` (Bricolage Grotesque) for headings and the brand;
`font-sans` (Instrument Sans) for everything else.

| Level | How | Size |
|---|---|---|
| Page title | `<h1>` in AppLayout's `#header` slot (the slot adds display, extrabold, tight tracking, `leading-tight`) | `text-3xl sm:text-4xl` |
| Section title | `<SectionTitle>` (h2) | `md`: `text-2xl` |
| Card / small section title | `<SectionTitle size="sm">` | `text-xl` |
| Item title in a list or grid | `h3`, `font-display font-bold` | `text-lg` – `text-xl` |
| Body | sans | `text-sm` in cards and forms, `text-base` on its own |
| Meta, hint, subtitle | sans, `text-moss` | `text-xs` – `text-sm` |
| Field label | `FormField` | `text-sm font-bold` |

Rules:
- One `h1` per page, always the page title.
- A heading is never smaller than the text under it.
- Use `SectionTitle` for every section heading rather than its classes.

## 5. Layout and spacing

- Every page wraps itself in `AppLayout` or `AuthLayout` (`Landing.vue` is the
  one exception). The layout sets the page width (`max-w-7xl`) and gutters
  (`px-4 sm:px-6 lg:px-8`) — pages never set their own gutters.
- **Content width**, chosen by the kind of page:

  | Page kind | Width | Pages today |
  |---|---|---|
  | Browse (grid, sidebar) | full (`max-w-7xl` from the layout) | Search, Hotel profile, Pets |
  | List | `max-w-3xl` | My bookings, Reviews |
  | Form or detail | `max-w-2xl` | Book a stay, Booking detail |
  | Settings | `max-w-lg` | Profile |
  | Moment (one message, centred) | `max-w-lg mx-auto text-center` | Booking confirmation |

- **Spacing scale** (Tailwind steps):
  - between sections or cards: `6`
  - between list items: `4` – `5`
  - inside a card: `4`
  - a heading and its content: `3` – `4`
  - a label and its control: `1.5` (FormField)
- Card padding is `p-6`. Use `p-4` only for a small card in a dense grid.
- Every layout must work at 375px wide with no sideways scroll.

## 6. Components: which one when

| Need | Use | Notes |
|---|---|---|
| Main action | `UiButton` (teal) | One per view. |
| Action that must stand out from teal (Register, Add pet) | `UiButton variant="mustard"` | Rare; at most one per screen. |
| Secondary action (Back, Cancel in a modal, Google sign-in) | `UiButton variant="white"` | The only secondary style. |
| Destructive action | `UiButton variant="coral"` | Cancel booking. |
| Small inline action or text link | `link-bold` + `text-teal` (or `text-coral` for destructive) | Edit / Remove, Back to list, pagination. |
| Go to another page | `UiButton as="link"` or Inertia `<Link>` | Not `as="a"` or a plain `<a>` (CLAUDE.md); Google sign-in is the one plain `<a>`. |
| Text, select or textarea field | `FormField` + `TextInput` | FormField ties label and error to the control. |
| Short message on the page | `Notice` | `info` (teal-light) for status, `warning` (mustard) for "you can't do this yet". |
| Success after an action | the flash from the server | AppLayout shows it. Pages never show their own success message. |
| Nothing to show | `EmptyState` | Title optional, one-line message, optional button in the slot. |
| Booking status | `StatusPill` | Never a hand-made pill. |
| Section heading | `SectionTitle` | |
| Brand | `BrandMark`, `PawIcon` | |

## 7. Page patterns

- **Page header.** `#header` slot: the `h1`. Optional subtitle under it
  (`font-sans font-medium tracking-normal text-sm text-moss`); use
  `flex flex-col gap-1` for title plus subtitle. An optional single action goes
  on the right (`flex items-center justify-between gap-4`).
- **List page.** Header, then `EmptyState` when empty, or a `space-y-5`
  list of `card-hard` rows. Each row: title, meta line in moss, `StatusPill`
  on the right where there is a status.
- **Detail page.** Header (title + subtitle + status), then `space-y-6` cards,
  one per topic, each starting with a `SectionTitle`. Actions at the bottom:
  a `link-bold` back link on the left, the main or destructive button on the right.
- **Form page.** Header, then the form: one `card-hard p-6` per group of
  fields, `space-y-6` between the cards and `space-y-4` between the
  `FormField`s inside one, the submit button last. Errors for a group of fields (like the two stay
  dates) sit under the group with the same style as a FormField error.
- **Modal.** `fixed inset-0 z-50` with a `bg-ink/60` backdrop that closes it
  on click. A `card-hard-lg w-full max-w-md mx-4 p-6` panel, a `SectionTitle`,
  the form (`space-y-4`), then Cancel (white) and the main button.
- **Auth page.** `AuthLayout` only: brand, subtitle, one `card-hard-lg` box.

## 8. States and words

- **Busy.** A submit button is `:disabled="form.processing"` and its label
  changes to the "-ing" form with "…" (Unicode ellipsis, not three dots):
  "Save changes" → "Saving…".
- **Errors.** Errors are coral (`text-sm font-semibold text-coral`), under
  their field, written as a sentence that says what to do.
- **Money, dates, nights.** `formatMoney()`, `formatDate()` and `nightsLabel()`.
  Never type "RM", a date format or "night(s)" by hand.
- **Sentence case** for every heading and button: "My bookings", "Edit pet",
  "Save changes", "Browse hotels".

## 9. Not matching yet

Decided on 2026-10-18; the pages still need the change.

- **Title Case** on buttons ("Save Changes", "Browse Hotels", "Request Booking",
  "Apply Filters", "+ Add Pet") and modal titles ("Edit Pet", "Leave a Review").
- **Busy labels** missing on Profile and the pet form; "..." instead of "…" on
  the four auth forms.
- **`cream` button**: "Back" on the booking form. Drop the variant from
  `UiButton` once it is unused.
- **`leading-tight`** is written on four page titles; move it into AppLayout's
  header slot and remove it from the pages.
- **Plain links**: `UiButton as="a"` and `<a href>` to other pages in the
  AppLayout nav, the booking detail and confirmation pages, and the booking
  form's Back button.
- **Corners off the scale**: `rounded-3xl` on the landing "owners" block and
  hero image, and on the confirmation icon tile; `rounded-md` on the
  confirmation page; `rounded-sm` on the availability calendar.
