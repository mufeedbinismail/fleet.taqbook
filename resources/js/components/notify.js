'use strict';

import { Notyf } from 'notyf';

/*
        App.notify.success('Saved.');       $notify.error('Could not save.') inside Alpine

    One notifier for the page. Every colour it wears comes from the stylesheet, by the type's own
    class, so a toast is drawn in whichever theme is in force when it opens.
*/

// Blanked rather than left out: the type merges over the library's own, and the library's carries
// a colour that would otherwise be written inline over anything the stylesheet says.
const uncoloured = { background: '', backgroundColor: '' };

const TYPES = [
    { type: 'success', icon: 'icon-circle-check' },
    { type: 'warning', icon: 'icon-warning' },
    { type: 'error', icon: 'icon-warning' },
].map(({ type, icon }) => ({
    type,
    className: `x-notify x-notify--${type}`,
    icon: { className: `icon ${icon} x-notify__icon`, tagName: 'span', text: '' },
    ...uncoloured,
}));

export function createNotifier(options = {}) {
    const notyf = new Notyf({
        duration: 5000,
        ripple: false,
        dismissible: true,
        position: { x: 'right', y: 'top' },
        ...options,
        types: TYPES,
    });

    return {
        success: (message) => notyf.open({ type: 'success', message }),
        warning: (message) => notyf.open({ type: 'warning', message }),
        error: (message) => notyf.open({ type: 'error', message }),
        dismissAll: () => notyf.dismissAll(),
    };
}

export const notify = createNotifier();

export default function (Alpine) {
    Alpine.magic('notify', () => notify);
}
