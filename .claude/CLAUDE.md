# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Taqbook is a Laravel 10 application wrapping a **legacy PHP accounting system** (FrontAccounting). The core purpose is to run the legacy codebase through Laravel's HTTP layer while incrementally porting modules to proper Laravel architecture.

## Environment

See `.claude/CLAUDE.local.md` for environment specific PHP binary paths, local configurations like db name

## The review pass

Invoke the `reviews-before-done` skill only when the user asks for a review pass — never on your
own initiative. When asked, standing permission: spawn its per-question agents in parallel,
without asking first.

Staffing, which is this file's to say:

- `review-lens` takes the checklist-shaped questions: the architecture, commenting, and
  designing-tests lenses, and the boilerplate question.
- `review-judgment` takes the judgement-heavy ones: the accommodation question and case law.
- An extra lens a skill declares for the diff at hand is staffed by its shape, not by being extra:
  `writing-css` is checklist-shaped and goes to `review-lens`; `refactor-blast-radius` is
  judgement-heavy and goes to `review-judgment`.

## Naming

Name from the call site, not from inside the file. Inside the file the work is what is visible, so
the work is what gets written down; the caller sees only what it asked for and what it gets. Answer
both questions in every name: what it is about, and what it hands back.

Let the argument carry the subject and supply only the product. Do not spell a relation the
argument already carries.

Spell the product in the form of its kind: a value or collection is a noun; a yes/no is a
proposition, either a third-person verb or an is/has/can form where the rest is not a verb; a count
is a count, never the rows it counted.

Reject names that report the work instead of the product. A participle is a noun with its head
missing, and the missing head is what the caller came for: name it, or for a yes/no make it a
proposition.

Name a transformation for its operation: the verb, the criterion, and what it worked on as a
concrete noun — plural for a set, singular for one thing — never the type that comes back. A
transformation hands back what it was given, changed, so the operation is the outcome and a
product noun there claims something the receiver never made. Short of all three the name reads
as a proposition: a participle object asks whether it is, an abstract object names an act
answered yes or no, and get names no operation at all.

A factory is an operation on its class: the class supplies the subject, so name the method for the
operation and where the value came from. A participle is sound there, because the class name on the
same line is its head.