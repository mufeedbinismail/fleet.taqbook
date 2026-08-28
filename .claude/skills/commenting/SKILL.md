---
name: commenting
description: Hard rules for docblocks and comments. Consult before writing, keeping, or reviewing ANY comment or docblock — when adding a class, function, or test, when refactoring, and when auditing a diff. Applies even when the surrounding file's existing comments look nothing like these rules, and especially during long sessions where the file's own style starts to feel like the standard.
---

# Comment rules

General Rule: **DO NOT COMMENT**. Let the code speaks for itself.
If Commenting: **MUST CLEAR TWO GATES**. A comment clears both or it does not go in.

## Gate 1: it must tell the reader something the code did not

Write only what the reader would not have had from the code in front of them. Truth is not the
bar — a block restating the signature or the control flow is true and worth nothing. Delete it
in your head and name what the reader now fails to know; no answer, no comment.

## Gate 2: it must stay true

A comment must stay true no matter what anyone edits in another file or another function. If an
edit elsewhere could falsify it, it does not belong here. No exceptions, no "but it's useful
context".

Judgment gets rationalized at writing time, so these are checked as text, not as intent.
A comment is deleted if it contains:

- The name of another class, file, function, or test, asserting what it does
- The words **caller**, **callers**, **call site**, or any account of how or where this code is
  invoked or reached — *"a caller reaches for whichever..."*, *"what the import job uses"*.
  Write plain `@internal`, never `@internal <caller> only`
- A named part of the system that lives elsewhere — **the browser**, **the panel**, **a
  screen**, **the shell**, **the database**, **a form** — with a claim about what it does,
  sends, compares, or draws
- A value, key, default, or behaviour defined elsewhere — *"matches the legacy constants"*,
  *"defaults to true"*
- The construct beneath it, named — *"a loop here instead of a map"*. Give the decision, not
  the shape it took
- Anything that does not exist yet, or planned work — *"phase 2 fixes this"*, *"once the old
  system is gone"*
- An explanation of a neighbouring function instead of this one
- A type the signature already declares

The reason these are word-tests rather than principles: a fluent writer can frame any caller
narrative as "context" and any cross-file claim as "an assumption". A word-test cannot be argued
with mid-sentence.

## The length cap

- A docblock is **one sentence** by default. A comment is one line.
- Two or three lines are allowed only when stating: an invariant this code holds by itself; the
  why of a non-obvious local decision; a hidden assumption invisible from the code; or an array
  shape / a type the signature does not carry.
- Nothing longer goes in without first naming — to yourself, explicitly — which single keep
  rule it clears. Then cut it to the shortest form that still carries the fact. Length is a
  claim of subtlety: an ordinary fact arriving after a paragraph sends the reader back through
  it hunting for what they missed. Fluency is not evidence of keep — a well-turned paragraph is
  *harder* to catch as padding than a clumsy one, which is why the cap is a number and not a
  feeling.

## the file you are in is not the style guide

The comments already sitting in this codebase are **not** evidence of what a new comment may
look like. Some of them predate these rules and break them; they are defects to repair, not a
voice to match. If a rule here conflicts with the tone or length of the comments around your
cursor, the rule wins. When editing a file, delete or fix any existing comment that breaks a
rule below — do not leave it, and do not imitate it.

## Worked examples

**Bad — real output this skill failed to stop:**

```php
/**
 * Whatever a caller handed a date field, as an instant — or null where it names none.
 *
 * A caller reaches for whichever of these its own code already holds: a column read off the
 * database is text in the fixed spelling, a value coming back off a form is text in the
 * preference, and anything that has been through the domain is an object. [...]
 */
public static function read(DateTimeInterface|string|null $value, string $format): ?DomainDateTime
```

Every sentence is about callers (deny), the claims drift when call sites change (gate 1), and
the union type is already in the signature (gate 2). **The compliant version is no docblock at
all**: the accepted types are in the signature, the fallback order is on the `foreach` line, and
everything else was a story about other files.

**Bad → good — a real seven-line comment and its one-line survivor:**

```js
// The element this is written on carries no x-data of its own, and the walk Alpine makes on
// start only visits elements that announce themselves as somewhere to start from. The shell
// announces itself today, so a pair inside it is reached anyway — but only while the shell is
// drawn: [...four more lines...]
Alpine.addInitSelector(() => '[x-date-range]');
```

"The shell announces itself today" is a claim about another file with an expiry date in it. The
one fact the reader could not get from the code is a framework behaviour — ours to state, not
ours to drift:

```js
// Alpine's init walk only starts from registered selectors; declaring a directive is not one.
Alpine.addInitSelector(() => '[x-date-range]');
```

## Worth keeping, once deny and both gates are cleared

- What this code guarantees on its own; the invariant it holds
- Reasoning behind a non-obvious local decision — why it ended up this way, not what it does
- Hidden assumptions not visible from the code
- Business logic weird enough that someone would "fix" it by accident
- Surprising framework or language behaviour — not our code, does not drift with our edits
- Types when NOT type-hinted; array shapes (`@param {id: number, name: string}[] $users`);
  narrowing a generic

If the useful fact belongs to another file, **put it in that file**. Do not mirror it.

## The final pass — mandatory, not optional

Before finishing any task that touched code:

1. Diff your changes and read **only the comments**, stripped of the code that makes them feel
   earned.
2. Run each one — new or pre-existing in a file you touched — through the deny-list word-tests,
   then the two gates, then the length cap.
3. **Assume at least one violation exists and go find it.** You wrote these comments; you are
   the least qualified reader they will ever have. If after honest search none exists, state
   that explicitly in your summary — the statement is what proves the pass happened.
