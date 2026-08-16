---
name: ui-component-design
description: How a shared UI component stays general — opinions about its own chrome and layout only, meaning left to the consumer through slots, variants and tokens. Invoke before creating or changing any Blade UI component, component JS factory, or component CSS, and when reviewing whether a component has overstepped.
---

# UI component design

## The one question

**Whose opinion is this?** A component holds opinions about itself — its chrome, its layout, its
own parts. It holds none about what the consumer's content means: no color for a status, no icon
for a state, no named domain concept, no knowledge of which consumers exist. Layout says where a
value goes, never what it means. Every rule below is this question applied somewhere.

## Data or presentation

**Data the component needs for a job it owns is welcome; presentation of consumer content is
not.** Ask: *if this fact vanished, does a control the component draws break?* Yes → data, declare
it. No → presentation, leave it to the consumer.

The one admissible shape: a closed set of primitive types (date, money, boolean) whose only
consequence is layout — finite and universal. An arbitrary value-to-anything map is neither, and
goes through a slot.

## Slots

- Every built-in rendering of consumer content is a default a slot may replace, with the current
  item in scope. Meaning enters by composition — the consumer places a badge, a link, a button in
  the slot — never by the host growing an option per case.
- If a consumer cannot express something through the slot, the slot is too small: widen it, do not
  add a bespoke option for the one case.
- Slots are collected by type (`ComponentSlot`), never by sweeping variable names.

## Props

- A caller decision becomes a prop with a default; a component's own concern never becomes a prop.
  Invoked with nothing but its required input, a component renders sensibly.
- Where a server or a declaration owns the fallback, the component stays silent unless the caller
  spoke — a default forced onto the wire is not a default.

## Styling

- **Semantic variants, never colors, cross the boundary.** Which pixels `success` means lives in
  the stylesheet alone.
- Every color is a token; tints derive from the token, never invented beside it.
- A component's stylesheet lives in `@layer components`, one file per component.
- A style-accepting input parses the common currencies — a variant the stylesheet knows, a CSS
  color, a class of the caller's own — rather than demanding one.
- **A component writes none of its own appearance as a class in its own template — its stylesheet
  draws it, and the template carries only what the caller wrote.** A utility outranks the component
  layer, so an appearance default written into the markup is the one declaration the component's own
  stylesheet cannot overrule: the rule that looks authoritative loses to the class nobody meant as a
  decision. The tell is a stylesheet rule that reads correctly and renders nothing.

## Three layers

- Behaviour is a framework-agnostic JS factory: dependencies injected, no element references,
  testable without markup. The Blade component wires configuration to the factory and draws. CSS
  reads the classes and attributes the markup sets and decides nothing.
- **Whatever layers a component has move together.** Blade file, JS factory and component CSS are
  one thing in as many trees as it needs — a behaviour may have no template, a template no script —
  but a change that adds, renames or moves one without the others it does have leaves a component
  that draws but does not behave, or behaves but cannot be styled.
- Standing in for a native control is the exception, and only for that control's own element: it is
  not a reference to be avoided but the state itself, since it is what holds the value, submits it,
  and the server repopulates. The factory owns it and keeps no second copy — a truth held beside a
  form control drifts from it the first time anything else writes.
- **A node the component does not own may be replaced without it being told.** Where a component
  reads another element — a control it takes a filter from, a field it mirrors — it resolves the
  selector afresh on each reading and hears from it by delegation. A reference taken once outlives
  the node it was taken from, and a listener left on that node goes on listening to something
  nothing reaches any more: the component keeps working and stops responding, which is the pair of
  symptoms nobody goes looking for.
- Every write a consumer can make, it can also make without announcing. A component that only ever
  announces turns "adjust this control when that one changes" into a loop. And a control announcing
  that it committed is not the same as its value having moved — whoever acts on the announcement
  compares before acting, or a no-op commit reads as a change somebody made.
- **A request the component makes for itself says so; only a request somebody is waiting on may
  take the screen.** The shared busy indicator answers to whatever fetches, and its default has to
  be "somebody is waiting" or the one request that forgets to say leaves a user staring at nothing.
  So the quiet kind is the one that declares itself, and a list filling in behind a panel that does
  not is indistinguishable from a form being submitted.
- A translated sentence is one string with placeholders, never assembled from fragments.
- PHP reaches a JS context only through `@js` / `Js::from` — "developer-supplied" values included.
  Configuration a component reads is data rather than a context: it travels as an escaped `data-`
  attribute the component parses, so a page carrying twenty of one component parses no script to
  use them.
