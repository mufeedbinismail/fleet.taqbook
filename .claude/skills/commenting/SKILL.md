---
name: commenting
description: Rules for every docblock and comment in this codebase's PHP and JavaScript. Invoke before writing, keeping, or reviewing any comment or docblock, which means when adding a class, function, or test, when refactoring, and when auditing a diff. Invoke it even when the comments already in the file look nothing like these rules, and especially late in a long session, when the file's own style has begun to feel like the standard.
---

## Whether to comment

Do not comment; let the code speak for itself. A comment is the one part of a file nothing tests: wrong code fails, but a wrong comment is read and believed, and that is why the bar is strict. Let a comment in only when it passes the first gate, the second gate, and the length cap.

Let a comment through the first gate only when it tells the reader something they would not get from the code at the pace they read it. Measure against a reader passing through, not one who stops to reason the code out: a fact that can be reconstructed from the code but does not surface on a reading is exactly what a comment is for, and measuring by what the code could yield instead deletes the comment that would have spared the reader the reconstruction. True is not enough; a block that restates the signature or the control flow is true and worth nothing. Delete the comment in your head and name what the reader would now have to stop and work out; if there is nothing, there is no comment.

Do not take a comment for a reason because it is phrased as one. Naming the construct beneath the comment, a loop where a map was possible, states the shape the decision took and not the decision, and repeating a type the signature declares states what the reader has just read; both arrive as additions and add nothing. Give the decision, not the shape it took, and where the signature carries the type, say nothing of it.

Let a comment through the second gate only when it stays true through the ordinary edits anyone makes to another file or another function. An ordinary edit changes implementation detail and is made without ceremony, and the person making it will not know this comment exists, so a comment that rests on how another file happens to work today is false the moment that file is tidied. A comment may rest on what another file promises, such as what a schema guarantees about a column, because a promise is a fixed fact that an ordinary edit does not touch. Ask of the fact whether the other file promises it or merely does it today; that the context feels useful is not the question.

Do not let how another file works today in because it arrives framed as context, as an assumption, or as a warning about what is coming. An account of how or where this code is reached, of who calls it and with what, is implementation detail of other files however it is framed, and a note about what a later change will do describes code that does not exist; both are false after the next ordinary edit. Count as another file any part of the system that lives elsewhere, such as the browser, the shell, the database, or a form. Mark visibility with plain `@internal` and no name after it.

## What to keep

Name, before writing a comment, which kind of fact it carries, and write it only when it is one of these. Keep what this code guarantees on its own, its invariant. Keep the reasoning behind a non-obvious local decision, why it ended up this way rather than what it does. Keep a hidden assumption the code does not show, and business logic strange enough that someone would fix it by accident.

Keep surprising framework or language behaviour, which is not our code and does not drift with our edits. Keep a type where the code is not type-hinted, an array shape such as `@param {id: number, name: string}[] $users`, and the narrowing of a generic.

Treat a comment that cannot be placed among these kinds as one argued through a gate rather than passed. Judgement gets rationalised at writing time, and a fluent writer can frame a caller narrative as context and a cross-file claim as an assumption, but neither can be placed on this list, which is why the placing happens before the writing and not after.

Put a fact about another file in that file, and a fact about a neighbouring function on that function; do not mirror either onto the code the comment sits on.

## Length

Write a docblock as one sentence and a comment as one line. Length is read as a signal of subtlety, so an ordinary fact that arrives after a paragraph sends the reader back through the paragraph hunting for what they missed. Fluent prose is harder to recognise as padding than clumsy prose, which is why the cap is a number and not a feeling.

Take two or three lines only when the one fact the comment was placed as cannot be stated in one. Before writing anything longer, cut it to the shortest form that still carries that fact, and take the cut form.

## The file around the cursor

Do not take the comments already in the file as evidence of what a new comment may look like. Some predate these rules and break them; they are defects, not a voice to match. When a rule here conflicts with the tone or length of the comments near the cursor, the rule wins.

Fix or delete an existing comment that breaks a rule only when the work touches the code it sits on, and leave every other comment in the file as it stands, however plainly it breaks a rule. The work is the work that was asked for, and a repair on code the task did not touch is a change the task did not ask for; a comment left standing is still not a voice to match.

## Two cases

Write no docblock on a function whose signature already carries the types it accepts and whose body shows the order it tries them in, however much there is to say about where each kind of value comes from. The types fail the first gate because they are in the signature, and the rest is a story about the files the values came from, which fails the second.

Cut a comment that explains a framework behaviour by way of the files that happen to satisfy it today down to the behaviour alone. The behaviour is not our code and does not drift with our edits, while every sentence about which of our files satisfies it has an expiry date built in.

## The final pass

Diff the changes before finishing any task that touched code, and read only the comments, stripped of the code that makes them feel earned. Run each comment, new or already sitting on code the diff touches, through the two gates, place it among the kinds of fact worth keeping, then hold it to the length cap. the author is the least qualified reader the comments will ever have. If an honest search finds none, say so explicitly in the summary, because that statement is what shows the pass happened.
