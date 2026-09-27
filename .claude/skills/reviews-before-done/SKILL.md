---
name: reviews-before-done
description: Invoke when the user asks for a review of the diff a task produced, once the work compiles and its tests pass. Walks the whole diff, code, documentation, skills, and configuration alike, one question per fresh agent against the rules the other skills own, and reports findings without editing. Never invoke unasked.
---

## Stance

Take every rule the pass checks from the skill that owns it, and take that skill as the authority: restate none of its rules and override none. The pass exists because writing and reviewing are different stances. The writing pass attends to making the thing work, and what it misses is found only by re-reading the finished diff with the question "does this follow the rules", a question the writing pass believes it already answered.

## Timing

Run the pass only when the user asks for it, and once asked, run it after the work compiles and its tests pass, where there is anything to run, and not earlier. Reviewing while still building is the writing pass in disguise.

Walk the diff whatever it is made of, and give documentation, skills, and configuration the same walk as code. Only a task that produced no diff has nothing for the pass.

## Agents

Take the whole diff as the scope, staged, unstaged, and untracked files alike, and cover all of it in every walk. Reading only the parts that felt risky is the writer's judgement in another form, and the misses are precisely where nothing felt risky.

Hand each question to its own fresh agent, and have every agent walk the whole diff from the top. One reader carrying several questions answers them all from a single reading, with each answer shaped by the ones already reached, and a question that already feels settled stays settled. Separate agents make the separation structural rather than a matter of discipline, because no mind carries two walks.

Give an agent the scope and its one question and nothing else. A digest of what to look at is the writer's judgement about what matters, which is the very thing the pass exists to bypass.

Take which agent carries which question from the repository's `CLAUDE.md`, which sends the checklist-shaped questions to `review-lens`, the judgement-heavy ones to `review-judgment`, and staffs an extra lens by its shape. That file owns the assignment; what cannot vary is one question per agent and no agent that carries the authoring context.

## Lenses

Check every new or moved file, class, folder, and export against the layout, the component types, and the naming rules. Take `architecture` as the authority for the backend and `ui-component-design` for the frontend layers.

Check every comment the diff adds or now sits beside, with `commenting` as the authority. Ask two things of each: whether it breaks a rule, and whether an already-broken neighbour was fixed rather than left, since that skill requires the fix.

Check every test the diff touches, with `designing-tests` as the authority. Ask of each whether it is named for a guarantee, coupled to a promise, and enacted through a real entry point rather than hand-built input.

Add any skill that declares itself an extra lens of the pass when its trigger matches the diff, on the same terms as the three lenses: its own fresh agent, the whole scope, one question. `refactor-blast-radius` joins when the diff reshapes something that existed; `writing-css` joins when the diff touches a stylesheet or writes a class.

Put the boilerplate question and the accommodation question on the same terms, though no skill owns them. Each is a whole-diff reading with its own fresh agent, the whole scope, and one question.

## Boilerplate

Point at every part of the diff that the next change of its kind would copy verbatim. Each such part is either a convention that deserves a name, whether a directive, a component, a helper, or a concern, or a decision to leave it raw, stated out loud in the report. This is the thinking `architecture` applies to folders, where a thing is dropped because it is improbable and never merely because it is unused, applied to lines: three lines of scaffolding repeated on every page is structure without a name, and the first instance is the cheapest moment it will ever have to become one line with a name.

## Accommodation

Point at every line that exists to tolerate something outside this change. An accommodation is a line that is correct where it sits, which is why somebody wrote it there and why no lens catches it. Treat each one as a finding about that outside thing, not as a line to review: say in the report what is being tolerated and whether it can be answered at its source.

Do not propose tidying an accommodation by naming it or lifting it somewhere shared. That is the answer the boilerplate question would give, and here it is the wrong one, because it makes the tolerated thing permanent and removes the only evidence that anyone should still be asking about it.

## Case law

Look for any convention the change created, altered, or retired, and report it when the skill that documents that convention did not change in the same diff. A convention that lives only in the code is one session away from being unwritten by someone following the skill faithfully.

Test for a convention by the next decision, not by the new structure. An entry is earned where a future change could go wrong while still looking locally right, and above all where the skill's current text would instruct the wrong thing. What one glance at the code settles, the code documents by existing; a fact that guards a single file belongs in that file's own comment; writing either kind into a skill turns a decision procedure into a catalogue.

## Report

Report and never edit. A walk that starts fixing has swapped back into the authoring stance the pass exists to leave, and what it reviewed is no longer what is there. Fixing is a separate task, asked for separately, and the diff it produces is owed its own pass.

Hold findings without priorities. Which findings are worth a change, and whether now is the time, is not a judgement the pass is placed to make.

Report a finding or it does not exist: never quietly dropped, never fixed instead of reported. Report a finding weighed and rejected together with the reason, because silence and a verdict read the same to whoever holds the report.

Give each finding whatever shape conveys it best, whether sentences, a table, a diagram, a heading with a section under it, or a list. The report exists so that the user understands each finding and the outcome of the pass, and a shape that conveys a finding better than prose is the right shape for it. Findings from one lens may read best as a section, several findings of the same kind across files as a table, and an accommodation with the thing it tolerates as a two-part entry.

Put everything the reader needs to understand one finding in one place, as one self-contained group: what was found, where, which rule it answers to, and the reason, without sending the reader to another finding or to the skill that holds the rule. Allow nothing that makes the reader move: no pointer back to something read earlier, no pointer forward to something coming, no label or code defined in one place and used in another, no fact given in two places so the reader has to reconcile them. The reader reads once, top to bottom, without shifting between places.

Fit a table or diagram to the terminal width, about eighty columns, with no wrapping or clipping. One that clips conveys nothing.

End with one line saying what was walked and what was found. "Reviewed, nothing found" is a legitimate outcome and is still said.
