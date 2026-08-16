---
name: refactor-blast-radius
description: The extra lens the done-pass takes whenever any part of the diff reshapes something that already existed — a rename, move, re-signature, split, merge, or extraction. Invoke from reviews-before-done, before such a diff is declared done; the walk starts at each changed definition and moves outward to every site that felt it.
---

# Refactor blast radius

**The diff is where the change was made; the radius is where it is felt.** A refactor's defining
risk is that its correctness lives at sites the diff never touched — every caller, feeder,
reference, and reader that was shaped around the old form. The other lenses read the diff; this
one reads outward from it, and it is finished only when every site that depended on the old shape
has been visited or ruled out by name.

## List the radius before judging any of it

For each definition the diff reshapes, write down what depended on its old shape — callers,
producers, config, serialized forms, docs, anything — before reading any one of them. Visiting
sites as they happen to surface is the writing pass back in disguise: the miss lives at the site
that never surfaces. The list is this lens's real output; the walk is checking it off. The
headings below are the directions a listed site tends to hide in — case law, not a fence.

## "Refactor" is a claim, not a category

The word buys a change lighter review, and that is backwards: behavioural equivalence is the very
thing under review. Every difference a consumer could have observed — ordering, timing, error
shape, what gets logged or persisted, what happens on the empty input — is either shown absent or
renamed a behaviour change and reviewed as one, tests and all.

## Search by the old name, everywhere text lives

The rename tool updated what it could resolve, and its reach quietly became the definition of
"all references." References do not live only where the compiler looks: strings, config, docs,
comments, CI scripts, reflection, serialized data, other skills. The check is a text search for
the old name and the old path across everything, and its result is empty or explained.

## Compiling is agreement to shape, not meaning

A call site that still compiles has accepted the new signature, not the new semantics. This is
where same-typed parameters swap silently, where a shifted default lands on callers who never
chose it, where a unit or a null now means something else. Each surviving call site is re-read
with one question: does the intent that was written here still arrive?

## The radius runs upstream too

Instinct walks downstream — the consumers of what changed, and what they assumed beyond the
signature. The bite instinct misses is upstream: producers still shaping data for a shape that is
gone, preparation feeding a reader that no longer reads, invariants maintained for a maintainer
that was deleted. Whatever fed or preceded the old form goes on the list with the same standing
as whatever consumed it.

## Closing

Findings close the way the done-pass closes: fixed or surfaced as an open decision, never
dropped, one line in the report. "Radius walked, nothing outside the diff moved" is a legitimate
outcome and is still said.
