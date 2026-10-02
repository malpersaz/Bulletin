/**
 * mod_slider.js
 * Material 3 Phone-First Controller for mod_slider
 * Mobile-first touch contract, zero inline JavaScript, clean data-* contracts.
 */

function getSliderModuleUrl() {
    if (typeof window.MODULE_URL !== 'undefined' && window.MODULE_URL) return window.MODULE_URL;
    const container = document.getElementById('content-list-container');
    if (container && container.dataset.moduleUrl) return container.dataset.moduleUrl;
    const siteUrl = (typeof window.SITE_URL !== 'undefined' && window.SITE_URL) ? window.SITE_URL : '/';
    return siteUrl + 'mod_slider/';
}

function getSliderDataUrl() {
    if (typeof window.DATA_URL !== 'undefined' && window.DATA_URL) return window.DATA_URL;
    const container = document.getElementById('content-list-container');
    if (container && container.dataset.dataUrl) return container.dataset.dataUrl;
    return getSliderModuleUrl() + 'datatable_json';
}

function getSliderLang() {
    if (typeof window.CONTENT_LANG !== 'undefined' && window.CONTENT_LANG) return window.CONTENT_LANG;
    const al = (typeof window.adminLang !== 'undefined' && window.adminLang) ? window.adminLang : {};
    const mod = al.active_module || al.mod_slider || {};
    const ctx = al.context_menu || {};
    const dt = al.datatable || {};

    return {
        confirm_remove: mod.grid?.unpublish?.value || mod.messages?.confirm_delete || 'Ma tu bawer î ku tu dixwazî vê slaydirê ji weşanê rakî?',
        success_remove: mod.messages?.success_delete || 'Ji slaydirê hat rakirin!',
        error_general: mod.error || ctx.error || 'Çewtiyek derket!',
        load_more: mod.grid?.load_more || 'Bêtir Barke',
        yes: mod.button?.yes || 'Erê',
        no: mod.button?.no || 'Na',
        ok: mod.button?.ok || 'Baş e',
        cancel: mod.button?.cancel || 'Betal',
        remove: mod.button?.remove || 'Rake',
        empty_no_data: mod.empty?.no_data || 'Hîn Slaydir nehatiye çêkirin.',
        empty_no_search_results: mod.empty?.no_search_results || 'Li gorî lêgerînê ti encam nehat dîtin.'
    };
}

class SliderManager {
    constructor() {
        this.container = document.getElementById('content-list-container');
        this.loader = document.getElementById('infinite-loader');
        this.emptyState = document.getElementById('empty-state');
        this.loadMoreBtn = null;

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
        this.initActionListeners();
        this.initOutsideClickListener();
    }

    createLoadMoreButton() {
        this.loadMoreBtn = document.createElement('button');
        this.loadMoreBtn.className = 'm3-btn-outlined is-hidden';
        this.loadMoreBtn.type = 'button';
        this.loadMoreBtn.id = 'loadMoreBtn';

        const btnText = getSliderLang().load_more;
        this.loadMoreBtn.innerHTML = `<i class="bi bi-arrow-down-circle"></i> <span>${btnText}</span>`;

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
            this.loadMoreBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> <span>...</span>`;
        }

        if (reset) {
            this.start = 0;
            if (this.container) {
                this.container.innerHTML = '';
                this.container.classList.add('is-hidden');
            }
            this.hasMore = true;
            this.draw = 1;
            this.clearSelection();
            if (this.emptyState) this.emptyState.classList.add('is-hidden');
            if (this.loader) this.loader.classList.remove('is-hidden');
            if (this.loadMoreBtn) this.loadMoreBtn.classList.add('is-hidden');
        } else {
            if (this.loader) this.loader.classList.remove('is-hidden');
        }

        const filterLengthInput = document.getElementById('filterLength');
        if (filterLengthInput && filterLengthInput.value) {
            this.length = parseInt(filterLengthInput.value, 10) || 10;
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
            const response = await fetch(getSliderDataUrl(), {
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
                this.emptyState.classList.remove('is-hidden');
                if (this.container) this.container.classList.add('is-hidden');
                const searchInput = document.getElementById('filterSearch');
                const descEl = document.getElementById('empty-state-desc');
                if (descEl) {
                    if (searchInput && searchInput.value.trim() !== '') {
                        descEl.textContent = getSliderLang().empty_no_search_results;
                    } else {
                        descEl.textContent = getSliderLang().empty_no_data;
                    }
                }
            } else if (this.container) {
                this.container.classList.remove('is-hidden');
                if (this.emptyState) this.emptyState.classList.add('is-hidden');
            }

        } catch (error) {
            console.error('Slider Data Load Error:', error);
        } finally {
            this.isLoading = false;
            if (this.loader) this.loader.classList.add('is-hidden');

            if (this.loadMoreBtn) {
                if (this.hasMore && this.container && this.container.children.length > 0) {
                    this.loadMoreBtn.classList.remove('is-hidden');
                } else {
                    this.loadMoreBtn.classList.add('is-hidden');
                }
                this.loadMoreBtn.disabled = false;
                const btnText = getSliderLang().load_more;
                this.loadMoreBtn.innerHTML = `<i class="bi bi-arrow-down-circle"></i> <span>${btnText}</span>`;
            }
        }
    }

    renderCards(items) {
        if (!items || items.length === 0 || !this.container) return;

        const fragment = document.createDocumentFragment();

        items.forEach((item) => {
            const compoundId = this.stripHtml(item[1]);
            const titleHtml = item[2];
            const imgHtml = item[3];
            const lang = this.stripHtml(item[4]);
            const date = this.stripHtml(item[5]);
            const moduleBadge = this.stripHtml(item[6]);

            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = titleHtml;
            const titleText = tempDiv.textContent || tempDiv.innerText || '';

            tempDiv.innerHTML = imgHtml;
            const imgTag = tempDiv.querySelector('img');
            const imgSrc = imgTag ? imgTag.src : '';

            const card = document.createElement('div');
            card.className = 'content-card';
            card.dataset.id = compoundId;
            card.onclick = (e) => {
                e.stopPropagation();
                this.selectContent(compoundId, card);
            };

            const hasValidImage = Boolean(imgSrc && !imgSrc.includes('slider_no_image') && !imgSrc.endsWith('/'));

            const thumbnailHtml = hasValidImage
                ? `<div class="card-thumbnail" style="background-image: url('${imgSrc}');"></div>`
                : `<div class="card-thumbnail empty-thumb"><i class="bi bi-image"></i></div>`;

            card.innerHTML = `
                <div class="card-content-layout">
                    ${thumbnailHtml}
                    <div class="card-body-col">
                        <div class="card-header-row">
                            <span class="module-badge">${moduleBadge}</span>
                        </div>
                        <h3 class="card-title">${titleText}</h3>
                        <div class="card-meta-row">
                            <span class="meta-item"><i class="bi bi-translate"></i> ${lang}</span>
                            <span class="meta-item"><i class="bi bi-calendar3"></i> ${date}</span>
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
        const tmp = document.createElement('DIV');
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
                }, 400);
            });
        }
    }

    initPullToRefresh() {
        window.refreshContent = () => this.loadData(true);
    }

    initActionListeners() {
        document.addEventListener('click', (e) => {
            const target = e.target.closest('[data-action]');
            if (!target) return;

            const action = target.dataset.action;
            if (action === 'remove-slider' || action === 'delete-content') {
                this.removeSelectedFromSlider();
            } else if (action === 'edit-content') {
                this.editSelectedContent();
            } else if (action === 'open-gallery') {
                this.openSelectedGallery();
            } else if (action === 'open-files') {
                this.openSelectedFiles();
            }
        });
    }

    initOutsideClickListener() {
        document.addEventListener('click', (e) => {
            if (!this.selectedId) return;
            const clickedCard = e.target.closest('.content-card');
            const clickedToolbar = e.target.closest('.standard-toolbar');
            const clickedModal = e.target.closest('.swal2-container, .modal');
            if (!clickedCard && !clickedToolbar && !clickedModal) {
                this.clearSelection();
            }
        });
    }

    selectContent(id, cardElement) {
        const isCurrent = cardElement.classList.contains('selected');

        if (isCurrent) {
            this.clearSelection();
            return;
        }

        document.querySelectorAll('.content-card.selected').forEach(el => {
            el.classList.remove('selected');
        });

        cardElement.classList.add('selected');
        this.selectedId = id;
        this.updateToolbar(true);
        if (window.navigator && window.navigator.vibrate) window.navigator.vibrate(10);
    }

    clearSelection() {
        document.querySelectorAll('.content-card.selected').forEach(el => {
            el.classList.remove('selected');
        });

        this.selectedId = null;
        this.updateToolbar(false);
        if (window.navigator && window.navigator.vibrate) window.navigator.vibrate(8);
    }

    updateToolbar(active) {
        const ids = ['toolbarSlider', 'toolbarGallery', 'toolbarFiles', 'toolbarEdit', 'toolbarDelete'];
        ids.forEach(btnId => {
            const btn = document.getElementById(btnId);
            if (!btn) return;
            if (active) {
                btn.classList.add('active');
                btn.classList.remove('is-disabled');
                btn.style.pointerEvents = 'auto';
                btn.style.opacity = '1';
            } else {
                btn.classList.remove('active');
                btn.classList.add('is-disabled');
                btn.style.pointerEvents = 'none';
                btn.style.opacity = '0.3';
            }
        });
    }

    parseCompoundId() {
        if (!this.selectedId) return null;
        const parts = this.selectedId.split(':');
        if (parts.length === 2) {
            return {
                module: parts[0],
                id: parts[1]
            };
        }
        return null;
    }

    editSelectedContent() {
        const parsed = this.parseCompoundId();
        if (!parsed) return;
        const siteUrl = (typeof window.SITE_URL !== 'undefined' && window.SITE_URL) ? window.SITE_URL : '/';
        window.location.href = siteUrl + parsed.module + '/edit/' + parsed.id;
    }

    openSelectedGallery() {
        const parsed = this.parseCompoundId();
        if (!parsed) return;
        const siteUrl = (typeof window.SITE_URL !== 'undefined' && window.SITE_URL) ? window.SITE_URL : '/';
        const url = siteUrl + 'mod_gallery/list/' + parsed.module + '/' + parsed.id;
        if (window.AFModalEngine) {
            window.AFModalEngine.push(url, { skipHistory: true });
        } else {
            window.location.href = url;
        }
    }

    openSelectedFiles() {
        const parsed = this.parseCompoundId();
        if (!parsed) return;
        const siteUrl = (typeof window.SITE_URL !== 'undefined' && window.SITE_URL) ? window.SITE_URL : '/';
        const url = siteUrl + 'mod_attachment/list/' + parsed.module + '/' + parsed.id;
        if (window.AFModalEngine) {
            window.AFModalEngine.push(url, { skipHistory: true });
        } else {
            window.location.href = url;
        }
    }

    removeSelectedFromSlider() {
        if (!this.selectedId) return;
        const id = this.selectedId;
        const lang = getSliderLang();

        const doUnpublish = () => {
            const formData = new FormData();
            formData.append('query[]', id);

            fetch(getSliderModuleUrl() + 'unpublish', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(msg => {
                const trimmed = (msg || '').trim();
                if (trimmed === 'Success') {
                    const card = document.querySelector(`.content-card[data-id="${id}"]`);
                    if (card) {
                        card.classList.add('fade-out');
                        setTimeout(() => {
                            card.remove();
                            if (this.container && this.container.children.length === 0) {
                                if (this.emptyState) this.emptyState.classList.remove('is-hidden');
                                this.container.classList.add('is-hidden');
                            }
                        }, 300);
                    }
                    this.clearSelection();
                    if (typeof showSnackbar === 'function') {
                        showSnackbar(lang.success_remove, 'success');
                    }
                } else {
                    if (typeof showSnackbar === 'function') {
                        showSnackbar(trimmed || lang.error_general, 'error');
                    }
                }
            })
            .catch(err => {
                console.error('Slider removal error:', err);
                if (typeof showSnackbar === 'function') {
                    showSnackbar(lang.error_general, 'error');
                }
            });
        };

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: lang.confirm_remove,
                icon: 'warning',
                showCancelButton: true,
                showDenyButton: false,
                confirmButtonColor: '#B71C1C',
                cancelButtonColor: '#6750A4',
                confirmButtonText: '<i class="bi bi-trash3"></i> ' + lang.yes,
                cancelButtonText: '<i class="bi bi-x-lg"></i> ' + lang.cancel,
                background: 'var(--md-sys-color-surface-container-high, #fff)',
                color: 'var(--md-sys-color-on-surface, #1C1B1F)'
            }).then((result) => {
                if (result.isConfirmed) {
                    doUnpublish();
                }
            });
        } else if (confirm(lang.confirm_remove)) {
            doUnpublish();
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.sliderManager = new SliderManager();
});

// Backward compatibility global wrappers
window.editSelected = function () {
    window.sliderManager?.editSelectedContent();
};

window.gallerySelected = function () {
    window.sliderManager?.openSelectedGallery();
};

window.filesSelected = function () {
    window.sliderManager?.openSelectedFiles();
};

window.deleteSelected = function () {
    window.sliderManager?.removeSelectedFromSlider();
};
