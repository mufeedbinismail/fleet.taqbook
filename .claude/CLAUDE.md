# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Taqbook is a Laravel 10 application wrapping a **legacy PHP accounting system** (FrontAccounting). The core purpose is to run the legacy codebase through Laravel's HTTP layer while incrementally porting modules to proper Laravel architecture.

## Architecture

### Folder Structure

The project uses a **domain-first, component-second** layout — the inverse of Laravel's default. Top-level folders under `app/` are domains, and component types (`Provider`, `Http`, `Model`, `Service`, `Exception`, etc.) are nested inside each domain. This keeps all code for a domain together without the full ceremony of DDD (no separate domain/infra/http layers). It is a work in progress.

`App\Foundation` is the cross-cutting infrastructure namespace (providers, middleware, base classes). `App\Legacy` wraps the legacy PHP accounting system. New domain work goes under its own top-level namespace (e.g. `App\Finance`).

**Naming convention: always use singular for folder and class names** — `Controller` not `Controllers`, `Setting` not `Settings`, `Entity` not `Entities`, etc. This applies to every component type folder and any new class names. It is a deliberate team convention to eliminate guessing.

## Coding Conventions

### New code follows the Laravel DDD-inspired architecture
All new code (entities, repositories, services, collections, queries) goes under `app/<Domain>/` following the project structure. Do **not** add new functions to FrontAccounting `.inc` files. Legacy `.inc` files may call into Laravel classes via `app(ClassName::class)` — that is the bridge pattern. FrontAccounting is actively being ported; every new feature should be written in the Laravel layer.
