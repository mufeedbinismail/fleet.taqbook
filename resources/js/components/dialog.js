'use strict';

// Imperative confirm dialog, in the fire()/then() shape SweetAlert2 uses, rather than the
// declarative x-collapse/x-accordion/x-drawer directives: there is exactly one layout (icon,
// title, text, cancel/confirm), so a caller has options to pass, not markup to author. One native
// <dialog> is built lazily and reused for every call.
//
//   const ok = await this.$confirm({
//       title: 'Delete this role?',
//       text: 'Every permission granted to Sales will be removed. This cannot be undone.',
//       confirmText: 'Yes, delete',
//       danger: true,
//   });
//   if (ok) destroy();
//
// Resolves true on confirm, false on cancel, Escape, or a backdrop click — a dismissed dialog is
// not an error, so there is no reject path.
const ICONS = {
    warning: 'icon-warning',
    info: 'icon-info',
    success: 'icon-check',
    question: 'icon-help',
};

let dialogEl = null;

export default function (Alpine) {
    Alpine.magic('confirm', () => fire);
}

function dialog() {
    return (dialogEl ??= build());
}

function build() {
    const el = document.createElement('dialog');
    el.className = 'x-dialog';
    el.innerHTML = `
        <div class="x-dialog-body">
            <h3 class="x-dialog-title">
                <span class="x-dialog-icon icon" aria-hidden="true"></span>
                <span data-title></span>
            </h3>
            <p class="x-dialog-text" data-text></p>
        </div>
        <div class="x-dialog-actions">
            <button type="button" class="x-dialog-button x-dialog-button--cancel" data-cancel></button>
            <button type="button" class="x-dialog-button x-dialog-button--confirm" data-confirm></button>
        </div>
    `;

    document.body.appendChild(el);

    return el;
}

function fire({
    title = '',
    text = '',
    icon = 'warning',
    danger = false,
    confirmText = 'OK',
    cancelText = 'Cancel',
} = {}) {
    const el = dialog();

    // A call arriving while the previous one is still open supersedes it rather than queuing —
    // closing here settles that earlier promise (via the 'close' listener below) before this
    // call's own listeners go on.
    if (el.open) el.close();

    el.querySelector('[data-title]').textContent = title;
    el.querySelector('[data-text]').textContent = text;
    el.querySelector('.x-dialog-icon').className =
        `x-dialog-icon icon ${ICONS[icon] ?? ICONS.warning}`;
    el.classList.toggle('x-dialog--danger', danger);

    const confirmBtn = el.querySelector('[data-confirm]');
    const cancelBtn = el.querySelector('[data-cancel]');
    confirmBtn.textContent = confirmText;
    cancelBtn.textContent = cancelText;

    el.showModal();

    return new Promise((resolve) => {
        function settle(result) {
            el.removeEventListener('close', onClose);
            el.removeEventListener('click', onBackdrop);
            confirmBtn.removeEventListener('click', onConfirm);
            cancelBtn.removeEventListener('click', onCancel);
            resolve(result);
        }

        // close() fires 'close' synchronously, so settling first keeps that listener from
        // resolving this same promise a second time.
        function onConfirm() {
            settle(true);
            el.close();
        }
        function onCancel() {
            settle(false);
            el.close();
        }
        function onClose() {
            settle(false);
        }
        function onBackdrop(event) {
            if (event.target === el) onCancel();
        }

        confirmBtn.addEventListener('click', onConfirm);
        cancelBtn.addEventListener('click', onCancel);
        el.addEventListener('close', onClose);
        el.addEventListener('click', onBackdrop);
    });
}
