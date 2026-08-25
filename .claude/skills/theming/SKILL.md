---
name: theming
description: How colour is organised into two tiers — a palette naming colours by the role they play in the theme, and role tokens naming colours by the job they do in the UI — and how a family's structure and ramp get recovered, built, or preserved so the app stays re-skinnable. Invoke before adding or changing a colour token, building or extending a colour ramp, working out a palette's family structure, choosing where a new hue or shade belongs, or reviewing whether a colour change would survive a re-skin.
---

# Theming

## Two tiers, one question

**A palette names colours by the part they play in the theme; role tokens name colours by the
job they do on screen.** Ask which one is changing: is this what a colour *is* — its hue, its
place in a ramp, which family it belongs to — or is it *what wears* that colour — a button's
background, an error message's text? The first is a palette change. The second is a role-token
change, and the token should point through the palette rather than carry its own hex.

Confusing the two is how a re-skin turns into a grep across every component instead of an edit in
one place. The palette is the one seam where re-skinning happens, and it only stays that seam if
nothing downstream — a component, a role token, a one-off style — holds a colour opinion of its
own instead of a reference.

## Recover a palette's family structure from whoever designed it, never from the values in use

**When a palette's family structure — its names, its groupings, which values belong to which
family — isn't already recorded, get it from whoever designed the palette; don't reverse-engineer
it from the colours currently rendered.** A family's structure encodes a decision the rendered
values alone don't carry: the same set of colours is consistent with many different groupings, so
inference produces a plausible-looking structure with no way to tell it apart from the true one —
and every attempt at inferring one here came out wrong, in a different way each time, until the
designer simply described how it was built.

## Name a family for its role, never for the hue it renders

**Call a family what it does in the theme — `base`, `contrast`, `danger` — never what colour it
currently is.** A hue-based name (`blue-grey`, `teal`) is a fact about today's skin, and it stops
being true the instant the skin changes — the whole point of separating palette from role tokens
is to make a re-skin a same-name, new-value edit, and a hue name defeats that by baking the old
value into the identifier itself.

## Anchor a family's canonical colour at `-500`

**Every family's defining colour — the one hex a designer actually chose, or the closest thing to
it — sits at rung `-500`.** This is the one convention a design system's consumers already assume:
`brand-500` means "the brand colour," full stop, not "whichever rung its raw lightness happened to
land on." Anchoring elsewhere forces every consumer to first learn which rung this particular
family uses before reaching for it, which is exactly the kind of fact a palette exists to make
unnecessary.

## A synthesised rung's chroma may never exceed the anchor's

**When generating a family's ramp in OKLCH, never let a non-anchor rung's chroma exceed the
anchor rung's own — chroma tapers as lightness moves away from the anchor, it never spikes past
it.** A rung more saturated than the colour it's a tint or shade of visibly drifts the family off
the hue it was built to represent.

## Preserve a designer's structure exactly as supplied, inside a family and between families alike

**Never let generated rungs collapse a designer's own values onto fewer synthesised steps, and
never bridge an empty lightness band between two designer families with synthesised rungs to fill
it.** A designer's ramp and the space between two families both encode a decision a formula can't
reconstruct: collapsing existing values onto a generated scheme throws away steps the designer
chose, and treating a gap between two families as a defect to fill merges two roles into one ramp.
Generation is for filling in families the designer never specified — it is not a cleanup pass to
run over structure, values or gaps, they did specify.

## Don't invent a hue nobody chose

**A family with no designer-supplied anchor and no live consumer is not evidence a colour is
missing — leave the slot unfilled rather than picking a hue to occupy it.** A palette's job is to
name the choices that were actually made; a family manufactured to round out the set is a choice
nobody made being presented as if someone did, and the next person to read the palette has no way
to tell the two apart.
