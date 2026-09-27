---
name: refactor-blast-radius
description: "Use from the review pass in `reviews-before-done`, before a diff is declared done, whenever any part of the diff reshapes something that already existed, whether by a rename, a move, a change of signature, a split, a merge or an extraction. The walk starts at each changed definition and moves outward to every site that felt the change."
---

## The list

Leave the diff itself to the other lenses of the review pass and read outward from it. The diff is where the change was made; the radius is where it is felt. A refactor's defining risk is that its correctness lives at sites the diff never touched, at every caller, feeder, reference and reader that was built around the old shape. Stop only when every site that depended on the old shape has been visited or ruled out by name.

Write the list before reading any site on it. For each definition the diff reshapes, set down everything that depended on its old shape, the callers, the producers, the configuration, the serialized forms, the documentation and whatever else, and open the first of them only once the list is complete. Visiting sites as they happen to surface is the authoring pass in disguise, because the miss lives at the site that never surfaces. The list is the real output; the walk is the act of checking it off. Callers, producers, configuration and the rest are directions a listed site tends to hide in, not a fence around what the list may hold.

Put whatever fed or preceded the old shape on the list with the same standing as whatever consumed it. Instinct walks downstream, to the consumers of what changed and to what they assumed beyond the signature; what it misses is upstream, where producers still shape data for a shape that is gone, preparation feeds a reader that no longer reads, and invariants are maintained for a maintainer that was deleted. For each reshaped definition ask who fed it as well as who took from it, and list both.

## The walk

Treat "refactor" as a claim to be checked, not as a label to be honoured. The word buys a change a lighter review, and that is backwards, because behavioural equivalence is the very thing under review. Take every difference a consumer could have observed, in ordering, in timing, in the shape of an error, in what gets logged or persisted, in what happens on empty input, and either show it to be absent or rename it a behaviour change and review it as one, tests included.

Search by text for the old name and the old path across everything text lives in. The rename tool updated what it could resolve, and its reach quietly became the definition of "all references"; references also live in strings, configuration, documentation, comments, continuous integration scripts, reflection, serialized data and other skills, where no compiler looks. Accept the search result only when it is empty or every hit is explained.

Re-read each surviving call site and ask whether the intent written there still arrives. Compiling is agreement to shape, not to meaning: a call site that still compiles has accepted the new signature, not the new semantics. This is where parameters of the same type swap silently, where a shifted default lands on callers who never chose it, and where a unit or a null now means something else. A passing build strikes nothing off the list; only the re-reading does.

## The report

Report the list of dependent sites and each finding in whatever shape conveys it best, whether sentences, a table, a small diagram, a heading with a section under it, or a list. The goal is that the user understands what was checked and what was found, and a shape that conveys that better than prose is the right shape: the sites that depended on an old shape may read best as a table of site, what it depended on and what was found there, and a chain of producers and consumers as a small diagram. Fit a table or diagram inside the terminal width, about eighty columns, with no wrapping or clipping, because one that clips conveys nothing.

Keep everything the reader needs to understand one finding in one place, as one self-contained group: the site, what it depended on, what changed, and what that means, without sending the reader to another entry. Allow nothing that makes the reader move: no pointer back to something read earlier, no pointer forward to something coming, no label or code defined in one place and used in another, no fact given in two places so the reader has to reconcile them. The reader reads once, top to bottom, without shifting between places.

Close every finding the way the review pass closes its findings: fixed, or surfaced as an open decision, never dropped, and with one line in the report. Say "radius walked, nothing outside the diff moved" when that is what the walk found. It is a legitimate outcome, and it is still said.
