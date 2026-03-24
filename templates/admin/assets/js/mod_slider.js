/**
 * mod_slider.js
 * Handles fetching data from existing Datatable JSON endpoint and rendering M3 Cards.
 */

class SliderManager {
    constructor() {
        this.container = document.getElementById('content-list-container');
        this.loader = document.getElementById('infinite-loader');
        this.emptyState = document.getElementById('empty-state');

        this.isLoading = false;
        this.hasMore = true;
        this.selectedId = null;

        this.start = 0;
        this.length = 10;
        this.draw = 1;

        this.init();
    }

    init() {
        this.createLoadMoreButton();
        this.loadData();
        this.initSearchListener();
        this.initPullToRefresh();
    }

    createLoadMoreButton() {
        this.loadMoreBtn = document.createElement('button');
        this.loadMoreBtn.className = 'm3-btn-outlined';
        this.loadMoreBtn.style.cssText = 'display:none; margin: 20px auto; width: fit-content; cursor:pointer;';

        let btnText = (typeof CONTENT_LANG !== 'undefined' && CONTENT_LANG.load_more) ? CONTENT_LANG.load_more : 'Load More';
        this.loadMoreBtn.innerHTML = `<i class="bi bi-arrow-down-circle" style="margin-right:8px;"></i>${btnText}`;

        if (this.container && this.loader) {
            this.container.parentNode.insertBefore(this.loadMoreBtn, this.loader);
        }

        this.loadMoreBtn.onclick = () => {
            this.loadData();
        };
    }

    async loadData(reset = false) {
        if (this.isLoading || (!this.hasMore && !reset)) return;
        this.isLoading = true;

        if (this.loadMoreBtn) {
            this.loadMoreBtn.disabled = true;
            this.loadMoreBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="margin-right:8px;"></span>...`;
        }

        if (reset) {
            this.start = 0;
            if (this.container) this.container.innerHTML = '';
            this.hasMore = true;
            this.draw = 1;
            if (this.emptyState) this.emptyState.style.display = 'none';
            if (this.loader) this.loader.style.display = 'flex';
            if (this.loadMoreBtn) this.loadMoreBtn.style.display = 'none';
        } else {
            if (this.loader) this.loader.style.display = 'flex';
        }

        const filterLengthInput = document.getElementById('filterLength');
        if (filterLengthInput && filterLengthInput.value) {
            this.length = parseInt(filterLengthInput.value) || 10;
        }

        const filterForm = document.getElementById('filterForm');
        let formData = new FormData();
        if (filterForm) {
            formData = new FormData(filterForm);
        }
        formData.set('start', this.start);
        formData.set('length', this.length);
        formData.set('draw', this.draw);

        try {
            const response = await fetch(DATA_URL, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            this.renderCards(data.data);

            this.start += this.length;
            this.draw++;

            if (this.start >= data.recordsFiltered) {
                this.hasMore = false;
            }

            if (data.recordsFiltered === 0 && this.emptyState) {
                this.emptyState.style.display = 'flex';
                const searchInput = document.getElementById('filterSearch');
                const descEl = document.getElementById('empty-state-desc');
                if (descEl) {
                    if (searchInput && searchInput.value.trim() !== '') {
                        descEl.innerHTML = (typeof CONTENT_LANG !== 'undefined' && CONTENT_LANG.empty_no_search_results) ? CONTENT_LANG.empty_no_search_results : 'No search results.';
                    } else {
                        descEl.innerHTML = (typeof CONTENT_LANG !== 'undefined' && CONTENT_LANG.empty_no_data) ? CONTENT_LANG.empty_no_data : 'No data yet.';
                    }
                }
            }

        } catch (error) {
            console.error('Data Load Error:', error);
        } finally {
            this.isLoading = false;
            if (this.loader) this.loader.style.display = 'none';

            if (this.loadMoreBtn) {
                this.loadMoreBtn.style.display = (this.hasMore && this.container && this.container.innerHTML !== '') ? 'block' : 'none';
                this.loadMoreBtn.disabled = false;
                let btnText = (typeof CONTENT_LANG !== 'undefined' && CONTENT_LANG.load_more) ? CONTENT_LANG.load_more : 'Load More';
                this.loadMoreBtn.innerHTML = `<i class="bi bi-arrow-down-circle" style="margin-right:8px;"></i>${btnText}`;
            }
        }
    }

    renderCards(items) {
        if (!items || items.length === 0 || !this.container) return;

        const fragment = document.createDocumentFragment();

        items.forEach((item) => {
            const id = this.stripHtml(item[1]);
            const titleHtml = item[2];
            const imgHtml = item[3];
            const lang = this.stripHtml(item[4]);
            const date = this.stripHtml(item[5]);
            const statusHtml = item[6]; 

            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = titleHtml;
            const titleText = tempDiv.textContent || tempDiv.innerText;

            tempDiv.innerHTML = imgHtml;
            const imgTag = tempDiv.querySelector('img');
            const imgSrc = imgTag ? imgTag.src : '';

            const card = document.createElement('div');
            card.className = 'm3-card content-card';
            card.dataset.id = id;
            card.onclick = () => window.sliderManager.selectContent(id, card);

            card.innerHTML = `
                <div class="card-content-layout">
                    <!-- Thumbnail -->
                    <div class="card-thumbnail" 
                         style="display:flex; align-items:center; justify-content:center; color:#999; ${imgSrc && !imgSrc.includes('slider_no_image') ? `background-image: url('${imgSrc}'); background-size: cover; background-position: center;` : 'background-color: #f0f0f0;'}">
                        ${!imgSrc ? '<i class="bi bi-image" style="font-size: 24px;"></i>' : ''}
                    </div>
                    
                    <div class="card-body-col">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                             <div>
                                <div class="meta-row" style="margin-top:0; margin-bottom:4px;">
                                    ${statusHtml}
                                </div>
                                <h3 style="margin:0; font-size:16px; font-weight:500; line-height:1.4; color: var(--md-sys-color-on-surface); text-align: left;">${titleText}</h3>
                             </div>
                        </div>

                        <div class="meta-row" style="margin-top:8px;">
                             <span style="display:flex; align-items:center; gap:4px;"><i class="bi bi-translate" style="font-size:11px;"></i> ${lang}</span>
                             <span style="margin-left: 12px; display:flex; align-items:center; gap:4px;"><i class="bi bi-calendar3" style="font-size:11px;"></i> ${date}</span>
                        </div>
                    </div>
                </div>
            `;

            fragment.appendChild(card);
        });

        this.container.appendChild(fragment);
    }

    stripHtml(html) {
        if (!html) return "";
        let tmp = document.createElement("DIV");
        tmp.innerHTML = html;
        return tmp.textContent || tmp.innerText || "";
    }

    initSearchListener() {
        const searchInput = document.getElementById('searchInput'); 
        if (searchInput) {
            let timeout = null;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    const filterSearch = document.getElementById('filterSearch');
                    if (filterSearch) {
                        filterSearch.value = e.target.value;
                        this.loadData(true);
                    }
                }, 500);
            });
        }
    }

    initPullToRefresh() {
        window.refreshContent = () => this.loadData(true);
    }

    selectContent(id, cardElement) {
        const isCurrent = cardElement.classList.contains('selected');

        document.querySelectorAll('.m3-card.selected, .content-card.selected').forEach(el => {
            el.classList.remove('selected');
        });

        if (!isCurrent) {
            cardElement.classList.add('selected');
            this.selectedId = id;
            this.updateToolbar(true);
            if (window.navigator && window.navigator.vibrate) window.navigator.vibrate(10);
        } else {
            this.selectedId = null;
            this.updateToolbar(false);
        }
    }

    updateToolbar(active) {
        const ids = ['toolbarEdit', 'toolbarGallery', 'toolbarFile', 'toolbarDelete'];
        ids.forEach(btnId => {
            const btn = document.getElementById(btnId);
            if (!btn) return;
            if (active) {
                btn.classList.add('active');
                btn.style.pointerEvents = 'auto';
                btn.style.opacity = '1';
            } else {
                btn.classList.remove('active');
                btn.style.pointerEvents = 'none';
                btn.style.opacity = '0.3';
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.sliderManager = new SliderManager();
});

window.editSelected = function () {
    const mgr = window.sliderManager;
    if (mgr && mgr.selectedId) {
        window.location.href = MODULE_URL + 'edit/' + mgr.selectedId;
    }
};

window.gallerySelected = function () {
    const mgr = window.sliderManager;
    if (mgr && mgr.selectedId) {
        window.location.href = SITE_URL + 'mod_gallery/list/mod_slider/' + mgr.selectedId;
    }
};

window.filesSelected = function () {
    const mgr = window.sliderManager;
    if (mgr && mgr.selectedId) {
        window.location.href = SITE_URL + 'mod_attachment/list/mod_slider/' + mgr.selectedId;
    }
};

window.deleteSelected = function () {
    const mgr = window.sliderManager;
    if (!mgr || !mgr.selectedId) return;
    const id = mgr.selectedId;

    let confirmMsg = (typeof CONTENT_LANG !== 'undefined' && CONTENT_LANG.confirm_delete) ? CONTENT_LANG.confirm_delete : 'Are you sure?';

    Swal.fire({
        title: confirmMsg,
        icon: 'warning',
        showCancelButton: true,
        showDenyButton: false,
        confirmButtonColor: '#B71C1C',
        cancelButtonColor: '#6750A4',
        confirmButtonText: '<i class="bi bi-trash3"></i> ' + ((typeof CONTENT_LANG !== 'undefined' && CONTENT_LANG.yes) ? CONTENT_LANG.yes : 'Yes'),
        cancelButtonText: '<i class="bi bi-x-lg"></i> ' + ((typeof CONTENT_LANG !== 'undefined' && CONTENT_LANG.no) ? CONTENT_LANG.no : 'No'),
        background: 'var(--md-sys-color-surface-container-high, #fff)',
        color: 'var(--md-sys-color-on-surface, #1C1B1F)'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('delete[]', id);

            fetch(MODULE_URL + 'delete', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(msg => {
                if (msg === 'Oke') {
                    mgr.selectedId = null;
                    mgr.updateToolbar(false);
                    mgr.loadData(true);
                    let successMsg = (typeof CONTENT_LANG !== 'undefined' && CONTENT_LANG.success_delete) ? CONTENT_LANG.success_delete : 'Successfully deleted';
                    if(typeof showSnackbar === 'function') {
                        showSnackbar(successMsg, 'success');
                    } else {
                        let okMsg = (typeof CONTENT_LANG !== 'undefined' && CONTENT_LANG.ok) ? CONTENT_LANG.ok : 'OK';
                        Swal.fire(okMsg, successMsg, 'success');
                    }
                } else {
                    if(typeof showSnackbar === 'function') showSnackbar(msg, 'error');
                    else Swal.fire('Error', msg, 'error');
                }
            })
            .catch(err => {
                console.error('Delete error:', err);
                if(typeof showSnackbar === 'function') {
                    let errMsg = (typeof CONTENT_LANG !== 'undefined' && CONTENT_LANG.error_general) ? CONTENT_LANG.error_general : 'Error';
                    showSnackbar(errMsg, 'error');
                }
            });
        }
    });
};
