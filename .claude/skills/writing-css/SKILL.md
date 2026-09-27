---
name: writing-css
description: >-
  Invoke before writing or reviewing any CSS at all, before naming or renaming
  any class wherever it is written (a stylesheet, a template, a script), and
  before adopting or upgrading a CSS framework or build plugin; invoke it also
  from `reviews-before-done` as an extra lens whenever the diff touches a
  stylesheet or writes a class from markup or script. Supplies the one
  class-naming grammar that every stylesheet, template, and script shares, and
  the rule that recent CSS features such as `@starting-style`,
  `interpolate-size`, `transition-behavior`, `:has()`, `oklch`, `@property`,
  and container queries may decorate the page but never carry it, because the
  application is read from phones running older WebKit and WebView engines.
  Do not wait to recognise a feature as recent before invoking: that
  recognition is what the skill supplies, so the trigger is any CSS, not CSS
  that already looks new.
---

## Class names

Write every class in the form `block__part--modifier`, wherever it is written: a layout stylesheet, a page template, and a shared component all use the same grammar. A grammar scoped to one folder tells the other folders nothing, so they fill the gap by inventing a second one, and once two grammars live in the same cascade no name says which one it was written in; telling a part from a variant from a state then requires knowing where the class came from, which is the one fact a class name does not carry. The tell that this has gone wrong is a single slot ending up under two names in two templates, only one of them carrying the rule that styles it, so the other is dead on arrival and silent about it.

Prefix a block with `x-` when a consumer composes it, mirroring the tag it is drawn by; give a block that exists once on the page no prefix.

Leave a class inherited from the legacy system as found; it is not this application's to rename.

Spend a single hyphen only on separating words inside one block, part, or modifier name; it does nothing else. The double underscore and the double hyphen are given meanings and the single hyphen is what is left over, but an undefined separator does not stay undefined: it takes on the sense of whichever neighbour it sits beside, so the first name that spends a hyphen on containment turns every other hyphen in the file into a guess. A guess is exactly what cannot be checked against the name, and checking against the name was the whole job the separators were introduced to do.

Watch for the part that has parts. Where `block__chip-label` names something inside the chip, nothing of that shape may name something beside the chip, because two names of one shape and opposite structure cost more than the shorthand ever saved.

Name a part against its block however deep it sits in the markup: never `block__part__part`, and never a hyphen standing in for the extra level. Depth is a fact about one arrangement of markup; belonging is a fact about the thing itself. A name that carries depth has to be rewritten whenever a wrapper is introduced, and nothing forces that rewrite, so the markup goes on rendering while a rule quietly stops matching.

Ask of a part that grows parts of its own whether it could stand somewhere with none of its block around it and still mean something; it poses a question rather than a nesting problem. If yes, it is a block, named for itself, with its own parts hanging off that name. If no, it stays a part, and its own sub-parts join it with the hyphen.

Write a state as a modifier on the part it is a state of, `.x-select__option--selected`, never as a vocabulary of its own. Without the boundary between block and part, a part and a modifier are the same shape, and telling them apart would need a second vocabulary invented alongside the first, kept in step with it, and saying nothing about which part a state belongs to. The separator earns its keep by making that second vocabulary unnecessary.

## Recent features

Use a recent feature to decorate the page, never to carry it. This application is read from phones whose WebKit and WebView engines run years behind the desktop its CSS is written on. Before shipping any recent feature, delete it mentally: the page that remains must still be correct, with everything visible, readable, and operable, merely plainer. Motion, polish, and precision may vanish; correctness may not. If the feature failing means the page failing, the feature cannot be used yet: give the load-bearing part to an older construction and let the new feature sit on top.

Gate a framework or a build plugin by the same mental deletion before adopting or upgrading it: ask what its generated output demands of the oldest engine in the field, not what its documentation demands of your own machine. A framework upgrade whose generated output leaned on recent features for all of its rendering has already had to be rolled back wholesale, because on those phones the page did not render at all.

Design the degradation and trace it; never assume it. CSS does not skip only the fragment it fails to parse, so "it falls back" is a claim to verify rather than assume. Engines adopt these features one at a time, so there is a middle tier as well as the two obvious ones: an engine may honour the transition but not the interpolation it was written for. For each of no support, partial support, and full support, work out which declarations survive and confirm that every surviving set is the correct plain page.

Count a declaration that contains one unknown keyword as lost whole, its supported values with it. A comma-separated shorthand is one declaration, so `transition: height 200ms, display 200ms allow-discrete` dies together with its supported half on an engine that cannot parse the new keyword. Split the supported half into its own declaration wherever its loss would be more than polish.

Put nothing load-bearing inside a new property or at-rule. An engine that does not know the property or the at-rule skips it bodily and silently, so whatever was written inside goes with it and nothing reports the loss.

Give a new selector its own rule rather than grouping it with an old one. A selector the engine cannot parse invalidates every selector grouped with it, so a rule shared between an old selector and a new one is lost whole, including the old selector's styling. Grouping is what spreads the loss; the same declarations written under their own separate selectors lose only the half that was new.

Keep base rules in universally parsed CSS and confine every new keyword to declarations whose total loss changes nothing but polish.
