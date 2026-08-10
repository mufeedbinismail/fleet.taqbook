# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Skills — check before writing code

| Skill | Invoke it when |
|---|---|
| `architecture` | **MANDATORY** before writing **any** code — creating a class, picking a folder, naming a file, or judging whether existing code sits in the right place. Defines the domain-first layout and what each component type may touch. Never guess the structure. |
| `commenting` | **MANDATORY** before writing, editing or reviewing **any** comment or docblock — a new class, function or test included. Hard rules, stricter than the default. Never write comments from memory. |
| `designing-tests` | **MANDATORY** before writing or editing **any** test, before agreeing a set of cases, and whenever a test fails or needs updating. Decides what is worth a case and what the case is allowed to know. |

## Project Overview

Taqbook is a Laravel 10 application wrapping a **legacy PHP accounting system** (FrontAccounting). The core purpose is to run the legacy codebase through Laravel's HTTP layer while incrementally porting modules to proper Laravel architecture.

## Environment

See `.claude/CLAUDE.local.md` for environment specific PHP binary paths, local configurations like db name