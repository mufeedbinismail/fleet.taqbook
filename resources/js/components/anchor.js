'use strict';

// Generic dynamic-positioning directive for any floating panel (dropdown,
// tooltip, context menu, ...): computes placement against a reference
// element via Floating UI. Not tied to any one component — pair with
// x-popover, x-dialog, or anything else that exposes a reactive
// open/closed boolean and a reference element.
//
// Usage: x-anchor="{ reference: $refs.trigger, open: $popover.isOpen }"
//
// Three placement strategies, picked via the `strategy` key:
//
// - 'flip' (default) — prefers `placement` (default 'bottom'), flipping
//   only to the opposite side of the same axis (bottom <-> top) if there's
//   no room. Never swaps to a perpendicular side. Right fit for
//   menu-shaped panels (dropdowns, popovers) that should always open
//   above/below their trigger.
//     x-anchor="{ reference, open }"
//     x-anchor="{ reference, open, placement: 'bottom-end' }"
//
// - 'auto' — Floating UI picks whichever side (top/right/bottom/left) has
//   the most room, re-evaluated live as the trigger moves. Right fit for
//   context menus or tooltips near a screen edge, where any side may need
//   to be the one that opens.
//     x-anchor="{ reference, open, strategy: 'auto' }"
//
// - 'pin' — locks to `placement` and never flips, even off-screen.
//     x-anchor="{ reference, open, strategy: 'pin', placement: 'left' }"
//
// All strategies still run `shift`, which slides the panel along its
// current axis to stay on-screen without changing which side it's on.
// Re-evaluated live via autoUpdate as the trigger moves/scrolls.
//
// An `onPlacement` callback may be passed in config, called with the
// resolved side ('top' | 'right' | 'bottom' | 'left') after every
// reposition — for a caller that wants to reflect which way the panel
// actually opened (flip can change it) on something other than the panel
// itself, e.g. a caret on the trigger button.
//     x-anchor="{ reference, open, onPlacement: (side) => ... }"
import { computePosition, autoUpdate, autoPlacement, flip, shift, offset } from '@floating-ui/dom';

function middlewareFor(strategy, placement) {
    if (strategy === 'auto') return [offset(8), autoPlacement(), shift({ padding: 8 })];
    if (strategy === 'pin') return [offset(8), shift({ padding: 8 })];
    return [offset(8), flip(), shift({ padding: 8 })];
}

export default function (Alpine) {
    Alpine.directive('anchor', (el, { expression }, { effect, evaluateLater, cleanup }) => {
        const getConfig = evaluateLater(expression);
        let stopAutoUpdate = null;

        el.style.position = 'absolute';

        effect(() => {
            getConfig(({ reference, open, placement = 'bottom', strategy = 'flip', onPlacement = null }) => {
                if (open && reference) {
                    if (stopAutoUpdate) return;

                    stopAutoUpdate = autoUpdate(reference, el, () => {
                        computePosition(reference, el, {
                            placement,
                            middleware: middlewareFor(strategy, placement),
                        }).then(({ x, y, placement: finalPlacement }) => {
                            Object.assign(el.style, { left: `${x}px`, top: `${y}px` });
                            onPlacement?.(finalPlacement.split('-')[0]);
                        });
                    });
                } else if (stopAutoUpdate) {
                    stopAutoUpdate();
                    stopAutoUpdate = null;
                }
            });
        });

        cleanup(() => stopAutoUpdate && stopAutoUpdate());
    });
}
