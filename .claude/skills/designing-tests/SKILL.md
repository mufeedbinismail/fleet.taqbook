---
name: designing-tests
description: A workflow for deciding what to test and how, so the suite fails on real breakage and stays silent through refactors. Use before writing or editing any test, before agreeing a case list, when asked to review a suite for coverage, and when a test fails. Use it for anything — a function, a service, a job, an endpoint, a component, a rule shared across two sides — because the workflow is what makes the model derive the cases from the thing under test rather than from the seam that happened to be easy.
---

# Test design

## Three rules

1. **A test is coupled to the promise, not to the mechanism.** Change what the system does → the
   test fails. Change how → it doesn't move. A test that knows a class name, method name, or call
   order it wasn't promised has already failed this.
2. **The promise is a business promise.** Before writing a feature test, answer in one sentence:
   *what does the business lose if this is wrong?* If nothing — money, correctness of records, a
   customer-visible outcome — demote it to a unit test or delete it. Assert the **consequence** of
   the action (the balance, the report line, the notification, the derived record), never the
   receipt: "row saved", "returns 200", a required field rejecting, a round-trip handing back what
   went in. A receipt may appear as a sanity guard, never as the point.
3. **A loud failure needs no permanent test.** Keep a case where being wrong costs something *and*
   would go unnoticed; cut it where the first person to use the thing cannot miss it — a blank
   screen, a 500, a column drawn over its neighbour. Ordinary use is already that test, and a case
   guarding one earns a red light on every refactor that changed nothing. Cost alone does not
   decide: the cases worth keeping longest are the ones whose failure looks exactly like success.

## The workflow

Five steps, in this order. Each produces something the next one uses; skipping one is how a suite
ends up full of receipts. Write the output of steps 1–3 down — in the proposal, or the file header —
before writing a test. A case list that exists only in your head was chosen by the seam, not by the
thing.

### 1. Name the thing and its stake

One sentence: what it is, and what the business loses if it is wrong. If the sentence has no loss
in it, the thing gets unit tests only, or none.

### 2. Inventory the thing

Two lists. Neither is optional and neither is finished until you have looked for a line you missed.

**A. Every way it comes to be exercised.** Each entry point, trigger, and lifecycle: a request, a
command, a scheduled job, a queued message, construction from code, creation after something else
already ran, re-creation after its host was replaced, restoration from saved state, being reached
through another feature. A thing exercised four ways and tested one way has three untested ways
in.

**B. Every capability it is promised.** What it does; what it refuses; what it does when driven by
code rather than by hand, if that is a supported path; and **where its result travels next** — the
record, the response, the notification, the dependent value, the next calculation. The last item
is where the stake usually lives, so it is the one most often left off.

**Name each line as a job — one thing the unit produces or decides — and never as a method it has.**
One job spreads across several methods and one method serves several jobs, so a list shaped like the
API duplicates wherever a job was split and falls silent wherever a method quietly does two. A job is
what has a right and a wrong outcome to tell apart, which is what makes it testable at all.

Every line on both lists becomes a case in step 4, or a written `not covered: <reason>`. A silent
omission is indistinguishable from an oversight.

### 3. Choose the seam per line

For each inventory line: the outermost seam that can *enact* it — actually cause it, not simulate
it — and never reach past that. Rules are tested at the seam that enforces them: the calculation at
the function, the wiring and consequence at the entry point.

| Seam | Immune to | Costs |
|---|---|---|
| Outermost entry (real trigger, real driver, real control) | everything, including markup and wiring | slowest; edge cases hard |
| Entry point (route / handler / command) | all internal renames | slow |
| Service method | internals below | one class name |
| Function | nothing | whole shape; fastest |

Two checks, in this order:

- **A seam that affords fewer lines than the inventory is the wrong seam, not a reason to shorten
  the inventory.** If the lines with a stake need the slow seam, the slow seam is the price.
- If no seam can enact a line — no real trigger can produce that input — **that is the finding.**
  Report it; don't paper over it with a hand-built input.

### 4. Write the case list

- Ordinary path first, as a sentence with an **actor and a stake**: who does what, and which
  business outcome must hold. Actor without stake is unfinished.
- Edge cases hang off that sentence with one thing varied, and inherit the full-journey shape.
- Rank by cost-of-being-wrong: money, records, permissions first; cosmetic last — and by
  rule 3, most of what lands last does not belong on the list at all.
- Group by what someone is doing, never by parameter, method, or layer. Name each test after the
  guarantee, not the method.
- One reason to edit per case; two reasons means two cases.
- Read the list back against step 2: every line accounted for, by a case or a stated gap.

### 5. Write the tests

Under the rules below. When done, apply the measure at the end.

**Verification written to reassure yourself is scaffolding, not a case: run it, then delete it.**
A check written after the case list — that a refactor landed, that a rename reached every site,
that a new shape survives its framework — never went through the ranking that produced the list,
and would not have survived it. It escapes the rule because it is yours and it passes, which is
the whole of why it has to be named: the same judgement that prunes an inherited suite goes quiet
on a test written ten minutes ago.

## Feature tests enact; unit tests pass arguments

- A **unit test** passes arguments and asserts the return. Keep them for calculations and branching.
- A **feature test** does what the user does: the real entry point, the payload the real trigger
  emits, through real middleware, asserting the downstream business consequence. One that
  hand-builds its input in the shape the code expects is a unit test in a costume — it proves the
  code agrees with itself.
- The same costume from the other side: producing the thing and reading its output configuration
  back proves the code emitted what it was told to emit. The thing was not exercised.

## Triage a failing test before touching it

1. Behaviour changed on purpose → update the test.
2. Behaviour changed by accident → fix the code.
3. Nothing observable changed → the test is wrong; fix its design.

Say the bucket before editing anything.

## Asserting

- Assert what came out — never that a collaborator *was called* (counts, shapes, order), never a
  private helper. Prefer a fake that behaves over a mock that records.
- Assert only the value the case is about, never the whole payload.
- Where a value surfaces in several places the business relies on, assert it in each; one surface
  alone is a receipt.
- Pin decisions that were hard to make — rounding, ordering, boundaries — before a later reader
  "simplifies" them.
- Touch both sides of every boundary the capability crosses at least once.
- **Where a rule admits a set, assert what it turns away, choosing the rejects that the likeliest
  wrong implementation would have let through.** Values shown passing prove the rule fires, never
  that it is the only thing firing — so a declaration meant to *replace* a default is satisfied by
  an implementation that appends, and every positive case still passes. One case, one bundle: the
  value that adheres alongside the near-misses that would adhere under the wrong rule.

## Fixtures

- Share the thing under test, never the data cases assert on — one test's setup must not decide
  what another asserts.
- **Where a rule is implemented twice because it crosses a boundary neither half can see across,
  state its cases once in a file both suites read.** This is the rule above rather than a carve-out
  from it: the agreement between the two implementations *is* the thing under test, and no test owns
  the cases for another to inherit. Written out per language instead, the two copies are correct on
  the day and drift the first time one side gains a case, which nothing fails on.
- A shared case file guards the agreement about a rule. It is not a substitute for the behaviour
  the rule serves; that behaviour still needs its own line in the inventory.
- A field one case needs goes in that case, not the fixture.
- Seed the narrowest set, constrain queries to it, and never seed through the code path under test.

## The measure

Blast radius of a behaviour change = exactly the cases describing it. And for the whole suite:
**if the records were wrong, would it go red?** If the answer for any line of the inventory is no,
go back to step 3 for that line.

**Answer that by breaking the code and watching, never by reading the test.** A suite read for
whether it *would* fail is read by whoever just wrote it and already believes it works, so a case
that has never been seen red is a guess about itself. Invert the decision each case guards — drop a
term, flip a comparison, widen a set — confirm exactly the cases describing it go red and no others,
then put the code back.
