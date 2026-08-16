# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Before writing backend code

Invoke the `architecture` skill before writing **any** backend code — creating a class, picking a
folder, naming a file, or judging whether existing code sits in the right place. Never guess the
structure.

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

## Project Overview

Taqbook is a Laravel 10 application wrapping a **legacy PHP accounting system** (FrontAccounting). The core purpose is to run the legacy codebase through Laravel's HTTP layer while incrementally porting modules to proper Laravel architecture.

## Environment

See `.claude/CLAUDE.local.md` for environment specific PHP binary paths, local configurations like db name
