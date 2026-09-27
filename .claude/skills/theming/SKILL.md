---
name: theming
description: "Invoke before adding or changing a colour token, building or extending a colour ramp, working out a palette's family structure, choosing where a new hue or shade belongs, or reviewing whether a colour change would survive a re-skin. Colour in this application is organised in two tiers so that it stays re-skinnable, a palette that names colours by the part they play in the theme and role tokens that name colours by the job they do on screen, with rules for how a family's structure and ramp are recovered, built or preserved."
---

## Tiers

Decide which tier is changing before touching any colour. The palette names a colour by the part it plays in the theme; a role token names a colour by the job it does on screen, and points through the palette rather than carrying a colour value of its own. The palette is the one seam where a re-skin happens, and it stays that seam only while nothing downstream, whether a component, a role token or a one-off style, holds a colour opinion of its own instead of a reference. Confuse the tiers and a re-skin stops being an edit in one place and becomes a search across every component.

Ask what the change is to. A change to what a colour is, its hue, its place in a ramp, which family it belongs to, is a palette change. A change to what wears the colour, a button's background, an error message's text, is a role-token change.

## Families

Get a palette's family structure from whoever designed the palette whenever it is not already recorded, and never reverse-engineer it from the colours currently rendered. The family names, the groupings and which values belong to which family encode a decision the rendered values alone do not carry: the same set of colours is consistent with many different groupings, so inference produces a plausible-looking structure with no way to tell it apart from the true one. In this codebase every attempt to infer the structure came out wrong, in a different way each time, until the designer simply described how it had been built.

Name a family for its role in the theme, `base`, `contrast`, `danger`, never for the hue it currently renders, `blue-grey`, `teal`. A hue name is a fact about today's skin and stops being true the moment the skin changes. Separating the palette from the role tokens exists to make a re-skin a same-name, new-value edit, and a hue name defeats that by baking the old value into the identifier.

## Ramps

Anchor each family at rung `-500` with its defining colour, the one value the designer actually chose or the closest thing to it. Consumers of a design system already assume this: `brand-500` means the brand colour, not whichever rung its raw lightness happened to land on. Anchoring anywhere else forces every consumer to first learn which rung a particular family uses, which is exactly the kind of fact a palette exists to make unnecessary.

Keep every rung's chroma at or below the anchor's own when generating a family's ramp in the OKLCH colour space. Taper chroma as lightness moves away from the anchor and never let it spike past the anchor, because a rung more saturated than the colour it is a tint or shade of visibly drifts the family off the hue it was built to represent. After generating, no rung in the family is more saturated than the anchor.

Generate rungs only for families the designer never specified. Never collapse a designer's own values onto fewer synthesised rungs, and never bridge an empty lightness band between two designer families with synthesised rungs. A designer's ramp, and the space between two families, both encode decisions a formula cannot reconstruct: collapsing existing values onto a generated scheme throws away rungs the designer chose, and treating a gap between two families as a defect to fill merges two roles into one ramp. Generation is not a cleanup pass to run over structure, values or gaps the designer did specify.

Leave a family unfilled when it has no designer-supplied anchor and no live consumer, rather than picking a hue to occupy the slot. An empty slot is not evidence that a colour is missing. A palette's job is to name the choices that were actually made, and a family manufactured to round out the set presents a choice nobody made as though someone did, with no way for the next reader to tell the two apart.
