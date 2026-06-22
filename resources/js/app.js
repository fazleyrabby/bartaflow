import './bootstrap';
import 'flowbite';

import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';

Alpine.plugin(focus);

/**
 * Global toast store. Blade flashes are read on load (see layouts/app.blade.php);
 * components may also push toasts via Alpine.store('toasts').push(...).
 * See docs/frontend.md §2 (flash/toasts).
 */
Alpine.store('toasts', {
    items: [],
    push({ type = 'info', message = '' }) {
        const id = Date.now() + Math.random();
        this.items.push({ id, type, message });
        setTimeout(() => this.remove(id), 4000);
    },
    remove(id) {
        this.items = this.items.filter((t) => t.id !== id);
    },
});

window.Alpine = Alpine;
Alpine.start();
