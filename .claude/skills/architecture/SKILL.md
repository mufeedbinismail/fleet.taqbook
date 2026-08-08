---
name: architecture
description: Where code goes in this codebase — domain-first layout and every component type (Entity, Model, ValueObject, DTO, Intent, Collection, Repository, Query, Action, Service, Registry, Builder, Source, Condition, Cart, Support, Facade, Contract, Concern, Http/*). Invoke before creating any class, choosing a folder, or naming a file, and when reviewing whether existing code sits in the right place.
---

# Architecture

## Layout: domain first, component second

Laravel's default is inverted. Top-level folders under `app/` are **domains**; component-type
folders nest inside them.

```
app/<Domain>/<ComponentType>/<Class>.php
app/Finance/Tax/Repository/TaxRepository.php
```

- `App\Foundation` — cross-cutting infrastructure (providers, middleware, base classes).
- `App\Legacy` — the FrontAccounting wrapper. Nothing outside it may extend it.
- `App\Shared` — types belonging to no single domain.
- New domain work gets its own top-level namespace.

**Singular everywhere.** `Controller`, not `Controllers`. `Entity`, `Setting`, `Action`. Folders
and class names alike. Deliberate, so nobody has to guess.

**Do not subdivide a domain further than it earns.** A subfolder is justified only when it could
itself hold subfolders. `Finance/Ledger` and `Finance/Banking` qualify. `Sale/Customer` does not —
a customer divides no further. Everything unqualified goes flat in the domain.

## Choosing a type

Pick by what the class is *allowed to touch*, not by what it is about. Two rules do most of the
work:

- **Only a Repository touches the database.** A Service that runs a query is in the wrong folder.
- **Only an `Http/Request` reads raw input.** Past that boundary everything is typed and validated.

### Data

| Type | What it is |
|---|---|
| **Entity** | A domain object **with identity**. Two with the same id are the same thing. If eloquent counterpart exists, entity is used sparingly. (only when eloquent is too heavy or causes serialization risk). |
| **Model** | Eloquent. The *table*, relationships, accessors etc. Default. |
| **ValueObject** | A read model, or a value with no identity. Read from, never mutated; a change returns a new one. |
| **DTO** | A carrier with no identity and no behaviour. If it has an id, it is an Entity. |
| **Intent** | The DDD Command: immutable, already-validated data saying what is wanted. |
| **Collection** | A typed collection over one Entity or ValueObject (`getType()`). |
| **Enum** | A closed set the code switches on. May implement a `Contract/Enum` interface and use a `Concern/Enum` trait. |
| **Constant** | Named literals. Handles on values the database owns, so an enum would mean maintaining the set twice. |

The Entity/DTO line is the one people get wrong: **identity decides it**, not size or purpose.

**A string or int literal is written once, in `Constant`, and referenced everywhere else — config files
included.** Enum where the code owns the set and switches on it; Constant where the database owns
it and code only needs to name it without mistyping.

### Behaviour

| Type | Holds state? | Touches DB? | What it is |
|---|---|---|---|
| **Repository** | no | **yes — only here** | The sole door to the database. Returns Entities, ValueObjects or Collections. |
| **Query** | no | builds | A named query builder handing back a Builder for a Repository to run. |
| **Service** | **no** | via Repository | The default home for behaviour: a subject's verbs gathered on one class. Given everything it needs; reads no input. |
| **Action** | no | via Repository | One verb that outgrew its Service. Executes an Intent. Refuses by throwing a domain Exception. |
| **Registry** | **yes** | no | The type that legitimately holds state. |
| **Cart** | **yes** | no | A mutable work-in-progress aggregate being assembled across a session. |
| **Builder** | yes, briefly | no | A fluent DSL that materialises value objects. |
| **Source** | no | no | A domain's declarative contribution to a Registry. |
| **Condition** | no | no | A predicate switching something on or off. |
| **Support** | no | no | Framework extensions and factories. |
| **Facade** | no | no | A static door that encapsulates. Used for fluency |
| **Contract** | — | — | Interfaces. |
| **Concern** | — | — | Traits. |
| **Exception** | — | — | Exceptions. |

### Service or Action

**Reach for a Service first.** A Service gathers one subject's verbs on one class: `RoleService`
saves a role, deletes one, and answers whether a save would lock its owner out. Most behaviour is a
handful of short methods that belong beside each other, and a class per method buys nothing but
files to open.

**An Action is the escape hatch, taken sparingly.** Split one out when the verb and the private
helpers it needs would bloat the Service — when the Service has begun to read as two classes sharing
a file. Splitting for symmetry is not a reason: a `SaveRoleAction` does not oblige a
`DeleteRoleAction`.

**A file earns its keep or it does not exist.** One method, no helpers, one caller is not a class —
it is a method on the Service that already owns the subject.

### Refusing

A Service and an Action both sit below HTTP, and **neither knows what a request is.** Neither may
throw `ValidationException`, and neither may read or shape a response.

- **Ask before doing.** A Service states its own checks as methods returning a `ValidationResult` —
  `validateSave()` beside `save()`. Whoever calls decides what a failure looks like.
- **Refuse if asked anyway.** Execution that reaches a breached invariant throws a domain
  `Exception` — one defined in the domain's own `Exception` folder, never the framework's. That
  reports a caller who skipped the check, not a user who typed something wrong, so it carries
  nothing anybody should be shown.
- **Only the HTTP layer turns a refusal into a 422.** A `ValidationResult` becomes a
  `ValidationException` in the Controller or the `Http/Request`, never below it. The Service says
  which field is at fault; the boundary decides what that is worth.

### HTTP

| Type | What it is |
|---|---|
| **Http/Request** | FormRequest. The single boundary where raw input is validated; hands back an Intent. |
| **Http/Controller** | Wires the pieces. No rules of its own. |
| **Http/Middleware** | Per-request wrapping. |
| **Http/Response** | Not an illuminate Response. API contract between third-parties. A frozen Response schema |
| **Provider** | Container bindings and boot wiring. |
| **View** | View composers. |
| **Console** | Kernel and commands. |

## The two paths

```
write:  Http/Request  →  Intent  →  Service / Action  →  Repository
          validates      the want      executes           persists

read:   Http/Controller  →  Repository  →  Entity / ValueObject
```

The Service is the ordinary executor; an Action stands in its place only for a verb heavy enough to
have earned the file. Either way an Intent goes in, and neither reads the request itself.

## Naming

Suffix the component type when the class **does** something; leave it off when the class **is**
data.

- Suffixed: `RoleRepository`, `SaveRoleAction`, `TaxService`, `LoginRequest`, `SourceRegistry`,
  `ItemInfoQuery`, `AllocLineCollection`, `SystemSource`, `NavigationException`, `SaveRoleIntent`.
- Bare: `Role` (Entity), `Crumb` (ValueObject), `Problem` (DTO),
  `SystemType` (Enum), `DimensionsEnabled` (Condition).

Name a Service for the subject whose verbs it holds, never for the narrowest rule it happened to
start with — `RoleService`, not `RoleLockout`. The next verb then has somewhere obvious to go, and
putting it there is not a rename.

When an Entity or Constant shares a name with a Model, the Model is the one aliased at the point of
use — the domain object is the thing, the table is the detail:

```php
use App\Foundation\Auth\Entity\Role;
use App\Foundation\Auth\Model\Role as RoleRecord;
```

## The legacy boundary

FrontAccounting is being ported, not maintained.

- Never add a function to a `.inc` file.
- A `.inc` file may call into Laravel via `app(ClassName::class)` — that is the bridge, and the
  traffic only goes that way.
- Every new feature is written in the Laravel layer, even when its screen is still legacy.
