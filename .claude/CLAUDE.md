# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Taqbook is a Laravel 10 application wrapping a **legacy PHP accounting system** (FrontAccounting). The core purpose is to run the legacy codebase through Laravel's HTTP layer while incrementally porting modules to proper Laravel architecture.

## Architecture

### Folder Structure

The project uses a **domain-first, component-second** layout — the inverse of Laravel's default. Top-level folders under `app/` are domains, and component types (`Provider`, `Http`, `Model`, `Service`, `Exception`, etc.) are nested inside each domain. This keeps all code for a domain together without the full ceremony of DDD (no separate domain/infra/http layers). It is a work in progress.

`App\Foundation` is the cross-cutting infrastructure namespace (providers, middleware, base classes). `App\Legacy` wraps the legacy PHP accounting system. New domain work goes under its own top-level namespace (e.g. `App\Finance`).

**Naming convention: always use singular for folder and class names** — `Controller` not `Controllers`, `Setting` not `Settings`, `Entity` not `Entities`, etc. This applies to every component type folder and any new class names. It is a deliberate team convention to eliminate guessing.

DO NOT further subdivide the top level domains. Smaller features that belongs - goes flat to the main domain. Only features that are qualified to have its own subdomain gets a subfolder.
eg. Finance/Ledger & Finance/Banking are qualified subfolder because they could house further subfolder Sales/Customer is not a subfolder because it ends with Customer. there is no dividing customer any further

## Environment

See `.claude/CLAUDE.local.md` for environment specific PHP binary paths, local configurations like db name 

### New code follows the Laravel DDD-inspired architecture
All new code (entities, repositories, services, collections, queries) goes under `app/<Domain>/` following the project structure. Do **not** add new functions to FrontAccounting `.inc` files. Legacy `.inc` files may call into Laravel classes via `app(ClassName::class)` — that is the bridge pattern. FrontAccounting is actively being ported; every new feature should be written in the Laravel layer.

### Docblock & Commenting Etiquette

#### DO NOT comment:
- Obvious code (e.g. `// increment i` above `i++`)
- How something is used elsewhere in the codebase — that belongs in the caller, not here
- Anything a reader can already infer from reading the code itself
- Type information in a docblock if the function signature is already strongly typed

#### DO comment:
- Type info that helps IntelliSense when arguments/returns are NOT type-hinted
- The shape of structured or scalar arrays (e.g. `@param {id: number, name: string}[] users`)
- Specific/narrowed types when the type hint is generic (e.g. `T` is actually always `string` here)
- Hidden assumptions or dependencies that aren't visible from the code alone
- Weird or non-obvious business logic that would otherwise get "fixed" by accident later
- The *reasoning* behind a decision that took back-and-forth to settle — why it ended up this way, not just what it does