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
| **Action** | One verb executing an Intent, through `execute()`. Where callers need the answer without the effect, `validate()` sits beside it — the same predicate `execute()` enforces for itself. Also where a verb outgrew its Service. |
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
- **Adding a parameter to a verb re-decides every parameter already beside it.** A second value
  read off the same thing as an existing one is that thing asking to be passed whole: replace the
  slice with the object, never append a second slice. The ruling that made one value the right
  argument — one value is nothing to bundle — was conditional on there being one, and the smallest
  diff into the existing signature is precisely what keeps that condition from being re-read. The
  tell is two parameters that cannot be wrong independently: an actor handed in as an id, then also
  as a role id, then also as a flag.

## Service or Action

**Reach for a Service first: one subject's verbs gathered on one class.** Two things promote a verb
out of it, and the first is a fact about the verb rather than a judgement about its size, so it
decides first:

1. **A caller wants the answer without the effect** — the verb has a check worth asking on its
   own. It becomes an Action: `validate()` beside `execute()`, both taking the Intent. **Where
   the verb's whole input is one value, both take the value** — an Intent exists to carry several
   already-validated values as one thing, and one value is nothing to bundle.
2. **The verb and its private helpers would bloat the Service** into two classes sharing a file.

Splitting for symmetry is neither: a `SaveRoleAction` does not oblige a `DeleteRoleAction`. When a
Service does split, its shared privates go **down** into a Query or Repository, or the Actions take
the Service — never sideways into a `Concern`, which is how a trait becomes a second Service nobody
named.

**A file earns its keep or it does not exist.** One method, no helpers, one caller is not a class —
it is a method on the Service that already owns the subject. Trigger 1 never argues with this: a
validate/execute pair is two public methods by construction.

**Count callers only where the callers exist.** A shared component's surface answers to callers
not yet written, so an uncalled member is evidence of nothing: drop one because it is improbable,
never because it is unused.

## A Service never knows what a request is

**A Service or Action may not throw `ValidationException`, and may not read or shape a response.**

- **A check only the verb consumes is an ordinary guard** — inside the verb, public to nobody,
  named nothing. Most checks are this one.
- **A check a caller wants to ask without doing makes the verb an Action**: `validate()` beside
  `execute()`, returning a `ValidationResult`. It is one predicate, not two. `execute()` calls
  `validate()` itself, inside the transaction and under whatever lock the write needs, and *that*
  call is the authoritative evaluation — the one concurrency has to get past. The public half is
  the same check offered early, for a 422, a greyed-out button, or a bulk screen reporting every
  failure without attempting every write. A caller who skips it changes nothing about correctness.
- **The refusal is the domain's own exception.** A check failing at execution throws from the
  domain's `Exception` folder — never the framework's. It reports a caller who skipped the check,
  not a user who typed something wrong, so it carries nothing anybody should be shown.
- **Only the HTTP layer turns a `ValidationResult` into a `ValidationException` and a 422** — in
  the Controller or the `Http/Request`, never below. The Action says which field is at fault; the
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

- Never add a function that carries logic to a `.inc` file. A named accessor that only forwards to
  Laravel is not logic — it is the bridge below, given a name so legacy call sites read like their
  neighbours.
- A `.inc` file may call into Laravel via `app(ClassName::class)` — that is the bridge, and the
  traffic only goes that way.
- Every new feature is written in the Laravel layer, even when its screen is still legacy.
