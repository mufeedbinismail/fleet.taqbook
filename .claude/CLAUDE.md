# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Before writing backend code

Invoke the `architecture` skill before writing **any** backend code — creating a class, picking a
folder, naming a file, or judging whether existing code sits in the right place. Never guess the
structure.

## Project Overview

Taqbook is a Laravel 10 application wrapping a **legacy PHP accounting system** (FrontAccounting). The core purpose is to run the legacy codebase through Laravel's HTTP layer while incrementally porting modules to proper Laravel architecture.

## Environment

See `.claude/CLAUDE.local.md` for environment specific PHP binary paths, local configurations like db name