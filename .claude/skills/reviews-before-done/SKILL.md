---
name: reviews-before-done
description: The final pass over the full diff before any coding task is declared done, re-reading the change in review stance rather than authoring stance. Invoke after the work runs green and before reporting completion, committing, or moving to the next task.
---

# Reviews before done

**This is a pass, not a rulebook.** Every rule it checks lives in another skill, and where those
speak they are the authority — nothing here restates or overrides them. This skill exists because
writing and reviewing are different stances: the writing pass attends to making the thing work,
and what it misses is only found by re-reading the finished diff with the question "does this
follow the rules" — a question the writing pass believes it already answered.

## When

After the work compiles and its tests pass; before "done" is said, before any commit. Not earlier:
reviewing while still building is the writing pass wearing a costume. A task that changed no code
has nothing for this pass.

## The pass

Collect the **whole diff** — staged, unstaged, and untracked files alike — and walk all of it once
per lens. Reading only the parts that felt risky is the writing pass's judgement smuggled back in;
the misses live precisely where nothing felt risky.

1. **Placement and naming — the `architecture` lens.** Every new or moved file, class, folder and
   export, checked against the layout, the component types, and the naming rules. Includes the
   frontend trees and the mirror rule: a thing that landed in one tree landed in all of them.
2. **Comments — the `commenting` lens.** Every comment the diff adds *or now sits beside*: does it
   break a rule, and were already-broken neighbours fixed rather than left, as those rules demand.
3. **Tests — the `designing-tests` lens.** Every test the diff touches: named for a guarantee,
   coupled to a promise, enacting through a real entry point rather than hand-built input.

## The boilerplate question

Then one question the lenses above do not ask, because it belongs to the diff as a whole:

**Point at every part of this diff that the next one of its kind would copy verbatim. Each one is
either a convention that deserves a name — a directive, a component, a helper, a concern — or a
decision to leave it raw, stated out loud in the report.**

This is the architecture skill's probability rule aimed at lines instead of folders. Three lines
of scaffolding repeated per page is structure wearing plain clothes, and the first instance is the
cheapest moment it will ever have to become one line with a name.

## The accommodation question

And one the lenses cannot ask either, because an accommodation is correct where it sits — which is
why somebody wrote it there:

**Point at every line here that exists to tolerate something outside this change. Each one is a
finding about that thing, not a line to review.**

Fix what is being tolerated, or say in the report why it cannot be. What must not happen is tidying
the accommodation — naming it, lifting it somewhere shared. That is the answer the question above
would give, and here it is the wrong one: it makes the tolerated thing permanent and retires the
only evidence that anyone should still be asking about it.

## Case law

Did the change mint, alter, or retire a convention? Then the skill that documents it changed
**in the same diff** — a convention that lives only in the code is one session away from being
unwritten by someone following the skill faithfully.

But the test for *convention* is the next decision, not the new structure. An entry is earned
where a future change could go wrong while still looking locally right — above all where the
skill's current text would instruct the wrong thing. What one glance at the code settles, the
code documents by existing; a fact that guards a single file belongs in that file's own comment;
writing either into a skill turns a decision procedure into a catalogue.

## Closing

A finding is fixed before done is declared, or surfaced as an explicit open decision — never
silently dropped. The pass ends with one line in the report: what was walked, what was found, what
changed because of it. "Reviewed, nothing found" is a legitimate outcome and is still said.
