---
title: Personas
description: Four people who use Pet Hotel, what each one expects, and the journey that tests our features against them. The seed data creates all four.
---

# Personas

Four people we design and test for. Dogs and cats only: they are the pets most
customers have. Each story is set on the next **1–5 November**, a school
holiday week, and `PersonaSeeder` (part of `migrate --seed`) creates the
people, pets, hotels and bookings each story needs. Every password is
`password`.

Use them to ask "does this make sense to Aisyah?" when designing a feature,
and as manual test scripts on the local stack.

## Aisyah — dog owner going on holiday

`aisyah@example.com` · Petaling Jaya · **Milo**, Golden Retriever, 4,
anxious with new people.

Aisyah flies to Penang on 1 November for four nights. She has never boarded
Milo and worries about leaving him with strangers.

**Expects:** to see only places that can take Milo for all four nights; to
know clearly whether Milo has a place before she flies; that the hotel reads
her note about his nerves.

**Journey:** search with 1–5 Nov → only hotels with a spot every night show
(Whiskers & Wags is closed on 3 Nov, so it is hidden) → open Rahman's Paws
House, dates already filled in → Book Now, pick Milo, add a note → send the
request → wait for the confirmation email.

**Tests:** search by dates, dates carried to the form, pet details and notes
in the owner pop-up, the confirmation email.

## Daniel — two pets, books late

`daniel@example.com` · Shah Alam · **Bruno**, Labrador, 6 · **Oyen**, cat, 2.

Daniel plans late and books in a hurry. He already asked Rahman to take Oyen
on 1–3 Nov and is still waiting for an answer. He boarded Bruno with Rahman
six weeks ago and has not left a review.

**Expects:** a clear "no" when something cannot work, with what to do
instead; different prices for his dog and his cat.

**Journey:** try to book Oyen at Whiskers & Wags for 1–3 Nov → refused,
because Oyen's pending request at Rahman's shares a night → cancel or wait.
Leave a review for Bruno's last stay.

**Tests:** one place a night per pet, pricing per pet type, customer
cancellation, reviews after a completed stay.

## Mei Ling — careful first-timer

`meiling@example.com` · Kuala Lumpur · **Mochi**, British Shorthair, 1.

Mei Ling has never boarded a cat. She reads every policy, photo and review
before she commits, and would sign up with Google if she could.

**Expects:** the cancellation policy and check-in times before she books; to
cancel without trouble if her plans change.

**Journey:** browse hotels without dates → compare two hotel pages (photos,
facilities, policy) → request a stay → cancel it while it is still pending.

**Tests:** hotel profile page, policies, customer cancellation. Google sign-in
cannot be tested on the `.local` stack (see `local-setup.md`), so her seeded
account has a password.

## Encik Rahman — small hotel owner

`rahman@example.com` · owner of **Rahman's Paws House**, Shah Alam,
capacity 6.

Rahman runs the house with his family and checks the owner panel on his
phone between walks. Four guests are already confirmed for 1–5 Nov, so two
spots are left each night, and Daniel's request for Oyen is waiting.

**Expects:** to see what is new without hunting; to decide from the pet's
details, not just a name; never to take more pets than he has room for.

**Journey:** open the bell → View booking → read Oyen's details and Daniel's
note → Confirm. Once two more pets are confirmed, the next confirm for those
nights is refused. A request he never answers expires the morning after its
check-in day.

**Tests:** owner pop-up, notification links, capacity on confirm, closed
dates, pending expiry. He is also the reason for the owner calendar idea in
the backlog.

## What the personas show today

Gaps found by walking the stories on 2026-10-18. Not yet decided or
scheduled:

- **"Book Now" promises more than it does.** The hotel page says Book Now,
  but it sends a request the owner must accept. The form's button says
  Request Booking, and nothing before it explains that the hotel answers
  first.
- **No answer time.** The confirmation page says the hotel "will confirm
  shortly". In fact a request can stay pending until the morning after its
  check-in day. Aisyah could request on 18 October and only learn on
  2 November, after she has flown, that Milo has no place.
