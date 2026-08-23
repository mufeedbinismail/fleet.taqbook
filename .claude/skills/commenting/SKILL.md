---
name: commenting
description: Hard rules for docblocks and comments. Invoke before writing, editing, or reviewing any comment or docblock — including when adding a class, function, or test, and when auditing a diff for stale comments.
---

# Comment rules

Two gates. A comment clears both or it does not go in.

## It must stay true

**A comment must stay true no matter what anyone edits in another file or another function.**
If an edit elsewhere could falsify it, it does not belong here. No exceptions, no "but it's useful
context".

## It must tell the reader something the code did not

**Write only what the reader would not have had from the code in front of them.** Truth is not the
bar — a block restating the file's name, the signature, or the shape of the control flow is true and
worth nothing. Delete it in your head and name what the reader now fails to know; no answer, no
comment.

**Then say it in the shortest form that carries it.** Length is a claim of subtlety, so an ordinary
fact arriving after a paragraph sends the reader back through it hunting for what they missed.
Fluency is not evidence of keep: a well-turned block restating a fallback chain is harder to catch
as padding than a clumsy one.

## NEVER write a comment that

- Names another class, file, function or test and asserts what it does — *"the validator drops these"*, *"covered by the integration test"*
- Says how or where this code is called — *"the sub-builders call this for you"*, *"what the import job uses"*. Write plain `@internal`, never `@internal <caller> only`
- Restates a value, key, default or behaviour defined elsewhere — *"matches the legacy constants"*, *"defaults to true"*
- Names the construct beneath it — *"a closure rather than a chain"*, *"a loop here instead of a map"*. Give the decision, not the shape it took; the shape gets rewritten without anyone rereading the line above it
- Names something that does not exist yet, or planned work — *"phase 2 fixes this"*, *"the new resolver will answer that"*, *"once the old system is gone"*
- Explains a neighbouring function instead of this one
- Repeats a type the signature already declares

## Worth keeping, once both gates are cleared

- What this code guarantees on its own; the invariant it holds
- Reasoning behind a non-obvious local decision — why it ended up this way, not what it does
- Hidden assumptions not visible from the code
- Business logic weird enough that someone would "fix" it by accident
- Surprising framework or language behaviour — not our code, does not drift with our edits
- Types when NOT type-hinted; array shapes (`@param {id: number, name: string}[] $users`); narrowing a generic

## Two habits

- If the useful fact belongs to another file, **put it in that file**. Do not mirror it.
- When editing a file, **delete or fix any comment already there that breaks a rule above**. Do not leave it.
