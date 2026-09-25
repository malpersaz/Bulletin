/**
 * mod_content.js
 * Handles fetching data from existing Datatable JSON endpoint and rendering M3 Cards.
 */

function getContentModuleUrl() {
    if (typeof window.MODULE_URL !== 'undefined' && window.MODULE_URL) return window.MODULE_URL;
    const container = document.getElementById('content-list-container');
    if (container && container.dataset.moduleUrl) return container.dataset.moduleUrl;
    const siteUrl = (typeof window.SITE_URL !== 'undefined' && window.SITE_URL) ? window.SITE_URL : '/';
    return siteUrl + 'mod_content/';
}

function getContentDataUrl() {
    if (typeof window.DATA_URL !== 'undefined' && window.DATA_URL) return window.DATA_URL;
    const container = document.getElementById('content-list-container');
    if (container && container.dataset.dataUrl) return container.dataset.dataUrl;
    return getContentModuleUrl() + 'datatable_json';
}

function getContentCategoryMap() {
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

function getContentLang() {
    if (typeof window.CONTENT_LANG !== 'undefined' && window.CONTENT_LANG) return window.CONTENT_LANG;
    const al = (typeof window.adminLang !== 'undefined' && window.adminLang) ? window.adminLang : {};
    const mod = al.mod_content || al.active_module || {};
    const ctx = al.context_menu || {};
    const dt = al.datatable || {};

    return {
        active: mod.status_badge?.active || 'Active',
        draft: mod.status_badge?.draft || 'Draft',
        general: mod.status_badge?.general || 'General',
        sheet_all: mod.sheet?.all || 'All',
        confirm_delete: mod.delete_confirm || ctx.delete_confirm || 'Are you sure you want to delete this content?',
        confirm_publish: mod.quick_publish_confirm || 'Do you want to publish this content?',
        confirm_unpublish: mod.quick_unpublish_confirm || 'Do you want to unpublish this content?',
        error_general: mod.error || 'An error occurred',
        error_upload: mod.message?.error?.add || 'Upload failed',
        cancel: mod.button?.cancel || ctx.cancel || 'Cancel',
        delete_confirm_text: mod.button?.yes || ctx.confirm || 'Yes',
        delete_cancel_text: mod.button?.no || ctx.cancel || 'Cancel',
        status_confirm_text: mod.button?.yes || 'Yes',
        status_cancel_text: mod.button?.no || 'No',
        batch_status_confirm_text: mod.button?.yes || 'Yes',
        batch_status_cancel_text: mod.button?.no || 'No',
        batch_delete_confirm_text: mod.button?.yes || 'Yes',
        image_remove_title: mod.remove_image || 'Are you sure you want to remove this image?',
        image_remove_confirm_text: mod.button?.delete || mod.button?.yes || 'Delete',
        image_remove_cancel_text: mod.button?.cancel || mod.button?.no || 'Cancel',
        image_remove_success: mod.remove_image_success || 'Image removed successfully',
        image_updated: mod.image_updated || 'Image updated successfully',
        image_title: mod.wizard?.step?.image || 'Cover Image',
        save_image: mod.button?.save || 'Save Image',
        hero_slider_add: mod.hero_slider_add || 'Add to Slider',
        hero_slider_remove: mod.hero_slider_remove || 'Remove from Slider',
        hero_slider_added: mod.hero_slider_added || 'Added to Hero Slider',
        hero_slider_removed: mod.hero_slider_removed || 'Removed from Hero Slider',
        hero_slider_error: mod.hero_slider_error || 'Failed to update Hero Slider',
        hero_slider_badge: mod.hero_slider_badge || 'Hero Slider',
        picker: {
            file: mod.picker?.file || 'Select Files',
            video: ctx.video || 'Select Video'
        },
        loading: dt.loadingRecords || 'Loading...',
        grid: {
            load_more: mod.grid?.load_more || 'Load More'
        }
    };
}

class ContentManager {
    constructor() {
        this.container = document.getElementById('content-list-container');
        this.loader = document.getElementById('infinite-loader');
        this.emptyState = document.getElementById('empty-state');
        this.emptyIcon = document.getElementById('emptyStateIcon');
        this.emptyTitle = document.getElementById('emptyStateTitle');
        this.emptyDesc = document.getElementById('emptyStateDesc');
        this.emptyAddBtn = document.getElementById('emptyStateAddBtn');
        this.emptyClearBtn = document.getElementById('emptyStateClearBtn');
        this.imageModal = document.getElementById('contentImageModal');
        this.imageModalTitle = document.getElementById('contentImageModalTitle');
        this.imageModalSubtitle = document.getElementById('contentImageModalSubtitle');
        this.imageMeta = document.getElementById('listImageMeta');
        this.imageFooter = document.getElementById('contentImageModalFooter');
        this.imageCancelBtn = document.getElementById('contentImageCancelBtn');
        this.imageSaveBtn = document.getElementById('contentImageSaveBtn');

        this.isLoading = false;
        this.hasMore = true;
        this.selectedContentId = null;
        this.selectedInHeroSlider = false;
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
        this.loadData();
        this.initStatsFilterListeners();
        this.initSearchListener();
        this.initEmptyStateListeners();
        this.initPullToRefresh();
        this.initListImageUploader();
        this.initializeImageModalCopy();
        this.initActionListeners();
    }

    initStatsFilterListeners() {
        const buttons = document.querySelectorAll('.content-stat-card[data-status-filter]');
        buttons.forEach(btn => {
            btn.addEventListener('click', () => {
                buttons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                const filterVal = btn.dataset.statusFilter;
                const statusInput = document.getElementById('filterStatus');
                if (statusInput) {
                    statusInput.value = (filterVal === 'all' || !filterVal) ? '' : filterVal;
                }
                this.loadData(true);
            });
        });
    }

    initActionListeners() {
        document.addEventListener('click', (e) => {
            const target = e.target.closest('[data-action]');
            if (!target) return;

            const action = target.dataset.action;
            if (action === 'close-image-uploader') {
                this.closeImageUploader();
            } else if (action === 'save-image-upload') {
                this.submitListImageUpload();
            } else if (action === 'toggle-slider') {
                this.toggleHeroSlider();
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
    }

    initializeImageModalCopy() {
        if (this.imageCancelBtn && typeof adminLang !== 'undefined' && adminLang.context_menu?.cancel) {
            this.imageCancelBtn.textContent = adminLang.context_menu.cancel;
        }
    }

    createLoadMoreButton() {
        this.loadMoreBtn = document.createElement('button');
        this.loadMoreBtn.className = 'm3-btn-outlined content-load-more-btn is-hidden';

        let btnText = 'Load More';
        if (getContentLang().grid && getContentLang().grid.load_more) {
            btnText = getContentLang().grid.load_more;
        }
        this.loadMoreBtn.innerHTML = `<i class="bi bi-arrow-down-circle"></i><span>${btnText}</span>`;

        // Insert after container, before loader
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
            this.loadMoreBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>...`;
        }

        if (reset) {
            this.start = 0;
            this.container.innerHTML = '';
            this.container.classList.add('is-hidden');
            this.hasMore = true;
            this.draw = 1;
            this.selectedContentId = null;
            this.selectedInHeroSlider = false;
            this.updateToolbar(false);
            this.updateFabState(false);
            if (this.emptyState) {
                this.emptyState.classList.add('is-hidden');
            }
            if (this.loader) {
                this.loader.classList.remove('is-hidden');
            }
            if (this.loadMoreBtn) {
                this.loadMoreBtn.classList.add('is-hidden');
            }
        } else {
            // Only show loader if not resetting and still has more data
            if (this.loader) {
                this.loader.classList.remove('is-hidden');
            }
        }

        // Update length from filter input if available
        const filterLengthInput = document.getElementById('filterLength');
        if (filterLengthInput && filterLengthInput.value) {
            this.length = parseInt(filterLengthInput.value) || 10;
        }

        // Prepare FormData for Datatable compatibility
        const formData = new FormData(document.getElementById('filterForm'));
        formData.set('start', this.start);
        formData.set('length', this.length);
        formData.set('draw', this.draw);

        try {
            const response = await fetch(getContentDataUrl(), {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data && data.data && data.data.length > 0) {
                this.container.classList.remove('is-hidden');
                this.renderCards(data.data);
            } else if (this.start === 0) {
                this.container.classList.add('is-hidden');
            }

            if (typeof data.totalContent !== 'undefined') {
                const totalEl = document.getElementById('statTotalContent');
                if (totalEl) totalEl.textContent = data.totalContent;
            }
            if (typeof data.publishedContent !== 'undefined') {
                const pubEl = document.getElementById('statPublishedContent');
                if (pubEl) pubEl.textContent = data.publishedContent;
            } else if (typeof data.totalContent !== 'undefined' && typeof data.draftContent !== 'undefined') {
                const pubEl = document.getElementById('statPublishedContent');
                if (pubEl) pubEl.textContent = Math.max(0, data.totalContent - data.draftContent);
            }
            if (typeof data.draftContent !== 'undefined') {
                const draftEl = document.getElementById('statDraftContent');
                if (draftEl) draftEl.textContent = data.draftContent;
            }

            // Update pagination tracking
            this.start += this.length;
            this.draw++;

            // Check if we reached the end
            if (this.start >= data.recordsFiltered) {
                this.hasMore = false;
                if (this.loader) {
                    this.loader.classList.add('is-hidden');
                }
            }

            this.updateEmptyState(data);

        } catch (error) {
            console.error('Data Load Error:', error);
        } finally {
            this.isLoading = false;
            if (this.loader) {
                this.loader.classList.add('is-hidden');
            }

            // Toggle Load More Button
            if (this.loadMoreBtn) {
                if (this.hasMore) {
                    this.loadMoreBtn.classList.remove('is-hidden');
                } else {
                    this.loadMoreBtn.classList.add('is-hidden');
                }
                this.loadMoreBtn.disabled = false;
                let btnText = 'Load More';
                if (getContentLang().grid && getContentLang().grid.load_more) {
                    btnText = getContentLang().grid.load_more;
                }
                this.loadMoreBtn.innerHTML = `<i class="bi bi-arrow-down-circle me-2"></i>${btnText}`;
            }
        }
    }

    renderCards(items) {
        if (!items || items.length === 0) return;

        const fragment = document.createDocumentFragment();

        items.forEach((item) => {
            // Data Mapping (Indices confirmed via server payload)
            // 0: Checkbox
            // 1: ID
            // 2: Name
            // 3: Image HTML
            // 4: Lang
            // 5: Date (String)
            // 6: Actions HTML
            // 7: Category ID
            // 8: Status
            // 9: Author Name
            // 10: Slider (1 or 0)

            const id = this.stripHtml(item[1]);
            const titleHtml = item[2];
            const imgHtml = item[3];
            const lang = this.stripHtml(item[4]);
            const date = this.stripHtml(item[5]);
            const categoryId = item[7] || 0;
            const statusRaw = item[8] || 'n';
            const authorName = this.stripHtml(item[9] || '');
            const inHeroSlider = String(item[10] || '0') === '1';

            // Extract Text Title
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = titleHtml;
            const titleText = tempDiv.textContent || tempDiv.innerText;

            // Extract Image Src
            tempDiv.innerHTML = imgHtml;
            const imgTag = tempDiv.querySelector('img');
            const imgSrc = imgTag ? imgTag.src : '';

            // Card Element
            const card = document.createElement('div');
            card.className = 'm3-card content-card';
            card.dataset.id = id;
            card.dataset.imageSrc = imgSrc || '';
            card.dataset.imageName = imgSrc ? (imgSrc.split('/').pop() || titleText || '') : '';
            card.dataset.inHeroSlider = inHeroSlider ? '1' : '0';
            card.addEventListener('click', () => window.contentManager.selectContent(id, card));

            // Status Logic
            const isPublished = (statusRaw === 'y');
            const statusText = isPublished ? getContentLang().active : getContentLang().draft;
            const statusClass = isPublished ? 'status-active' : 'status-draft';

            // Category Logic
            let categoryName = getContentLang().general;
            if (Object.keys(getContentCategoryMap()).length > 0 && getContentCategoryMap()[categoryId]) {
                categoryName = getContentCategoryMap()[categoryId];
            }

            const safeStatusText = this.escapeHtml(statusText);
            const safeCategoryName = this.escapeHtml(categoryName);
            const safeTitleText = this.escapeHtml(titleText);
            const safeLang = this.escapeHtml(lang);
            const safeDate = this.escapeHtml(date);
            const safeAuthorName = this.escapeHtml(authorName);
            const safeSliderBadge = this.escapeHtml(getContentLang().hero_slider_badge);

            // HTML Structure (Material 3 Mobile-First with Strict Code Separation)
            card.innerHTML = `
                <div class="card-content-layout">
                    <div class="card-thumbnail${imgSrc ? ' has-image' : ' empty-thumb'}">
                        ${!imgSrc ? '<i class="bi bi-camera-fill"></i>' : ''}
                    </div>
                    
                    <div class="card-body-col">
                        <div class="card-header-row">
                             <div class="card-header-main">
                                <div class="card-meta-top">
                                    <span class="hero-slider-badge${inHeroSlider ? ' is-visible' : ''}">
                                        <i class="bi bi-stars"></i> ${safeSliderBadge}
                                    </span>
                                    <span class="hero-slider-dot${inHeroSlider ? ' is-visible' : ''}">•</span>
                                    <span class="card-status-pill ${statusClass}">${safeStatusText}</span>
                                    <span class="meta-separator">•</span>
                                    <span class="card-category-pill">${safeCategoryName}</span>
                                </div>
                                <h3 class="card-title-text">${safeTitleText}</h3>
                             </div>
                             <button type="button" class="m3-icon-btn card-context-btn" aria-label="Actions">
                                <i class="bi bi-three-dots-vertical"></i>
                             </button>
                        </div>

                        <div class="card-meta-bottom">
                             <span class="meta-item meta-lang"><i class="bi bi-translate"></i> ${safeLang}</span>
                             <span class="meta-item meta-date"><i class="bi bi-calendar3"></i> ${safeDate}</span>
                             <span class="meta-item meta-author">${safeAuthorName}</span>
                        </div>
                    </div>
                </div>
            `;

            const thumbnail = card.querySelector('.card-thumbnail');
            if (thumbnail) {
                if (imgSrc) {
                    thumbnail.style.backgroundImage = `url("${imgSrc}")`;
                }

                thumbnail.addEventListener('click', (event) => {
                    event.stopPropagation();
                    this.processImageUpload(id);
                });
            }

            const actionButton = card.querySelector('.card-context-btn') || card.querySelector('.m3-icon-btn');
            if (actionButton) {
                actionButton.addEventListener('click', (event) => {
                    event.stopPropagation();
                    this.openActionSheet(id, titleText, statusRaw);
                });
            }

            fragment.appendChild(card);
        });

        this.container.appendChild(fragment);
    }

    stripHtml(html) {
        let tmp = document.createElement("DIV");
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

    initScrollListener() {
        const scrollFrame = document.querySelector('.app-scroll-frame');
        if (scrollFrame) {
            let scrollTimeout = null;
            scrollFrame.addEventListener('scroll', () => {
                // Throttle: Only load when scrolling stops
                if (scrollTimeout) clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(() => {
                    // IMPORTANT: Check if there are still more data
                    if (!this.hasMore || this.isLoading) {
                        return;
                    }
                    // Threshold check: Ensure we are really at the bottom
                    // Also check if content is actually taller than viewport
                    if (scrollFrame.scrollHeight <= scrollFrame.clientHeight) {
                        return; // Don't auto load if content fits
                    }

                    if (scrollFrame.scrollTop + scrollFrame.clientHeight >= scrollFrame.scrollHeight - 50) {
                        this.loadData();
                    }
                }, 200); // 200ms debounce
            });
        }
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

    initEmptyStateListeners() {
        if (this.emptyClearBtn) {
            this.emptyClearBtn.addEventListener('click', () => {
                this.clearFiltersAndSearch();
            });
        }
    }

    clearFiltersAndSearch() {
        const searchInput = document.getElementById('searchInput');
        if (searchInput) searchInput.value = '';

        const filterSearch = document.getElementById('filterSearch');
        if (filterSearch) filterSearch.value = '';

        const filterCategory = document.getElementById('filterCategory');
        if (filterCategory) filterCategory.value = '';

        const filterStatus = document.getElementById('filterStatus');
        if (filterStatus) filterStatus.value = '';

        const statButtons = document.querySelectorAll('.content-stat-card[data-status-filter]');
        statButtons.forEach(btn => {
            if (btn.dataset.statusFilter === 'all') {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        this.loadData(true);
    }

    updateEmptyState(data) {
        if (!this.emptyState) return;

        const hasNoResults = (!data || !data.data || data.data.length === 0 || data.recordsFiltered === 0);

        if (!hasNoResults) {
            this.emptyState.classList.add('is-hidden');
            if (this.container) {
                this.container.classList.remove('is-hidden');
            }
            return;
        }

        if (this.container) {
            this.container.classList.add('is-hidden');
        }

        const searchValue = (document.getElementById('filterSearch')?.value || '').trim();
        const categoryValue = (document.getElementById('filterCategory')?.value || '').trim();
        const statusValue = (document.getElementById('filterStatus')?.value || '').trim();
        const isFiltered = Boolean(searchValue || categoryValue || statusValue || (data && data.recordsTotal > 0 && data.recordsFiltered === 0));

        const ds = this.emptyState.dataset || {};
        const initialTitle = ds.initialTitle || 'No content yet';
        const initialDesc = ds.initialDesc || '';
        const searchTitle = ds.searchTitle || 'No matching results';
        const searchDesc = ds.searchDesc || '';
        const clearActionText = ds.searchAction || 'Clear Search';

        if (isFiltered) {
            if (this.emptyIcon) {
                this.emptyIcon.innerHTML = '<i class="bi bi-search"></i>';
            }
            if (this.emptyTitle) {
                this.emptyTitle.textContent = searchTitle;
            }
            if (this.emptyDesc) {
                this.emptyDesc.textContent = searchDesc;
            }
            if (this.emptyAddBtn) {
                this.emptyAddBtn.classList.add('is-hidden');
            }
            if (this.emptyClearBtn) {
                this.emptyClearBtn.classList.remove('is-hidden');
                const span = this.emptyClearBtn.querySelector('span');
                if (span) span.textContent = clearActionText;
            }
        } else {
            if (this.emptyIcon) {
                this.emptyIcon.innerHTML = '<i class="bi bi-inbox"></i>';
            }
            if (this.emptyTitle) {
                this.emptyTitle.textContent = initialTitle;
            }
            if (this.emptyDesc) {
                this.emptyDesc.textContent = initialDesc;
            }
            if (this.emptyAddBtn) {
                this.emptyAddBtn.classList.remove('is-hidden');
            }
            if (this.emptyClearBtn) {
                this.emptyClearBtn.classList.add('is-hidden');
            }
        }

        this.emptyState.classList.remove('is-hidden');
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
            hintRemoteFailed: data.hintRemoteFailed || getContentLang().image_remote_failed,
            emptyName: data.emptyName || getContentLang().image_empty,
            emptyMeta: data.emptyMeta || getContentLang().image_drop_description,
            imageLabel: getContentLang().image_title
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
        this.imageModal = document.getElementById('contentImageModal');
        this.imageModalTitle = document.getElementById('contentImageModalTitle');
        this.imageModalSubtitle = document.getElementById('contentImageModalSubtitle');
        this.imageSaveBtn = document.getElementById('contentImageSaveBtn');
        this.imageCancelBtn = document.getElementById('contentImageCancelBtn');
        this.imageFooter = document.getElementById('contentImageModalFooter');

        if (this.imageSaveBtn) {
            this.imageSaveBtn.onclick = () => this.submitListImageUpload();
        }

        const card = document.querySelector(`.content-card[data-id="${id}"]`);
        
        if (!title && card) {
            title = card.querySelector('h3')?.textContent?.trim() || '';
        }

        const existingImageSrc = card?.dataset.imageSrc || '';
        const existingImageName = card?.dataset.imageName || title || getContentLang().image_title;
        this.imageModalState = {
            hasExistingImage: Boolean(existingImageSrc),
            existingImageSrc,
            existingImageName
        };

        if (this.imageModalTitle) {
            this.imageModalTitle.textContent = getContentLang().image_title;
        }

        const subtitleEl = this.imageModalSubtitle || document.getElementById('contentImageModalSubtitle');
        if (subtitleEl) {
            if (title) {
                subtitleEl.textContent = title;
                subtitleEl.removeAttribute('hidden');
                subtitleEl.hidden = false;
                subtitleEl.style.display = 'block';
            } else {
                subtitleEl.textContent = '';
                subtitleEl.setAttribute('hidden', 'true');
                subtitleEl.hidden = true;
                subtitleEl.style.display = 'none';
            }
        }

        this.resetListImageUploader();

        if (this.imageModal) {
            document.body.classList.add('content-image-modal-open');
            this.imageModal.classList.add('active');
            this.imageModal.setAttribute('aria-hidden', 'false');
        }
    }

    closeImageUploader() {
        if (this.imageModal) {
            this.imageModal.classList.remove('active');
            this.imageModal.setAttribute('aria-hidden', 'true');
        }

        document.body.classList.remove('content-image-modal-open');
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
                name: this.imageModalState.existingImageName || getContentLang().image_title,
                metaText: ''
            });
        }
    }

    resetImageModalActions() {
        if (this.imageSaveBtn) {
            this.imageSaveBtn.disabled = true;
            this.imageSaveBtn.hidden = true;
            this.imageSaveBtn.innerHTML = getContentLang().save_image;
        }
        if (this.imageMeta) {
            this.imageMeta.hidden = true;
        }
        if (this.imageFooter) {
            this.imageFooter.classList.add('is-single-action');
        }
    }

    updateImageModalActions(source, file) {
        const hasPendingFile = Boolean(file) && source !== 'existing';

        if (this.imageSaveBtn) {
            this.imageSaveBtn.hidden = !hasPendingFile;
            this.imageSaveBtn.disabled = !hasPendingFile;
        }
        if (this.imageMeta) {
            this.imageMeta.hidden = !hasPendingFile;
        }
        if (this.imageFooter) {
            this.imageFooter.classList.toggle('is-single-action', !hasPendingFile);
        }
    }

    processImageUpload(id) {
        const card = document.querySelector(`.content-card[data-id="${id}"]`);
        const title = card?.querySelector('h3')?.textContent?.trim() || '';
        this.openImageUploader(id, title);
    }

    handleListImageRemoveAction() {
        const pendingFile = this.imageUploader?.getSelectedFile();
        if (pendingFile) {
            this.resetListImageUploader();
            return;
        }

        if (this.imageModalState.hasExistingImage) {
            this.removeExistingListImage();
        }
    }

    async removeExistingListImage() {
        if (!this.imageUploadContentId || !this.imageModalState.hasExistingImage) {
            return;
        }

        const result = await Swal.fire({
            title: getContentLang().image_remove_title,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#B71C1C',
            cancelButtonColor: '#6750A4',
            confirmButtonText: getContentLang().image_remove_confirm_text,
            cancelButtonText: getContentLang().image_remove_cancel_text,
            background: 'var(--md-sys-color-surface-container-high, #fff)',
            color: 'var(--md-sys-color-on-surface, #1C1B1F)'
        });

        if (!result.isConfirmed) {
            return;
        }

        this.loader.style.display = 'flex';

        try {
            const formData = new URLSearchParams();
            formData.append('id', this.imageUploadContentId);

            const response = await fetch(`${getContentModuleUrl()}image_remove_image`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            });

            const text = (await response.text()).trim();
            if (text !== 'Success') {
                showSnackbar(text || getContentLang().error_general, 'error');
                return;
            }

            const card = document.querySelector(`.content-card[data-id="${this.imageUploadContentId}"]`);
            if (card) {
                const thumb = card.querySelector('.card-thumbnail');
                if (thumb) {
                    thumb.style.backgroundImage = '';
                    thumb.classList.add('empty-thumb');
                    thumb.classList.remove('has-image');
                    if (!thumb.querySelector('i')) {
                        thumb.innerHTML = '<i class="bi bi-camera-fill"></i>';
                    }
                }
                card.dataset.imageSrc = '';
                card.dataset.imageName = '';
            }

            this.closeImageUploader();
            await this.loadData(true);
            showSnackbar(getContentLang().image_remove_success || getContentLang().image_updated, 'success', 2000);
        } catch (error) {
            console.error('Remove image error:', error);
            showSnackbar(getContentLang().error_general, 'error');
        } finally {
            this.loader.style.display = 'none';
        }
    }

    async submitListImageUpload() {
        const fileData = this.imageUploader?.getSelectedFile();
        if (!this.imageUploadContentId || !fileData) {
            this.imageUploader?.showFeedback(getContentLang().error_upload, 'error');
            return;
        }

        const formData = new FormData();
        formData.append('image', fileData);
        formData.append('id', this.imageUploadContentId);

        if (this.imageSaveBtn) {
            this.imageSaveBtn.hidden = false;
            this.imageSaveBtn.disabled = true;
            this.imageSaveBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ${getContentLang().loading}`;
        }

        this.loader.style.display = 'flex';

        try {
            const response = await fetch(`${getContentModuleUrl()}ajax_image_upload`, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.status === 'success') {
                const newImgUrl = result.url || result.image_url || '';
                this.imageModalState.hasExistingImage = true;
                if (newImgUrl) {
                    this.imageModalState.existingImageSrc = newImgUrl;
                    const card = document.querySelector(`.content-card[data-id="${this.imageUploadContentId}"]`);
                    if (card) {
                        const thumb = card.querySelector('.card-thumbnail');
                        if (thumb) {
                            thumb.style.backgroundImage = `url("${newImgUrl}")`;
                            thumb.classList.remove('empty-thumb');
                            thumb.classList.add('has-image');
                            const icon = thumb.querySelector('i');
                            if (icon) icon.remove();
                        }
                        card.dataset.imageSrc = newImgUrl;
                    }
                }
                this.resetListImageUploader({ preserveExisting: true });
                this.closeImageUploader();
                await this.loadData(true);
                showSnackbar(result.message || getContentLang().image_updated, 'success', 2000);
            } else {
                this.imageUploader?.showFeedback(result.message || getContentLang().error_upload, 'error');
                showSnackbar(result.message || getContentLang().error_upload, 'error');
            }
        } catch (err) {
            console.error('Upload error:', err);
            this.imageUploader?.showFeedback(getContentLang().error_upload, 'error');
            showSnackbar(getContentLang().error_upload, 'error');
        } finally {
            this.loader.style.display = 'none';
            if (this.imageSaveBtn) {
                const hasPendingFile = !!this.imageUploader?.getSelectedFile();
                this.imageSaveBtn.hidden = !hasPendingFile;
                this.imageSaveBtn.disabled = !hasPendingFile;
                this.imageSaveBtn.innerHTML = getContentLang().save_image;
            }
        }
    }

    openActionSheet(id, title, status) {
        const titleEl = document.getElementById('contextMenuTitle');
        if (titleEl) titleEl.textContent = title;

        window.currentContentId = id;

        const isPublished = (status === 'y');
        if (typeof openContextMenu === 'function') {
            openContextMenu(id, title, isPublished);
        }
    }

    // --- Card Selection & Toolbar ---
    selectContent(id, cardElement) {
        const isCurrent = cardElement.classList.contains('selected');

        if (isCurrent) {
            this.clearSelection();
            return;
        }

        // Remove all selections
        document.querySelectorAll('.m3-card.selected, .content-card.selected').forEach(el => {
            el.classList.remove('selected');
        });

        cardElement.classList.add('selected');
        this.selectedContentId = id;
        this.selectedInHeroSlider = cardElement.dataset.inHeroSlider === '1';
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
        const ids = ['toolbarEdit', 'toolbarGallery', 'toolbarFiles', 'toolbarDelete', 'toolbarSlider', 'toolbarVideo', 'toolbarFaq'];
        ids.forEach(btnId => {
            const btn = document.getElementById(btnId);
            if (!btn) return;
            if (active) {
                btn.classList.remove('is-disabled');
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
                btn.classList.add('is-disabled');
            }
        });
        this.syncSliderToolbarState(active);
    }

    syncSliderToolbarState(active) {
        const sliderBtn = document.getElementById('toolbarSlider');
        if (!sliderBtn) return;

        const label = active && this.selectedInHeroSlider
            ? getContentLang().hero_slider_remove
            : getContentLang().hero_slider_add;

        sliderBtn.classList.toggle('is-selected', Boolean(active && this.selectedInHeroSlider));
        sliderBtn.title = label;
        sliderBtn.setAttribute('aria-label', label);
    }

    getSelectedCard() {
        if (!this.selectedContentId) return null;
        return document.querySelector(`.content-card[data-id="${this.selectedContentId}"]`);
    }

    applyHeroSliderState(card, inHeroSlider) {
        if (!card) return;

        card.dataset.inHeroSlider = inHeroSlider ? '1' : '0';
        const badge = card.querySelector('.hero-slider-badge');
        const dot = card.querySelector('.hero-slider-dot');

        if (badge) {
            badge.classList.toggle('is-visible', Boolean(inHeroSlider));
        }
        if (dot) {
            dot.classList.toggle('is-visible', Boolean(inHeroSlider));
        }
    }

    async toggleHeroSlider() {
        if (!this.selectedContentId) return;

        const formData = new URLSearchParams();
        formData.append('id', this.selectedContentId);

        try {
            const response = await fetch(`${getContentModuleUrl()}toggle_hero_slider`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            });
            const result = await response.json();

            if (result.status !== 'success') {
                showSnackbar(result.message || getContentLang().hero_slider_error, 'error');
                return;
            }

            this.selectedInHeroSlider = Boolean(result.in_hero_slider);
            this.applyHeroSliderState(this.getSelectedCard(), this.selectedInHeroSlider);
            this.syncSliderToolbarState(true);
            showSnackbar(
                result.message || (this.selectedInHeroSlider ? getContentLang().hero_slider_added : getContentLang().hero_slider_removed),
                'success'
            );
        } catch (error) {
            console.error('Hero slider toggle error:', error);
            showSnackbar(getContentLang().hero_slider_error, 'error');
        }
    }
}

// Initialize on load and expose globally
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('content-list-container')) {
        window.contentManager = new ContentManager();
    }

    const addShell = document.getElementById('contentAddShell') || (window.location.pathname.includes('/mod_content/add') ? document.body : null);
    if (addShell) {
        if (typeof adminLang !== 'undefined' && Array.isArray(adminLang.languages)) {
            adminLang.languages = adminLang.languages.filter(item => item.status === 'y');
        }

        const wizardOverlay = document.getElementById('contentWizard');
        if (wizardOverlay) {
            wizardOverlay.classList.add('active');
            wizardOverlay.style.visibility = 'visible';
        }

        if (typeof window.wizardData !== 'undefined') {
            const activeRouteLang = (addShell.dataset?.lang || window.SITE_LANG || 'tr').toLowerCase();
            window.wizardData.lang = activeRouteLang;
            if (typeof restoreWizardUI === 'function') {
                restoreWizardUI();
            } else {
                if (typeof ensureWizardCategoryMatchesLanguage === 'function') {
                    ensureWizardCategoryMatchesLanguage();
                }
                if (typeof syncWizardCategorySheet === 'function') {
                    syncWizardCategorySheet();
                }
            }
        }
    }
});

// --- Global toolbar action functions (called by onclick in HTML) ---
window.clearSelection = function () {
    window.contentManager?.clearSelection();
};

window.editSelected = function () {
    const mgr = window.contentManager;
    if (mgr && mgr.selectedContentId) {
        window.location.href = getContentModuleUrl() + 'edit/' + mgr.selectedContentId;
    }
};

window.videoSelected = function () {
    const mgr = window.contentManager;
    if (mgr && mgr.selectedContentId) {
        openMediaRelationPicker(mgr.selectedContentId, 'video');
    }
};

window.gallerySelected = function () {
    const mgr = window.contentManager;
    if (mgr && mgr.selectedContentId) {
        const url = SITE_URL + 'mod_gallery/list/mod_content/' + mgr.selectedContentId;
        if (window.AFModalEngine) {
            AFModalEngine.push(url, { skipHistory: true });
        } else {
            window.location.href = url;
        }
    }
};

window.faqSelected = function () {
    const mgr = window.contentManager;
    if (mgr && mgr.selectedContentId) {
        const url = SITE_URL + 'mod_faq/list/mod_content/' + mgr.selectedContentId;
        if (window.AFModalEngine) {
            AFModalEngine.push(url, { skipHistory: true });
        } else {
            window.location.href = url;
        }
    }
};

window.filesSelected = function () {
    const mgr = window.contentManager;
    if (mgr && mgr.selectedContentId) {
        openMediaRelationPicker(mgr.selectedContentId, 'files');
    }
};

window.sliderSelected = function () {
    const mgr = window.contentManager;
    if (mgr && mgr.selectedContentId) {
        mgr.toggleHeroSlider();
    }
};

window.deleteSelected = function () {
    const mgr = window.contentManager;
    if (!mgr || !mgr.selectedContentId) return;
    const id = mgr.selectedContentId;

    Swal.fire({
        title: getContentLang().confirm_delete,
        icon: 'warning',
        showCancelButton: true,
        showDenyButton: false,
        confirmButtonColor: '#B71C1C',
        cancelButtonColor: '#6750A4',
        confirmButtonText: '<i class="bi bi-trash3"></i> ' + (adminLang.context_menu?.confirm || 'Yes'),
        cancelButtonText: '<i class="bi bi-x-lg"></i> ' + adminLang.context_menu.cancel,
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

            fetch(getContentModuleUrl() + 'delete', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
                .then(response => response.text())
                .then(msg => {
                    const trimmedMsg = (msg || '').trim();
                    if (trimmedMsg === 'Success') {
                        mgr.selectedContentId = null;
                        mgr.updateToolbar(false);
                        mgr.loadData(true);
                        showSnackbar(adminLang.context_menu.delete_success, 'success');
                    } else {
                        showSnackbar(trimmedMsg || getContentLang().error_general, 'error');
                    }
                })
                .catch(err => {
                    console.error('Delete error:', err);
                    showSnackbar(getContentLang().error_general, 'error');
                });
        }
    });
};

// Helper for Context Menu Actions (Global scope for onclicks)
// Helper for Context Menu Actions (Global scope for onclicks)
window.handleContextAction = function (action) {
    const id = window.currentContentId;
    if (!id) return;

    switch (action) {
        case 'edit':
            window.location.href = `${getContentModuleUrl()}edit/${id}`;
            break;
        case 'faq':
            closeContextMenu();
            const url = SITE_URL + 'mod_faq/list/mod_content/' + id;
            if (window.AFModalEngine) {
                AFModalEngine.push(url, { skipHistory: true });
            } else {
                window.location.href = url;
            }
            return;
        case 'delete':
            Swal.fire({
                title: getContentLang().confirm_delete,
                icon: 'warning',
                showCancelButton: true,
                showDenyButton: false,
                confirmButtonColor: '#B71C1C',
                cancelButtonColor: '#6750A4',
                confirmButtonText: '<i class="bi bi-trash3"></i> ' + (adminLang.context_menu?.confirm || 'Yes'),
                cancelButtonText: '<i class="bi bi-x-lg"></i> ' + adminLang.context_menu.cancel,
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

                    fetch(`${getContentModuleUrl()}delete`, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                        .then(response => response.text())
                        .then(msg => {
                            const trimmedMsg = (msg || '').trim();
                            if (trimmedMsg === 'Success') {
                                window.contentManager.loadData(true);
                                closeContextMenu();
                                showSnackbar(adminLang.context_menu.delete_success, 'success');
                            } else {
                                showSnackbar(trimmedMsg || getContentLang().error_general, 'error');
                            }
                        })
                        .catch(err => {
                            console.error('Delete error:', err);
                            showSnackbar(getContentLang().error_general, 'error');
                        });
                }
            });
            break;
        case 'gallery':
            closeContextMenu();
            const galleryUrl = `${SITE_URL}mod_gallery/list/mod_content/${id}`;
            if (window.AFModalEngine) {
                AFModalEngine.push(galleryUrl, { skipHistory: true });
            } else {
                window.location.href = galleryUrl;
            }
            break;
        case 'files':
            closeContextMenu();
            openMediaRelationPicker(id, 'files');
            return;
        case 'video':
            closeContextMenu();
            openMediaRelationPicker(id, 'video');
            return;
        case 'slider':
            closeContextMenu();
            if (window.contentManager) {
                window.contentManager.selectedContentId = id;
                const card = document.querySelector(`.content-card[data-id="${id}"]`);
                if (card) {
                    window.contentManager.selectedInHeroSlider = card.dataset.inHeroSlider === '1';
                }
                window.contentManager.toggleHeroSlider();
            }
            return;
        case 'status':
            // Call the existing toggleStatus logic
            const currentStatus = (typeof activeContextItem !== 'undefined' && activeContextItem && activeContextItem.status) ? 'y' : 'n';
            window.toggleStatus(id, currentStatus);
            break;
    }
    closeContextMenu();
} // End function

document.addEventListener('click', function(e) {
    if (e.target.matches('#select-picker-overlay') || e.target.closest('.m3-btn-text')) {
        const btn = document.getElementById('selectPickerAddBtn');
        if (btn) {
            btn.style.display = 'none';
        }
    }
});

window.openMediaRelationPicker = function(contentId, type) {
    if (type !== 'files' && type !== 'video') {
        return;
    }

    let endpoint = `${getContentModuleUrl()}ajax_get_files`;
    
    if (type === 'video') {
        endpoint = `${getContentModuleUrl()}all_video`;
        window.currentMediaPickerAddUrl = `${SITE_URL}mod_video_embed/add`;
    } else {
        window.currentMediaPickerAddUrl = `${SITE_URL}mod_attachment/list/mod_content/${contentId}`;
    }

    const addBtn = document.getElementById('selectPickerAddBtn');
    if (addBtn) {
        addBtn.style.display = 'block';
    }

    const pickerData = new FormData();
    pickerData.append('q', '');

    const currentData = new FormData();
    currentData.append('id', contentId);

    Promise.all([
        fetch(endpoint, { method: 'POST', body: pickerData }).then((response) => response.json()),
        fetch(`${getContentModuleUrl()}ajax_get_current_media`, { method: 'POST', body: currentData }).then((response) => response.json())
    ])
        .then(([data, currentMedia]) => {
            let select = document.getElementById('dynamicMediaSelect');
            if (!select) {
                select = document.createElement('select');
                select.id = 'dynamicMediaSelect';
                select.style.display = 'none';
                document.body.appendChild(select);

                select.addEventListener('change', function() {
                    const val = this.value;
                    if (val === undefined || val === null) {
                        return;
                    }

                    const fd = new FormData();
                    fd.append('id', window.currentMediaPickerContentId);
                    fd.append('type', window.currentMediaPickerType);
                    fd.append('value', val);

                    fetch(`${getContentModuleUrl()}ajax_update_relation`, {
                        method: 'POST',
                        body: fd
                    })
                        .then((response) => response.json())
                        .then((result) => {
                            if (result.status === 'success') {
                                showSnackbar(result.message, 'success');
                                if (window.contentManager) {
                                    window.contentManager.loadData(true);
                                }
                                if (typeof closeSelectPickerSheet === 'function') {
                                    closeSelectPickerSheet();
                                }
                            } else {
                                showSnackbar(result.message || getContentLang().error_general, 'error');
                            }
                        })
                        .catch((error) => {
                            console.error('Update Error:', error);
                            showSnackbar(getContentLang().error_general, 'error');
                        });
                });
            }

            window.currentMediaPickerContentId = contentId;
            window.currentMediaPickerType = type;

            const card = document.querySelector(`.content-card[data-id="${contentId}"]`);
            const itemTitle = card?.querySelector('h3')?.textContent?.trim() || '';

            const pickerTitleEl = document.getElementById('selectPickerTitle');
            const pickerLangEl = document.getElementById('selectPickerActiveLang');
            const pickerSubtitleEl = document.getElementById('selectPickerSubtitle');
            
            if (pickerTitleEl) {
                pickerTitleEl.textContent = type === 'video' ? getContentLang().picker.video : getContentLang().picker.file;
            }
            if (pickerLangEl) {
                pickerLangEl.textContent = '';
            }
            if (pickerSubtitleEl) {
                pickerSubtitleEl.textContent = itemTitle;
                pickerSubtitleEl.style.display = itemTitle ? 'block' : 'none';
            }

            select.innerHTML = '';
            data.forEach((item) => {
                const opt = document.createElement('option');
                opt.value = item.value || item.id;
                opt.textContent = item.name || item.text || item.title || String(item.id);
                select.appendChild(opt);
            });

            if (type === 'video') {
                select.value = '0';
                select.dataset.pickerTitle = getContentLang().picker.video;
            } else {
                select.value = currentMedia.file_id ? currentMedia.file_id.toString() : '0';
                select.dataset.pickerTitle = getContentLang().picker.file;
            }

            if (typeof openSelectPickerSheet === 'function') {
                openSelectPickerSheet('dynamicMediaSelect');
            }
        })
        .catch((error) => {
            console.error(error);
            showSnackbar(getContentLang().error_general, 'error');
        });
};

window.handleSelectPickerAdd = function() {
    if (window.currentMediaPickerAddUrl) {
        window.location.href = window.currentMediaPickerAddUrl;
    }
};

/**
 * Ported from legacy script.twig: Quick Publish/Unpublish
 */
window.toggleStatus = function (id, currentStatus) {
    if (typeof event !== 'undefined') event.stopPropagation();
    const endpoint = currentStatus === 'y' ? 'unapprove' : 'approve';
    const isPublishing = currentStatus !== 'y';

    const confirmMsg = isPublishing ? getContentLang().confirm_publish : getContentLang().confirm_unpublish;
    const successMsg = isPublishing ? adminLang.context_menu.published_msg : adminLang.context_menu.unpublished_msg;

    Swal.fire({
        title: confirmMsg,
        icon: isPublishing ? 'question' : 'info',
        iconColor: isPublishing ? '#2E7D32' : '#E65100',
        showCancelButton: true,
        showDenyButton: false,
        confirmButtonColor: isPublishing ? '#2E7D32' : '#E65100',
        cancelButtonColor: '#6750A4',
        confirmButtonText: isPublishing
            ? '<i class="bi bi-eye"></i> ' + adminLang.context_menu.publish
            : '<i class="bi bi-eye-slash"></i> ' + adminLang.context_menu.unpublish,
        cancelButtonText: '<i class="bi bi-x-lg"></i> ' + adminLang.context_menu.cancel,
        background: 'var(--md-sys-color-surface-container-high, #fff)',
        color: 'var(--md-sys-color-on-surface, #1C1B1F)',
        customClass: { popup: 'm3-swal-popup', title: 'm3-swal-title', actions: 'm3-swal-actions' },
        showClass: { popup: 'animate__animated animate__fadeInUp animate__faster' },
        hideClass: { popup: 'animate__animated animate__fadeOutDown animate__faster' }
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('query[]', id);
            formData.append('ajax_request', '1');

            fetch(`${getContentModuleUrl()}${endpoint}`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
                .then(response => response.text())
                .then(msg => {
                    const trimmedMsg = (msg || '').trim();
                    if (trimmedMsg === 'Success') {
                        window.contentManager.loadData(true);
                        showSnackbar(successMsg, 'success');
                    } else {
                        showSnackbar(trimmedMsg || getContentLang().error_general, 'error');
                    }
                })
                .catch(err => {
                    console.error('Status error:', err);
                    showSnackbar(getContentLang().error_general, 'error');
                });
        }
    });
};

/**
 * ContentEditManager
 * Handles all interactions on the mod_content edit page without inline JavaScript.
 */
class ContentEditManager {
    constructor(config = {}) {
        this.form = document.getElementById('editForm');
        let formConfig = {};
        if (this.form) {
            try {
                formConfig = {
                    lang: this.form.dataset.lang || '',
                    errors: this.form.dataset.errors ? JSON.parse(this.form.dataset.errors) : [],
                    errorMessage: this.form.dataset.errorMessage || '',
                    okText: this.form.dataset.okText || 'OK',
                    successUrl: this.form.dataset.successUrl || null,
                    redirectDelay: parseInt(this.form.dataset.redirectDelay || '2000')
                };
            } catch (e) {
                console.error('Error parsing editForm dataset:', e);
            }
        }

        this.config = Object.assign({
            lang: '',
            categorySheetId: 'categorySheet',
            errors: [],
            errorMessage: '',
            okText: 'OK',
            successUrl: null,
            redirectDelay: 2000
        }, formConfig, config);

        if (!this.form) return;

        this.init();
    }

    init() {
        this.initLanguageFilter();
        this.initRichEditor();
        this.initImageUploader();
        this.initTomSelects();
        this.initCharCounters();
        this.initSelectionCards();
        this.initAdvancedAccordion();
        this.initTitleSync();
        this.initActionHandlers();
        this.initAlerts();
    }

    initLanguageFilter() {
        const currentLang = (this.config.lang || document.getElementById('editLang')?.value || '').toLowerCase();
        if (typeof adminLang !== 'undefined' && Array.isArray(adminLang.languages)) {
            adminLang.languages = adminLang.languages.filter(item => item.status === 'y' || (item.code && item.code.toLowerCase() === currentLang));
        }
        if (typeof wizardData !== 'undefined') {
            wizardData.lang = currentLang;
        }
    }

    initRichEditor() {
        if (typeof initRichEditor === 'function' && document.getElementById('editContentTextarea')) {
            initRichEditor('#editContentTextarea', 400);
        }
    }

    initImageUploader() {
        const shell = document.getElementById('editImageShell');
        if (!shell) return;

        if (typeof initializeEditImageInteractions === 'function') {
            this.editImageUploader = initializeEditImageInteractions();
        }

        const uploadUrl = shell.dataset.uploadUrl || `${getContentModuleUrl()}ajax_image_upload`;
        const contentId = shell.dataset.contentId || shell.dataset.removeId;
        const removeUrl = shell.dataset.removeUrl || `${getContentModuleUrl()}image_remove_image`;

        const input = document.getElementById('editImgInput');
        if (input) {
            input.addEventListener('change', async (e) => {
                const file = e.target.files?.[0];
                if (file && contentId) {
                    await this.uploadEditImageFile(file, contentId, uploadUrl);
                }
            });
        }

        const importBtn = document.getElementById('editImageUrlImportBtn');
        if (importBtn) {
            importBtn.addEventListener('click', () => {
                setTimeout(async () => {
                    const file = input?.files?.[0] || this.editImageUploader?.getSelectedFile?.();
                    if (file && contentId) {
                        await this.uploadEditImageFile(file, contentId, uploadUrl);
                    }
                }, 350);
            });
        }

        // Synchronize dropzone drop event for immediate AJAX upload
        const dropzone = document.getElementById('editImageDropzone');
        if (dropzone) {
            dropzone.addEventListener('drop', (e) => {
                setTimeout(async () => {
                    const file = input?.files?.[0] || this.editImageUploader?.getSelectedFile?.();
                    if (file && contentId) {
                        await this.uploadEditImageFile(file, contentId, uploadUrl);
                    }
                }, 100);
            });
        }

        window.removeEditImage = async () => {
            await this.handleEditImageRemove(shell, contentId, removeUrl);
        };
    }

    async uploadEditImageFile(file, contentId, uploadUrl) {
        const shell = document.getElementById('editImageShell');
        if (!file || !contentId || !uploadUrl) return;

        const formData = new FormData();
        formData.append('image', file);
        formData.append('id', contentId);

        if (typeof showEditImageFeedback === 'function') {
            showEditImageFeedback(getContentLang().loading || 'Uploading...', 'default');
        }

        try {
            const response = await fetch(uploadUrl, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.status === 'success') {
                const newUrl = result.url || result.image_url || '';
                if (shell) {
                    shell.dataset.existingImage = 'true';
                    shell.dataset.existingSrc = newUrl;
                    shell.dataset.pendingImage = 'false';
                    shell.classList.add('has-image');
                }
                const previewImg = document.getElementById('editImgPreview');
                if (previewImg && newUrl) {
                    previewImg.src = newUrl;
                    previewImg.removeAttribute('hidden');
                    previewImg.hidden = false;
                }
                const previewCard = document.getElementById('editImagePreviewCard');
                if (previewCard) {
                    previewCard.setAttribute('data-visible', 'true');
                }
                const emptyState = document.getElementById('editImageEmptyState');
                if (emptyState) {
                    emptyState.setAttribute('hidden', 'true');
                    emptyState.hidden = true;
                }
                const divider = shell?.querySelector('.wizard-image-divider');
                if (divider) divider.setAttribute('hidden', 'true');
                const urlRow = shell?.querySelector('.wizard-image-url-row');
                if (urlRow) urlRow.setAttribute('hidden', 'true');

                if (typeof showSnackbar === 'function') {
                    showSnackbar(result.message || getContentLang().image_updated, 'success');
                }
            } else {
                if (typeof showSnackbar === 'function') {
                    showSnackbar(result.message || getContentLang().error_upload, 'error');
                }
            }
        } catch (err) {
            console.error('Edit image upload error:', err);
            if (typeof showSnackbar === 'function') {
                showSnackbar(getContentLang().error_upload, 'error');
            }
        }
    }

    async handleEditImageRemove(shell, contentId, removeUrl) {
        if (!shell) shell = document.getElementById('editImageShell');
        if (!shell) return;

        const hasExisting = shell.dataset.existingImage === 'true';
        const input = document.getElementById('editImgInput');

        if (hasExisting && contentId) {
            const confirmResult = await Swal.fire({
                title: shell.dataset.removeTitle || getContentLang().remove_image || 'Remove Image',
                text: shell.dataset.removeConfirm || 'Are you sure you want to remove this image?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ba1a1a',
                cancelButtonColor: '#6e7881',
                confirmButtonText: shell.dataset.removeConfirmText || getContentLang().yes || 'Yes',
                cancelButtonText: shell.dataset.removeCancelText || getContentLang().no || 'No'
            });

            if (!confirmResult.isConfirmed) {
                return;
            }

            try {
                const formData = new URLSearchParams();
                formData.append('id', contentId);

                const response = await fetch(removeUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData
                });

                const data = (await response.text()).trim();
                if (data !== 'Success') {
                    if (typeof showSnackbar === 'function') {
                        showSnackbar(data || getContentLang().error_general, 'error');
                    } else {
                        Swal.fire(shell.dataset.errorTitle || 'Error', data, 'error');
                    }
                    return;
                }

                shell.dataset.existingImage = 'false';
                shell.dataset.existingSrc = '';
                shell.dataset.existingName = '';
                shell.dataset.existingMeta = '';
                shell.dataset.pendingImage = 'false';
                shell.classList.remove('has-image');

                if (input) input.value = '';

                if (typeof resetEditImageShell === 'function') {
                    resetEditImageShell();
                }

                const previewCard = document.getElementById('editImagePreviewCard');
                if (previewCard) previewCard.removeAttribute('data-visible');
                const emptyState = document.getElementById('editImageEmptyState');
                if (emptyState) {
                    emptyState.removeAttribute('hidden');
                    emptyState.hidden = false;
                }
                const previewImg = document.getElementById('editImgPreview');
                if (previewImg) {
                    previewImg.setAttribute('hidden', 'true');
                    previewImg.hidden = true;
                    previewImg.src = '';
                }
                const divider = shell.querySelector('.wizard-image-divider');
                if (divider) divider.removeAttribute('hidden');
                const urlRow = shell.querySelector('.wizard-image-url-row');
                if (urlRow) urlRow.removeAttribute('hidden');

                if (typeof showSnackbar === 'function') {
                    showSnackbar(getContentLang().image_remove_success || getContentLang().image_updated, 'success');
                } else {
                    Swal.fire(shell.dataset.successTitle || '', shell.dataset.successMessage || '', 'success');
                }
            } catch (err) {
                console.error('Remove error:', err);
                if (typeof showSnackbar === 'function') {
                    showSnackbar(getContentLang().error_general, 'error');
                }
            }
            return;
        }

        if (input) input.value = '';
        if (typeof resetEditImageShell === 'function') {
            resetEditImageShell();
        }
    }

    initTomSelects() {
        if (typeof TomSelect === 'undefined') return;

        const initSelect = (selector, customConfig = {}) => {
            const el = document.querySelector(selector);
            if (!el) return null;
            return new TomSelect(selector, Object.assign({
                create: false,
                maxOptions: 200,
                dropdownClass: 'm3-select-dropdown',
                render: {
                    item: (data, escape) => `<div class="m3-ts-selected-value" title="${escape(data.text || '')}">${escape(data.text || '')}</div>`,
                    option: (data, escape) => `<div class="m3-ts-option-value">${escape(data.text || '')}</div>`
                }
            }, customConfig));
        };

        initSelect('#editGallerySelect', {
            controlInput: null,
            wrapperClass: 'ts-wrapper m3-input-lg-ts'
        });

        initSelect('#editFileSelect', {
            wrapperClass: 'ts-wrapper m3-input-lg-ts'
        });

        initSelect('#editAclSelect', {
            controlInput: null,
            wrapperClass: 'ts-wrapper m3-input-lg-ts'
        });
    }

    initCharCounters() {
        const elements = document.querySelectorAll('[data-counter-target]');
        elements.forEach(input => {
            const targetId = input.dataset.counterTarget;
            const max = parseInt(input.dataset.counterMax, 10) || 120;
            const min = parseInt(input.dataset.counterMin, 10) || 90;
            const targetEl = document.getElementById(targetId);

            const update = () => {
                if (targetEl) {
                    const len = input.value.length;
                    targetEl.textContent = `${len} / ${max}`;
                    if (min > 0 && len > 0 && len < min) {
                        targetEl.classList.add('is-warning');
                        targetEl.classList.remove('is-valid', 'is-error');
                    } else if (len >= min && len <= max) {
                        targetEl.classList.remove('is-warning', 'is-error');
                        targetEl.classList.add('is-valid');
                    } else if (len > max) {
                        targetEl.classList.add('is-error');
                        targetEl.classList.remove('is-warning', 'is-valid');
                    } else {
                        targetEl.classList.remove('is-warning', 'is-valid', 'is-error');
                    }
                }
            };

            input.addEventListener('input', update);
            update();
        });
    }

    initSelectionCards() {
        // Deck Selection
        const deckHidden = document.getElementById('deckHidden');
        const deckCards = document.querySelectorAll('[data-action="select-deck"]');
        deckCards.forEach(card => {
            card.addEventListener('click', () => {
                const val = card.dataset.value;
                if (deckHidden) deckHidden.value = val;
                deckCards.forEach(c => c.classList.remove('active'));
                card.classList.add('active');
            });
        });

        // Publish Status Selection
        const publishHidden = document.getElementById('publishHidden');
        const statusCards = document.querySelectorAll('[data-action="select-status"]');
        statusCards.forEach(card => {
            card.addEventListener('click', () => {
                const val = card.dataset.value;
                if (publishHidden) publishHidden.value = val;
                statusCards.forEach(c => c.classList.remove('active'));
                card.classList.add('active');
            });
        });
    }

    initAdvancedAccordion() {
        const toggleBtn = document.querySelector('[data-action="toggle-advanced"]');
        const content = document.getElementById('advancedContent');
        if (toggleBtn && content) {
            toggleBtn.addEventListener('click', () => {
                toggleBtn.classList.toggle('active');
                content.classList.toggle('active');
            });
        }
    }

    initTitleSync() {
        const titleInput = document.getElementById('editTitleInput');
        const seoInput = document.getElementById('seo');
        const titleMeta = document.getElementById('title');
        const keywordsInput = document.getElementById('keywords');
        const descriptionInput = document.getElementById('description');
        const langInput = document.getElementById('editLang');

        if (seoInput) {
            seoInput.addEventListener('input', () => {
                seoInput.dataset.touched = 'true';
            });
        }

        if (descriptionInput) {
            descriptionInput.addEventListener('input', () => {
                descriptionInput.dataset.touched = 'true';
            });
        }

        if (titleInput) {
            titleInput.addEventListener('input', () => {
                let name = titleInput.value;
                const locale = (langInput && langInput.value) ? String(langInput.value).toLowerCase().trim() : (typeof getCurrentLocale === 'function' ? getCurrentLocale() : 'en');

                if (typeof normalizeKurdishTypos === 'function') {
                    name = normalizeKurdishTypos(name, locale);
                }
                titleInput.value = name;

                if (seoInput && (!seoInput.value || seoInput.dataset.touched !== 'true')) {
                    seoInput.value = typeof makeSeoValue === 'function' ? makeSeoValue(name, locale) : name;
                }
                if (titleMeta) {
                    titleMeta.value = typeof sanitizeText === 'function' ? sanitizeText(name) : name;
                }
                if (keywordsInput) {
                    keywordsInput.value = typeof makeKeywordsValue === 'function' ? makeKeywordsValue(name, locale) : name;
                }
            });
        }

        const introInput = document.getElementById('editIntroInput');
        if (introInput) {
            introInput.addEventListener('input', () => {
                let introText = introInput.value;
                const locale = (langInput && langInput.value) ? String(langInput.value).toLowerCase().trim() : (typeof getCurrentLocale === 'function' ? getCurrentLocale() : 'en');
                if (typeof normalizeKurdishTypos === 'function') {
                    introText = normalizeKurdishTypos(introText, locale);
                }
                if (descriptionInput && (!descriptionInput.value || descriptionInput.dataset.touched !== 'true')) {
                    const cleanDesc = typeof sanitizeText === 'function' ? sanitizeText(introText) : introText;
                    descriptionInput.value = cleanDesc.substring(0, 120);
                }
            });
        }
    }

    initActionHandlers() {
        // Language Sheet Opener
        const langTrigger = document.querySelector('[data-action="open-lang-sheet"]');
        if (langTrigger) {
            langTrigger.addEventListener('click', () => this.openLangSheet());
        }

        // Category Sheet Opener
        const catTrigger = document.querySelector('[data-action="toggle-category-sheet"]');
        if (catTrigger) {
            catTrigger.addEventListener('click', () => {
                if (typeof toggleWizardCategorySheet === 'function') {
                    toggleWizardCategorySheet();
                }
            });
        }

        // Global Category Selection Callback for Sheet
        window.selectWizardCategory = (name, id) => {
            const catHidden = document.getElementById('editCategoryId');
            const catLabel = document.getElementById('wizardSelectedCategory');
            if (catHidden) catHidden.value = id;
            if (catLabel) {
                catLabel.textContent = name;
                catLabel.style.color = 'var(--md-sys-color-on-surface)';
            }
            if (typeof toggleWizardCategorySheet === 'function') {
                toggleWizardCategorySheet();
            }
        };

        // Form Submission
        const submitBtn = document.querySelector('[data-action="submit-edit-form"]');
        if (submitBtn) {
            submitBtn.addEventListener('click', () => this.submitForm());
        }

        // Cancel Button
        const cancelBtn = document.querySelector('[data-action="cancel-edit"]');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                const targetUrl = cancelBtn.dataset.href;
                if (targetUrl) {
                    window.location.href = targetUrl;
                }
            });
        }
    }

    openLangSheet() {
        if (typeof openLangSheet !== 'function') return;

        const editLangInput = document.getElementById('editLang');
        window.tempSelectedLang = editLangInput ? editLangInput.value : '';

        if (typeof renderLangList === 'function') {
            renderLangList();
        }

        window.toggleLangSelection = (code) => {
            window.tempSelectedLang = code;
            window.confirmLangSelection();
        };

        window.confirmLangSelection = () => {
            const lang = window.tempSelectedLang;
            if (editLangInput) editLangInput.value = lang;

            const langLabel = document.getElementById('editSelectedLang');
            if (langLabel && typeof adminLang !== 'undefined' && Array.isArray(adminLang.languages)) {
                const langObj = adminLang.languages.find(l => l.code && (l.code.toUpperCase() === lang.toUpperCase() || l.code.toLowerCase() === lang.toLowerCase()));
                langLabel.textContent = langObj ? (langObj.name || langObj.ad) : lang.toUpperCase();
            }

            if (typeof closeLangSheet === 'function') {
                closeLangSheet();
            }

            if (window.wizardData) {
                wizardData.lang = lang;
            }
        };

        openLangSheet();
    }

    submitForm() {
        if (typeof tinymce !== 'undefined') {
            tinymce.triggerSave();
        }

        const introInput = document.getElementById('editIntroInput');
        if (introInput) {
            const introVal = (introInput.value || '').trim();
            if (introVal.length < 90 || introVal.length > 120) {
                introInput.focus();
                introInput.reportValidity();
                return;
            }
        }

        if (!this.form.checkValidity()) {
            this.form.reportValidity();
            return;
        }

        const submitBtn = document.querySelector('[data-action="submit-edit-form"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.classList.add('is-loading');
        }

        this.form.submit();
    }

    initAlerts() {
        if (this.config.errors && this.config.errors.length > 0 && typeof Swal !== 'undefined') {
            const wrapper = document.createElement('div');
            wrapper.style.textAlign = 'left';

            const titleRow = document.createElement('div');
            titleRow.style.marginBottom = '8px';
            titleRow.innerHTML = '<i class="bi bi-exclamation-triangle-fill" style="color: #ba1a1a;"></i> <b></b>';
            titleRow.querySelector('b').textContent = this.config.errorMessage || 'Error';
            wrapper.appendChild(titleRow);

            const list = document.createElement('ul');
            list.style.margin = '0';
            list.style.paddingLeft = '20px';
            list.style.fontSize = '14px';
            list.style.color = '#ba1a1a';

            this.config.errors.forEach(item => {
                const listItem = document.createElement('li');
                listItem.style.marginBottom = '4px';
                listItem.textContent = item;
                list.appendChild(listItem);
            });
            wrapper.appendChild(list);

            Swal.fire({
                icon: 'warning',
                html: wrapper.outerHTML,
                confirmButtonText: this.config.okText || 'OK',
                confirmButtonColor: '#ba1a1a'
            });
        }

        if (this.config.successUrl) {
            setTimeout(() => {
                window.location.href = this.config.successUrl;
            }, this.config.redirectDelay || 2000);
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('editForm')) {
        window.contentEditManager = new ContentEditManager(window.MOD_CONTENT_EDIT_CONFIG || {});
    }
});

