---
name: ui-component-design
description: >-
  Keeps a shared UI component general by asking, of every opinion it holds, whose opinion it is:
  the component's own, about its chrome, layout and parts, or the consumer's, about what the
  content means. Use this whenever you create, change or review a shared component in any layer:
  a Blade component under resources/views/components/ui, a JavaScript factory under
  resources/js/components, the Alpine directive wiring the two, or a stylesheet in
  @layer components. Use it especially when a page asks a shared component for something of its
  own, such as a colour for a status, an icon for a type, a special case for one screen or a prop
  that maps values to anything, and whenever you are about to add a prop, option, class or default
  to an existing component, even as a one-line tweak. Use it when reviewing whether a component
  has taken an opinion about its consumer's content.
---

# UI component design

## The one question

**Whose opinion is this?**

A shared component holds opinions about itself: its chrome, its layout, its own parts. It holds
none about the consumer's content or the consumer's page: no colour for a status, no icon for a
state, no named domain concept, no knowledge of which consumers exist. Layout says where a value
goes, never what it means.

Every rule below is this question asked at one boundary, and a case no rule covers is answered
by asking it there: whose fact, whose meaning, whose decision, whose pixels, and inside the
component, whose job, whose element, whose change, whose wait. A question rather than a checklist,
because a shared component serves pages that do not exist yet. An opinion it takes about one
consumer's meaning is wrong for the next, and the usual fix, an option per case, teaches the host
every consumer's vocabulary until it is not shared but a union of its pages.

## Data or presentation: whose fact is this?

Take in the data a component needs for a job it owns; refuse the presentation of the consumer's
content. Test any fact by taking it away. If a control the component draws breaks without it, it
is data, and the component declares it. If nothing the component draws breaks, it is
presentation, and it stays with the consumer.

One shape of data about the consumer's content is admissible: a closed set of primitive types,
such as date, money and boolean, whose only consequence is layout. That set is finite and the
same for every consumer, so knowing it teaches the component nothing about any one page. A map
from the consumer's values to anything else is neither finite nor shared, so it goes through a
slot and never through a prop.

## Slots: whose meaning is this?

Make every built-in rendering of consumer content a default that a slot may replace, with the
current item in scope. Meaning enters by composition: the consumer places a badge, a link or a
button into the slot, and the host never learns what any of them stands for.

When a consumer cannot express something through the slot, the slot is too small. Widen it rather
than adding a bespoke option for the one case, because an option added for one consumer's meaning
teaches the host that meaning, and every later consumer inherits it.

Collect slots by type, `ComponentSlot`, never by sweeping variable names. A slot is whatever
arrived as a slot, whatever it was named; a variable that happens to share a prefix is not one.

## Props: whose decision is this?

Turn a consumer's decision into a prop with a default. Never turn the component's own concern into
a prop: invoked with nothing but its required input, a component renders sensibly.

Where a server or a declaration owns the fallback, stay silent unless the consumer spoke. A
default the component sends anyway is a value the consumer never chose, and it stops the real
fallback from ever applying.

## Styling: whose pixels are these?

Let semantic variants cross the component boundary, never colours. Which pixels `success` means
lives in the stylesheet alone, so a consumer says what a thing is without saying how it looks.

Write every colour as a token, and derive a tint from the token rather than inventing one beside
it. The tell is a colour literal in a component stylesheet.

Put a component's stylesheet in `@layer components`, one file per component.

Write none of a component's own appearance as a class in its own template. The stylesheet draws
it, and the template carries only what the consumer wrote. A utility class outranks the component
layer, so an appearance default written into the markup is the one declaration the component's
own stylesheet cannot overrule. The tell is a stylesheet rule that reads correctly and renders
nothing.

Where an input takes styling, accept each form a consumer commonly reaches for: a variant the
stylesheet knows, a CSS colour, or a class of the consumer's own. Demanding one form pushes the
consumer's opinion into the host's vocabulary.

## Inside the component: the same question, turned inward

The boundaries above separate a component from its consumers. The same question separates a
component's parts from each other, and the component from the page around it.

- **Whose job.** Behaviour is a JavaScript factory that knows no framework: dependencies
  injected, no element references, testable without markup. The Blade component wires
  configuration into the factory and draws; the Alpine directive is only that wiring. The
  stylesheet reads the classes and attributes the markup sets and decides nothing. Whatever layers
  a component has move together: adding, renaming or moving one without the others leaves a
  component that draws but does not behave, or behaves but cannot be styled.

- **Whose element.** The native form control a component stands in for is the one exception to
  "no element references", and only that element: it is not a reference to avoid but the state
  itself, because it holds the value, submits it, and the server repopulates it. The factory owns
  it and keeps no second copy, since a copy beside a form control drifts the first time anything
  else writes to the control. Every other element the component reads but does not own, a control
  it takes a filter from, a field it mirrors, may be replaced without the component being told.
  Resolve its selector afresh on each reading and hear from it by delegation. A reference taken
  once outlives the node it was taken from, and a listener left there keeps listening to something
  nothing reaches: the component keeps working and stops responding, the pair of symptoms nobody
  goes looking for.

- **Whose change.** A write the consumer makes is the consumer's change, so give every write a
  silent form that makes it without announcing. A component that only ever announces turns
  "adjust this control when that one changes" into a loop. And an announcement is not a change:
  a control saying it committed is not its value having moved, so whoever acts on the
  announcement compares first, or a commit that moved nothing reads as a change somebody made.

- **Whose wait.** A request the component makes for itself is `background`; only a `live`
  request, one the whole screen is waiting on, may take the screen. The shared busy indicator
  answers to whatever fetches and defaults to `live`, because the one request that forgets to
  declare itself must not leave a user staring at nothing. So the quiet request is the one that
  declares itself; without the declaration, a list filling in behind a panel is indistinguishable
  from a form being submitted.

## Two rules the question does not reach

These are not applications of the question. They stay because they bite in this codebase.

- A translated sentence is one string with placeholders, never assembled from fragments.
- PHP reaches a JavaScript context only through `@js` or `Js::from`, and an Alpine expression is
  a JavaScript context. Values described as developer-supplied are included: the value that looks
  safe to write raw is the one this rule is for. Configuration a component reads is data rather
  than a context, so carry it as an escaped `data-` attribute the component parses, and a page
  carrying twenty of one component parses no script to use them.

## Applying the question

**A page asks the table for a `statusColors` prop mapping paid to green and overdue to red.**
Whose opinion is the colour? The page's: it knows what paid means. A value-to-colour map goes
through a slot, so the page places a badge with a semantic variant in the cell slot, with the row
in scope, and its stylesheet says which pixels the variant means. The table learns nothing.

**A report page asks the date-range component to default to this month when given nothing,
though the server already defaults the report period.** Whose decision is the fallback? The
server's. The component stays silent when the page said nothing; a default it sends anyway is a
period nobody chose, and the server's fallback never applies.

**A select fetches options as the person types, and the page asks it to raise the global spinner
meanwhile.** Whose wait is the fetch? The select's own: it will show the result, and it can say
so in its own panel. The request declares `background`; the global spinner is for requests the
whole screen waits on, such as a form submitting.
