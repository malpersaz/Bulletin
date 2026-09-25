/**
 * mod_news.js
 * Material 3 Phone-First Controller for mod_news (List & Edit views)
 * Enforces zero inline JavaScript and clean data-* contracts.
 */

function getNewsModuleUrl() {
    if (typeof window.MODULE_URL !== 'undefined' && window.MODULE_URL) return window.MODULE_URL;
    const container = document.getElementById('content-list-container');
    if (container && container.dataset.moduleUrl) return container.dataset.moduleUrl;
    const siteUrl = (typeof window.SITE_URL !== 'undefined' && window.SITE_URL) ? window.SITE_URL : '/';
    return siteUrl + 'mod_news/';
}

function getNewsDataUrl() {
    if (typeof window.DATA_URL !== 'undefined' && window.DATA_URL) return window.DATA_URL;
    const container = document.getElementById('content-list-container');
    if (container && container.dataset.dataUrl) return container.dataset.dataUrl;
    return getNewsModuleUrl() + 'datatable_json';
}

function getNewsCategoryMap() {
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

function getNewsLang() {
    if (typeof window.CONTENT_LANG !== 'undefined' && window.CONTENT_LANG) return window.CONTENT_LANG;
    const al = (typeof window.adminLang !== 'undefined' && window.adminLang) ? window.adminLang : {};
    const mod = al.mod_news || al.mod_content || {};
    const ctx = al.context_menu || {};
    const dt = al.datatable || {};

    return {
        active: mod.status_badge?.active || 'Active',
        draft: mod.status_badge?.draft || 'Draft',
        general: mod.status_badge?.general || 'General',
        sheet_all: mod.sheet?.all || 'All',
        confirm_delete: mod.delete_confirm || ctx.delete_confirm || 'Are you sure you want to delete this news item?',
        confirm_publish: mod.quick_publish_confirm || 'Do you want to publish this news item?',
        confirm_unpublish: mod.quick_unpublish_confirm || 'Do you want to unpublish this news item?',
        error_general: mod.error_occurred || mod.error || 'An error occurred',
        error_upload: mod.message?.error?.add || 'Upload failed',
        cancel: mod.button?.cancel || ctx.cancel || 'Cancel',
        delete_confirm_text: mod.button?.yes || ctx.confirm || 'Yes',
        delete_cancel_text: mod.button?.no || ctx.cancel || 'Cancel',
        status_confirm_text: mod.button?.yes || 'Yes',
        status_cancel_text: mod.button?.no || 'No',
        image_remove_title: mod.remove_image || 'Remove Image',
        image_remove_confirm: mod.remove_image || 'Are you sure you want to remove this image?',
        image_remove_confirm_text: mod.button?.yes || 'Yes, Delete',
        image_remove_cancel_text: mod.button?.no || 'Cancel',
        image_remove_success: mod.remove_image_success || 'Image removed successfully',
        image_updated: mod.success || 'Image updated successfully',
        image_title: mod.wizard?.step?.image || 'Cover Image',
        save_image: mod.wizard?.button?.save || mod.button?.save || 'Save Image',
        hero_slider_add: mod.hero_slider_add || 'Add to Slider',
        hero_slider_remove: mod.hero_slider_remove || 'Remove from Slider',
        hero_slider_added: mod.hero_slider_added || 'Added to Hero Slider',
        hero_slider_removed: mod.hero_slider_removed || 'Removed from Hero Slider',
        hero_slider_error: mod.hero_slider_error || 'Failed to update Hero Slider',
        hero_slider_badge: mod.hero_slider_badge || 'Hero Slider',
        picker: {
            file: mod.picker?.file || ctx.files || 'Select Files',
            video: mod.picker?.video || ctx.video || 'Select Video'
        },
        loading: dt.loadingRecords || mod.grid?.loading || 'Loading...',
        grid: {
            load_more: mod.grid?.load_more || 'Load More'
        }
    };
}

class NewsManager {
    constructor() {
        this.container = document.getElementById('content-list-container');
        this.loader = document.getElementById('infinite-loader');
        this.emptyState = document.getElementById('empty-state');
        this.imageModal = document.getElementById('newsImageModal');
        this.imageModalTitle = document.getElementById('newsImageModalTitle');
        this.imageModalSubtitle = document.getElementById('newsImageModalSubtitle');
        this.imageMeta = document.getElementById('listImageMeta');
        this.imageFooter = document.getElementById('newsImageModalFooter');
        this.imageCancelBtn = document.getElementById('newsImageCancelBtn');
        this.imageSaveBtn = document.getElementById('newsImageSaveBtn');

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
        this.loadMoreBtn.className = 'm3-btn-outlined';
        this.loadMoreBtn.style.cssText = 'display:none; margin: 20px auto; width: fit-content; cursor:pointer;';

        let btnText = 'Load More';
        if (getNewsLang().grid && getNewsLang().grid.load_more) {
            btnText = getNewsLang().grid.load_more;
        }
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
            this.container.innerHTML = '';
            this.hasMore = true;
            this.draw = 1;
            this.selectedContentId = null;
            this.selectedInHeroSlider = false;
            this.updateToolbar(false);
            this.updateFabState(false);
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

        const formData = new FormData(document.getElementById('filterForm'));
        formData.set('start', this.start);
        formData.set('length', this.length);
        formData.set('draw', this.draw);

        try {
            const response = await fetch(getNewsDataUrl(), {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            this.renderCards(data.data);

            if (typeof data.totalNews !== 'undefined') {
                const totalEl = document.getElementById('statTotalNews');
                if (totalEl) totalEl.textContent = data.totalNews;
            }
            if (typeof data.draftNews !== 'undefined') {
                const draftEl = document.getElementById('statDraftNews');
                if (draftEl) draftEl.textContent = data.draftNews;
            }

            this.start += this.length;
            this.draw++;

            if (this.start >= data.recordsFiltered) {
                this.hasMore = false;
                if (this.loader) this.loader.style.display = 'none';
            }

            if (data.recordsFiltered === 0 && this.emptyState) {
                this.emptyState.style.display = 'flex';
            }

        } catch (error) {
            console.error('Data Load Error:', error);
        } finally {
            this.isLoading = false;
            if (this.loader) this.loader.style.display = 'none';

            if (this.loadMoreBtn) {
                this.loadMoreBtn.style.display = (this.hasMore) ? 'block' : 'none';
                this.loadMoreBtn.disabled = false;
                let btnText = 'Load More';
                if (getNewsLang().grid && getNewsLang().grid.load_more) {
                    btnText = getNewsLang().grid.load_more;
                }
                this.loadMoreBtn.innerHTML = `<i class="bi bi-arrow-down-circle" style="margin-right:8px;"></i>${btnText}`;
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
            const date = this.stripHtml(item[5]);
            const categoryId = item[7] || 0;
            const statusRaw = item[8] || 'n';
            const authorName = this.stripHtml(item[9] || '');
            const inHeroSlider = String(item[10] || '0') === '1';

            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = titleHtml;
            const titleText = tempDiv.textContent || tempDiv.innerText;

            tempDiv.innerHTML = imgHtml;
            const imgTag = tempDiv.querySelector('img');
            const imgSrc = imgTag ? imgTag.src : '';

            const card = document.createElement('div');
            card.className = 'm3-card content-card';
            card.dataset.id = id;
            card.dataset.imageSrc = imgSrc || '';
            card.dataset.imageName = imgSrc ? (imgSrc.split('/').pop() || titleText || '') : '';
            card.dataset.inHeroSlider = inHeroSlider ? '1' : '0';
            card.onclick = () => window.newsManager.selectContent(id, card);

            let statusText = getNewsLang().draft;
            let statusColor = "#FFAB00";
            if (statusRaw === 'y') {
                statusText = getNewsLang().active;
                statusColor = "var(--md-sys-color-primary)";
            }

            let categoryName = getNewsLang().general;
            const catMap = getNewsCategoryMap();
            if (Object.keys(catMap).length > 0 && catMap[categoryId]) {
                categoryName = catMap[categoryId];
            }

            const safeStatusText = this.escapeHtml(statusText);
            const safeCategoryName = this.escapeHtml(categoryName);
            const safeTitleText = this.escapeHtml(titleText);
            const safeLang = this.escapeHtml(lang);
            const safeDate = this.escapeHtml(date);
            const safeAuthorName = this.escapeHtml(authorName);
            const safeSliderBadge = this.escapeHtml(getNewsLang().hero_slider_badge);

            card.innerHTML = `
                <div class="card-content-layout">
                    <div class="card-thumbnail" style="cursor:pointer; display:flex; align-items:center; justify-content:center; color:#999; ${imgSrc ? '' : 'background-color: #f0f0f0;'}">
                        ${!imgSrc ? '<i class="bi bi-camera-fill" style="font-size: 24px;"></i>' : ''}
                    </div>
                    
                    <div class="card-body-col">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                             <div>
                                <div class="meta-row" style="margin-top:0; margin-bottom:4px;">
                                    <span class="hero-slider-badge" style="${inHeroSlider ? 'display:inline-flex;' : 'display:none;'} align-items:center; gap:4px; padding:2px 8px; border-radius:999px; background:rgba(255,193,7,0.18); color:#8a5a00; font-weight:700;">
                                        <i class="bi bi-stars"></i> ${safeSliderBadge}
                                    </span>
                                    <span class="hero-slider-dot" style="${inHeroSlider ? 'display:inline-flex;' : 'display:none;'}">•</span>
                                    <span style="color:${statusColor}; font-weight:500;">${safeStatusText}</span>
                                    <span>•</span>
                                    <span>${safeCategoryName}</span>
                                </div>
                                <h3 style="margin:0; font-size:16px; font-weight:500; line-height:1.4; color: var(--md-sys-color-on-surface); text-align: left;">${safeTitleText}</h3>
                             </div>
                             <button type="button" class="m3-icon-btn" style="margin-top:-8px; margin-right:-8px;">
                                <i class="bi bi-three-dots-vertical"></i>
                             </button>
                        </div>

                        <div class="meta-row" style="margin-top:8px;">
                             <span style="display:flex; align-items:center; gap:4px;"><i class="bi bi-translate" style="font-size:11px;"></i> ${safeLang}</span>
                             <span style="margin-left: 12px; display:flex; align-items:center; gap:4px;"><i class="bi bi-calendar3" style="font-size:11px;"></i> ${safeDate}</span>
                             <span style="margin-left: auto; font-size: 12px; color: var(--md-sys-color-on-surface-variant);">${safeAuthorName}</span>
                        </div>
                    </div>
                </div>
            `;

            const thumbnail = card.querySelector('.card-thumbnail');
            if (thumbnail) {
                if (imgSrc) {
                    thumbnail.style.backgroundImage = `url("${imgSrc}")`;
                    thumbnail.style.backgroundSize = 'cover';
                    thumbnail.style.backgroundPosition = 'center';
                }

                thumbnail.addEventListener('click', (event) => {
                    event.stopPropagation();
                    this.processImageUpload(id);
                });
            }

            const actionButton = card.querySelector('.m3-icon-btn');
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
            hintRemoteFailed: data.hintRemoteFailed || getNewsLang().image_remote_failed,
            emptyName: data.emptyName || getNewsLang().image_empty,
            emptyMeta: data.emptyMeta || getNewsLang().image_drop_description,
            imageLabel: getNewsLang().image_title
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
        this.imageModal = document.getElementById('newsImageModal');
        this.imageModalTitle = document.getElementById('newsImageModalTitle');
        this.imageModalSubtitle = document.getElementById('newsImageModalSubtitle');
        this.imageSaveBtn = document.getElementById('newsImageSaveBtn');
        this.imageCancelBtn = document.getElementById('newsImageCancelBtn');
        this.imageFooter = document.getElementById('newsImageModalFooter');

        if (this.imageSaveBtn) {
            this.imageSaveBtn.onclick = () => this.submitListImageUpload();
        }

        const card = document.querySelector(`.content-card[data-id="${id}"]`);
        
        if (!title && card) {
            title = card.querySelector('h3')?.textContent?.trim() || '';
        }

        const existingImageSrc = card?.dataset.imageSrc || '';
        const existingImageName = card?.dataset.imageName || title || getNewsLang().image_title;
        this.imageModalState = {
            hasExistingImage: Boolean(existingImageSrc),
            existingImageSrc,
            existingImageName
        };

        if (this.imageModalTitle) {
            this.imageModalTitle.textContent = getNewsLang().image_title;
        }

        const subtitleEl = this.imageModalSubtitle || document.getElementById('newsImageModalSubtitle');
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
                name: this.imageModalState.existingImageName || getNewsLang().image_title,
                metaText: ''
            });
        }
    }

    resetImageModalActions() {
        if (this.imageSaveBtn) {
            this.imageSaveBtn.disabled = true;
            this.imageSaveBtn.hidden = true;
            this.imageSaveBtn.innerHTML = getNewsLang().save_image;
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
            title: getNewsLang().image_remove_title,
            text: getNewsLang().image_remove_confirm,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#B71C1C',
            cancelButtonColor: '#6750A4',
            confirmButtonText: getNewsLang().image_remove_confirm_text,
            cancelButtonText: getNewsLang().image_remove_cancel_text,
            background: 'var(--md-sys-color-surface-container-high, #fff)',
            color: 'var(--md-sys-color-on-surface, #1C1B1F)'
        });

        if (!result.isConfirmed) {
            return;
        }

        if (this.loader) this.loader.style.display = 'flex';

        try {
            const formData = new URLSearchParams();
            formData.append('id', this.imageUploadContentId);

            const response = await fetch(`${getNewsModuleUrl()}image_remove_image`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            });

            const text = (await response.text()).trim();
            if (text !== 'Success') {
                showSnackbar(text || getNewsLang().error_general, 'error');
                return;
            }

            this.closeImageUploader();
            await this.loadData(true);
            showSnackbar(getNewsLang().image_remove_success || getNewsLang().image_updated, 'success', 2000);
        } catch (error) {
            console.error('Remove image error:', error);
            showSnackbar(getNewsLang().error_general, 'error');
        } finally {
            if (this.loader) this.loader.style.display = 'none';
        }
    }

    async submitListImageUpload() {
        const fileData = this.imageUploader?.getSelectedFile();
        if (!this.imageUploadContentId || !fileData) {
            this.imageUploader?.showFeedback(getNewsLang().error_upload, 'error');
            return;
        }

        const formData = new FormData();
        formData.append('image', fileData);
        formData.append('id', this.imageUploadContentId);

        if (this.imageSaveBtn) {
            this.imageSaveBtn.hidden = false;
            this.imageSaveBtn.disabled = true;
            this.imageSaveBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ${getNewsLang().loading}`;
        }

        if (this.loader) this.loader.style.display = 'flex';

        try {
            const response = await fetch(`${getNewsModuleUrl()}ajax_image_upload`, {
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
                showSnackbar(result.message || getNewsLang().image_updated, 'success', 2000);
            } else {
                this.imageUploader?.showFeedback(result.message || getNewsLang().error_upload, 'error');
                showSnackbar(result.message || getNewsLang().error_upload, 'error');
            }
        } catch (err) {
            console.error('Upload error:', err);
            this.imageUploader?.showFeedback(getNewsLang().error_upload, 'error');
            showSnackbar(getNewsLang().error_upload, 'error');
        } finally {
            if (this.loader) this.loader.style.display = 'none';
            if (this.imageSaveBtn) {
                const hasPendingFile = !!this.imageUploader?.getSelectedFile();
                this.imageSaveBtn.hidden = !hasPendingFile;
                this.imageSaveBtn.disabled = !hasPendingFile;
                this.imageSaveBtn.innerHTML = getNewsLang().save_image;
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
        this.syncSliderToolbarState(active);
    }

    syncSliderToolbarState(active) {
        const sliderBtn = document.getElementById('toolbarSlider');
        if (!sliderBtn) return;

        const label = active && this.selectedInHeroSlider
            ? getNewsLang().hero_slider_remove
            : getNewsLang().hero_slider_add;

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
            badge.style.display = inHeroSlider ? 'inline-flex' : 'none';
        }
        if (dot) {
            dot.style.display = inHeroSlider ? 'inline-flex' : 'none';
        }
    }

    async toggleHeroSlider() {
        if (!this.selectedContentId) return;

        const formData = new URLSearchParams();
        formData.append('id', this.selectedContentId);

        try {
            const response = await fetch(`${getNewsModuleUrl()}toggle_hero_slider`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            });
            const result = await response.json();

            if (result.status !== 'success') {
                showSnackbar(result.message || getNewsLang().hero_slider_error, 'error');
                return;
            }

            this.selectedInHeroSlider = Boolean(result.in_hero_slider);
            this.applyHeroSliderState(this.getSelectedCard(), this.selectedInHeroSlider);
            this.syncSliderToolbarState(true);
            showSnackbar(
                result.message || (this.selectedInHeroSlider ? getNewsLang().hero_slider_added : getNewsLang().hero_slider_removed),
                'success'
            );
        } catch (error) {
            console.error('Hero slider toggle error:', error);
            showSnackbar(getNewsLang().hero_slider_error, 'error');
        }
    }
}

// Global actions for list view
window.clearSelection = function () {
    (window.newsManager || window.contentManager)?.clearSelection();
};

window.editSelected = function () {
    const mgr = window.newsManager || window.contentManager;
    if (mgr && mgr.selectedContentId) {
        window.location.href = getNewsModuleUrl() + 'edit/' + mgr.selectedContentId;
    }
};

window.videoSelected = function () {
    const mgr = window.newsManager || window.contentManager;
    if (mgr && mgr.selectedContentId) {
        openMediaRelationPicker(mgr.selectedContentId, 'video');
    }
};

window.gallerySelected = function () {
    const mgr = window.newsManager || window.contentManager;
    if (mgr && mgr.selectedContentId) {
        const url = SITE_URL + 'mod_gallery/list/mod_news/' + mgr.selectedContentId;
        if (window.AFModalEngine) {
            AFModalEngine.push(url, { skipHistory: true });
        } else {
            window.location.href = url;
        }
    }
};

window.faqSelected = function () {
    const mgr = window.newsManager || window.contentManager;
    if (mgr && mgr.selectedContentId) {
        const url = SITE_URL + 'mod_faq/list/mod_news/' + mgr.selectedContentId;
        if (window.AFModalEngine) {
            AFModalEngine.push(url, { skipHistory: true });
        } else {
            window.location.href = url;
        }
    }
};

window.filesSelected = function () {
    const mgr = window.newsManager || window.contentManager;
    if (mgr && mgr.selectedContentId) {
        openMediaRelationPicker(mgr.selectedContentId, 'files');
    }
};

window.sliderSelected = function () {
    const mgr = window.newsManager || window.contentManager;
    if (mgr && mgr.selectedContentId) {
        mgr.toggleHeroSlider();
    }
};

window.deleteSelected = function () {
    const mgr = window.newsManager || window.contentManager;
    if (!mgr || !mgr.selectedContentId) return;
    const id = mgr.selectedContentId;

    Swal.fire({
        title: getNewsLang().confirm_delete,
        icon: 'warning',
        showCancelButton: true,
        showDenyButton: false,
        confirmButtonColor: '#B71C1C',
        cancelButtonColor: '#6750A4',
        confirmButtonText: '<i class="bi bi-trash3"></i> ' + (adminLang.context_menu?.confirm || 'Delete'),
        cancelButtonText: '<i class="bi bi-x-lg"></i> ' + (adminLang.context_menu?.cancel || 'Cancel'),
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

            fetch(getNewsModuleUrl() + 'delete', {
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
                        showSnackbar(adminLang.context_menu?.delete_success || 'Deleted successfully', 'success');
                    } else {
                        showSnackbar(trimmedMsg || getNewsLang().error_general, 'error');
                    }
                })
                .catch(err => {
                    console.error('Delete error:', err);
                    showSnackbar(getNewsLang().error_general, 'error');
                });
        }
    });
};

window.handleContextAction = function (action) {
    const id = window.currentContentId;
    if (!id) return;

    switch (action) {
        case 'edit':
            window.location.href = `${getNewsModuleUrl()}edit/${id}`;
            break;
        case 'faq':
            closeContextMenu();
            const faqUrl = SITE_URL + 'mod_faq/list/mod_news/' + id;
            if (window.AFModalEngine) {
                AFModalEngine.push(faqUrl, { skipHistory: true });
            } else {
                window.location.href = faqUrl;
            }
            return;
        case 'delete':
            Swal.fire({
                title: getNewsLang().confirm_delete,
                icon: 'warning',
                showCancelButton: true,
                showDenyButton: false,
                confirmButtonColor: '#B71C1C',
                cancelButtonColor: '#6750A4',
                confirmButtonText: '<i class="bi bi-trash3"></i> ' + (adminLang.context_menu?.confirm || 'Delete'),
                cancelButtonText: '<i class="bi bi-x-lg"></i> ' + (adminLang.context_menu?.cancel || 'Cancel'),
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

                    fetch(`${getNewsModuleUrl()}delete`, {
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
                                (window.newsManager || window.contentManager)?.loadData(true);
                                closeContextMenu();
                                showSnackbar(adminLang.context_menu?.delete_success || 'Deleted successfully', 'success');
                            } else {
                                showSnackbar(trimmedMsg || getNewsLang().error_general, 'error');
                            }
                        })
                        .catch(err => {
                            console.error('Delete error:', err);
                            showSnackbar(getNewsLang().error_general, 'error');
                        });
                }
            });
            break;
        case 'gallery':
            closeContextMenu();
            const galleryUrl = `${SITE_URL}mod_gallery/list/mod_news/${id}`;
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
        case 'status':
            const currentStatus = activeContextItem?.status ? 'y' : 'n';
            window.toggleStatus(id, currentStatus);
            break;
    }
    closeContextMenu();
};

window.openMediaRelationPicker = function(contentId, type) {
    if (type !== 'files' && type !== 'video') {
        return;
    }

    let endpoint = `${getNewsModuleUrl()}ajax_get_files`;
    
    if (type === 'video') {
        endpoint = `${getNewsModuleUrl()}all_video`;
        window.currentMediaPickerAddUrl = `${SITE_URL}mod_video_embed/add`;
    } else {
        window.currentMediaPickerAddUrl = `${SITE_URL}mod_attachment/list/mod_news/${contentId}`;
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
        fetch(`${getNewsModuleUrl()}ajax_get_current_media`, { method: 'POST', body: currentData }).then((response) => response.json())
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

                    fetch(`${getNewsModuleUrl()}ajax_update_relation`, {
                        method: 'POST',
                        body: fd
                    })
                        .then((response) => response.json())
                        .then((result) => {
                            if (result.status === 'success') {
                                showSnackbar(result.message, 'success');
                                const mgr = window.newsManager || window.contentManager;
                                if (mgr) {
                                    mgr.loadData(true);
                                }
                                if (typeof closeSelectPickerSheet === 'function') {
                                    closeSelectPickerSheet();
                                }
                            } else {
                                showSnackbar(result.message || getNewsLang().error_general, 'error');
                            }
                        })
                        .catch((error) => {
                            console.error('Update Error:', error);
                            showSnackbar(getNewsLang().error_general, 'error');
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
                pickerTitleEl.textContent = type === 'video' ? getNewsLang().picker.video : getNewsLang().picker.file;
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
                select.dataset.pickerTitle = getNewsLang().picker.video;
            } else {
                select.value = currentMedia.file_id ? currentMedia.file_id.toString() : '0';
                select.dataset.pickerTitle = getNewsLang().picker.file;
            }

            if (typeof openSelectPickerSheet === 'function') {
                openSelectPickerSheet('dynamicMediaSelect');
            }
        })
        .catch((error) => {
            console.error(error);
            showSnackbar(getNewsLang().error_general, 'error');
        });
};

window.toggleStatus = function (id, currentStatus) {
    if (typeof event !== 'undefined') event.stopPropagation();
    const endpoint = currentStatus === 'y' ? 'unapprove' : 'approve';
    const isPublishing = currentStatus !== 'y';

    const confirmMsg = isPublishing ? getNewsLang().confirm_publish : getNewsLang().confirm_unpublish;
    const successMsg = isPublishing ? (adminLang.context_menu?.published_msg || 'Published') : (adminLang.context_menu?.unpublished_msg || 'Unpublished');

    Swal.fire({
        title: confirmMsg,
        icon: isPublishing ? 'question' : 'info',
        iconColor: isPublishing ? '#2E7D32' : '#E65100',
        showCancelButton: true,
        showDenyButton: false,
        confirmButtonColor: isPublishing ? '#2E7D32' : '#E65100',
        cancelButtonColor: '#6750A4',
        confirmButtonText: isPublishing
            ? '<i class="bi bi-eye"></i> ' + (adminLang.context_menu?.publish || 'Publish')
            : '<i class="bi bi-eye-slash"></i> ' + (adminLang.context_menu?.unpublish || 'Unpublish'),
        cancelButtonText: '<i class="bi bi-x-lg"></i> ' + (adminLang.context_menu?.cancel || 'Cancel'),
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

            fetch(`${getNewsModuleUrl()}${endpoint}`, {
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
                        (window.newsManager || window.contentManager)?.loadData(true);
                        showSnackbar(successMsg, 'success');
                    } else {
                        showSnackbar(trimmedMsg || getNewsLang().error_general, 'error');
                    }
                })
                .catch(err => {
                    console.error('Status error:', err);
                    showSnackbar(getNewsLang().error_general, 'error');
                });
        }
    });
};

/**
 * NewsEditManager
 * Handles all interactions on the mod_news edit page without inline JavaScript.
 */
class NewsEditManager {
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
        if (typeof initializeEditImageInteractions === 'function') {
            initializeEditImageInteractions();
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
        const langTrigger = document.querySelector('[data-action="open-lang-sheet"]');
        if (langTrigger) {
            langTrigger.addEventListener('click', () => this.openLangSheet());
        }

        const catTrigger = document.querySelector('[data-action="toggle-category-sheet"]');
        if (catTrigger) {
            catTrigger.addEventListener('click', () => {
                if (typeof toggleWizardCategorySheet === 'function') {
                    toggleWizardCategorySheet();
                }
            });
        }

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

        const submitBtn = document.querySelector('[data-action="submit-edit-form"]');
        if (submitBtn) {
            submitBtn.addEventListener('click', () => this.submitForm());
        }

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
    if (document.getElementById('content-list-container')) {
        window.newsManager = new NewsManager();
        window.contentManager = window.newsManager;
    }
    if (document.getElementById('editForm')) {
        window.newsEditManager = new NewsEditManager(window.MOD_NEWS_EDIT_CONFIG || {});
        window.contentEditManager = window.newsEditManager;
    }
});
