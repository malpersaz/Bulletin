class AnnouncementsListManager {
    constructor() {
        this.container = document.getElementById('announcements-list-container');
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

    stripHtml(html) {
        const tmp = document.createElement("DIV");
        tmp.innerHTML = html;
        return tmp.textContent || tmp.innerText || "";
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
            // item is array: [checkbox_html, id, name_html, image_html, lang_html, created_at, status_html, actions_html]
            const id = this.stripHtml(item[1]);
            const name = this.stripHtml(item[2]);
            const imageHtml = item[3];
            const lang = this.stripHtml(item[4]);
            const date = this.stripHtml(item[5]);
            const statusHtml = item[6];

            // Extract Image Src from imageHtml
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = imageHtml;
            const imgTag = tempDiv.querySelector('img');
            const imgSrc = imgTag ? imgTag.src : '';

            // Extract Status Raw
            const isSuccess = statusHtml.indexOf('label-success') !== -1;
            const statusColor = isSuccess ? '#1e4620' : '#b3261e';
            const statusBackground = isSuccess ? '#e6f4ea' : '#fdeded';
            const statusText = isSuccess ? (ANNOUNCEMENT_LANG.active || 'Aktîf') : (ANNOUNCEMENT_LANG.draft || 'Arşîv');

            const card = document.createElement('div');
            card.className = 'm3-card content-card';
            card.dataset.id = id;

            card.innerHTML = `
                <div style="display:flex; gap:16px; align-items:center;">
                    <div style="width:56px; height:56px; border-radius:var(--af-radius-md); background:var(--md-sys-color-surface-container); display:flex; align-items:center; justify-content:center; flex-shrink:0; overflow:hidden;">
                        ${imgSrc ? `<img src="${this.escapeHtml(imgSrc)}" style="width:100%; height:100%; object-fit:cover;">` : '<i class="bi bi-megaphone" style="font-size:24px; color:var(--md-sys-color-primary);"></i>'}
                    </div>
                    <div style="flex:1; min-width:0;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                            <span style="font-size:10px; font-weight:700; background:${statusBackground}; color:${statusColor}; padding:2px 6px; border-radius:var(--af-radius-xs); text-transform:uppercase;">${this.escapeHtml(statusText)}</span>
                            <span style="font-size:10px; font-weight:700; color:var(--md-sys-color-on-surface-variant);">${this.escapeHtml(lang)}</span>
                            <span style="font-size:10px; color:var(--md-sys-color-on-surface-variant);">${this.escapeHtml(date)}</span>
                        </div>
                        <h3 style="margin:0; font-size:16px; font-weight:700; color:var(--md-sys-color-on-surface); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${this.escapeHtml(name)}</h3>
                    </div>
                </div>
            `;

            card.onclick = () => this.selectCard(card, id);
            card.ondblclick = () => location.href = `${MODULE_URL}edit/${id}`;
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
    manager = new AnnouncementsListManager();
});

function editSelected() {
    if (manager.selectedId) location.href = `${MODULE_URL}edit/${manager.selectedId}`;
}

function gallerySelected() {
    if (manager.selectedId) location.href = `${SITE_URL}mod_gallery/list/mod_announcements/${manager.selectedId}`;
}

function filesSelected() {
    if (manager.selectedId) location.href = `${SITE_URL}mod_attachment/list/mod_announcements/${manager.selectedId}`;
}

async function deleteSelected() {
    if (!manager.selectedId) return;
    if (!confirm(ANNOUNCEMENT_LANG.delete_confirm || 'Gelo hûn guman dikin ku dixwazin vê tomarê jê bibin?')) return;

    const formData = new FormData();
    formData.append('delete[]', manager.selectedId);

    try {
        const res = await fetch(`${MODULE_URL}delete`, { method: 'POST', body: formData });
        const text = await res.text();
        if (text.trim() === 'OK') {
            manager.loadData(true);
        } else {
            alert(text || 'Şaşiyek çêbû.');
        }
    } catch (e) {
        console.error(e);
    }
}
