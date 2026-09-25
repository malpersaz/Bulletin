class DocumentListManager {
    constructor() {
        this.container = document.getElementById('document-list-container');
        this.loader = document.getElementById('infinite-loader');
        this.emptyState = document.getElementById('empty-state');
        this.filterForm = document.getElementById('filterForm');
        this.selectedId = null;
        this.isLoading = false;
        this.hasMore = true;
        this.start = 0;
        this.length = 10;
        this.draw = 1;

        this.init();
    }

    init() {
        this.loadData(true);
        this.initSearchListener();
        window.refreshContent = () => this.loadData(true);
    }

    initSearchListener() {
        const searchInput = document.getElementById('searchInput');
        if (!searchInput) return;

        let timeout = null;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                document.getElementById('filterSearch').value = e.target.value;
                this.loadData(true);
            }, 350);
        });
    }

    escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            }[char];
        });
    }

    async loadData(reset = false) {
        if (this.isLoading || (!this.hasMore && !reset)) return;

        this.isLoading = true;
        if (this.loader) this.loader.style.display = 'flex';

        if (reset) {
            this.start = 0;
            this.draw = 1;
            this.hasMore = true;
            this.selectedId = null;
            this.container.innerHTML = '';
            this.updateToolbar(false);
        }

        try {
            const formData = new FormData(this.filterForm);
            formData.set('start', this.start);
            formData.set('length', this.length);
            formData.set('draw', this.draw);

            const response = await fetch(DATA_URL, { method: 'POST', body: formData });
            const result = await response.json();
            const items = result.data || [];

            if (reset && items.length === 0) {
                this.emptyState.style.display = 'grid';
            } else {
                this.emptyState.style.display = 'none';
                this.renderCards(items);
            }

            if (typeof result.totalDocument !== 'undefined') {
                const totalEl = document.getElementById('statTotalDocument');
                if (totalEl) totalEl.textContent = result.totalDocument;
            }
            if (typeof result.liveDocument !== 'undefined') {
                const liveEl = document.getElementById('statLiveDocument');
                if (liveEl) liveEl.textContent = result.liveDocument;
            }

            this.start += items.length;
            this.draw += 1;
            this.hasMore = this.start < Number(result.recordsFiltered || 0);
        } catch (e) {
            console.error(e);
        } finally {
            this.isLoading = false;
            if (this.loader) this.loader.style.display = 'none';
        }
    }

    renderCards(items) {
        items.forEach(item => {
            const card = document.createElement('div');
            card.className = 'm3-card content-card';
            card.dataset.id = item.id;
            
            const statusColor = item.status === 'y' ? '#1e4620' : '#0b57d0';
            const statusBackground = item.status === 'y' ? '#e6f4ea' : '#e8f0fe';
            const statusText = item.status === 'y' ? DOCUMENT_LANG.active : DOCUMENT_LANG.draft;
            const safeImageUrl = this.escapeHtml(item.image_url);
            const safeStatusText = this.escapeHtml(statusText);
            const safeLang = this.escapeHtml(item.lang);
            const safeName = this.escapeHtml(item.name);

            card.innerHTML = `
                <div style="display:flex; gap:16px; align-items:center;">
                    <div style="width:56px; height:56px; border-radius:var(--af-radius-md); background:var(--md-sys-color-surface-container); display:flex; align-items:center; justify-content:center; flex-shrink:0; overflow:hidden;">
                        ${item.image_url ? `<img src="${safeImageUrl}" style="width:100%; height:100%; object-fit:cover;">` : '<i class="bi bi-file-earmark-text" style="font-size:24px; color:var(--md-sys-color-primary);"></i>'}
                    </div>
                    <div style="flex:1; min-width:0;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                            <span style="font-size:10px; font-weight:700; background:${statusBackground}; color:${statusColor}; padding:2px 6px; border-radius:var(--af-radius-xs); text-transform:uppercase;">${safeStatusText}</span>
                            <span style="font-size:10px; font-weight:700; color:var(--md-sys-color-on-surface-variant);">${safeLang}</span>
                        </div>
                        <h3 style="margin:0; font-size:16px; font-weight:700; color:var(--md-sys-color-on-surface); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${safeName}</h3>
                    </div>
                    <div style="display:flex; gap:8px; color:var(--md-sys-color-on-surface-variant);">
                        <i class="bi bi-images" style="${item.has_gallery ? 'color:var(--md-sys-color-primary);' : 'opacity:0.2;'}"></i>
                        <i class="bi bi-paperclip" style="${item.has_file ? 'color:var(--md-sys-color-primary);' : 'opacity:0.2;'}"></i>
                    </div>
                </div>
            `;

            card.onclick = () => this.selectCard(card, item.id);
            card.ondblclick = () => location.href = `${MODULE_URL}edit/${item.id}`;
            this.container.appendChild(card);
        });
    }

    selectCard(card, id) {
        const isSelected = card.classList.contains('selected');
        document.querySelectorAll('.content-card.selected').forEach(c => c.classList.remove('selected'));
        
        if (isSelected) {
            this.selectedId = null;
            this.updateToolbar(false);
        } else {
            card.classList.add('selected');
            this.selectedId = id;
            this.updateToolbar(true);
            if (window.navigator.vibrate) window.navigator.vibrate(5);
        }
    }

    updateToolbar(active) {
        ['toolbarEdit', 'toolbarGallery', 'toolbarFiles', 'toolbarDelete'].forEach(id => {
            const btn = document.getElementById(id);
            if (!btn) return;
            btn.style.opacity = active ? '1' : '0.3';
            btn.style.pointerEvents = active ? 'auto' : 'none';
        });
    }
}

let manager;
document.addEventListener('DOMContentLoaded', () => {
    manager = new DocumentListManager();
});

function editSelected() {
    if (manager.selectedId) location.href = `${MODULE_URL}edit/${manager.selectedId}`;
}

function gallerySelected() {
    if (manager.selectedId) {
        const url = `${SITE_URL}mod_gallery/list/mod_document/${manager.selectedId}`;
        if (window.AFModalEngine) {
            AFModalEngine.push(url, { skipHistory: true });
        } else {
            location.href = url;
        }
    }
}

function filesSelected() {
    if (manager.selectedId) {
        const url = `${SITE_URL}mod_attachment/list/mod_document/${manager.selectedId}`;
        if (window.AFModalEngine) {
            AFModalEngine.push(url, { skipHistory: true });
        } else {
            location.href = url;
        }
    }
}

async function deleteSelected() {
    if (!manager.selectedId) return;
    if (!confirm(DOCUMENT_LANG.delete_confirm)) return;

    const formData = new FormData();
    formData.append('query[]', manager.selectedId);
    formData.append('ajax_request', '1');

    try {
        const res = await fetch(`${MODULE_URL}delete`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });
        const text = await res.text();
        if (text.trim() === 'Success') {
            manager.loadData(true);
        } else {
            if (typeof showSnackbar === 'function') {
                showSnackbar(text || DOCUMENT_LANG.delete_failed, 'error');
            } else {
                alert(text || DOCUMENT_LANG.delete_failed);
            }
        }
    } catch (e) {
        console.error(e);
    }
}
