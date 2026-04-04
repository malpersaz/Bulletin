/**
 * mod_news.js
 * Handles fetching data from existing Datatable JSON endpoint and rendering M3 Cards.
 */

class ContentManager {
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
        // console.log("DEBUG - ContentManager init, length:", this.length);
        this.createLoadMoreButton();
        this.loadData();
        // Manual Load More Button replaces Infinite Scroll for better control
        // this.initScrollListener(); 
        this.initSearchListener();
        this.initPullToRefresh();
        this.initListImageUploader();
        this.initializeImageModalCopy();
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
        if (typeof CONTENT_LANG !== 'undefined' && CONTENT_LANG.grid && CONTENT_LANG.grid.load_more) {
            btnText = CONTENT_LANG.grid.load_more;
        }
        this.loadMoreBtn.innerHTML = `<i class="bi bi-arrow-down-circle" style="margin-right:8px;"></i>${btnText}`;

        // Insert after container, before loader
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
            this.emptyState.style.display = 'none';
            this.loader.style.display = 'flex';
            if (this.loadMoreBtn) this.loadMoreBtn.style.display = 'none';
        } else {
            // Only show loader if not resetting and still has more data
            this.loader.style.display = 'flex';
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
            const response = await fetch(DATA_URL, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            this.renderCards(data.data);

            // Update pagination tracking
            this.start += this.length;
            this.draw++;

            // Check if we reached the end
            if (this.start >= data.recordsFiltered) {
                this.hasMore = false;
                this.loader.style.display = 'none';
            }

            if (data.recordsFiltered === 0) {
                this.emptyState.style.display = 'flex';
            }

        } catch (error) {
            console.error('Data Load Error:', error);
            // Show error toast
        } finally {
            this.isLoading = false;
            // Hide loader if not more data, usually kept if loading, but here we hide it after batch
            if (!this.hasMore) this.loader.style.display = 'none';
            else this.loader.style.display = 'none'; // Always hide loader after load, wait for button click

            // Toggle Load More Button
            if (this.loadMoreBtn) {
                this.loadMoreBtn.style.display = (this.hasMore) ? 'block' : 'none';
                this.loadMoreBtn.disabled = false;
                let btnText = 'Load More';
                if (typeof CONTENT_LANG !== 'undefined' && CONTENT_LANG.grid && CONTENT_LANG.grid.load_more) {
                    btnText = CONTENT_LANG.grid.load_more;
                }
                this.loadMoreBtn.innerHTML = `<i class="bi bi-arrow-down-circle" style="margin-right:8px;"></i>${btnText}`;
            }
        }
    }

    renderCards(items) {
        if (!items || items.length === 0) return;

        const fragment = document.createDocumentFragment();

        items.forEach((item, index) => {
            // Data Mapping (Indices confirmed via browser logs)
            // 0: Checkbox
            // 1: ID
            // 2: Name
            // 3: Image HTML
            // 4: Lang
            // 5: Date (String)
            // 6: Actions HTML
            // 7: Category ID
            // 8: Status

            const id = this.stripHtml(item[1]);
            const titleHtml = item[2];
            const imgHtml = item[3];
            const lang = this.stripHtml(item[4]);
            const date = this.stripHtml(item[5]); // Confirmed date string
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
            card.onclick = () => window.contentManager.selectContent(id, card);

            // Status Logic
            let statusText = (typeof CONTENT_LANG !== 'undefined') ? CONTENT_LANG.draft : "Draft";
            let statusColor = "#FFAB00"; // Orange
            if (statusRaw === 'y') {
                statusText = (typeof CONTENT_LANG !== 'undefined') ? CONTENT_LANG.active : "Active";
                statusColor = "var(--md-sys-color-primary)"; // Purple/Primary
            }

            // Category Logic
            let categoryName = (typeof CONTENT_LANG !== 'undefined') ? CONTENT_LANG.general : "General";
            if (typeof CATEGORY_MAP !== 'undefined' && CATEGORY_MAP[categoryId]) {
                categoryName = CATEGORY_MAP[categoryId];
            }

            const safeStatusText = this.escapeHtml(statusText);
            const safeCategoryName = this.escapeHtml(categoryName);
            const safeTitleText = this.escapeHtml(titleText);
            const safeLang = this.escapeHtml(lang);
            const safeDate = this.escapeHtml(date);
            const safeAuthorName = this.escapeHtml(authorName);
            const safeSliderBadge = this.escapeHtml(CONTENT_LANG.slider_badge || 'Slider');

            // HTML Structure (Updated to match Prototype)
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
            hintRemoteFailed: data.hintRemoteFailed || CONTENT_LANG.image_remote_failed || '',
            emptyName: data.emptyName || CONTENT_LANG.image_empty || '',
            emptyMeta: data.emptyMeta || CONTENT_LANG.image_drop_description || '',
            imageLabel: CONTENT_LANG.image_title || 'Image'
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
                    }
                    this.updateImageModalActions(source, file);
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
        const card = document.querySelector(`.content-card[data-id="${id}"]`);
        const existingImageSrc = card?.dataset.imageSrc || '';
        const existingImageName = card?.dataset.imageName || title || CONTENT_LANG.image_title;
        this.imageModalState = {
            hasExistingImage: Boolean(existingImageSrc),
            existingImageSrc,
            existingImageName
        };

        if (this.imageModalTitle) {
            this.imageModalTitle.textContent = CONTENT_LANG.image_title;
        }
        if (this.imageModalSubtitle) {
            this.imageModalSubtitle.textContent = '';
            this.imageModalSubtitle.hidden = true;
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
                name: this.imageModalState.existingImageName || CONTENT_LANG.image_title,
                metaText: ''
            });
        }
    }

    resetImageModalActions() {
        if (this.imageSaveBtn) {
            this.imageSaveBtn.disabled = true;
            this.imageSaveBtn.hidden = true;
            this.imageSaveBtn.innerHTML = CONTENT_LANG.save_image;
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
            title: CONTENT_LANG.image_remove_title,
            text: CONTENT_LANG.image_remove_confirm,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#B71C1C',
            cancelButtonColor: '#6750A4',
            confirmButtonText: CONTENT_LANG.image_remove_confirm_text,
            cancelButtonText: CONTENT_LANG.image_remove_cancel_text,
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

            const response = await fetch(`${MODULE_URL}image_remove_image`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            });

            const text = (await response.text()).trim();
            if (text !== 'Success') {
                showSnackbar(text || CONTENT_LANG.error_general, 'error');
                return;
            }

            this.closeImageUploader();
            await this.loadData(true);
            showSnackbar(CONTENT_LANG.image_remove_success || CONTENT_LANG.image_updated, 'success');
        } catch (error) {
            console.error('Remove image error:', error);
            showSnackbar(CONTENT_LANG.error_general, 'error');
        } finally {
            this.loader.style.display = 'none';
        }
    }

    async submitListImageUpload() {
        const fileData = this.imageUploader?.getSelectedFile();
        if (!this.imageUploadContentId || !fileData) {
            this.imageUploader?.showFeedback(CONTENT_LANG.error_upload, 'error');
            return;
        }

        const formData = new FormData();
        formData.append('image', fileData);
        formData.append('id', this.imageUploadContentId);

        if (this.imageSaveBtn) {
            this.imageSaveBtn.hidden = false;
            this.imageSaveBtn.disabled = true;
            this.imageSaveBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ${CONTENT_LANG.loading}`;
        }

        this.loader.style.display = 'flex';

        try {
            const response = await fetch(`${MODULE_URL}ajax_image_upload`, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.status === 'success') {
                this.closeImageUploader();
                await this.loadData(true);
                showSnackbar(result.message || CONTENT_LANG.image_updated, 'success');
            } else {
                this.imageUploader?.showFeedback(result.message || CONTENT_LANG.error_upload, 'error');
                showSnackbar(result.message || CONTENT_LANG.error_upload, 'error');
            }
        } catch (err) {
            console.error('Upload error:', err);
            this.imageUploader?.showFeedback(CONTENT_LANG.error_upload, 'error');
            showSnackbar(CONTENT_LANG.error_upload, 'error');
        } finally {
            this.loader.style.display = 'none';
            if (this.imageSaveBtn) {
                const hasPendingFile = !!this.imageUploader?.getSelectedFile();
                this.imageSaveBtn.hidden = !hasPendingFile;
                this.imageSaveBtn.disabled = !hasPendingFile;
                this.imageSaveBtn.innerHTML = CONTENT_LANG.save_image;
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

        // Remove all selections
        document.querySelectorAll('.m3-card.selected, .content-card.selected').forEach(el => {
            el.classList.remove('selected');
        });

        if (!isCurrent) {
            cardElement.classList.add('selected');
            this.selectedContentId = id;
            this.selectedInHeroSlider = cardElement.dataset.inHeroSlider === '1';
            this.updateToolbar(true);
            if (window.navigator.vibrate) window.navigator.vibrate(10);
        } else {
            this.selectedContentId = null;
            this.selectedInHeroSlider = false;
            this.updateToolbar(false);
        }
    }

    updateToolbar(active) {
        const ids = ['toolbarEdit', 'toolbarGallery', 'toolbarFiles', 'toolbarDelete', 'toolbarSlider', 'toolbarVideo'];
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
        this.syncSliderToolbarState(active);
    }

    syncSliderToolbarState(active) {
        const sliderBtn = document.getElementById('toolbarSlider');
        if (!sliderBtn) return;

        const label = active && this.selectedInHeroSlider
            ? (CONTENT_LANG.slider_remove || 'Remove from Slider')
            : (CONTENT_LANG.slider_add || 'Send to Slider');

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

        const currentId = this.selectedContentId;
        const formData = new URLSearchParams();
        formData.append('id', currentId);

        try {
            const response = await fetch(`${MODULE_URL}toggle_hero_slider`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            });
            const result = await response.json();

            if (result.status !== 'success') {
                showSnackbar(result.message || CONTENT_LANG.slider_error, 'error');
                return;
            }

            this.selectedInHeroSlider = Boolean(result.in_hero_slider);
            this.applyHeroSliderState(this.getSelectedCard(), this.selectedInHeroSlider);
            this.syncSliderToolbarState(true);
            showSnackbar(
                result.message || (this.selectedInHeroSlider ? CONTENT_LANG.slider_added : CONTENT_LANG.slider_removed),
                'success'
            );
        } catch (error) {
            console.error('Hero slider toggle error:', error);
            showSnackbar(CONTENT_LANG.slider_error, 'error');
        }
    }
}

// Initialize on load and expose globally
document.addEventListener('DOMContentLoaded', () => {
    window.contentManager = new ContentManager();
});

// --- Global toolbar action functions (called by onclick in HTML) ---
window.editSelected = function () {
    const mgr = window.contentManager;
    if (mgr && mgr.selectedContentId) {
        window.location.href = MODULE_URL + 'edit/' + mgr.selectedContentId;
    }
};

window.gallerySelected = function () {
    const mgr = window.contentManager;
    if (mgr && mgr.selectedContentId) {
        openMediaRelationPicker(mgr.selectedContentId, 'gallery');
    }
};

window.filesSelected = function () {
    const mgr = window.contentManager;
    if (mgr && mgr.selectedContentId) {
        openMediaRelationPicker(mgr.selectedContentId, 'files');
    }
};

window.videoSelected = function () {
    const mgr = window.contentManager;
    if (mgr && mgr.selectedContentId) {
        openMediaRelationPicker(mgr.selectedContentId, 'video');
    }
};

window.deleteSelected = function () {
    const mgr = window.contentManager;
    if (!mgr || !mgr.selectedContentId) return;
    const id = mgr.selectedContentId;

    Swal.fire({
        title: CONTENT_LANG.confirm_delete,
        icon: 'warning',
        showCancelButton: true,
        showDenyButton: false,
        confirmButtonColor: '#B71C1C',
        cancelButtonColor: '#6750A4',
        confirmButtonText: '<i class="bi bi-trash3"></i> ' + adminLang.context_menu.delete_confirm,
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

            fetch(MODULE_URL + 'delete', {
                method: 'POST',
                body: formData
            })
                .then(response => response.text())
                .then(msg => {
                    if ((msg || '').trim() === 'Success') {
                        mgr.selectedContentId = null;
                        mgr.updateToolbar(false);
                        mgr.loadData(true);
                        showSnackbar(adminLang.context_menu.delete_success, 'success');
                    } else {
                        showSnackbar(msg, 'error');
                    }
                })
                .catch(err => {
                    console.error('Delete error:', err);
                    showSnackbar(CONTENT_LANG.error_general, 'error');
                });
        }
    });
};

window.sliderSelected = function () {
    const mgr = window.contentManager;
    if (mgr && mgr.selectedContentId) {
        mgr.toggleHeroSlider();
    }
};

// Helper for Context Menu Actions (Global scope for onclicks)
// Helper for Context Menu Actions (Global scope for onclicks)
window.handleContextAction = function (action) {
    const id = window.currentContentId;
    if (!id) return;

    switch (action) {
        case 'edit':
            window.location.href = `${MODULE_URL}edit/${id}`;
            break;
        case 'delete':
            Swal.fire({
                title: CONTENT_LANG.confirm_delete,
                icon: 'warning',
                showCancelButton: true,
                showDenyButton: false,
                confirmButtonColor: '#B71C1C',
                cancelButtonColor: '#6750A4',
                confirmButtonText: '<i class="bi bi-trash3"></i> ' + adminLang.context_menu.delete_confirm,
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

                    fetch(`${MODULE_URL}delete`, {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.text())
                        .then(msg => {
                            if ((msg || '').trim() === 'Success') {
                                window.contentManager.loadData(true);
                                closeContextMenu();
                                showSnackbar(adminLang.context_menu.delete_success, 'success');
                            } else {
                                showSnackbar(msg, 'error');
                            }
                        })
                        .catch(err => {
                            console.error('Delete error:', err);
                            showSnackbar(CONTENT_LANG.error_general, 'error');
                        });
                }
            });
            break;
        case 'video':
        case 'gallery':
        case 'files':
            closeContextMenu(); // close context menu before opening picker
            openMediaRelationPicker(id, action);
            break;
        case 'status':
            // Call the existing toggleStatus logic
            // We need the current status 'e' or 'h'. We can get it from the UI or pass it.
            // Simplified: we can find the card and see its status, but better if openContextMenu passed it
            // activeContextItem.status is a boolean (isPublished)
            const currentStatus = activeContextItem.status ? 'y' : 'n';
            window.toggleStatus(id, currentStatus);
            break;
    }
    closeContextMenu();
} // End function

/**
 * Ported from legacy script.twig: Quick Publish/Unpublish
 */
window.toggleStatus = function (id, currentStatus) {
    if (typeof event !== 'undefined') event.stopPropagation();
    const endpoint = currentStatus === 'y' ? 'unapprove' : 'approve';
    const isPublishing = currentStatus !== 'y';

    const confirmMsg = isPublishing ? CONTENT_LANG.confirm_publish : CONTENT_LANG.confirm_unpublish;
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

            fetch(`${MODULE_URL}${endpoint}`, {
                method: 'POST',
                body: formData
            })
                .then(response => response.text())
                .then(msg => {
                    if ((msg || '').trim() === 'Success') {
                        window.contentManager.loadData(true);
                        showSnackbar(successMsg, 'success');
                    } else {
                        showSnackbar(msg, 'error');
                    }
                })
                .catch(err => {
                    console.error('Status error:', err);
                    showSnackbar(CONTENT_LANG.error_general, 'error');
                });
        }
    });
}

// Listen to select picker close to hide our add button
document.addEventListener('click', function(e) {
    if (e.target.matches('#select-picker-overlay') || e.target.closest('.m3-btn-text')) {
        const btn = document.getElementById('selectPickerAddBtn');
        if (btn) btn.style.display = 'none';
    }
});

// Added for media relation picker
window.openMediaRelationPicker = function(newsId, type) {
    let endpoint = '';
    let title = '';
    let addUrl = '';
    
    if (type === 'gallery') {
        endpoint = `${MODULE_URL}ajax_get_galleries`;
        title = 'Galeriya Heyî Hilbijêre';
        addUrl = `${SITE_URL}mod_gallery/list/mod_news/${newsId}`;
    } else if (type === 'files') {
        endpoint = `${MODULE_URL}ajax_get_files`;
        title = 'Pelê Heyî Hilbijêre';
        addUrl = `${SITE_URL}mod_attachment/list/mod_news/${newsId}`;
    } else if (type === 'video') {
        endpoint = `${MODULE_URL}all_video_get`;
        // Use translation fallback
        title = 'Vîdeoya Heyî Hilbijêre';
        addUrl = `${SITE_URL}mod_video_embed/add`; 
    }

    window.currentMediaPickerAddUrl = addUrl;
    const addBtn = document.getElementById('selectPickerAddBtn');
    if (addBtn) addBtn.style.display = 'block';

    const formData = new FormData();
    formData.append('q', '');

    const currentData = new FormData();
    currentData.append('id', newsId);

    Promise.all([
        fetch(endpoint, { method: 'POST', body: formData }).then(r => r.json()),
        fetch(`${MODULE_URL}ajax_get_current_media`, { method: 'POST', body: currentData }).then(r => r.json())
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
                if (val === undefined || val === null) return;
                
                const fd = new FormData();
                fd.append('id', window.currentMediaPickerNewsId);
                fd.append('type', window.currentMediaPickerType);
                fd.append('value', val);
                
                fetch(`${MODULE_URL}ajax_update_relation`, {
                    method: 'POST',
                    body: fd
                })
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        showSnackbar(res.message, 'success');
                        if (window.contentManager) window.contentManager.loadData(true);
                        closeSelectPickerSheet();
                    } else {
                        showSnackbar(res.message || 'Error', 'error');
                    }
                })
                .catch(err => showSnackbar('Error occurred', 'error'));
            });
        }
        
        window.currentMediaPickerNewsId = newsId;
        window.currentMediaPickerType = type;
        
        select.innerHTML = '<option value="0" style="display:none"></option>';
        data.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item.value || item.id;
            opt.textContent = item.name || item.text || item.title || ('ID: ' + item.id);
            select.appendChild(opt);
        });
        
        // Pre-select the appropriate value based on currentMedia
        let currentValue = '0';
        if (type === 'files' && currentMedia.file_id) currentValue = currentMedia.file_id.toString();
        if (type === 'gallery' && currentMedia.gallery_id) currentValue = currentMedia.gallery_id.toString();
        // Ignoring video value for now as it's serialized data, not an ID
        
        select.value = currentValue;
        select.dataset.pickerTitle = title;
        if (typeof openSelectPickerSheet === 'function') {
            openSelectPickerSheet('dynamicMediaSelect');
        }
    })
    .catch(err => {
        console.error(err);
        showSnackbar('Error loading modal', 'error');
    });
};

window.handleSelectPickerAdd = function() {
    if (window.currentMediaPickerAddUrl) {
        window.location.href = window.currentMediaPickerAddUrl;
    }
};
