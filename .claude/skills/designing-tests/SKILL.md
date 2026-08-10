---
name: designing-tests
description: Rules for what to test and how, so the suite fails on real breakage and stays silent through refactors. Invoke before writing or editing any test, before agreeing a case list, and when a test fails.
---

# Test design

## Two rules

1. **A test is coupled to the promise, not to the mechanism.** Change what the system does → the
   test fails. Change how → it doesn't move. A test that knows a class name, method name, or call
   order it wasn't promised has already failed this.
2. **The promise is a business promise.** "Row saved" and "returns 200" are receipts, not promises.
   Before writing a feature test, answer in one sentence: *what does the business lose if this is
   wrong?* If nothing — money, correctness of records, a customer-visible outcome — demote it to a
   unit test or delete it. Assert the **consequence** of the action (the balance, the report line,
   the notification, the derived record), not the receipt.

## Feature tests enact; unit tests pass arguments

- A **unit test** passes arguments and asserts the return. Keep them for calculations and branching.
- A **feature test** does what the user does: the real entry point, the payload the real control
  emits, through real middleware, asserting the downstream business consequence. A feature test
  that hand-builds its input in the shape the code expects is a unit test in a costume — it proves
  the code agrees with itself.
- If no real entry point can emit that input, **that is the finding**. Report it; don't paper over
  it with a hand-built input.

## Triage a failing test before touching it

1. Behaviour changed on purpose → update the test.
2. Behaviour changed by accident → fix the code.
3. Nothing observable changed → the test is wrong; fix its design.

Say the bucket before editing anything.

## Choosing the case list

- Ordinary path first, as a sentence with an **actor and a stake**: who does what, and which
  business outcome must hold. Actor without stake is unfinished.
- It travels the whole way — real control, real emission, server, downstream consequence.
- Edge cases hang off that sentence with one thing varied, and inherit the full-journey shape.
- Rank by cost-of-being-wrong: money, records, permissions first; cosmetic last.
- Group by what someone is doing, never by parameter, method, or layer.

## NEVER

- Write a case that can't fail interestingly — no bare 200s, no required-field checks, no
  round-trips, no "record exists" as a feature test's main assertion.
- Assert a receipt as the point; it may appear only as a sanity guard.
- Assert on private helpers, or that a collaborator *was called* (counts, shapes, order). Assert
  what came out.
- Hand-build a feature test's input when a real entry point exists.
- Reach past the outermost seam that can afford the test.
- Let one test's setup decide data another asserts on.
- Assert a whole payload when the case is about one field.
- Seed through the code path under test.

## ALWAYS

- State, per case, what the business loses if it fails.
- Name the test after the guarantee, not the method.
- One reason to edit per case; two reasons means two cases.
- Prefer a fake that behaves over a mock that records.
- Assert only the value the case is about.
- Pin decisions that were hard to make — rounding, ordering, boundaries — before a later reader
  "simplifies" them.
- Where a value surfaces in several places the business relies on, assert it in each; one surface
  alone is a receipt.
- Touch both sides of every boundary the capability crosses at least once.

## Seam

Outermost that can afford the case. Rules are tested at the seam that enforces them: the
calculation at the function, the wiring and consequence at the entry point.

| Seam | Immune to | Costs |
|---|---|---|
| Entry point (route/UI) | all internal renames | slow; edge cases hard |
| Service method | internals below | one class name |
| Function | nothing | whole shape; fastest |

## Fixtures

- Share the thing under test, never the data others assert on.
- A field one case needs goes in that case, not the fixture.
- Seed the narrowest set and constrain queries to it.

## The measure

Blast radius of a behaviour change = exactly the cases describing it. And one question for the
whole suite: **if the records were wrong, would it go red?** Green over wrong records is a suite
of receipts.
