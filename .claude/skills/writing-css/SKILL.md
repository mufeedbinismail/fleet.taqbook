---
name: writing-css
description: How CSS is written here — the one class-naming grammar every stylesheet, template and script shares, and the rule that recent CSS may decorate this app but never carry it, since the audience includes phones on older WebKit and WebView engines. Invoke before writing or reviewing any CSS, before naming or renaming any class wherever it is written, before adopting or upgrading a CSS framework or build plugin, and from reviews-before-done as an extra lens whenever the diff touches a stylesheet or writes a class from markup or script. The gate cannot be narrower than "any CSS": recognising a feature as recent (@starting-style, interpolate-size, transition-behavior, :has(), oklch, @property, container queries, …) is knowledge this skill supplies, so a trigger that presumes it fires exactly when it is not needed.
---

# Writing CSS

## One grammar, every class

**Every class this app writes is `block__part--modifier`, wherever it is written — a layout
stylesheet, a page template and a shared component all take the same grammar.** A grammar scoped to
one folder tells the other folders nothing, so they fill the gap by inventing a second one; and once
two live in the same cascade, no name says which it was written in. Telling a part from a variant
from a state then requires knowing where the class came from, which is the one fact a class name
does not carry. The tell is a single slot ending up under two names in two templates, only one of
them carrying the rule that styles it — dead on arrival and silent about it.

`x-` marks a block a consumer composes, mirroring the tag it is drawn by; a block that exists once
on the page takes no prefix. Classes inherited from the legacy system are not this app's to rename
and are left as found.

## A hyphen joins words; it never marks a part

**A single hyphen separates words inside one block, part or modifier name, and does nothing else.**
`__` and `--` are given meanings and `-` is whatever is left over — but an undefined separator does
not stay undefined. It takes the sense of whichever neighbour it sits beside, so the first name that
spends a hyphen on containment turns every other hyphen in the file into a guess, and a guess is
exactly what cannot be checked against the name. That checking is the whole job the separators were
introduced to do.

The confusable line is a part that has parts. Where `…__chip-label` names something inside the chip,
nothing of that shape may name something beside it — two names of one shape and opposite structure
cost more than the shorthand ever saved.

## `__` marks belonging, not depth

**Name a part against its block however deep it sits: never `block__part__part`, and never a hyphen
standing in for the extra level.** Depth is a fact about one arrangement of markup; belonging is a
fact about the thing itself. A name carrying depth has to be rewritten whenever a wrapper is
introduced, and nothing forces the rewrite — the markup goes on rendering while a rule quietly stops
matching.

So a part that grows parts of its own poses a question rather than a nesting problem: **could it
stand somewhere with none of its block around it and still mean something?** Yes — it is a block,
named for itself, and its parts hang off that name. No — it stays a part, and its own parts join it
by the hyphen above.

## A state is a modifier of whatever it is a state of

**Write a state as a modifier on the part it is a state of — `.x-select__option--selected` — never
as a vocabulary of its own.** Without a boundary between block and part, a part and a modifier are
the same shape, and telling them apart needs a second vocabulary invented alongside the first — one
that then has to be kept in step with it, and that says nothing about which part a state belongs to.
The separator earns its keep by making that second vocabulary unnecessary.

## Modern CSS is an enhancement, never a load-bearing wall

**Before shipping any recent feature, delete it in your head: the page that remains must still be
correct — everything visible, readable and operable, merely plainer.** This app is read from phones
whose engines run years behind the desktop its CSS was written on, and a framework upgrade whose
output leaned on modern CSS for *all* rendering had to be rolled back wholesale, because on those
phones the page did not render at all. Motion, polish and precision may vanish; correctness may
not. If the feature failing means the page failing, the feature cannot be used yet — find an older
construction for the load-bearing part and let the new feature sit on top.

The same test gates a framework or plugin adoption: ask what its *generated* output demands of the
oldest engine in the field, not what the docs demand of yours.

## Degradation is not automatic — design it, then trace it per engine

**Trace what survives on each engine tier rather than assuming a fallback.** CSS does not skip just
the fragment it fails to parse:

- A declaration containing one unknown keyword is dropped **whole**, and a comma-separated
  shorthand is one declaration — `transition: height 200ms, display 200ms allow-discrete` dies
  together with its supported half on an engine that cannot parse the new keyword.
- An unknown property or at-rule is skipped bodily, silently.
- A selector the engine cannot parse invalidates **every selector grouped with it**, so a rule
  shared between an old selector and a new one is lost whole — the old selector's styling included.
  Grouping is what spreads the loss; the same declarations written under their own selectors lose
  only the half that was new.

So "it falls back" is a claim to verify, not assume. Engines adopt these features one at a time, so
there is a middle tier as well as the two obvious ones — an engine may honour the transition but not
the interpolation it was written for. Work out for each of no support, partial and full which
declarations survive, and confirm every surviving set is the correct plain page. The safe
construction keeps base rules in universally-parsed CSS and confines every new keyword to
declarations whose total loss changes nothing but polish.
