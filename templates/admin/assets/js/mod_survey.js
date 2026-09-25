class SurveyListManager {
    constructor() {
        this.container = document.getElementById('survey-list-container');
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
                const filterSearch = document.getElementById('filterSearch');
                if (filterSearch) filterSearch.value = e.target.value;
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
        if (this.loader) this.loader.classList.remove('u-hidden');

        if (reset) {
            this.start = 0;
            this.draw = 1;
            this.hasMore = true;
            this.selectedId = null;
            if (this.container) this.container.innerHTML = '';
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
                if (this.emptyState) this.emptyState.classList.remove('u-hidden');
            } else {
                if (this.emptyState) this.emptyState.classList.add('u-hidden');
                this.renderItems(items);
                this.start += items.length;
                this.draw++;
                if (items.length < this.length) {
                    this.hasMore = false;
                }
            }
        } catch (error) {
            console.error("Failed to load surveys:", error);
        } finally {
            this.isLoading = false;
            if (this.loader) this.loader.classList.add('u-hidden');
        }
    }

    renderItems(items) {
        if (!this.container) return;
        const fragment = document.createDocumentFragment();

        items.forEach(item => {
            const card = document.createElement('div');
            card.className = `interactive-card survey-card ${this.selectedId === item.id ? 'active' : ''}`;
            card.dataset.id = item.id;

            const isDefault = item.is_default === 'y';
            const statusClass = item.status === 'y' ? 'bg-success text-white' : 'bg-secondary text-white';
            const statusText = item.status === 'y' ? (SURVEY_LANG.active || 'Active') : (SURVEY_LANG.draft || 'Draft');

            card.innerHTML = `
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="card-title mb-0">${this.escapeHtml(this.stripHtml(item.question))}</h6>
                        <div>
                            ${isDefault ? '<span class="badge bg-warning text-dark me-1"><i class="bi bi-star-fill"></i> Default</span>' : ''}
                            <span class="badge ${statusClass}">${statusText}</span>
                        </div>
                    </div>
                    <div class="card-meta text-muted small d-flex justify-content-between">
                        <span><i class="bi bi-calendar3"></i> ${item.created_at || ''}</span>
                        <span><i class="bi bi-translate"></i> ${item.lang ? item.lang.toUpperCase() : ''}</span>
                    </div>
                </div>
            `;

            card.addEventListener('click', () => this.selectItem(item.id, card));
            fragment.appendChild(card);
        });

        this.container.appendChild(fragment);
    }

    selectItem(id, cardElement) {
        if (this.selectedId === id) {
            this.selectedId = null;
            cardElement.classList.remove('active');
            this.updateToolbar(false);
        } else {
            const prevActive = this.container.querySelector('.interactive-card.active');
            if (prevActive) prevActive.classList.remove('active');

            this.selectedId = id;
            cardElement.classList.add('active');
            this.updateToolbar(true);
        }
    }

    updateToolbar(enabled) {
        const toolbarBtns = document.querySelectorAll('.survey-toolbar .toolbar-item');
        toolbarBtns.forEach(btn => {
            if (enabled) {
                btn.classList.remove('toolbar-disabled');
            } else {
                btn.classList.add('toolbar-disabled');
            }
        });
    }
}

function editSelected() {
    if (!window.manager || !window.manager.selectedId) return;
    location.href = `${MODULE_URL}edit/${window.manager.selectedId}`;
}

async function setDefaultSelected() {
    if (!window.manager || !window.manager.selectedId) return;
    if (!confirm(SURVEY_LANG.set_default_confirm || 'Are you sure you want to set this survey as default?')) return;

    const formData = new FormData();
    formData.append('id', window.manager.selectedId);

    try {
        const res = await fetch(`${MODULE_URL}set_default`, { method: 'POST', body: formData });
        const text = await res.text();
        if (text.trim() === 'Success') {
            window.manager.loadData(true);
        } else {
            alert(text || 'An error occurred.');
        }
    } catch (e) {
        console.error(e);
    }
}

async function deleteSelected() {
    if (!window.manager || !window.manager.selectedId) return;
    if (!confirm(SURVEY_LANG.delete_confirm || 'Are you sure you want to delete this record?')) return;

    const formData = new FormData();
    formData.append('delete[]', window.manager.selectedId);

    try {
        const res = await fetch(`${MODULE_URL}delete`, { method: 'POST', body: formData });
        const text = await res.text();
        if (text.trim() === 'Success') {
            window.manager.loadData(true);
        } else {
            alert(text || 'An error occurred.');
        }
    } catch (e) {
        console.error(e);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.manager = new SurveyListManager();
    const btnDel = document.getElementById('toolbarDelete');
    if (btnDel) btnDel.addEventListener('click', deleteSelected);
    const btnDef = document.getElementById('toolbarDefault');
    if (btnDef) btnDef.addEventListener('click', setDefaultSelected);
    const btnEdit = document.getElementById('toolbarEdit');
    if (btnEdit) btnEdit.addEventListener('click', editSelected);
    const mainFab = document.getElementById('mainFab');
    if (mainFab && mainFab.dataset.href) {
        mainFab.addEventListener('click', () => { window.location.href = mainFab.dataset.href; });
    }
});
