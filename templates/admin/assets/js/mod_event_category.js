/**
 * Amed Framework - Event Category Management JS
 * templates/admin/assets/js/mod_event_category.js
 */

class EventCategoryManager {
    constructor() {
        this.selectedId = null;
        this.reorderMode = false;
        this.init();
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {
        document.addEventListener('click', (e) => {
            const toggleBtn = e.target.closest('[data-action="toggle-category"]');
            if (toggleBtn) {
                e.stopPropagation();
                this.toggleCategory(toggleBtn);
                return;
            }

            const selectCard = e.target.closest('[data-action="select-category"]');
            if (selectCard) {
                const id = selectCard.dataset.id;
                this.selectCategory(id, selectCard);
                return;
            }

            const reorderBtn = e.target.closest('[data-action="toggle-reorder"]');
            if (reorderBtn) {
                this.toggleReorderMode();
                return;
            }

            const homepageBtn = e.target.closest('[data-action="toggle-homepage"]');
            if (homepageBtn) {
                e.stopPropagation();
                const id = homepageBtn.dataset.id;
                this.toggleHomepage(id, homepageBtn);
                return;
            }
        });
    }

    toggleCategory(button) {
        const node = button.closest('.category-node');
        if (!node) return;
        const children = node.querySelector('.list-children');
        if (!children) return;

        const isCollapsed = children.classList.contains('collapsed');
        if (isCollapsed) {
            children.classList.remove('collapsed');
            button.classList.remove('collapsed');
        } else {
            children.classList.add('collapsed');
            button.classList.add('collapsed');
        }
    }

    selectCategory(id, cardElement) {
        if (this.selectedId === id) {
            this.selectedId = null;
            cardElement.classList.remove('selected');
            this.updateToolbar(false);
            return;
        }

        document.querySelectorAll('.m3-list-card.selected').forEach(el => el.classList.remove('selected'));
        cardElement.classList.add('selected');
        this.selectedId = id;
        this.updateToolbar(true);
    }

    updateToolbar(hasSelection) {
        const toolbar = document.getElementById('contentToolbar');
        if (!toolbar) return;

        if (hasSelection) {
            toolbar.setAttribute('aria-hidden', 'false');
            toolbar.classList.add('visible');
            toolbar.querySelectorAll('.toolbar-item').forEach(btn => btn.classList.remove('is-disabled'));
        } else {
            toolbar.setAttribute('aria-hidden', 'true');
            toolbar.classList.remove('visible');
            toolbar.querySelectorAll('.toolbar-item').forEach(btn => btn.classList.add('is-disabled'));
        }
    }

    toggleReorderMode() {
        this.reorderMode = !this.reorderMode;
        document.body.classList.toggle('reorder-active', this.reorderMode);

        const btn = document.getElementById('reorderToggleBtn');
        const icon = document.getElementById('reorderIcon');
        const text = document.getElementById('reorderText');

        if (this.reorderMode) {
            btn?.classList.add('active');
            if (icon) {
                icon.className = 'bi bi-unlock-fill';
            }
        } else {
            btn?.classList.remove('active');
            if (icon) {
                icon.className = 'bi bi-lock-fill';
            }
        }
    }

    async toggleHomepage(id, button) {
        try {
            const formData = new FormData();
            formData.append('id', id);

            const siteUrl = window.SITE_URL || '/';
            const response = await fetch(`${siteUrl}mod_event_category/toggle_homepage`, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const text = await response.text();
            if (text.includes('Success')) {
                const icon = button.querySelector('i');
                if (icon) {
                    if (icon.classList.contains('bi-eye-fill')) {
                        icon.className = 'bi bi-eye-slash text-muted';
                    } else {
                        icon.className = 'bi bi-eye-fill text-primary';
                    }
                }
            }
        } catch (err) {
            console.error('Failed to toggle homepage status', err);
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.eventCategoryManager = new EventCategoryManager();
});
