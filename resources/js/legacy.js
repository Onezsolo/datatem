import Alpine from 'alpinejs';
import { createAuthStore } from './stores/auth.js';
import { alertModalStore } from './stores/alert-modal.js';

window.Alpine = Alpine;
Alpine.store('auth', createAuthStore());
Alpine.store('alertModal', alertModalStore);
window.showAlert = (message, title, type) => {
    if (window.Alpine && window.Alpine.store && window.Alpine.store('alertModal')) {
        window.Alpine.store('alertModal').open(message || '', title || 'Notice', type || 'info');
    } else {
        window.alert(message);
    }
};

Alpine.start();
