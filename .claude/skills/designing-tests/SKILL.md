---
name: designing-tests
description: Use before writing or editing any test, before agreeing a list of test cases, when asked to review a suite for coverage, and when a test fails, whatever is under test, whether a function, a service, a job, an endpoint, a component of the interface, or a rule implemented on both sides of a boundary, so that the cases come from the thing under test rather than from whichever seam was easy to reach, and the suite fails on real breakage and stays quiet through refactors.
---

## What a test guards

Couple every test to the promise and never to the mechanism. Change what the system does and the test fails; change how it does it and the test does not move. A test that knows a class name, a method name, or a call order it was never promised has already failed this.

Make the promise a business promise. Before writing a feature test, state in one sentence what the business loses if this is wrong, and if the answer is nothing, no money, no correctness of records, no customer-visible outcome, demote the test to a unit test or delete it.

Assert the consequence of the action, never the receipt. The balance, the report line, the notification, the derived record are consequences; a row saved, a `200` coming back, a required field rejecting, a round-trip handing back what went in are receipts. Let a receipt stand as a sanity guard inside a case and never as the point of one.

Cut the case whose failure is loud. Keep a case where being wrong costs something and would go unnoticed, and cut a case where the first person to use the thing cannot miss the failure, a blank screen, a 500, a column drawn over its neighbour, because ordinary use already tests that and such a case goes red on every refactor that changed nothing. Cost alone does not decide: the cases worth keeping longest are the ones whose failure looks exactly like success.

## Order of work

Work the six steps in order and let each hand its product to the next: the sentence to the inventory, the inventory to the pruned inventory, the pruned inventory to the seams, the seams to the case list, the case list to the tests. Skipping one is how a suite fills up with receipts. Write down the products of the first four, the sentence, the inventory, the `not covered` lines, and the seam per line, in the proposal or in the header of the test file, before any test is written; a case list that exists only in one's head was chosen by the seam rather than by the thing.

## Name the thing

Name the thing and its stake in one sentence before anything else: what it is, and what the business loses if it is wrong. If the sentence contains no loss, give the thing unit tests only, or none.

## Inventory the thing

Inventory the thing in two lists, neither optional and neither finished until a missed line has been looked for and not found.

List first every way the thing comes to be exercised: each entry point, trigger, and lifecycle, such as a request, a command, a scheduled job, a queued message, construction from code, creation after something else has already run, re-creation after its host was replaced, restoration from saved state, or being reached through another feature. A thing exercised four ways and tested one way has three untested ways in.

List second every capability the thing is promised: what it does, what it refuses, what it does when driven by code rather than by hand where that is a supported path, and where its result travels next, the record, the response, the notification, the dependent value, the next calculation. That last item is where the stake usually lives and is the one most often left off.

Name each line as a job, one thing the thing produces or decides, and never as a method the thing has. One job spreads across several methods and one method serves several jobs, so a method-shaped list duplicates wherever a job was split and goes silent wherever a method quietly does two things. A job is what has a right and a wrong outcome to tell apart, and that is what makes it testable.

## Prune the inventory

Prune the inventory before choosing any seam: go through every line on both lists and cut the ones that do not need a test. Only the lines that survive go on to have a seam chosen. Judge each line by the rule that the cases worth keeping longest are the ones whose failure looks exactly like success.

Cut a line whose failure would be loud, where the first person to use the thing could not miss it breaking, a blank screen, a 500, a column drawn over its neighbour. Ordinary use already tests that, and a permanent case for it goes red on every refactor that changed nothing.

Cut a line where nothing is lost if it is wrong, no money, no correctness of records, no customer-visible outcome.

Write every cut line down at this step as `not covered: <reason>`, in the same place the inventory is written. A silent omission cannot be told from an oversight.

## Choose the seam

Choose for each surviving line the outermost seam that can actually cause the line to happen, enacting it rather than simulating it, and never reach past that seam. Test a rule at the seam that enforces it: a calculation at the function, the wiring and its consequence at the entry point.

Weigh each seam by what it is immune to and what it costs, from outermost to innermost. The outermost entry, a real trigger, a real driver, a real control, is immune to everything including markup and wiring, and is the slowest and makes edge cases hard to reach. The entry point, a route, a handler, a command, is immune to all internal renames and is slow. The service method is immune to the internals beneath it and costs coupling to one class name. The function is immune to nothing, couples to the whole shape, and is fastest.

Check the chosen seam against the pruned inventory first. A seam that affords fewer lines than the pruned inventory is the wrong seam and not a reason to shorten the inventory; if the lines with a stake need the slow seam, the slow seam is the price.

Report as a finding, once the seam has been checked against the pruned inventory and not before, any line that no seam can enact because no real trigger can produce its input. Do not paper over it with a hand-built input.

## Write the case list

Write the ordinary path first, as a sentence with an actor and a stake: who does what, and which business outcome must hold. An actor without a stake is an unfinished sentence.

Hang each edge case off that sentence with one thing varied, and let it inherit the full-journey shape.

Rank the cases that survived pruning by the cost of being wrong, with money, records, and permissions first and cosmetic matters last. The ranking orders the list and cuts nothing, because the cutting was done at the pruning step.

Group the cases by what someone is doing, never by parameter, method, or layer, and name each test after the guarantee, not the method.

Give each case one reason it would need editing; two reasons means two cases.

Read the list back against the full inventory before writing anything, so that every line is accounted for, by a case or by its `not covered` line from the pruning.

## Write the tests

Write the tests from the case list and nothing else, and count the work unfinished until the code has been broken and the suite watched go red.

Run verification written to reassure the writer, that a refactor landed, that a rename reached every site, that a new shape survives its framework, and then delete it. Such a check is scaffolding and not a case: it never went through the ranking that produced the list and would not have survived it. It escapes notice because it is the writer's own and it passes, and the same judgement that prunes an inherited suite goes quiet on a test written ten minutes ago.

## Feature tests and unit tests

Keep unit tests for calculations and branching. A unit test passes arguments and asserts the return.

Make a feature test do what the user does: the real entry point, the payload the real trigger emits, through real middleware, asserting the downstream business consequence. A feature test that hand-builds its input in the shape the code expects is a unit test in a costume, and it proves the code agrees with itself.

Watch for the same costume from the other direction. Producing the thing and reading its output configuration back proves the code emitted what it was told to emit, and the thing itself was never exercised.

## Failing tests

Say which bucket a failing test is in before editing anything. The behaviour changed on purpose, so update the test; the behaviour changed by accident, so fix the code; nothing observable changed, so fix the test's design, because that is what is wrong.

## Asserting

Assert what came out, never that a collaborator was called, how many times, with what shape, or in what order, and never a private helper. Prefer a fake that behaves over a mock that records.

Assert only the value the case is about, never the whole payload.

Assert a value in each place the business relies on it, where it surfaces in several. One surface alone is a receipt.

Pin decisions that were hard to make, rounding, ordering, boundaries, before a later reader simplifies them.

Touch both sides of every boundary the capability crosses at least once.

Assert what a rule turns away, where it admits a set of values, and choose the rejects that the most likely wrong implementation would have let through. Values shown passing prove the rule fires, never that it is the only thing firing: a declaration meant to replace a default is satisfied by an implementation that merely appends, and every positive case still passes. Bundle in one case the value that adheres with the near-misses that would adhere under the wrong rule.

## Fixtures

Share the thing under test, never the data the cases assert on. One test's setup must not decide what another test asserts.

State the cases once, in a file that both suites read, where a rule is implemented twice because it crosses a boundary neither side can see across. This is sharing the thing under test and not an exception to it: the agreement between the two implementations is the thing under test, and no test owns the cases for another to inherit. Written out per language instead, the two copies are correct on the day and drift the first time one side gains a case, which nothing fails on.

Keep a line in the inventory for the behaviour the rule serves, even where a shared case file exists. The shared file guards the agreement about the rule and is not a substitute for the behaviour.

Put a field that one case needs in that case, not in the fixture.

Seed the narrowest set, constrain queries to it, and never seed through the code path under test.

## The measure

Hold the blast radius of a behaviour change to exactly the set of cases describing it, no more and no fewer.

Ask of the whole suite whether it would go red if the records were wrong. Send any line of the inventory whose answer is no back to choosing its seam.

Answer by breaking the code and watching, never by reading the test. A suite read for whether it would fail is read by the person who just wrote it and already believes it works, so a case that has never been seen red is a guess about itself.

Invert the decision each case guards, drop a term, flip a comparison, widen a set, and confirm that exactly the cases describing that decision go red and no others. Then restore the code.
