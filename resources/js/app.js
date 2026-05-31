/**
 * Storefront dialog accessibility helper.
 *
 * A self-contained focus manager for the cart drawer and search modal (which
 * are server-rendered Livewire dialogs toggled by an `open` property). It does
 * not depend on the Alpine Focus plugin: on open it moves focus to the first
 * focusable element inside the dialog and remembers the trigger; on close it
 * restores focus to the trigger; and `trapTab` keeps Tab/Shift+Tab cycling
 * within the dialog while it is open.
 *
 * Usage (Blade):
 *   <div x-data="storefrontDialog($wire, 'open')"
 *        x-on:keydown.tab="trapTab($event)" ...>
 *
 * @param {object} wire  The Livewire $wire proxy for the dialog component.
 * @param {string} prop  The boolean property name that holds the open state.
 */
window.storefrontDialog = function (wire, prop) {
    return {
        previouslyFocused: null,

        init() {
            // React to open-state changes coming from the server (Livewire).
            this.$watch(`$wire.${prop}`, (isOpen) => {
                if (isOpen) {
                    this.onOpen();
                } else {
                    this.onClose();
                }
            });

            if (wire[prop]) {
                this.onOpen();
            }
        },

        onOpen() {
            this.previouslyFocused = document.activeElement;
            // Defer until the dialog content is visible in the DOM.
            this.$nextTick(() => {
                const first = this.focusable()[0];
                if (first) {
                    first.focus();
                }
            });
        },

        onClose() {
            const target = this.previouslyFocused;
            this.previouslyFocused = null;
            if (target && typeof target.focus === 'function') {
                this.$nextTick(() => target.focus());
            }
        },

        /**
         * All visible, focusable elements within the dialog root.
         */
        focusable() {
            const selector = [
                'a[href]',
                'button:not([disabled])',
                'input:not([disabled])',
                'select:not([disabled])',
                'textarea:not([disabled])',
                '[tabindex]:not([tabindex="-1"])',
            ].join(',');

            return Array.from(this.$root.querySelectorAll(selector)).filter(
                (el) => el.offsetParent !== null,
            );
        },

        /**
         * Keep Tab focus cycling within the dialog.
         */
        trapTab(event) {
            if (!wire[prop]) {
                return;
            }

            const items = this.focusable();
            if (items.length === 0) {
                return;
            }

            const first = items[0];
            const last = items[items.length - 1];
            const active = document.activeElement;

            if (event.shiftKey && active === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && active === last) {
                event.preventDefault();
                first.focus();
            } else if (!this.$root.contains(active)) {
                // Focus escaped the dialog; pull it back in.
                event.preventDefault();
                first.focus();
            }
        },
    };
};
