/**
 * Amed Framework - Event Module JS (Phone-first Mobile Admin)
 * templates/admin/assets/js/mod_event.js
 */

class EventManager {
    constructor() {
        this.container = document.getElementById('content-list-container');
        if (!this.container) return;

        this.moduleUrl = this.container.dataset.moduleUrl || '/mod_event/';
        this.dataUrl = this.container.dataset.dataUrl || '/mod_event/datatable_json';
        this.categories = [];
        try {
            this.categories = JSON.parse(this.container.dataset.categories || '[]');
        } catch (e) {
            this.categories = [];
        }

        this.selectedId = null;
        this.items = [];
        this.start = 0;
        this.length = 10;
        this.totalRecords = 0;
        this.isLoading = false;
        this.hasMore = true;
        this.activeStatusFilter = 'all';

        this.init();
    }

    init() {
        this.bindEvents();
        this.loadData(true);
    }

    bindEvents() {
        // Status filter buttons
        document.querySelectorAll('.content-stat-card').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.content-stat-card').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                this.activeStatusFilter = btn.dataset.statusFilter || 'all';
                this.loadData(true);
            });
        });

        // Infinite scroll
        window.addEventListener('scroll', () => {
            if (this.isLoading || !this.hasMore) return;
            if ((window.innerHeight + window.scrollY) >= (document.body.offsetHeight - 300)) {
                this.loadData(false);
            }
        });

        // Toolbar delegation
        const toolbar = document.getElementById('contentToolbar');
        if (toolbar) {
            toolbar.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-action]');
                if (!btn || btn.classList.contains('is-disabled')) return;
                const action = btn.dataset.action;
                this.handleToolbarAction(action);
            });
        }

        // Modal image uploader close
        document.querySelectorAll('[data-action="close-image-uploader"]').forEach(el => {
            el.addEventListener('click', () => {
                const modal = document.getElementById('eventImageModal');
                if (modal) modal.classList.remove('active');
            });
        });
    }

    async loadData(reset = false) {
        if (this.isLoading) return;
        this.isLoading = true;

        if (reset) {
            this.start = 0;
            this.items = [];
            this.container.innerHTML = '';
            this.hasMore = true;
            this.selectedId = null;
            this.updateToolbar(false);
        }

        const loader = document.getElementById('infinite-loader');
        if (loader) loader.classList.remove('is-hidden');

        try {
            const formData = new FormData();
            formData.append('start', this.start);
            formData.append('length', this.length);
            formData.append('order[0][column]', 0);
            formData.append('order[0][dir]', 'desc');

            if (this.activeStatusFilter !== 'all') {
                formData.append('search_radio[status]', this.activeStatusFilter);
            }

            const response = await fetch(this.dataUrl, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const json = await response.json();
            const data = json.data || [];
            this.totalRecords = json.recordsTotal || 0;

            // Update stats
            const totalEl = document.getElementById('statTotalEvent');
            const pubEl = document.getElementById('statPublishedEvent');
            const draftEl = document.getElementById('statDraftEvent');
            if (totalEl && json.total_event !== undefined) totalEl.textContent = json.total_event;
            if (pubEl && json.published_event !== undefined) pubEl.textContent = json.published_event;
            if (draftEl && json.draft_event !== undefined) draftEl.textContent = json.draft_event;

            if (data.length === 0 && this.start === 0) {
                const empty = document.getElementById('empty-state');
                if (empty) empty.classList.remove('is-hidden');
            } else {
                const empty = document.getElementById('empty-state');
                if (empty) empty.classList.add('is-hidden');
            }

            this.renderItems(data);
            this.start += data.length;
            if (this.start >= (json.recordsFiltered || this.totalRecords) || data.length === 0) {
                this.hasMore = false;
            }
        } catch (err) {
            console.error('Failed to load events', err);
        } finally {
            this.isLoading = false;
            if (loader) loader.classList.add('is-hidden');
        }
    }

    renderItems(data) {
        data.forEach(row => {
            // row indices: [0: cb, 1: id, 2: name, 3: image_html, 4: lang, 5: start_date, 6: actions, 7: category_id, 8: status, 9: author, 10: slider, 11: deck, 12: end_date, 13: location]
            const id = row[1];
            const name = row[2];
            const lang = row[4];
            const startDate = row[5];
            const status = row[8];
            const location = row[13] || '';

            const isPublished = (status === 'y');
            const card = document.createElement('div');
            card.className = 'content-card';
            card.dataset.id = id;

            // Extract image src from row[3] if present
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = row[3];
            const imgEl = tempDiv.querySelector('img');
            const imgSrc = imgEl ? imgEl.src : '';

            card.innerHTML = `
                <div class="card-content-layout">
                    <div class="card-thumbnail" data-action="quick-image" data-id="${id}">
                        ${imgSrc ? `<img src="${imgSrc}" class="card-thumb-img" alt="${name}">` : `<div class="thumb-empty-placeholder"><i class="bi bi-calendar-event"></i></div>`}
                        <div class="thumb-edit-overlay"><i class="bi bi-camera"></i></div>
                    </div>
                    <div class="card-body-layout">
                        <div>
                            <div class="card-meta-top">
                                <span class="badge-pill badge-lang">${lang.toUpperCase()}</span>
                                <span class="badge-pill badge-status ${isPublished ? '' : 'draft'}">${isPublished ? 'PUB' : 'DRAFT'}</span>
                            </div>
                            <div class="card-headline">${name}</div>
                        </div>
                        <div class="card-meta-bottom">
                            <span class="meta-item"><i class="bi bi-clock"></i> ${startDate}</span>
                            ${location ? `<span class="meta-item"><i class="bi bi-geo-alt"></i> ${location}</span>` : ''}
                        </div>
                    </div>
                </div>
            `;

            card.addEventListener('click', (e) => {
                const thumb = e.target.closest('[data-action="quick-image"]');
                if (thumb) {
                    e.stopPropagation();
                    this.openImageModal(id, name);
                    return;
                }
                this.selectCard(id, card);
            });

            this.container.appendChild(card);
        });
    }

    selectCard(id, card) {
        if (this.selectedId === id) {
            this.selectedId = null;
            card.classList.remove('selected');
            this.updateToolbar(false);
            return;
        }

        document.querySelectorAll('.content-card.selected').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
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

    async handleToolbarAction(action) {
        if (!this.selectedId) return;

        switch (action) {
            case 'toggle-status':
                await this.postAction('toggle_status');
                break;
            case 'toggle-deck':
                await this.postAction('toggle_deck');
                break;
            case 'toggle-slider':
                await this.postAction('toggle_hero_slider');
                break;
            case 'open-gallery':
                location.href = `${window.SITE_URL || '/'}mod_gallery/list/mod_event/${this.selectedId}`;
                break;
            case 'open-files':
                location.href = `${window.SITE_URL || '/'}mod_attachment/list/mod_event/${this.selectedId}`;
                break;
            case 'open-faq':
                location.href = `${window.SITE_URL || '/'}mod_faq/list/mod_event/${this.selectedId}`;
                break;
            case 'open-video':
                location.href = `${this.moduleUrl}edit/${this.selectedId}#video`;
                break;
            case 'edit-content':
                location.href = `${this.moduleUrl}edit/${this.selectedId}`;
                break;
            case 'delete-content':
                if (confirm('Are you sure you want to delete this event?')) {
                    await this.postAction('delete', { id: this.selectedId });
                }
                break;
        }
    }

    async postAction(endpoint, extraData = {}) {
        try {
            const formData = new FormData();
            formData.append('id', this.selectedId);
            for (const [key, value] of Object.entries(extraData)) {
                formData.append(key, value);
            }

            const response = await fetch(`${this.moduleUrl}${endpoint}`, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const result = await response.json().catch(() => null);
            this.loadData(true);
        } catch (err) {
            console.error(`Action ${endpoint} failed`, err);
        }
    }

    openImageModal(id, name) {
        const modal = document.getElementById('eventImageModal');
        const subtitle = document.getElementById('eventImageModalSubtitle');
        if (subtitle) {
            subtitle.textContent = name;
            subtitle.hidden = false;
        }
        if (modal) modal.classList.add('active');
    }
}

// Edit Form Handler
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('content-list-container')) {
        window.eventManager = new EventManager();
    }

    const editForm = document.getElementById('editForm');
    if (editForm) {
        // Toggle selection cards (Deck, Slider, Status)
        document.querySelectorAll('.selection-card[data-action]').forEach(card => {
            card.addEventListener('click', () => {
                const action = card.dataset.action;
                const value = card.dataset.value;
                const parent = card.closest('.selection-grid');
                if (parent) {
                    parent.querySelectorAll('.selection-card').forEach(c => c.classList.remove('active'));
                    card.classList.add('active');
                }

                if (action === 'select-deck') {
                    const hidden = document.getElementById('deckHidden');
                    if (hidden) hidden.value = value;
                } else if (action === 'select-slider') {
                    const hidden = document.getElementById('sliderHidden');
                    if (hidden) hidden.value = value;
                } else if (action === 'select-status') {
                    const hidden = document.getElementById('statusHidden');
                    if (hidden) hidden.value = value;
                }
            });
        });

        // Summary char counter
        const intro = document.getElementById('editIntroInput');
        const counter = document.getElementById('editIntroCounter');
        if (intro && counter) {
            intro.addEventListener('input', () => {
                counter.textContent = `${intro.value.length} / 255`;
            });
        }
    }
});
