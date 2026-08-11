'use strict';

import { attach } from './modal';

//   const ok = await this.$confirm({
//       title: 'Delete this role?',
//       text: 'Every permission granted to Sales will be removed. This cannot be undone.',
//       confirmText: 'Yes, delete',
//       danger: true,
//   });
//   if (ok) destroy();
//
// Resolves true on confirm and false on every dismissal: a dialog waved away is not an error, so
// there is no reject path.
const ICONS = {
    warning: 'icon-warning',
    info: 'icon-info',
    success: 'icon-button-ok',
    question: 'icon-help-outline',
};

let handle = null;

export default function (Alpine) {
    Alpine.magic('confirm', () => fire);
}

function dialog() {
    return (handle ??= attach(build()));
}

function build() {
    const el = document.createElement('dialog');
    el.className = 'x-modal x-modal--sm x-modal--none';
    el.innerHTML = `
        <div class="x-modal__body">
            <h3 class="x-dialog__title">
                <span class="x-dialog__icon icon" aria-hidden="true"></span>
                <span data-title></span>
            </h3>
            <p class="x-dialog__text" data-text></p>
        </div>
        <div class="x-modal__foot">
            <button type="button" class="x-dialog__button x-dialog__button--cancel" data-cancel></button>
            <button type="button" class="x-dialog__button x-dialog__button--confirm" data-confirm></button>
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
    const { el, show, hide } = dialog();

    // A call arriving while the previous one is still open supersedes it rather than queuing:
    // closing here settles the earlier promise before this call's own listeners go on.
    if (el.open) hide();

    el.querySelector('[data-title]').textContent = title;
    el.querySelector('[data-text]').textContent = text;
    el.querySelector('.x-dialog__icon').className =
        `x-dialog__icon icon ${ICONS[icon] ?? ICONS.warning}`;
    el.classList.toggle('x-dialog--danger', danger);

    const confirmBtn = el.querySelector('[data-confirm]');
    const cancelBtn = el.querySelector('[data-cancel]');
    confirmBtn.textContent = confirmText;
    cancelBtn.textContent = cancelText;

    show();

    return new Promise((resolve) => {
        function settle(result) {
            el.removeEventListener('close', onClose);
            confirmBtn.removeEventListener('click', onConfirm);
            cancelBtn.removeEventListener('click', onCancel);
            resolve(result);
        }

        // close() fires 'close' synchronously, so settling first keeps that listener from
        // resolving this same promise a second time.
        function onConfirm() {
            settle(true);
            hide();
        }
        function onCancel() {
            settle(false);
            hide();
        }
        function onClose() {
            settle(false);
        }

        confirmBtn.addEventListener('click', onConfirm);
        cancelBtn.addEventListener('click', onCancel);
        el.addEventListener('close', onClose);
    });
}
