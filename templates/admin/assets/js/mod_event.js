/**
 * Amed Framework - Event Module JS (Phone-first Mobile Admin)
 * templates/admin/assets/js/mod_event.js
 * Aligned with mod_news standard: M3 Cards, Frosted Glass Toolbar,
 * Search Highlighting, Pull-To-Refresh, Load More & Media Relation Picker.
 */

function getEventModuleUrl() {
    if (typeof window.MODULE_URL !== 'undefined' && window.MODULE_URL) return window.MODULE_URL;
    const container = document.getElementById('content-list-container');
    if (container && container.dataset.moduleUrl) return container.dataset.moduleUrl;
    const siteUrl = (typeof window.SITE_URL !== 'undefined' && window.SITE_URL) ? window.SITE_URL : '/';
    return siteUrl + 'mod_event/';
}

function getEventDataUrl() {
    if (typeof window.DATA_URL !== 'undefined' && window.DATA_URL) return window.DATA_URL;
    const container = document.getElementById('content-list-container');
    if (container && container.dataset.dataUrl) return container.dataset.dataUrl;
    return getEventModuleUrl() + 'datatable_json';
}

function getEventCategoryMap() {
    if (typeof window.CATEGORY_MAP !== 'undefined' && window.CATEGORY_MAP && Object.keys(window.CATEGORY_MAP).length > 0) {
        return window.CATEGORY_MAP;
    }
    const container = document.getElementById('content-list-container');
    if (container && container.dataset.categories) {
        try {
            const list = JSON.parse(container.dataset.categories);
            if (Array.isArray(list)) {
                return list.reduce((acc, cat) => {
                    if (cat && cat.id) acc[cat.id] = cat.name || '';
                    return acc;
                }, {});
            }
        } catch (e) {}
    }
    return {};
}

function getEventLang() {
    if (typeof window.CONTENT_LANG !== 'undefined' && window.CONTENT_LANG) return window.CONTENT_LANG;
    const al = (typeof window.adminLang !== 'undefined' && window.adminLang) ? window.adminLang : {};
    const mod = al.mod_event || al.active_module || {};
    const ctx = mod.context_menu || al.context_menu || {};
    const dt = mod.datatable || al.datatable || {};

    return {
        active: mod.status_badge?.active || 'Active',
        draft: mod.status_badge?.draft || 'Draft',
        general: mod.status_badge?.general || 'General',
        sheet_all: mod.sheet?.all || 'All',
        confirm_delete: mod.delete_confirm || ctx.delete_confirm || 'Are you sure you want to delete this event?',
        confirm_publish: mod.quick_publish_confirm || 'Do you want to publish this event?',
        confirm_unpublish: mod.quick_unpublish_confirm || 'Do you want to unpublish this event?',
        error_general: mod.error_occurred || mod.error || 'An error occurred',
        error_upload: mod.message?.error?.add || 'Upload failed',
        cancel: mod.button?.cancel || ctx.cancel || 'Cancel',
        delete_confirm_text: mod.button?.yes || ctx.confirm || 'Yes',
        delete_cancel_text: mod.button?.cancel || mod.button?.no || ctx.cancel || 'Cancel',
        status_confirm_text: mod.button?.yes || 'Yes',
        status_cancel_text: mod.button?.no || 'No',
        image_remove_title: mod.remove_image_title || mod.button?.remove || 'Remove Poster',
        image_remove_confirm: mod.remove_image || 'Are you sure you want to remove this poster image?',
        image_remove_confirm_text: (mod.button?.yes && mod.button?.delete) ? `${mod.button.yes}, ${mod.button.delete}` : (mod.button?.yes || 'Yes, Delete'),
        image_remove_cancel_text: mod.button?.cancel || mod.button?.no || 'Cancel',
        image_remove_success: mod.remove_image_success || 'Poster removed successfully',
        image_updated: mod.success || 'Poster updated successfully',
        image_title: mod.upload_image || 'Poster Image',
        save_image: mod.button?.send || mod.button?.save || 'Save Poster',
        hero_slider_add: mod.hero_slider_add || 'Send to Slider',
        hero_slider_remove: mod.hero_slider_remove || 'Remove from Slider',
        hero_slider_added: mod.hero_slider_added || 'Sent to slider.',
        hero_slider_removed: mod.hero_slider_removed || 'Removed from slider.',
        hero_slider_error: mod.hero_slider_error || 'Slider update failed.',
        hero_slider_badge: mod.hero_slider_badge || 'Slider',
        deck_add: mod.deck_add || 'Mark as Featured',
        deck_remove: mod.deck_remove || 'Remove from Featured',
        deck_added: mod.deck_added || 'Marked as featured.',
        deck_removed: mod.deck_removed || 'Removed from featured.',
        deck_error: mod.deck_error || 'Failed to update featured state.',
        deck_badge: mod.deck_badge || 'Featured',
        publish: ctx.publish || al.context_menu?.publish || 'Publish',
        unpublish: ctx.unpublish || al.context_menu?.unpublish || 'Unpublish',
        published_msg: ctx.published_msg || al.context_menu?.published_msg || 'Published',
        unpublished_msg: ctx.unpublished_msg || al.context_menu?.unpublished_msg || 'Unpublished',
        status_updated: mod.message?.success?.edit || mod.success || 'Status updated',
        status_error: mod.message?.error?.edit || mod.error_occurred || 'Failed to update status',
        picker: {
            gallery: mod.picker?.gallery || 'Select Gallery',
            file: mod.picker?.file || 'Select Files',
            video: mod.picker?.video || 'Select Video'
        },
        loading: dt.loadingRecords || mod.grid?.loading || 'Loading...',
        grid: {
            load_more: mod.grid?.load_more || 'Load More'
        }
    };
}

class EventManager {
    constructor() {
        this.container = document.getElementById('content-list-container');
        this.loader = document.getElementById('infinite-loader');
        this.emptyState = document.getElementById('empty-state');
        this.imageModal = document.getElementById('eventImageModal');
        this.imageModalTitle = document.getElementById('eventImageModalTitle');
        this.imageModalSubtitle = document.getElementById('eventImageModalSubtitle');
        this.imageMeta = document.getElementById('listImageMeta');
        this.imageFooter = document.getElementById('eventImageModalFooter');
        this.imageCancelBtn = document.getElementById('eventImageCancelBtn');
        this.imageSaveBtn = document.getElementById('eventImageSaveBtn');

        this.isLoading = false;
        this.hasMore = true;
        this.selectedContentId = null;
        this.selectedInHeroSlider = false;
        this.selectedInDeck = false;
        this.imageUploadContentId = null;
        this.imageUploader = null;
        this.imageUploaderBound = false;
        this.imageModalState = {
            hasExistingImage: false,
            existingImageSrc: '',
            existingImageName: ''
        };

        this.start = 0;
        this.length = 10;
        this.draw = 1;

        this.init();
    }

    init() {
        this.createLoadMoreButton();
        this.updateToolbar(false);
        this.loadData();
        this.initSearchListener();
        this.initPullToRefresh();
        this.initListImageUploader();
        this.initializeImageModalCopy();
        this.initActionListeners();
    }

    initActionListeners() {
        document.addEventListener('click', (e) => {
            const target = e.target.closest('[data-action]');
            if (!target) return;

            const action = target.dataset.action;
            if (action === 'close-image-uploader') {
                this.closeImageUploader();
            } else if (action === 'submit-list-image-upload') {
                this.submitListImageUpload();
            } else if (action === 'toggle-status') {
                this.toggleEventStatus();
            } else if (action === 'toggle-slider') {
                this.toggleHeroSlider();
            } else if (action === 'toggle-deck') {
                this.toggleDeck();
            } else if (action === 'open-video') {
                window.videoSelected?.();
            } else if (action === 'open-gallery') {
                window.gallerySelected?.();
            } else if (action === 'open-files') {
                window.filesSelected?.();
            } else if (action === 'open-faq') {
                window.faqSelected?.();
            } else if (action === 'edit-content') {
                window.editSelected?.();
            } else if (action === 'delete-content') {
                window.deleteSelected?.();
            }
        });

        const statCards = document.querySelectorAll('.content-stats-row .content-stat-card');
        statCards.forEach((card) => {
            card.addEventListener('click', () => {
                const statusVal = card.dataset.statusFilter || 'all';
                const filterStatusInput = document.getElementById('filterStatus');
                if (!filterStatusInput) return;

                statCards.forEach((c) => {
                    const isSelected = c === card;
                    c.classList.toggle('active', isSelected);
                    c.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
                });

                filterStatusInput.value = (statusVal === 'all') ? '' : statusVal;
                this.loadData(true);
            });
        });
    }

    initializeImageModalCopy() {
        if (this.imageCancelBtn && typeof adminLang !== 'undefined' && adminLang.context_menu?.cancel) {
            this.imageCancelBtn.textContent = adminLang.context_menu.cancel;
        }
    }

    createLoadMoreButton() {
        this.loadMoreBtn = document.createElement('button');
        this.loadMoreBtn.className = 'm3-btn-outlined load-more-btn';

        let btnText = 'Load More';
        if (getEventLang().grid && getEventLang().grid.load_more) {
            btnText = getEventLang().grid.load_more;
        }
        this.loadMoreBtn.innerHTML = `<i class="bi bi-arrow-down-circle btn-icon-left"></i>${btnText}`;

        if (this.container && this.loader) {
            this.container.parentNode.insertBefore(this.loadMoreBtn, this.loader);
        }

        this.loadMoreBtn.addEventListener('click', () => {
            this.loadData();
        });
    }

    async loadData(reset = false) {
        if (this.isLoading || (!this.hasMore && !reset)) return;

        this.isLoading = true;
        if (this.loadMoreBtn) {
            this.loadMoreBtn.disabled = true;
            this.loadMoreBtn.innerHTML = `<span class="spinner-border spinner-border-sm btn-icon-left" role="status" aria-hidden="true"></span>...`;
        }

        if (reset) {
            this.start = 0;
            this.container.innerHTML = '';
            this.hasMore = true;
            this.draw = 1;
            this.selectedContentId = null;
            this.selectedInHeroSlider = false;
            this.selectedInDeck = false;
            this.updateToolbar(false);
            this.updateFabState(false);
            if (this.emptyState) this.emptyState.classList.add('is-hidden');
            if (this.loader) this.loader.classList.remove('is-hidden');
            if (this.loadMoreBtn) this.loadMoreBtn.classList.remove('is-visible');
        } else {
            if (this.loader) this.loader.classList.remove('is-hidden');
        }

        const filterLengthInput = document.getElementById('filterLength');
        if (filterLengthInput && filterLengthInput.value) {
            this.length = parseInt(filterLengthInput.value, 10) || 10;
        }

        const formData = new FormData(document.getElementById('filterForm'));
        formData.set('start', this.start);
        formData.set('length', this.length);
        formData.set('draw', this.draw);

        try {
            const response = await fetch(getEventDataUrl(), {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            this.renderCards(data.data);

            const totalCount = data.total_event !== undefined ? data.total_event : (data.totalEvents !== undefined ? data.totalEvents : data.recordsTotal);
            const pubCount = data.published_event !== undefined ? data.published_event : data.publishedEvents;
            const draftCount = data.draft_event !== undefined ? data.draft_event : data.draftEvents;

            if (typeof totalCount !== 'undefined') {
                const totalEl = document.getElementById('statTotalEvent');
                if (totalEl) totalEl.textContent = totalCount;
            }
            if (typeof pubCount !== 'undefined') {
                const pubEl = document.getElementById('statPublishedEvent');
                if (pubEl) pubEl.textContent = pubCount;
            }
            if (typeof draftCount !== 'undefined') {
                const draftEl = document.getElementById('statDraftEvent');
                if (draftEl) draftEl.textContent = draftCount;
            }

            this.start += this.length;
            this.draw++;

            if (this.start >= data.recordsFiltered) {
                this.hasMore = false;
                if (this.loader) this.loader.classList.add('is-hidden');
            }

            if (data.recordsFiltered === 0 && this.emptyState) {
                this.emptyState.classList.remove('is-hidden');
            }

        } catch (error) {
            console.error('Data Load Error:', error);
        } finally {
            this.isLoading = false;
            if (this.loader) this.loader.classList.add('is-hidden');

            if (this.loadMoreBtn) {
                this.loadMoreBtn.classList.toggle('is-visible', Boolean(this.hasMore));
                this.loadMoreBtn.disabled = false;
                let btnText = 'Load More';
                if (getEventLang().grid && getEventLang().grid.load_more) {
                    btnText = getEventLang().grid.load_more;
                }
                this.loadMoreBtn.innerHTML = `<i class="bi bi-arrow-down-circle btn-icon-left"></i>${btnText}`;
            }
        }
    }

    renderCards(items) {
        if (!items || items.length === 0) return;

        const fragment = document.createDocumentFragment();

        items.forEach((item) => {
            const id = this.stripHtml(item[1]);
            const titleHtml = item[2];
            const imgHtml = item[3];
            const lang = this.stripHtml(item[4]);
            const startDate = this.stripHtml(item[5]);
            const categoryId = item[7] || 0;
            const statusRaw = item[8] || 'n';
            const inHeroSlider = String(item[10] || '0') === '1';
            const inDeck = String(item[11] || '0') === '1';
            const location = this.stripHtml(item[13] || '');

            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = titleHtml;
            const titleText = tempDiv.textContent || tempDiv.innerText;

            tempDiv.innerHTML = imgHtml;
            const imgTag = tempDiv.querySelector('img');
            const imgSrc = imgTag ? imgTag.src : '';

            const card = document.createElement('article');
            card.className = 'm3-card content-card';
            card.dataset.id = id;
            card.dataset.status = statusRaw === 'y' ? 'y' : 'n';
            card.dataset.imageSrc = imgSrc || '';
            card.dataset.imageName = imgSrc ? (imgSrc.split('/').pop() || titleText || '') : (titleText || '');
            card.dataset.inHeroSlider = inHeroSlider ? '1' : '0';
            card.dataset.inDeck = inDeck ? '1' : '0';
            card.addEventListener('click', () => this.selectContent(id, card));

            let categoryName = getEventLang().general;
            const catMap = getEventCategoryMap();
            if (Object.keys(catMap).length > 0 && catMap[categoryId]) {
                categoryName = catMap[categoryId];
            }

            const safeCategoryName = this.escapeHtml(categoryName);
            const safeTitleText = this.escapeHtml(titleText);
            const safeLang = this.escapeHtml(String(lang || '').toUpperCase());
            let rawDate = String(startDate || '');
            if (rawDate.includes(' ')) {
                rawDate = rawDate.split(' ')[0];
            }
            const safeDate = this.escapeHtml(rawDate);
            const safeLocation = this.escapeHtml(location);
            const safeImgSrc = this.escapeHtml(imgSrc);

            const isPublished = statusRaw === 'y';
            const statusTitle = this.escapeHtml(isPublished ? getEventLang().active : getEventLang().draft);
            const deckTitle = this.escapeHtml(inDeck ? getEventLang().deck_badge : getEventLang().deck_add);
            const sliderTitle = this.escapeHtml(inHeroSlider ? getEventLang().hero_slider_badge : getEventLang().hero_slider_add);

            card.innerHTML = `
                <div class="card-content-layout">
                    <div class="news-thumb-wrapper event-thumb-wrapper">
                        <div class="card-thumbnail">
                            ${imgSrc ? `<img src="${safeImgSrc}" alt="${safeTitleText}" class="news-card-thumb event-card-thumb" loading="lazy">` : '<i class="bi bi-calendar-event"></i>'}
                        </div>
                    </div>
                    
                    <div class="card-body-col">
                        <div class="card-header-row">
                            <h3 class="news-card-title">${safeTitleText}</h3>
                            <button type="button" class="news-action-btn m3-icon-btn" aria-label="${this.escapeHtml(getEventLang().context_menu?.title || 'Actions')}">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="meta-bottom-row">
                     <span class="meta-bottom-item"><i class="bi bi-translate"></i> ${safeLang}</span>
                     <span class="meta-bottom-item meta-date"><i class="bi bi-calendar3"></i> ${safeDate}</span>
                     ${safeLocation ? `<span class="meta-bottom-item meta-location"><i class="bi bi-geo-alt"></i> ${safeLocation}</span>` : `<span class="meta-bottom-item meta-category"><i class="bi bi-diagram-2"></i> ${safeCategoryName}</span>`}
                     <div class="card-state-indicators" aria-hidden="true">
                         <span class="card-state-pill state-status ${isPublished ? 'is-active' : 'is-passive'}" title="${statusTitle}">
                             <i class="bi ${isPublished ? 'bi-eye-fill' : 'bi-eye-slash'}"></i>
                         </span>
                         <span class="card-state-pill state-deck ${inDeck ? 'is-active' : 'is-passive'}" title="${deckTitle}">
                             <i class="bi ${inDeck ? 'bi-bookmark-star-fill' : 'bi-bookmark-star'}"></i>
                         </span>
                         <span class="card-state-pill state-slider ${inHeroSlider ? 'is-active' : 'is-passive'}" title="${sliderTitle}">
                             <i class="bi bi-stars"></i>
                         </span>
                     </div>
                </div>
            `;

            const thumb = card.querySelector('.event-thumb-wrapper');
            if (thumb) {
                thumb.addEventListener('click', (event) => {
                    event.stopPropagation();
                    this.processImageUpload(id);
                });
            }

            const actionButton = card.querySelector('.news-action-btn');
            if (actionButton) {
                actionButton.addEventListener('click', (event) => {
                    event.stopPropagation();
                    this.selectContent(id, card);
                });
            }

            fragment.appendChild(card);
        });

        this.container.appendChild(fragment);

        const searchInput = document.getElementById('searchInput') || document.getElementById('filterSearch');
        if (searchInput && searchInput.value) {
            this.highlightSearchHits(this.container, searchInput.value);
        }
    }

    clearHighlights(el) {
        if (!el) return;
        const marks = el.querySelectorAll('mark.news-search-hit');
        marks.forEach(mark => {
            const parent = mark.parentNode;
            if (parent) {
                parent.replaceChild(document.createTextNode(mark.textContent), mark);
                parent.normalize();
            }
        });
    }

    highlightSearchHits(container, query) {
        if (!container || !query || !query.trim()) return;
        this.clearHighlights(container);

        const trimmed = query.trim();
        const lowerQuery = trimmed.toLowerCase();
        const titleElements = container.querySelectorAll('.news-card-title');

        titleElements.forEach(titleEl => {
            const walker = document.createTreeWalker(titleEl, NodeFilter.SHOW_TEXT, null, false);
            const textNodes = [];
            while (walker.nextNode()) {
                textNodes.push(walker.currentNode);
            }

            textNodes.forEach(node => {
                const text = node.nodeValue;
                const lowerText = text.toLowerCase();
                let index = lowerText.indexOf(lowerQuery);
                if (index === -1) return;

                const fragment = document.createDocumentFragment();
                let lastIndex = 0;
                while (index !== -1) {
                    if (index > lastIndex) {
                        fragment.appendChild(document.createTextNode(text.substring(lastIndex, index)));
                    }
                    const mark = document.createElement('mark');
                    mark.className = 'news-search-hit';
                    mark.textContent = text.substring(index, index + lowerQuery.length);
                    fragment.appendChild(mark);
                    lastIndex = index + lowerQuery.length;
                    index = lowerText.indexOf(lowerQuery, lastIndex);
                }
                if (lastIndex < text.length) {
                    fragment.appendChild(document.createTextNode(text.substring(lastIndex)));
                }
                if (node.parentNode) {
                    node.parentNode.replaceChild(fragment, node);
                }
            });
        });
    }

    stripHtml(html) {
        const tmp = document.createElement("DIV");
        tmp.innerHTML = html;
        return tmp.textContent || tmp.innerText || "";
    }

    escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
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

    getListImageI18n() {
        const shell = document.getElementById('listImageShell');
        const data = shell ? shell.dataset : {};

        return {
            dropTitle: data.dropTitle || '',
            dropDescription: data.dropDescription || '',
            uploadLink: data.uploadLink || '',
            linkSeparator: data.linkSeparator || '',
            linkPlaceholder: data.linkPlaceholder || '',
            linkAction: data.linkAction || '',
            hintLoading: data.hintLoading || '',
            hintInvalidType: data.hintInvalidType || '',
            hintNoDropData: data.hintNoDropData || '',
            hintRemoteFailed: data.hintRemoteFailed || getEventLang().error_upload,
            emptyName: data.emptyName || getEventLang().image_title,
            emptyMeta: data.emptyMeta || '',
            imageLabel: getEventLang().image_title
        };
    }

    initListImageUploader() {
        if (typeof window.createImageUploader !== 'function') {
            return;
        }

        if (!this.imageUploader) {
            this.imageUploader = window.createImageUploader({
                rootId: 'listImageShell',
                inputId: 'listImgInput',
                previewId: 'listImgPreview',
                emptyStateId: 'listImageEmptyState',
                previewCardId: 'listImagePreviewCard',
                fileNameId: 'listImageFileName',
                fileDetailsId: 'listImageFileDetails',
                dropzoneId: 'listImageDropzone',
                urlInputId: 'listImageUrlInput',
                urlImportBtnId: 'listImageUrlImportBtn',
                getI18n: () => this.getListImageI18n(),
                onTriggerPicker: () => document.getElementById('listImgInput')?.click(),
                shouldHandlePaste: () => {
                    if (!this.imageModal || !this.imageModal.classList.contains('active')) {
                        return false;
                    }
                    const activeElement = document.activeElement;
                    return !activeElement || activeElement === document.body || this.imageModal.contains(activeElement);
                },
                onChange: ({ file, source, name }) => {
                    if (source === 'existing' && name) {
                        this.imageModalState.existingImageName = name;
                        return;
                    }
                    if (file && source !== 'existing') {
                        this.submitListImageUpload();
                    }
                }
            });
        }

        if (!this.imageUploaderBound && this.imageUploader) {
            this.imageUploader.bind();
            this.imageUploaderBound = true;
        }

        if (!this.imageModal) {
            return;
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && this.imageModal.classList.contains('active')) {
                this.closeImageUploader();
            }
        });
    }

    openImageUploader(id, title) {
        this.initListImageUploader();
        this.imageUploadContentId = id;
        this.imageModal = document.getElementById('eventImageModal');
        this.imageModalTitle = document.getElementById('eventImageModalTitle');
        this.imageModalSubtitle = document.getElementById('eventImageModalSubtitle');
        this.imageSaveBtn = document.getElementById('eventImageSaveBtn');
        this.imageCancelBtn = document.getElementById('eventImageCancelBtn');
        this.imageFooter = document.getElementById('eventImageModalFooter');

        const card = document.querySelector(`.content-card[data-id="${id}"]`);
        
        if (!title && card) {
            title = card.querySelector('h3')?.textContent?.trim() || '';
        }

        const existingImageSrc = card?.dataset.imageSrc || '';
        const existingImageName = card?.dataset.imageName || title || getEventLang().image_title;
        this.imageModalState = {
            hasExistingImage: Boolean(existingImageSrc),
            existingImageSrc,
            existingImageName
        };

        if (this.imageModalTitle) {
            this.imageModalTitle.textContent = getEventLang().image_title;
        }

        const subtitleEl = this.imageModalSubtitle || document.getElementById('eventImageModalSubtitle');
        if (subtitleEl) {
            if (title) {
                subtitleEl.textContent = title;
                subtitleEl.removeAttribute('hidden');
                subtitleEl.hidden = false;
                subtitleEl.classList.remove('is-hidden');
            } else {
                subtitleEl.textContent = '';
                subtitleEl.setAttribute('hidden', 'true');
                subtitleEl.hidden = true;
                subtitleEl.classList.add('is-hidden');
            }
        }

        this.resetListImageUploader();

        if (this.imageModal) {
            document.body.classList.add('news-image-modal-open');
            this.imageModal.classList.add('active');
            this.imageModal.setAttribute('aria-hidden', 'false');
        }
    }

    closeImageUploader() {
        if (this.imageModal) {
            this.imageModal.classList.remove('active');
            this.imageModal.setAttribute('aria-hidden', 'true');
        }

        document.body.classList.remove('news-image-modal-open');
        this.imageUploadContentId = null;
        this.imageModalState = {
            hasExistingImage: false,
            existingImageSrc: '',
            existingImageName: ''
        };
        this.resetListImageUploader({ preserveExisting: false });
    }

    resetListImageUploader(options = {}) {
        if (this.imageUploader) {
            this.imageUploader.reset();
        }
        this.resetImageModalActions();
        if (options.preserveExisting !== false && this.imageModalState.hasExistingImage && this.imageUploader) {
            this.imageUploader.renderExistingImage({
                src: this.imageModalState.existingImageSrc,
                name: this.imageModalState.existingImageName || getEventLang().image_title,
                metaText: ''
            });
        }
    }

    resetImageModalActions() {
        if (this.imageSaveBtn) {
            this.imageSaveBtn.disabled = true;
            this.imageSaveBtn.hidden = true;
            this.imageSaveBtn.innerHTML = getEventLang().save_image;
        }
        if (this.imageMeta) {
            this.imageMeta.hidden = true;
        }
        if (this.imageFooter) {
            this.imageFooter.classList.add('is-single-action');
        }
    }

    processImageUpload(id) {
        const card = document.querySelector(`.content-card[data-id="${id}"]`);
        const title = card?.querySelector('h3')?.textContent?.trim() || '';
        this.openImageUploader(id, title);
    }

    async removeExistingListImage() {
        if (!this.imageUploadContentId || !this.imageModalState.hasExistingImage) {
            return;
        }

        const result = await Swal.fire({
            title: getEventLang().image_remove_title,
            text: getEventLang().image_remove_confirm,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#B71C1C',
            cancelButtonColor: '#6750A4',
            confirmButtonText: getEventLang().image_remove_confirm_text,
            cancelButtonText: getEventLang().image_remove_cancel_text,
            background: 'var(--md-sys-color-surface-container-high, #fff)',
            color: 'var(--md-sys-color-on-surface, #1C1B1F)'
        });

        if (!result.isConfirmed) {
            return;
        }

        if (this.loader) this.loader.classList.remove('is-hidden');

        try {
            const formData = new URLSearchParams();
            formData.append('id', this.imageUploadContentId);

            const response = await fetch(`${getEventModuleUrl()}image_remove_image`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            });

            const text = (await response.text()).trim();
            if (text !== 'Success') {
                showSnackbar(text || getEventLang().error_general, 'error');
                return;
            }

            this.closeImageUploader();
            await this.loadData(true);
            showSnackbar(getEventLang().image_remove_success || getEventLang().image_updated, 'success', 2000);
        } catch (error) {
            console.error('Remove image error:', error);
            showSnackbar(getEventLang().error_general, 'error');
        } finally {
            if (this.loader) this.loader.classList.add('is-hidden');
        }
    }

    async submitListImageUpload() {
        const fileData = this.imageUploader?.getSelectedFile();
        if (!this.imageUploadContentId || !fileData) {
            return;
        }

        if (this.loader) this.loader.classList.remove('is-hidden');

        try {
            const formData = new FormData();
            formData.append('id', this.imageUploadContentId);
            formData.append('image_upload', fileData);

            const response = await fetch(`${getEventModuleUrl()}list_upload_image`, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.status === 'success') {
                const newImgUrl = result.url || result.image_url || '';
                this.imageModalState.hasExistingImage = true;
                if (newImgUrl) {
                    this.imageModalState.existingImageSrc = newImgUrl;
                }
                this.resetListImageUploader({ preserveExisting: true });
                this.closeImageUploader();
                await this.loadData(true);
                showSnackbar(result.message || getEventLang().image_updated, 'success', 2000);
            } else {
                this.imageUploader?.showFeedback(result.message || getEventLang().error_upload, 'error');
                showSnackbar(result.message || getEventLang().error_upload, 'error');
            }
        } catch (err) {
            console.error('Upload error:', err);
            this.imageUploader?.showFeedback(getEventLang().error_upload, 'error');
            showSnackbar(getEventLang().error_upload, 'error');
        } finally {
            if (this.loader) this.loader.classList.add('is-hidden');
            if (this.imageSaveBtn) {
                const hasPendingFile = Boolean(this.imageUploader?.getSelectedFile());
                this.imageSaveBtn.hidden = !hasPendingFile;
                this.imageSaveBtn.disabled = !hasPendingFile;
                this.imageSaveBtn.innerHTML = getEventLang().save_image;
            }
        }
    }

    selectContent(id, cardElement) {
        const isCurrent = cardElement.classList.contains('selected');

        if (isCurrent) {
            this.clearSelection();
            return;
        }

        document.querySelectorAll('.m3-card.selected, .content-card.selected').forEach(el => {
            el.classList.remove('selected');
        });

        cardElement.classList.add('selected');
        this.selectedContentId = id;
        this.selectedInHeroSlider = cardElement.dataset.inHeroSlider === '1';
        this.selectedInDeck = cardElement.dataset.inDeck === '1';
        this.updateToolbar(true);
        this.updateFabState(true);
        if (window.navigator.vibrate) window.navigator.vibrate(10);
    }

    clearSelection() {
        if (!this.selectedContentId) {
            document.querySelectorAll('.m3-card.selected, .content-card.selected').forEach(el => {
                el.classList.remove('selected');
            });
            this.updateToolbar(false);
            this.updateFabState(false);
            return;
        }

        document.querySelectorAll('.m3-card.selected, .content-card.selected').forEach(el => {
            el.classList.remove('selected');
        });

        this.selectedContentId = null;
        this.selectedInHeroSlider = false;
        this.selectedInDeck = false;
        this.updateToolbar(false);
        this.updateFabState(false);
        if (window.navigator.vibrate) window.navigator.vibrate(8);
    }

    updateFabState(active) {
        if (typeof window.updateGlobalFabState === 'function') {
            window.updateGlobalFabState(active);
        }
    }

    updateToolbar(active) {
        const toolbar = document.getElementById('contentToolbar');
        if (toolbar) {
            toolbar.classList.toggle('is-visible', Boolean(active));
            toolbar.setAttribute('aria-hidden', active ? 'false' : 'true');
        }
        document.body.classList.toggle('has-selection', Boolean(active));

        const ids = [
            'toolbarStatus', 'toolbarSlider', 'toolbarDeck',
            'toolbarVideo', 'toolbarGallery', 'toolbarFiles', 'toolbarFaq',
            'toolbarEdit', 'toolbarDelete'
        ];

        ids.forEach(id => {
            const btn = document.getElementById(id);
            if (btn) {
                btn.classList.toggle('active', Boolean(active));
                btn.classList.toggle('is-disabled', !active);
            }
        });

        this.syncCardToolbarState(active);
    }

    syncCardToolbarState(active) {
        const card = this.getSelectedCard();
        const statusBtn = document.getElementById('toolbarStatus');
        if (statusBtn) {
            const isPublished = Boolean(active && card && card.dataset.status === 'y');
            const statusLabel = isPublished
                ? getEventLang().unpublish
                : getEventLang().publish;
            const statusIcon = statusBtn.querySelector('i');
            statusBtn.classList.toggle('is-published', isPublished);
            statusBtn.classList.toggle('is-draft', !isPublished && Boolean(active));
            if (statusIcon) {
                statusIcon.className = isPublished ? 'bi bi-eye-slash' : 'bi bi-eye-fill';
            }
            statusBtn.title = statusLabel;
            statusBtn.setAttribute('aria-label', statusLabel);
        }

        const deckBtn = document.getElementById('toolbarDeck');
        if (deckBtn) {
            const inDeck = Boolean(active && this.selectedInDeck);
            const deckLabel = inDeck
                ? getEventLang().deck_remove
                : getEventLang().deck_add;
            deckBtn.classList.toggle('is-selected', inDeck);
            deckBtn.title = deckLabel;
            deckBtn.setAttribute('aria-label', deckLabel);
        }

        const sliderBtn = document.getElementById('toolbarSlider');
        if (sliderBtn) {
            const inSlider = Boolean(active && this.selectedInHeroSlider);
            const sliderLabel = inSlider
                ? getEventLang().hero_slider_remove
                : getEventLang().hero_slider_add;
            sliderBtn.classList.toggle('is-selected', inSlider);
            sliderBtn.title = sliderLabel;
            sliderBtn.setAttribute('aria-label', sliderLabel);
        }
    }

    getSelectedCard() {
        if (!this.selectedContentId) return null;
        return document.querySelector(`.content-card[data-id="${this.selectedContentId}"]`);
    }

    applyHeroSliderState(card, inHeroSlider) {
        if (!card) return;
        card.dataset.inHeroSlider = inHeroSlider ? '1' : '0';
        const sliderPill = card.querySelector('.card-state-pill.state-slider');
        if (sliderPill) {
            sliderPill.classList.toggle('is-active', Boolean(inHeroSlider));
            sliderPill.classList.toggle('is-passive', !inHeroSlider);
            sliderPill.title = inHeroSlider ? getEventLang().hero_slider_badge : getEventLang().hero_slider_add;
        }
    }

    applyDeckState(card, inDeck) {
        if (!card) return;
        card.dataset.inDeck = inDeck ? '1' : '0';
        const deckPill = card.querySelector('.card-state-pill.state-deck');
        if (deckPill) {
            deckPill.classList.toggle('is-active', Boolean(inDeck));
            deckPill.classList.toggle('is-passive', !inDeck);
            deckPill.title = inDeck ? getEventLang().deck_badge : getEventLang().deck_add;
            const deckIcon = deckPill.querySelector('i');
            if (deckIcon) {
                deckIcon.className = inDeck ? 'bi bi-bookmark-star-fill' : 'bi bi-bookmark-star';
            }
        }
    }

    applyStatusState(card, isPublished) {
        if (!card) return;
        card.dataset.status = isPublished ? 'y' : 'n';
        const statusPill = card.querySelector('.card-state-pill.state-status');
        if (statusPill) {
            statusPill.classList.toggle('is-active', Boolean(isPublished));
            statusPill.classList.toggle('is-passive', !isPublished);
            statusPill.title = isPublished ? getEventLang().active : getEventLang().draft;
            const statusIcon = statusPill.querySelector('i');
            if (statusIcon) {
                statusIcon.className = isPublished ? 'bi bi-eye-fill' : 'bi bi-eye-slash';
            }
        }
    }

    async toggleEventStatus() {
        if (!this.selectedContentId) return;
        const card = this.getSelectedCard();
        const statusBtn = document.getElementById('toolbarStatus');
        const statusIcon = statusBtn ? statusBtn.querySelector('i') : null;

        if (statusBtn) statusBtn.classList.add('is-loading');
        if (statusIcon) statusIcon.className = 'bi bi-arrow-repeat';

        const formData = new URLSearchParams();
        formData.append('id', this.selectedContentId);

        try {
            const response = await fetch(`${getEventModuleUrl()}toggle_status`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            });
            const result = await response.json();

            const isSuccess = result.status === 'success' || (result.new_status !== undefined && result.status !== 'error');
            if (!isSuccess) {
                showSnackbar(result.message || getEventLang().status_error, 'error');
                return;
            }

            const newStatus = result.new_status || (result.status === 'y' ? 'y' : 'n');
            if (card) {
                const previousStatus = card.dataset.status;
                this.applyStatusState(card, newStatus === 'y');

                if (previousStatus !== newStatus) {
                    const pubEl = document.getElementById('statPublishedEvent');
                    if (pubEl) {
                        const currentPub = parseInt(pubEl.textContent, 10) || 0;
                        pubEl.textContent = Math.max(0, newStatus === 'y' ? currentPub + 1 : currentPub - 1);
                    }
                    const draftEl = document.getElementById('statDraftEvent');
                    if (draftEl) {
                        const currentDrafts = parseInt(draftEl.textContent, 10) || 0;
                        draftEl.textContent = Math.max(0, newStatus === 'n' ? currentDrafts + 1 : currentDrafts - 1);
                    }
                }
            }

            if (window.navigator.vibrate) window.navigator.vibrate(12);
            showSnackbar(result.message || (newStatus === 'y' ? getEventLang().published_msg : getEventLang().unpublished_msg), 'success');
        } catch (error) {
            console.error('Status toggle error:', error);
            showSnackbar(getEventLang().status_error, 'error');
        } finally {
            if (statusBtn) statusBtn.classList.remove('is-loading');
            this.syncCardToolbarState(Boolean(this.selectedContentId));
        }
    }

    async toggleHeroSlider() {
        if (!this.selectedContentId) return;

        const sliderBtn = document.getElementById('toolbarSlider');
        const sliderIcon = sliderBtn ? sliderBtn.querySelector('i') : null;
        if (sliderBtn) sliderBtn.classList.add('is-loading');
        if (sliderIcon) sliderIcon.className = 'bi bi-arrow-repeat';

        const formData = new URLSearchParams();
        formData.append('id', this.selectedContentId);

        try {
            const response = await fetch(`${getEventModuleUrl()}toggle_hero_slider`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            });
            const result = await response.json();

            if (result.status !== 'success') {
                showSnackbar(result.message || getEventLang().hero_slider_error, 'error');
                return;
            }

            this.selectedInHeroSlider = Boolean(result.in_hero_slider);
            this.applyHeroSliderState(this.getSelectedCard(), this.selectedInHeroSlider);
            if (window.navigator.vibrate) window.navigator.vibrate(12);
            showSnackbar(
                result.message || (this.selectedInHeroSlider ? getEventLang().hero_slider_added : getEventLang().hero_slider_removed),
                'success'
            );
        } catch (error) {
            console.error('Hero slider toggle error:', error);
            showSnackbar(getEventLang().hero_slider_error, 'error');
        } finally {
            if (sliderBtn) sliderBtn.classList.remove('is-loading');
            if (sliderIcon) sliderIcon.className = 'bi bi-stars';
            this.syncCardToolbarState(Boolean(this.selectedContentId));
        }
    }

    async toggleDeck() {
        if (!this.selectedContentId) return;

        const deckBtn = document.getElementById('toolbarDeck');
        const deckIcon = deckBtn ? deckBtn.querySelector('i') : null;
        if (deckBtn) deckBtn.classList.add('is-loading');
        if (deckIcon) deckIcon.className = 'bi bi-arrow-repeat';

        const formData = new URLSearchParams();
        formData.append('id', this.selectedContentId);

        try {
            const response = await fetch(`${getEventModuleUrl()}toggle_deck`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            });
            const result = await response.json();

            if (result.status !== 'success') {
                showSnackbar(result.message || getEventLang().deck_error, 'error');
                return;
            }

            this.selectedInDeck = Boolean(result.in_deck);
            this.applyDeckState(this.getSelectedCard(), this.selectedInDeck);
            if (window.navigator.vibrate) window.navigator.vibrate(12);
            showSnackbar(
                result.message || (this.selectedInDeck ? getEventLang().deck_added : getEventLang().deck_removed),
                'success'
            );
        } catch (error) {
            console.error('Deck toggle error:', error);
            showSnackbar(getEventLang().deck_error, 'error');
        } finally {
            if (deckBtn) deckBtn.classList.remove('is-loading');
            this.syncCardToolbarState(Boolean(this.selectedContentId));
        }
    }
}

// Global actions for list view
window.clearSelection = function () {
    window.eventManager?.clearSelection();
};

window.editSelected = function () {
    const mgr = window.eventManager;
    if (mgr && mgr.selectedContentId) {
        window.location.href = getEventModuleUrl() + 'edit/' + mgr.selectedContentId;
    }
};

window.videoSelected = function () {
    const mgr = window.eventManager;
    if (mgr && mgr.selectedContentId) {
        openMediaRelationPicker(mgr.selectedContentId, 'video');
    }
};

window.gallerySelected = function () {
    const mgr = window.eventManager;
    if (mgr && mgr.selectedContentId) {
        const siteUrl = (typeof window.SITE_URL !== 'undefined' && window.SITE_URL) ? window.SITE_URL : '/';
        const url = siteUrl + 'mod_gallery/list/mod_event/' + mgr.selectedContentId;
        if (window.AFModalEngine) {
            AFModalEngine.push(url, { skipHistory: true });
        } else {
            window.location.href = url;
        }
    }
};

window.faqSelected = function () {
    const mgr = window.eventManager;
    if (mgr && mgr.selectedContentId) {
        const siteUrl = (typeof window.SITE_URL !== 'undefined' && window.SITE_URL) ? window.SITE_URL : '/';
        const url = siteUrl + 'mod_faq/list/mod_event/' + mgr.selectedContentId;
        if (window.AFModalEngine) {
            AFModalEngine.push(url, { skipHistory: true });
        } else {
            window.location.href = url;
        }
    }
};

window.filesSelected = function () {
    const mgr = window.eventManager;
    if (mgr && mgr.selectedContentId) {
        openMediaRelationPicker(mgr.selectedContentId, 'files');
    }
};

window.sliderSelected = function () {
    const mgr = window.eventManager;
    if (mgr && mgr.selectedContentId) {
        mgr.toggleHeroSlider();
    }
};

window.deckSelected = function () {
    const mgr = window.eventManager;
    if (mgr && mgr.selectedContentId) {
        mgr.toggleDeck();
    }
};

window.deleteSelected = function () {
    const mgr = window.eventManager;
    if (!mgr || !mgr.selectedContentId) return;
    const id = mgr.selectedContentId;
    const al = (typeof window.adminLang !== 'undefined' && window.adminLang) ? window.adminLang : {};

    Swal.fire({
        title: getEventLang().confirm_delete,
        icon: 'warning',
        showCancelButton: true,
        showDenyButton: false,
        confirmButtonColor: '#B71C1C',
        cancelButtonColor: '#6750A4',
        confirmButtonText: '<i class="bi bi-trash3"></i> ' + (al.context_menu?.confirm || 'Delete'),
        cancelButtonText: '<i class="bi bi-x-lg"></i> ' + (al.context_menu?.cancel || 'Cancel'),
        background: 'var(--md-sys-color-surface-container-high, #fff)',
        color: 'var(--md-sys-color-on-surface, #1C1B1F)',
        customClass: { popup: 'm3-swal-popup', title: 'm3-swal-title', actions: 'm3-swal-actions' },
        showClass: { popup: 'animate__animated animate__fadeInUp animate__faster' },
        hideClass: { popup: 'animate__animated animate__fadeOutDown animate__faster' }
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('delete[]', id);
            formData.append('ajax_request', '1');

            fetch(getEventModuleUrl() + 'delete', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
                .then(response => response.text())
                .then(msg => {
                    const trimmedMsg = (msg || '').trim();
                    if (trimmedMsg === 'Success' || trimmedMsg === 'OK') {
                        mgr.clearSelection();
                        mgr.loadData(true);
                        showSnackbar(al.context_menu?.delete_success || 'Deleted successfully', 'success');
                    } else {
                        showSnackbar(trimmedMsg || getEventLang().error_general, 'error');
                    }
                })
                .catch(err => {
                    console.error('Delete error:', err);
                    showSnackbar(getEventLang().error_general, 'error');
                });
        }
    });
};

window.openMediaRelationPicker = function(contentId, type) {
    if (type !== 'files' && type !== 'video' && type !== 'gallery') {
        return;
    }

    const siteUrl = (typeof window.SITE_URL !== 'undefined' && window.SITE_URL) ? window.SITE_URL : '/';
    let endpoint = `${getEventModuleUrl()}ajax_get_files`;
    let addUrl = '';
    let manageUrl = '';
    
    if (type === 'video') {
        endpoint = `${getEventModuleUrl()}all_video`;
        addUrl = `${siteUrl}mod_video_embed/add?target_module=mod_event&target_id=${contentId}`;
        manageUrl = `${siteUrl}mod_video_embed/list`;
    } else if (type === 'gallery') {
        endpoint = `${getEventModuleUrl()}ajax_get_galleries`;
        addUrl = `${siteUrl}mod_gallery/list/mod_event/${contentId}`;
        manageUrl = addUrl;
    } else {
        addUrl = `${siteUrl}mod_attachment/list/mod_event/${contentId}`;
        manageUrl = addUrl;
    }

    window.currentMediaPickerModule = 'mod_event';
    window.currentMediaPickerRecordId = contentId;
    window.currentMediaPickerContentId = contentId;
    window.currentMediaPickerType = type;
    window.currentMediaPickerAddUrl = addUrl;
    window.currentMediaPickerManageUrl = manageUrl;

    const addBtn = document.getElementById('selectPickerAddBtn');
    const manageBtn = document.getElementById('selectPickerManageBtn');
    if (addBtn) addBtn.classList.remove('is-hidden');
    if (manageBtn) manageBtn.classList.remove('is-hidden');

    const pickerData = new FormData();
    pickerData.append('q', '');

    const currentData = new FormData();
    currentData.append('id', contentId);

    Promise.all([
        fetch(endpoint, { method: 'POST', body: pickerData }).then((r) => r.json()),
        fetch(`${getEventModuleUrl()}ajax_get_current_media`, { method: 'POST', body: currentData }).then((r) => r.json())
    ])
        .then(([data, currentMedia]) => {
            let select = document.getElementById('dynamicMediaSelect');
            if (!select) {
                select = document.createElement('select');
                select.id = 'dynamicMediaSelect';
                select.className = 'is-hidden';
                document.body.appendChild(select);

                select.addEventListener('change', function() {
                    const val = this.value;
                    if (val === undefined || val === null) return;

                    const fd = new FormData();
                    fd.append('id', window.currentMediaPickerContentId);
                    fd.append('type', window.currentMediaPickerType);
                    fd.append('value', val);

                    fetch(`${getEventModuleUrl()}ajax_update_relation`, {
                        method: 'POST',
                        body: fd
                    })
                        .then((response) => response.json())
                        .then((result) => {
                            if (result.status === 'success') {
                                showSnackbar(result.message, 'success');
                                const mgr = window.eventManager;
                                if (mgr) mgr.loadData(true);
                            } else {
                                showSnackbar(result.message || getEventLang().error_general, 'error');
                            }
                        })
                        .catch((error) => {
                            console.error('Update Error:', error);
                            showSnackbar(getEventLang().error_general, 'error');
                        });
                });
            }

            const card = document.querySelector(`.content-card[data-id="${contentId}"]`);
            const itemTitle = card?.querySelector('h3')?.textContent?.trim() || '';

            const pickerTitleEl = document.getElementById('selectPickerTitle');
            const pickerLangEl = document.getElementById('selectPickerActiveLang');
            const pickerSubtitleEl = document.getElementById('selectPickerSubtitle');
            
            const pickerTitleText = type === 'video'
                ? getEventLang().picker.video
                : (type === 'gallery' ? getEventLang().picker.gallery : getEventLang().picker.file);

            if (pickerTitleEl) pickerTitleEl.textContent = pickerTitleText;
            if (pickerLangEl) pickerLangEl.textContent = '';
            if (pickerSubtitleEl) {
                pickerSubtitleEl.textContent = itemTitle;
                pickerSubtitleEl.classList.toggle('is-hidden', !itemTitle);
            }

            select.innerHTML = '';
            data.forEach((item) => {
                const opt = document.createElement('option');
                opt.value = item.value || item.id;
                opt.textContent = item.name || item.text || item.title || String(item.id);
                select.appendChild(opt);
            });

            if (type === 'video') {
                const activeVideos = Array.isArray(currentMedia.video) ? currentMedia.video : [];
                select.value = activeVideos.length > 0 ? String(activeVideos[0]) : '0';
                select.dataset.pickerTitle = getEventLang().picker.video;
            } else if (type === 'gallery') {
                select.value = currentMedia.gallery_id ? String(currentMedia.gallery_id) : '0';
                select.dataset.pickerTitle = getEventLang().picker.gallery;
            } else {
                select.value = currentMedia.file_id ? String(currentMedia.file_id) : '0';
                select.dataset.pickerTitle = getEventLang().picker.file;
            }

            if (typeof openSelectPickerSheet === 'function') {
                openSelectPickerSheet('dynamicMediaSelect');
            }
        })
        .catch((error) => {
            console.error(error);
            showSnackbar(getEventLang().error_general, 'error');
        });
};

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
