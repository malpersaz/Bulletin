/**
 * mod_newsletter.js - Admin Newsletter List Interactions
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        if (typeof openNewsletterModal === 'function') {
            openNewsletterModal();
        }
    });

    window.deleteSelectedSubscribers = function() {
        // Toolbar batch delete handler
        const selected = document.querySelectorAll('.subscriber-card.selected');
        if (!selected.length) return;
        // Batch delete implementation
    };
})();
