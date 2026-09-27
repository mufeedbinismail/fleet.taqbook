---
name: architecture
description: Use before creating, moving or naming any file under app/, routes/, config/, lang/, resources/views/ or tests/, that is, before choosing whether a class is a Service, an Action, a Query, a Repository or another component type, before choosing the module, the feature and the folder it lives in, before naming the file, before adding a parameter to an existing verb, before reporting or removing a member that nothing calls, before adding anything to the FrontAccounting legacy layer, and when reviewing whether existing code sits in the right folder, under the right type, or does something its type may not.
---

## Precedence

Derive every path from these rules and never from a neighbouring file, and when the tree disagrees with a rule, fix the tree rather than follow it. Placement copied off a neighbour is how a tree ends up teaching two conventions, and the later one wins.

Resolve a collision between two rules by the kind of rule: a rule about what a class may do beats a rule about where it goes, and a rule about where it goes beats a rule about what it is called. Between two rules of the same kind, take the less permanent commitment, because a path is quasi-permanent the moment other code imports it.

## What a class may do decides what it is

Choose between component types by what the class is allowed to do, never by what it is about. Where there is no type to choose between, because the file is a one-off that no type describes, the only question left is what it is about and what it belongs to, and that decides its folder, as it already decides its module and its feature.

Design a query only in a Query or a Repository. Designing a query means choosing the joins, the conditions and the shape of the rows, and nothing else composes one. Running a query is different from designing one: a Service, a Controller, or anything else may hold a Query, ask it for an Illuminate query `Builder` and call `->get()` on it, and that is allowed anywhere. Which Repository a piece of persistence belongs to is a second question, and reading the rule on designing queries as if it answered it is the mistake to watch for.

Read raw input only in an `Http/Request`. Everything past that boundary works with typed, validated data.

Hold state only in a Registry, a Cart or a Builder. Every other behaviour type is given what it needs and keeps nothing.

Route a write and a read along these paths:

```
write:  Http/Request  →  Intent  →  Action  →  Repository
          validates      the want    executes    persists

read:   Http/Controller  →  Repository / Query  →  Model / Entity / ValueObject
```

## Where the file goes

Give every folder under `app/` above the component-type folders one of these five shapes, and never mix two shapes at one level.

| Folder | What its top level holds | Where its providers sit |
|---|---|---|
| an umbrella, a root folder grouping modules, such as `app/Foundation/` or `app/Trade/` | modules, and a `Shared/` | none |
| a module without feature folders | component-type folders, and one folder per minted type it implements | in `Provider/`, one of the component-type folders, such as `Provider/<Module>ServiceProvider.php` |
| a module with feature folders | feature folders, a `Shared/`, and the provider files | directly at the module root, such as `<Module>ServiceProvider.php` |
| a feature | component-type folders, and one folder per minted type it implements | none |
| a `Shared/` | component-type folders | none |

Treat a folder as a module when and only when it holds a service provider. Place a module at `app/` root or under an umbrella, and nowhere else.

Resolve a new class's path one segment at a time, in this order.

1. Put the FrontAccounting bridge, and nothing else, in `app/Legacy/`, a one-off at `app/` root that groups the bridge's modules and component-type folders side by side, because nothing else could contain it. Put every other module at `app/` root, or under the umbrella it has been confirmed to belong to; `app/Foundation/` is the umbrella for the modules every other module depends on.

2. Put the class in the module whose concept it is, and let consumption decide nothing: a class five modules read still belongs to the one whose concept it is, and the others import it from there. Put a class in a `Shared/` only after every candidate module has failed to own it, where owning it would make that module's peers depend on it for something that is not that module's business. Use the umbrella's `Shared/` when every candidate sits under one umbrella, and `app/Foundation/Shared/` otherwise.

3. Put the class in the feature whose concept it is when the module has feature folders, and in `<Module>/Shared/<ComponentType>/` when no feature owns it. A module without feature folders has no feature, so this step does not apply to it.

4. Give every class the folder of its component type, and open that folder for the first file rather than waiting for a second.

Never create an umbrella, and never place or move a module under one, on your own. When a new or existing module plausibly belongs under an existing umbrella, `app/Foundation/` included, or plausibly warrants a new one, propose the move and wait for confirmation; for `app/Foundation/` in particular, the criterion for admission is not yet settled.

Start every module flat, and let a concept earn a feature folder only when its files have spread across so many component-type folders that gathering them under one folder would encapsulate the concept better than the flat module does. A concept with a Model, a Controller, a Repository and a Service has not earned one, which is why `Customer` sits flat in `Sale`; only when a concept outgrows those basics does it earn a folder. Propose the first feature folder of a module and wait for confirmation rather than adding it, and when it is added, move every component-type folder under a feature or into `Shared/` in the same commit.

Put a class implementing a type that a mechanism minted in a folder named for that type, dropping the mechanism's name where the type carries it, in the mechanism and in a contributing module alike: `Filter/TextFilter.php` inside Table, `Source/AccessSource.php` in Auth for Navigation's `NavigationSource`, `Condition/DimensionsEnabled.php` in Legacy for Navigation's `Condition`. Put a contribution to a UI component under `Component/<Name>/` instead, such as `app/Trade/Sale/Component/Select/CustomerSelect.php`, because otherwise a module answering to a second UI component would grow a second top-level folder that means the same thing as the first.

Keep files, never folders, inside a component-type folder or a minted-type folder. Treat `Http/`, `Database/` and `Component/` as groups of folders rather than as types, and let them be the only folders at that level that hold folders: `Http/` holds `Controller/`, `Request/`, `Middleware/` and `Response/`; `Database/` holds `Factory/`, in the Model's own feature; and `Component/` holds one `<Name>/` per UI component the module contributes to.

Keep a one-off where it belongs. A file or folder that belongs to a folder but is none of the things that folder's slots hold sits directly in that folder, named for what it is, with no folder of its own until a second of its kind arrives and the two earn one. This holds in `app/` and in the folders Laravel owns alike: the HTTP kernel sits in `Http/` beside its four folders, the exception handler in `Exception/` beside the exceptions, `routes/legacy.php` in `routes/` beside `web/`, and `routes/web/guest.php` beside the module files it is loaded before.

## Where the non-class files go

Key every non-class file by module, never by umbrella, and key a nested module by its own short name, so `sale` and not `trade`, `auth` and not `foundation`.

| File | Path |
|---|---|
| routes | `routes/web/<module>.php`, required from `routes/web/index.php` in the order that file states |
| config | `config/<module>.php` |
| lang | `lang/en/<module>.php` |
| page views | `resources/views/pages/<module>/` |
| provider registration | one line per provider in `config/app.php` |

Leave migrations where Laravel puts them, in `database/migrations/`, and never move one.

## Where the test goes

Place a test at its subject's path from `app/` under a test root, mirrored down to the feature or to `Shared/` and never down to the component-type folder. `app/Finance/Tax/Service/TaxService.php` is tested from `tests/Unit/Finance/Tax/`, a class under `app/Trade/Sale/` from `tests/Unit/Trade/Sale/`, and an integration test the same way under Laravel's `tests/Feature/`. The mirror stops above the component-type folder because a test is named for a guarantee and a guarantee spans several component types.

Put a trait shared between tests in `tests/Concern/`, and put a fixture class beside the tests it serves.

## The component types

Treat the four component-type tables as closed: add a component type by adding a row, never by coining a suffix in a pull request. A class whose name ends in a word that is in none of the tables either has a component type it has not yet named, implements a type that a mechanism minted, or is a one-off kept flat in the folder it belongs to.

### Data

| Type | What it is |
|---|---|
| Model | Eloquent: the table with its relationships and accessors. The default whenever a table exists. |
| Entity | A domain object with identity in the domain's sense: two instances that stand for the same thing are the same thing. For when Eloquent is too heavy or risks serialisation. |
| ValueObject | A read model or a value without identity. Read from and never mutated; a change returns a new one. |
| DTO | A carrier with no identity and no behaviour. |
| Intent | The Command of domain-driven design: immutable, already-validated data saying what is wanted. |
| Collection | A typed collection over one Entity or ValueObject, declaring its type through `getType()`. |
| Enum | A closed set the code switches on. |
| Constant | Named literals. Enum where the code owns the set, Constant where the database owns it, because an enum over a database-owned set means maintaining the set in two places. |

### Behaviour

| Type | What it is |
|---|---|
| Service | Behaviour reused across operations, gathered by subject on one class: calculations, derivations, and what several Actions or screens share. |
| Action | One operation that a single request performs, executed through `execute()`. |
| Repository | Loads and persists; returns Models, Entities, ValueObjects or Collections. |
| Query | A named description of a set of rows, returning an Illuminate query `Builder` for whoever needs to run it. |
| Registry | A store built once and read from thereafter; the socket other modules' contributions plug into. |
| Cart | A mutable work-in-progress aggregate assembled across a session. |
| Builder | A fluent domain-specific language that materialises value objects. |
| Support | Framework extensions, and object factories other than Eloquent model factories. |
| Facade | A static door that encapsulates, used for fluency. |
| Job, Event, Listener, Observer, Policy, Mail, Notification | Laravel's meanings. Thin: receive, then delegate to a Service or an Action, with no rules of their own. |

### HTTP

| Type | What it is |
|---|---|
| `Http/Request` | A `FormRequest` that validates and returns an Intent. |
| `Http/Controller` | Wires pieces together; has no rules of its own. |
| `Http/Middleware` | Wraps a request. |
| `Http/Response` | Not an Illuminate Response but a frozen response schema: the contract with third parties. |

### Language and framework

| Type | What it is |
|---|---|
| Contract | Interfaces. |
| Concern | Traits. |
| Exception | Exceptions. |
| Provider | Laravel's service provider: bindings and boot wiring. |
| View | Laravel's view composers. |
| Console | Laravel's kernel and its commands. |

## The second axis

Let a mechanism mint its own contribution types by publishing each one's interface in its own `Contract/`, and nowhere else: a folder named for a minted type appears in the mechanism after the interface exists, never in a contributor first. Table's `Filter` is a type on Table's axis and not on the component-type axis, and a class implementing it carries no component type in its path, so let the minted type's interface say what such a class may do.

Keep the words "second axis", "minted", "mechanism", "shape" and "umbrella" inside this skill. In code, in commit messages and in replies, name the thing itself instead, such as Table's `Filter`, Navigation's `NavigationSource`, or the `Trade` folder, because those are the names the codebase and its readers already share.

## The lines people get wrong

Let identity alone decide between an Entity and a DTO, never size and never purpose. Ask whether two instances that stand for the same thing in the domain are the same thing, and ask nothing else.

Take identity in the domain's sense and not the database's: a class needs no `id` field to have it, and a field named `id` does not confer it. A tax group line is an Entity because the domain has one line that two readings refer to, whether or not a column identifies it; a carrier holding a `customer_id` beside other values is still a DTO.

Give a Query exactly one public method, `builder()`, and pass anything that narrows the query to it as an argument, never as constructor state. A Query built once per request with its narrowing in the constructor can only be read once, so a second reading of the same rows ends up hand-written. A second public method on a Query is a second query and gets its own class.

Write a literal that carries a meaning in the domain once, in a Constant, and reference it everywhere else, config files included: a status code, a system type number, an account code, a permission name. A literal that means nothing beyond the line it sits on, such as a delimiter used once or an array offset, stays where it is; the test is whether a reader of the domain would recognise the value as one of its facts.

Reopen the decision about the existing parameters whenever a parameter is added to a verb. When the new value is read off the same object as an existing parameter, pass the object whole in place of the existing slice rather than appending a second slice, because the original choice to pass a single value was conditional on there being only one, and the smallest possible diff into the existing signature is exactly what prevents that condition from being re-examined. The tell is two parameters that cannot be wrong independently of each other: an actor passed as an identifier, then also as a role identifier, then also as a flag.

## Members with no callers

Judge a member of a layer by whether a consumer will plausibly want it, and never report it as dead, unused or removable because nothing calls it. A layer is code written so that other code can be written against it, so its callers arrive after it by design; drop a member only when no consumer the layer was written for would reach for it.

Recognise a layer by asking whether the work that will depend on the code is still to be written, not by where the code sits or who calls it now: a UI component before its definitions, a helper gathered so that the modules to come do not each write their own, a mechanism before its contributors. In this codebase most of that dependent work is still FrontAccounting in `public/` and reaches a layer only as each screen is ported, so a correct layer has no callers in `app/` for a long time.

## Service or Action

Put an operation that a single request performs in an Action of its own, executed through `execute()`, and put behaviour that is reused across operations in a Service: calculations, derivations, and whatever several Actions or screens share, encapsulated behind the class of the subject it belongs to. An operation and the behaviour it reuses are different kinds of thing, so sorting by kind decides where the old question of size never could.

Let both `validate()` and `execute()` take the value directly when the operation's entire input is one value. An Intent exists to carry several already-validated values as one thing, and a single value is not something to bundle.

Put behaviour that two Actions share in a Service they both take, or down in a Query or a Repository, and never sideways in a `Concern`, because that is how a trait becomes a second Service that nobody named.

Keep a calculation with no helpers and one caller on the Service that already owns the subject; it is not a class. Count callers only where callers exist.

## The HTTP boundary

Turn a `ValidationResult` into a `ValidationException` and a 422 only in the HTTP layer, in the Controller or the `Http/Request`, and never throw `ValidationException` or read or shape a response from a Service or an Action. A Service never knows what a request is; the Action says which field is at fault, and the boundary decides what that is worth.

Write a check that only the verb itself uses as a plain guard inside the verb: not public, not named. Most checks are this one.

Keep one predicate, not two, when a caller wants to ask a check without executing: `execute()` calls `validate()` itself, inside the transaction and under whatever lock the write needs, and that inner call is the authoritative evaluation, the one concurrency has to get past. The public `validate()` returning a `ValidationResult` is the same check offered early, for a 422, a disabled button, or a bulk screen reporting every failure before attempting any write. A caller who skips the public call changes nothing about correctness.

Throw from the module's own `Exception/` folder, never from the framework, when a check fails at execution time. The failure reports a caller who skipped the check rather than a user who typed something wrong, and so it carries nothing that should be shown to anyone.

## Naming

| What is being named | Form |
|---|---|
| a folder or class Taqbook authors | singular, PascalCase: `Controller/`, `Entity/`, `Tax/`, never `Controllers/` |
| a folder Laravel owns | Laravel's own lowercase spelling: `routes/`, `config/`, `lang/`, `database/`, `resources/`, `tests/` |
| a behaviour class | suffixed with its component type: `Repository/RoleRepository`, `Action/SaveRoleAction`, `Http/Request/LoginRequest`, `Registry/SourceRegistry` |
| a Support or a Facade | bare, the exceptions among behaviour: `Support/MoneyFactory`, `Facade/Navigation` |
| a data class | bare, because the type folder is the handle: `Entity/Role`, `ValueObject/Crumb`, `DTO/Problem`, `Enum/SystemType` |
| an Intent or a Collection | suffixed, the exceptions among data: `Intent/SaveRoleIntent`, because a bare verb phrase reads as behaviour; `Collection/AllocLineCollection`, because a bare name collides with its own element |
| a Contract or a Concern under `app/` | suffixed, and a trait implementing a contract shares its stem: `Contract/HasLabelContract`, `Concern/HasLabelConcern`; bare under `tests/Concern/` |
| an implementation of a type a mechanism minted, in the mechanism or in a contributor | suffixed with the minted type, dropping the mechanism's name where the type carries it, or with the UI component's name for a contribution to one: `Filter/TextFilter`, `Source/AccessSource`, `Component/Select/CustomerSelect`, `Component/Table/UserTable` |
| an implementation of Navigation's `Condition` | bare, the exception among minted types, because the name already reads as the proposition it tests: `Condition/DimensionsEnabled` |
| a Service | for the subject whose behaviour it holds, never for the narrow rule it started with: `Service/RoleService`, not `Service/RoleLockout`, so that the next calculation has an obvious home and adding it there is not a rename |
| a Model sharing its name with an Entity or a Constant | aliased at the point of use, because the domain object is the thing and the table is the detail: `use App\Foundation\Auth\Entity\Role;` and `use App\Foundation\Auth\Model\Role as RoleRecord;` |

## Naming members

Covers methods, properties, parameters and locals.

1. Write the **call site** before the declaration: the line that uses the member, as the
   caller would say it aloud with the file closed.
2. Declare the member under the call-site name, unchanged.
3. On a collision, return to step 1 for a different word, or a different home for the
   member.
4. When no name comes, the member is two things: split it and name each half from its own
   call site.

### The subject

The call site supplies a **subject**; the name supplies the rest. Decide which host the
member hangs off, then name for that host:

| Host | Subject | Name supplies | Reads as |
|---|---|---|---|
| Proposition (yes/no) | the receiver | an assertion: third-person verb, or `is` / `has` / `can` + rest | `$subscription->isActive()`, `$cart->hasItems()`, `$member->canVote()`, `$period->contains($date)` |
| Entity, model, value object | the receiver | a short verb or noun read against it | `$order->total()`, `$email->domain()`, `$money->add($other)`, `$invoice->markPaid()` |
| Service, repository, umbrella | the arguments | the operation and its object, in full | `$userRepository->findByEmail($email)`, `$paymentService->charge($order)`, `$mailer->sendReceipt($order)`, `$signer->sign($payload)` |
| Factory | the class | the operation and origin | `Money::fromCents(1999)`, `Key::fromDelegation($packed, $root)`, `Period::between($start, $end)`, `Duration::ofMinutes(15)` |

### Collaborators

A collaborator is a dependency injected into a constructor or a method. Take its subject
from where it is injected: the class supplies the subject for a constructor dependency, and
the method name supplies it for a method dependency. Name the collaborator by whether it
shares that subject:

- **Same subject** → the bare role word, in full. `CustomerService` holds
  `CustomerRepository` as `$repository`; `CustomerRepository` holds `CustomerService` as
  `$service`; `OrderController::cancel()` takes `CancelOrderAction` as `$action`.
- **Different subject** → the full class name, singular. `CustomerService` holds
  `EmployeeRepository` as `$employeeRepository`; `PlaceOrderAction` holds
  `ReserveStockAction` as `$reserveStockAction`; `ValidationService` is held as
  `$validationService`. A service scoped to one action is the agent noun of that action
  instead: `Signer` as `$signer`, `Mailer` as `$mailer`, `Geocoder` as `$geocoder`. A
  framework collaborator keeps Laravel's own short name instead: `$app`, `$http`, `$gate`,
  `$auth`, `$router`.

### Guardrail

A name that reports what happened to the value on the way here (`parsed…`, `sorted…`,
`resolved…`) is the file's view. Return to step 1 and name what the caller asked for.

## The legacy boundary

Port FrontAccounting; do not maintain it. Write every new screen or capability in `app/`, even when its screen is still a legacy one.

Add no function that carries logic to a `.inc` file. A named accessor that only forwards to Laravel is not logic; it is the bridge given a name so that legacy call sites read like their neighbours.

Let a `.inc` file call into Laravel through `app(ClassName::class)`, and let traffic cross only in that direction: nothing in `app/` calls a FrontAccounting function, and nothing outside `app/Legacy/` extends it.
