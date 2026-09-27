---
name: writing-skills
description: Use before creating a SKILL.md file, before adding a rule to or revising an existing one, and when reviewing a skill file. Covers what belongs in a skill, which skill a rule goes in, how the frontmatter description gets the skill invoked, and the register every skill in this setup is written in. Applies to SKILL.md files and nothing else.
---

## What This Skill Applies To

Apply these rules to SKILL.md files and to nothing else. Nothing here is guidance for writing code, help text, documentation, commit messages, or replies to the user, and the standard for what belongs in a skill is not a standard for what belongs in any other kind of writing. A skill about skills gets generalised into a theory of all writing unless the skill itself forbids that, so the moment the file in hand is not a skill, none of this applies to it.

## Earning and Recording a Rule

Write a skill as guidance that makes a decision come out right at the moment it would otherwise go wrong. A description of the codebase, a tour of its features, or a record of what was built says what exists and turns no decision, so the decision goes the way it would have gone with no skill loaded. Ask of each paragraph which decision it turns, and remove the paragraph that turns none.

Add a rule only because a decision actually went wrong, taken wrongly in the work or caught in review, and the rule would have prevented it. A rule written speculatively, or to make a section look complete, has no failure behind it to show what it prevents or whether it is even right. Ordinary professional competence that any practitioner already has is not a rule either; the skill exists for the decisions competence did not save.

Record the decision that went wrong, not the incident and not the specific fix. The incident stops being relevant when that code is finished, and the fix stops being relevant when the implementation changes, while the decision comes round again in every piece of work of the same kind. Imagine the code that prompted the rule deleted; the rule must still make sense.

Keep the change that prompted a rule out of the skill. Do not enumerate the controls, props, or features of that change; generalise so that the rule holds for consumers that do not exist yet. The consumers that exist now are where the decision was seen to go wrong, not the only ones it goes wrong for, and a list of them is read as the whole extent of the rule. Let a concrete name appear only where the name itself is the convention.

Point an example at the situation where the rule applies, the case people actually get wrong, and not at a code specimen. A specimen gets copied where the rule should have been applied, so the output takes the specimen's shape in cases where that shape is wrong. Where a skill's product is prose, as with a skill that shapes how a reply to the user is written, give a short sample of the intended prose and label it as a sample.

State a rule once, in one place, at full strength, and let the rest of the skill lean on it. The same rule summarised at the top, restated inside another rule, and recalled again in an example gets weaker each time it is echoed, and a change to it has to reach every echo or the copies drift apart. Merge the echoes into the single sharpest statement and delete the rest. This is about where a rule lives in the skill: a heading that says what its section covers is not an echo, and neither is a reply that restates a fact where its reader needs it.

## Filing a Rule and Dividing Skills

Put a rule in the skill that is open at the moment that decision gets made. That is not always the skill about the subject where the problem happened, and a rule filed by where the problem showed up is not loaded when the decision is next made. Ask which skill is in front of whoever is deciding at that moment, and file the rule there.

The confusable case is a problem that surfaced in one area of the work but was decided in another. The rule reads as being about the area where it surfaced and gets filed under it, and the next time the decision is made that file is closed.

Divide skills by working situation, not by subject. If two skills would always be wanted in the same sitting they are one skill, and a split is right only where the sittings differ. A split that follows subject leaves one sitting needing two files, and whichever file is not loaded is the one whose rules go missing.

## Register

### The Paragraph of a Rule

Open every paragraph with a command addressed to whoever is doing the work, and make each paragraph one rule. Do not describe what is done, do not write a sentence about the model, the reader, or the skill, and do not announce what a section is about to do. Whatever register a skill is written in comes back out in the work done under it, so a skill written as description of the work produces description of the work where the work itself was wanted.

Follow the command with its reason where there is one, one or two plain sentences saying why the rule holds or what goes wrong without it, and keep the paragraph to two to five sentences. The reason is what lets the rule reach a case it did not name; without it the rule is applied only where it is recognised and dropped everywhere else. Close the paragraph, where it helps, with the test, or with the command restated in operational terms.

Never invent a reason. A rule's reason comes from the decision that went wrong when the rule was earned, and where that is not known, state the command bare rather than supply a plausible one. The reason is what gets applied to the case the rule did not name, so an invented reason sends that case the wrong way while sounding as authoritative as an earned one; a bare command is applied only where it is recognised, which is the lesser harm.

Give a rule that is hard to recognise in the moment a second short paragraph describing the confusable case, so that it is known when met. That is the only extension a rule gets. A rule never gets an essay, because an essay is exposition, and exposition comes back out as exposition about the work instead of the work.

Use ordinary words, and call each thing by its own name every time it appears. Write no fragments standing in for sentences, no labels or codes standing in for a thing that has its own name, no abbreviations or coined shorthand, and no cross-references that send the reader elsewhere in the file, such as "see above" or "as described below". Compression and back-and-forth in the file come back out as fragmented, label-ridden output that the reader has to assemble.

### Shape and Length of the File

Make the default shape of a rule a command with its reason in a paragraph, because that is the shape that comes back out as work done. Use a table, a diagram, or a list wherever it conveys a rule's content better than sentences do, as with a catalogue of types, a matrix of cases against outcomes, or a dependency order. Hold that structure to a containment standard: self-contained, nothing the reader has to look up elsewhere, and fitted to the width it will be read at. Put identifiers, paths, and code in backticks or fenced code blocks. All of this binds the skill file only and says nothing about the shape of what is produced for the user while the skill is loaded.

Head each section with a title that describes everything the block under it holds, such as "Deliverables and Their Waves", written after reading every paragraph in the block. The reader scans headings to find a section, so a heading that names one rule from the block, or an abstract noun for its subject, sends them past what they came for. Where a block holds parts the reader will want to find on their own, nest a subsection heading over each part, and keep the parent heading covering the whole.

Put long derivations, measurements, and alternatives that were considered in a `references/` folder beside the SKILL.md, not in the body. A derivation is consulted when a rule is doubted, not when it is applied, and in the body it reads as exposition among the commands.

Start the skill with its first rule and end it with its last. Write no introduction, no summary, and no closing; a passage that says what the skill is about turns no decision, and it comes back out as commentary on the work.

Let length follow from the rules. Token count is not a concern, so never trim a reason or drop a confusable case to make a skill shorter, and never pad a rule past what its command and its reason need.

## Vocabulary and the Output It Shapes

Say, wherever a skill coins names for the steps or concepts of its own procedure, that those names are internal to the skill and must not appear in what the user reads. A name the procedure needed is otherwise carried into the output as though the user shared it, and the user has never seen it.

Where the procedure ends in something the user reads, say in the skill what that output looks like, stated for that output and in the skill's own terms. A skill written in commands and paragraphs otherwise passes its own register on to the reply, and the shape of the output is improvised at the end out of whatever the procedure was using.

Hold that output to this standard, and have the skill state it: each thing in the output takes the shape that conveys it best, whether sentences, a table, a diagram, a heading with a section under it, or a list. The goal is that whoever reads it understands the outcome, so a shape that conveys the outcome better than prose is the right shape.

Allow nothing in that output that makes the reader move: no pointer back to something read earlier, no pointer forward to something coming, no label or code defined in one place and used in another, and no fact given in two places so that the reader has to reconcile them. Everything the reader needs to understand one thing sits in one place, as one self-contained group, and the reader reads once, top to bottom, without shifting between places. A shape is chosen for what it conveys, and a pointer conveys nothing but the distance to what it points at.

## Writing the Description

State in the frontmatter `description` when to invoke the skill, in terms of the work about to be done, such as before creating any class or when a test fails, and not merely the topic. The description is all that is read when the choice to invoke is made, and one that only names a subject does not get the skill invoked when it is needed, because the need arrives as a piece of work about to happen and not as a subject coming up. Test the description by asking whether it names a moment that would be recognised while working.
