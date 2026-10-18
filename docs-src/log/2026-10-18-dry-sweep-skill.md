---
title: DRY sweep skill
description: Rules for repeated code, and a /dry-sweep skill that audits the codebase against them and extracts approved copies with tests.
date: 2026-10-18
pr: ~
plan: ~
---

# DRY sweep skill

## Asked
Shape DRY rules for backend and frontend ("if repeat more than 2 times then
only extract"), allow code that repeats for a reason, and make it a skill to
run periodically, with tests done after the code changes.

## Done
- `.claude/skills/dry-sweep/rules.md`: rule of three; business rules (money,
  user-facing dates, availability, status changes, security) extract on the
  second copy; count meaning, not looks; never extract migrations, config or
  test setup under 3 copies in one file; where each kind of shared code lives;
  no flag parameters.
- Deliberate repeats carry a `dry-keep:` comment with the reason. Added it to
  the two PHP twins on the frontend, `money.js` and `petTypes.js`.
- `.claude/skills/dry-sweep/SKILL.md`: branch, scan, classify (Extract /
  Leave / Ask / Inline back), stop for approval, then per finding: pin
  behaviour with tests, extract, test the new home, commit; full checks; log
  entry; PR to `dev`. Only runs when the user types `/dry-sweep`.
- CLAUDE.md points at the rules so everyday coding follows them too.

## Not done
- No sweep was run. A first look found `format('D, d M Y')` 9 times across
  four notifications and the owner `BookingResource`, so the first sweep
  will most likely start there.
- No scheduled run. The sweep stops for approval, so it is started by hand.

## Produced
- ADR: none
- Knowledge: none
- Intake: none

## Verified
No PHP changed; JS changed in comments only. `bun run lint` and `bun run test`
pass.
