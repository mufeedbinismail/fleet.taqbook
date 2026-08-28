---
name: reviews-before-done
description: A review pass over the full diff a task produced — code, docs, skills, and config diffs alike — re-reading the change in review stance rather than authoring stance. Invoke only when the user asks for the pass, never on your own initiative.
---

# Reviews before done

**This is a pass, not a rulebook.** Every rule it checks lives in another skill, and where those
speak they are the authority — nothing here restates or overrides them. This skill exists because
writing and reviewing are different stances: the writing pass attends to making the thing work,
and what it misses is only found by re-reading the finished diff with the question "does this
follow the rules" — a question the writing pass believes it already answered.

## When

Only when the user asks for the pass — never self-invoked. Once asked, run it after the work
compiles and its tests pass, where there is anything to run. Not earlier: reviewing while still
building is the writing pass wearing a costume. What the pass covers is the diff, not what it is
made of — docs, skills, and config diffs get the same walk as code. Only a task that produced no
diff has nothing for this pass.

## One question, one agent

Establish the scope of the **whole diff** — staged, unstaged, and untracked files alike — then
hand each question below to its own fresh agent, every agent walking the whole diff from the top.
An agent is handed the scope and its question, nothing else: a digest of what to look at is the
writer's judgement about what matters, which is the one thing the pass exists to route around.

One reader carrying several questions answers them all out of a single reading, each answer shaped
by the ones already reached — a question that already feels settled tends to stay settled.
Separate agents make the separation structural rather than a discipline: no mind carries two
walks, so nothing one walk concluded can shape another. Who staffs each question is CLAUDE.md's
to say; what cannot vary is one question per agent, and no agent that carries the authoring
context.

Reading only the parts that felt risky is the writer's judgement in another costume; the misses
live precisely where nothing felt risky. Every walk covers all of it.

## The lenses

1. **Placement and naming — the `architecture` lens.** Every new or moved file, class, folder and
   export, checked against the layout, the component types, and the naming rules — `architecture`
   for the backend and `ui-component-design` for the frontend layers.
2. **Comments — the `commenting` lens.** Every comment the diff adds *or now sits beside*: does it
   break a rule, and were already-broken neighbours fixed rather than left, as those rules demand.
3. **Tests — the `designing-tests` lens.** Every test the diff touches: named for a guarantee,
   coupled to a promise, enacting through a real entry point rather than hand-built input.

The two below are questions rather than lenses — no skill owns them — but they are whole-diff
readings on the same terms.

A skill that declares itself an extra lens of this pass joins on the same terms when its trigger
matches the diff: its own fresh agent, the whole scope, one question.

## The boilerplate question

**Point at every part of this diff that the next one of its kind would copy verbatim. Each one is
either a convention that deserves a name — a directive, a component, a helper, a concern — or a
decision to leave it raw, stated out loud in the report.**

This is the architecture skill's probability rule aimed at lines instead of folders. Three lines
of scaffolding repeated per page is structure wearing plain clothes, and the first instance is the
cheapest moment it will ever have to become one line with a name.

## The accommodation question

An accommodation is correct where it sits — which is why somebody wrote it there, and why no lens
catches it:

**Point at every line here that exists to tolerate something outside this change. Each one is a
finding about that thing, not a line to review.**

Report what is being tolerated, and whether it can be answered at its source. What must not be
proposed is tidying the accommodation — naming it, lifting it somewhere shared. That is the answer
the question above would give, and here it is the wrong one: it makes the tolerated thing permanent
and retires the only evidence that anyone should still be asking about it.

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

**The pass reports; nothing in it edits.** A walk that begins fixing has swapped back into the
authoring stance this pass exists to get out of, and what it reviewed is no longer what is there.
It also holds the findings without the priorities: which of them are worth a change, and whether
now is when, is not a judgement this pass stands in the right place to make.

A finding is reported or it does not exist — never quietly dropped, and never fixed instead of
said. One weighed and rejected is reported with the reason: silence and a verdict read the same to
whoever holds the report.

Fixing is a separate task, asked for separately, and the diff it authors is owed its own pass.

The pass ends with one line: what was walked, and what was found. "Reviewed, nothing found" is a
legitimate outcome and is still said.
