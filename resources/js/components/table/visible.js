'use strict';

/**
 * Calls back the first time any part of the element is on screen, and never again. Hands back the
 * way to stop waiting.
 *
 * Any part of it rather than some proportion: a threshold gives back part of the head start this
 * exists to buy.
 */
export function whenVisible(element, seen) {
    const observer = new IntersectionObserver((entries) => {
        if (!entries.some((entry) => entry.isIntersecting)) {
            return;
        }

        observer.disconnect();
        seen();
    });

    observer.observe(element);

    return () => observer.disconnect();
}
