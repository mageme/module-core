/**
 * MageMe house dialog: <dialog>-based replacement for swal (.fire) and tingle (.modal).
 * Exposes BOTH APIs from one object so legacy callers keep working when it is
 * injected under the swal/tingle option slots. Native <dialog> gives Esc, focus-trap and a
 * backdrop for free; the box is appended to <body> so it survives teardown of a parent popup.
 * The open element carries the `tingle-modal--visible` class as a hook for the colour picker.
 * `window.mmDialogClasses` may map each BEM hook to extra utility classes (utility-first storefronts
 * supply Tailwind strings there and skip dialog.css); the BEM hooks are always kept.
 */
(function (root, factory) {
    'use strict';
    const dialog = factory();
    root.mmDialog = dialog;
    if (typeof define === 'function') {
        define('MageMe_Core/js/dialog', [], () => dialog);
    }
}(typeof self !== 'undefined' ? self : this, () => {
    'use strict';

    const cls = (base, key) => {
        const extra = (globalThis.mmDialogClasses || {})[key];
        return extra ? `${base} ${extra}` : base;
    };

    class Modal {
        constructor(opts = {}) {
            this.opts = opts;
            this.dialog = document.createElement('dialog');
            this.dialog.className = cls('mm-dialog tingle-modal--visible', 'dialog');
            if (Array.isArray(opts.cssClass)) {
                opts.cssClass.forEach((cls) => this.dialog.classList.add(cls));
            }
            this.modalBox = document.createElement('div');
            this.modalBox.className = cls('mm-dialog__box', 'box');
            this.modalBoxContent = document.createElement('div');
            this.modalBoxContent.className = cls('mm-dialog__content', 'content');
            this.modalBox.appendChild(this.modalBoxContent);
            this.dialog.appendChild(this.modalBox);
            this.dialog.addEventListener('click', (event) => {
                if (event.target === this.dialog) {
                    this.close();
                }
            });
            this.dialog.addEventListener('cancel', () => this.close());
            this.dialog.addEventListener('close', () => {
                if (!this.dialog.open) {
                    this.teardown();
                }
            });
            this.dialog.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    event.mmDialogPrevented = event.defaultPrevented;
                }
            });
            this.onWindowKeydown = (event) => {
                if (event.key !== 'Escape' || !this.isTopmost()) {
                    return;
                }
                if (this.dialog.contains(event.target) && event.mmDialogPrevented) {
                    return;
                }
                event.preventDefault();
                this.close();
            };
        }

        isTopmost() {
            const open = document.querySelectorAll('dialog.mm-dialog[open]');
            return open.length > 0 && open[open.length - 1] === this.dialog;
        }

        setContent(node) {
            this.modalBoxContent.replaceChildren();
            if (typeof node === 'string') {
                this.modalBoxContent.innerHTML = node;
            } else if (node) {
                this.modalBoxContent.appendChild(node);
            }
            return this;
        }

        getContent() {
            return this.modalBoxContent;
        }

        open() {
            this.torndown = false;
            if (typeof this.opts.beforeOpen === 'function') {
                this.opts.beforeOpen();
            }
            if (!this.dialog.isConnected) {
                document.body.appendChild(this.dialog);
            }
            document.body.classList.add(...cls('tingle-enabled', 'bodyOpen').split(' '));
            if (!this.dialog.open) {
                this.dialog.showModal();
            }
            window.addEventListener('keydown', this.onWindowKeydown);
            return this;
        }

        close() {
            if (this.dialog.open) {
                this.dialog.close();
            }
            this.teardown();
            return this;
        }

        teardown() {
            if (this.torndown) {
                return this;
            }
            this.torndown = true;
            window.removeEventListener('keydown', this.onWindowKeydown);
            document.body.classList.remove(...cls('tingle-enabled', 'bodyOpen').split(' '));
            this.dialog.remove();
            if (typeof this.opts.onClose === 'function') {
                this.opts.onClose();
            }
            return this;
        }
    }

    const ICON_MARK = {
        error: '<path d="M29 29 51 51M51 29 29 51" stroke="currentColor" stroke-width="4.5" stroke-linecap="round"/>',
        success: '<path d="M25 41 36 52 56 28" fill="none" stroke="currentColor" stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round"/>',
        warning: '<path d="M40 22v25" stroke="currentColor" stroke-width="5" stroke-linecap="round"/><circle cx="40" cy="58" r="3.2" fill="currentColor"/>',
        info: '<circle cx="40" cy="24" r="3.2" fill="currentColor"/><path d="M40 34v24" stroke="currentColor" stroke-width="5" stroke-linecap="round"/>'
    };

    const iconSvg = (type) => {
        if (!ICON_MARK[type]) {
            return '';
        }
        return `<svg class="${cls('', 'iconSvg').trim()}" viewBox="0 0 80 80" width="80" height="80" aria-hidden="true">`
            + `<circle cx="40" cy="40" r="35" fill="none" stroke="currentColor" stroke-width="4"/>`
            + `${ICON_MARK[type]}</svg>`;
    };

    const fire = (config = {}) => {
        const modal = new Modal();
        modal.dialog.className = cls('mm-dialog tingle-modal--visible mm-dialog--alert', 'alert');
        modal.modalBoxContent.className = cls('mm-dialog__content', 'alertContent');
        const type = config.type || config.icon || 'info';
        const box = document.createElement('div');
        box.className = cls('mm-swal', 'swal');

        const svg = iconSvg(type);
        if (svg) {
            const iconEl = document.createElement('div');
            iconEl.className = cls(cls('mm-swal__icon mm-swal__icon--' + type, 'icon'), 'icon_' + type);
            iconEl.innerHTML = svg;
            box.appendChild(iconEl);
        }
        if (config.title) {
            const title = document.createElement('h2');
            title.className = cls('mm-swal__title', 'title');
            title.textContent = config.title;
            box.appendChild(title);
        }
        const body = document.createElement('div');
        body.className = cls('mm-swal__html', 'html');
        // Parity with the previous swal.fire: server/i18n messages render as HTML.
        body.innerHTML = config.html || config.text || '';
        box.appendChild(body);

        const actions = document.createElement('div');
        actions.className = cls('mm-swal__actions', 'actions');
        const ok = document.createElement('button');
        ok.type = 'button';
        ok.className = cls('mm-swal__confirm action primary btn btn-primary', 'confirm');
        ok.textContent = config.confirmButtonText || 'OK';
        ok.addEventListener('click', () => modal.close());
        actions.appendChild(ok);
        box.appendChild(actions);

        modal.setContent(box);
        modal.open();
        ok.focus();
        return Promise.resolve({ isConfirmed: true });
    };

    return { modal: Modal, fire };
}));
