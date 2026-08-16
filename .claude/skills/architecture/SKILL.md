---
name: architecture
description: Where code goes in this codebase — the domain-first layout, and what each component type may do. Invoke before writing or moving any backend code — creating a class, choosing a folder, naming a file, deciding which component type a piece of behaviour belongs to — and when reviewing whether existing code sits in the right place.
---

# Architecture

## What a class may do decides what it is

**Pick by what the class is allowed to do, not by what it is about.** Two rules do most of the
work:

- **Only a Query or a Repository designs a query.** The joins, the conditions, the shape of the
  rows — authored in one of those two and nowhere else.
- **Only an `Http/Request` reads raw input.** Past that boundary everything is typed and validated.

**Running a query is not designing one.** A Service, a Controller, or anything else may hold a
Query, ask it for a Builder, and call `->get()` on what it is handed — that is reading, and it puts
nothing in the wrong folder. What it may not do is compose the query itself.

```
write:  Http/Request  →  Intent  →  Service / Action  →  Repository
          validates      the want      executes           persists

read:   Http/Controller  →  Repository / Query  →  Entity / ValueObject
```

*Which* Repository is a second question, and reading the first as answering it is the mistake to
watch for.

## Where the file goes

**Top-level folders under `app/` are domains; component-type folders nest inside them.** Laravel's
default, inverted.

```
app/<Domain>/<ComponentType>/<Class>.php
app/Finance/Tax/Repository/TaxRepository.php
```

- `App\Foundation\Framework` — cross-cutting infrastructure (providers, middleware, base classes).
- `App\Foundation\Component\<Name>` — everything every consumer of one UI component shares: the wire
  format, the input validation, and the reading that is the same for all of them. Which rows exist
  stays with the domain that owns them, at the same address under its own namespace.
- `App\Legacy` — the FrontAccounting wrapper. Nothing outside it may extend it.
- `App\Foundation\Shared` — types belonging to no single domain.
- New domain work gets its own top-level namespace.

**Subdivide a domain only where the subfolder could itself hold subfolders.** `Finance/Ledger` and
`Finance/Banking` qualify. `Sale/Customer` does not — a customer divides no further. Everything
unqualified goes flat in the domain.

## Where the test goes

**Place a test at its subject's own path under a test root, mirrored down to the domain — never to
the component-type folder, and never copied off a neighbouring test.** `app/Finance/Tax/Service/`
is tested from `tests/Unit/Finance/Tax/`. The mirror stops above the component-type folder because
a test is named for a guarantee, and a guarantee spans several of them. Placement copied off a
neighbour is how a tree ends up teaching two conventions, and the second one to arrive wins.

## The component types

**Data.**

| Type | What it is |
|---|---|
| **Entity** | A domain object **with identity**: two with the same id are the same thing. |
| **Model** | Eloquent. The *table*, relationships, accessors etc. Default. |
| **ValueObject** | A read model, or a value with no identity. Read from, never mutated; a change returns a new one. |
| **DTO** | A carrier with no identity and no behaviour. |
| **Intent** | The DDD Command: immutable, already-validated data saying what is wanted. |
| **Collection** | A typed collection over one Entity or ValueObject (`getType()`). |
| **Enum** | A closed set the code switches on. May implement a `Contract/Enum` interface and use a `Concern/Enum` trait. |
| **Constant** | Named literals. |

**Behaviour.**

| Type | What it is |
|---|---|
| **Repository** | Loads and persists. Returns Entities, ValueObjects or Collections. |
| **Query** | A named description of a set of rows, handing back a Builder for whoever needs it to run. |
| **Service** | The default home for behaviour: a subject's verbs gathered on one class. |
| **Action** | One verb that outgrew its Service. Executes an Intent. |
| **Registry** | A store built once and read from thereafter. |
| **Cart** | A mutable work-in-progress aggregate being assembled across a session. |
| **Builder** | A fluent DSL that materialises value objects. |
| **Source** | A domain's declarative contribution to a Registry. |
| **Component/\<Name\>** | A domain's declarative contribution to one shared UI component: which rows a control offers, and the narrowing it accepts. Folder and suffix both take that component's own name — filed flat, a domain answering to a second component grows a second top-level type meaning the same thing as the first. |
| **Condition** | A predicate switching something on or off. |
| **Support** | Framework extensions and factories. |
| **Facade** | A static door that encapsulates. Used for fluency. |

**HTTP.**

| Type | What it is |
|---|---|
| **Http/Request** | FormRequest. Validates, and hands back an Intent. |
| **Http/Controller** | Wires the pieces. No rules of its own. |
| **Http/Middleware** | Per-request wrapping. |
| **Http/Response** | Not an Illuminate Response. A frozen response schema — the API contract with third parties. |

`Contract` (interfaces), `Concern` (traits) and `Exception` carry their ordinary meanings;
`Provider`, `View` and `Console` hold Laravel's own — bindings and boot wiring, view composers, the
kernel and its commands.

## The lines people get wrong

- **Identity decides the Entity/DTO line** — not size, not purpose. It is the one people get wrong.
- **Where an Eloquent Model exists for the table, prefer the Model** — reach for an Entity only when
  Eloquent is too heavy or risks serialization.
- **Write a string or int literal once, in `Constant`, and reference it everywhere else — config
  files included.** Enum where the code owns the set and switches on it; Constant where the database
  owns it and an enum would mean maintaining the set twice.
- **A Query exposes one public method, `builder`, and what narrows it arrives there as an argument —
  never as constructor state.** One built per request can only be read once, and the second reading
  of the same rows then has to be written by hand. A second public method is a second query wearing
  the first one's name: give it its own class.
- **Only Registry, Cart and Builder hold state.** Every other behaviour type is given everything it
  needs and keeps nothing.

## Service or Action

**Reach for a Service first: one subject's verbs gathered on one class.** `RoleService` saves a
role, deletes one, and answers whether a save would lock its owner out. Most behaviour is a
handful of short methods that belong beside each other, and a class per method buys nothing but
files to open.

**Split out an Action only when the verb and its private helpers would bloat the Service** — when
the Service has begun to read as two classes sharing a file. Splitting for symmetry is not a
reason: a `SaveRoleAction` does not oblige a `DeleteRoleAction`.

**A file earns its keep or it does not exist.** One method, no helpers, one caller is not a class —
it is a method on the Service that already owns the subject.

**Count callers only where the callers exist.** A shared component's surface answers to callers
not yet written, so an uncalled member is evidence of nothing: drop one because it is improbable,
never because it is unused.

## A Service never knows what a request is

**A Service or Action may not throw `ValidationException`, and may not read or shape a response.**

- **Ask before doing.** Checks are methods returning a `ValidationResult`, beside the verb —
  `validateSave()` beside `save()`. Whoever calls decides what a failure looks like.
- **Refuse if asked anyway.** Execution reaching a breached invariant throws a domain `Exception`
  — one defined in the domain's own `Exception` folder, never the framework's. That reports a
  caller who skipped the check, not a user who typed something wrong, so it carries nothing
  anybody should be shown.
- **Only the HTTP layer turns a `ValidationResult` into a `ValidationException` and a 422** — in
  the Controller or the `Http/Request`, never below. The Service says which field is at fault; the
  boundary decides what that is worth.

## Naming

**Singular everywhere — folders and class names alike.** `Controller`, not `Controllers`.
`Entity`, `Setting`, `Action`. Deliberate, so nobody has to guess.

**Suffix the component type when the class does something; leave it off when the class is data** —
an Intent takes the suffix despite being data.

- Suffixed: `RoleRepository`, `SaveRoleAction`, `TaxService`, `LoginRequest`, `SourceRegistry`,
  `ItemInfoQuery`, `AllocLineCollection`, `SystemSource`, `ItemSelect`, `NavigationException`,
  `SaveRoleIntent`.
- Bare: `Role` (Entity), `Crumb` (ValueObject), `Problem` (DTO), `SystemType` (Enum),
  `DimensionsEnabled` (Condition).

**Name a Service for the subject whose verbs it holds, never for the narrowest rule it happened to
start with** — `RoleService`, not `RoleLockout`. The next verb then has somewhere obvious to go,
and putting it there is not a rename.

**When an Entity or Constant shares a name with a Model, alias the Model at the point of use** —
the domain object is the thing, the table is the detail:

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
