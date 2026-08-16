---
name: writing-skills
description: What a skill is and what an entry must be — decision rules born from real bites, recorded as root causes, free of the working tree that prompted them. Invoke before creating or editing any SKILL.md, and when reviewing whether a skill has decayed into documentation.
---

# Writing skills

## What a skill is

**A skill is a decision guide.** It exists to make the next decision come out right at the moments
it would otherwise go wrong. It is not documentation, not a feature tour, and not a record of what
was built — those describe the past. A skill decides the future.

## Where an entry comes from

**An entry is born when something bites.** A wrong turn was taken, or caught in review, and the
entry records what would have prevented it. That is the admission ticket: no bite, no entry. Rules
written speculatively, for completeness, or to round out a section decide nothing and bury the
entries that do.

Craft any competent practitioner already carries — a control needs a label, logic should be
testable — is not an entry either: no bite separates this codebase from instinct there. The skill
records where instinct fails, not where it suffices.

## Record the root cause — never the symptom, never the mechanism

A bite has three descriptions; only one belongs in the skill.

- The **symptom** names the incident: "the table grew a badge-map prop." It expires the day that
  component is finished.
- The **mechanism** names the fix's machinery: the helper reached for, the prop renamed, the class
  introduced. It expires the day the implementation changes.
- The **root cause** names the decision that went wrong: "a component held an opinion about what
  the consumer's content means." It was true before the incident and stays true after the code is
  deleted.

The test: delete, in your head, the code that prompted the entry. An entry that stops making sense
was a symptom or a mechanism wearing a rule's clothes.

## File by the moment, not by the subject

Having named the root cause, file it where that decision gets made — not with the skill that owns
the subject the bite happened in. A wrong turn taken while writing a test is a test-design entry
only if the test's design is what went wrong; where what went wrong was *noticing*, it belongs
wherever noticing happens. Filing by subject is the instinct, and it buries the entry in a skill
nobody has open at the moment it would have helped. Same test the description is held to: what work
is underway when this needs to be known?

A moment is the undertaking, not the action inside it. Actions decompose without limit, so filing
by action yields skills that are always needed together — many invocations to reach what one would
have carried. The test is the invocation trace: if two skills would routinely be wanted in the same
sitting, they are one skill. Split only where the sittings diverge — where whoever is in one has no
use for the other.

## Keep the working tree out

The change on the desk is the worst source of material, because all of it feels important while it
is open. A skill that enumerates the current change's controls, props, or feature list is the
working tree leaking in: it reads as the spec of one component and ages with it. Generalise before
writing — the entry must hold for the consumer that does not exist yet.

A concrete name may appear only when the name itself is the settled rule — a repo-wide convention
or a platform's one blessed door — never because it is what the change on the desk happens to do.

## An example refers; it never specifies

An example's one job is to point at the application site where the rule genuinely helps — the line
people actually get wrong, named so the reader recognises the moment when it arrives. An example
that instead shows the code the rule produced is a specification, and invites copying the
implementation rather than applying the rule. One pointed example at the confusable line beats a
catalogue.

## The rule leads; the reasoning follows

**State the entry as an imperative first — one bold sentence that can be obeyed on its own — and
let the root cause follow it, never replace it.** An entry recorded only as its diagnosis ("the
tell that this went wrong is…") must be reverse-engineered into an instruction at the moment of
use, and the reader doing that is the one with the least context — a fresh agent holding the skill
and nothing else. The reasoning is what lets the rule transfer to a case it never named; the
headline is what lets it be found and obeyed at all. An entry needs both, in that order.

## Every entry earns its keep

For each entry, name the future decision it flips. If there is none — it restates a neighbour,
states the obvious, or describes rather than decides — delete it. A skill is trusted in proportion
to its density; every padded entry taxes the ones that matter.

## One rule, one place

State a rule once, at its strongest, and let the rest of the skill lean on it. When the same root
cause resurfaces under several headings — once as a principle, again as a bullet, again inside an
example — the skill has been organised by topic instead of by decision. Repetition reads as
emphasis and acts as dilution: every echo weakens the original and doubles the cost of changing
the rule. Merge into the single sharpest statement and delete the rest.

A section is held to the same bar as an entry. A heading written for shape — a "may" to mirror a
"may not", a taxonomy to look complete — fills itself with entries no bite produced.

## A skill nobody invokes decides nothing

The frontmatter description is the only trigger — it is what gets read when the work starts, so it
states *when to invoke* in terms of the work about to be done ("before creating any class"), not in
terms of the topic. A description that names the subject rather than the moment leaves the skill
unread no matter how good its entries are. Listing the skill elsewhere buys nothing and drifts.
