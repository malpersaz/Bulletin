class ProductListManager {
    constructor() {
        this.container = document.getElementById('product-list-container');
        this.loader = document.getElementById('infinite-loader');
        this.emptyState = document.getElementById('empty-state');
        this.filterForm = document.getElementById('filterForm');
        this.imageModal = document.getElementById('productImageModal');
        this.imageModalTitle = document.getElementById('productImageModalTitle');
        this.imageModalSubtitle = document.getElementById('productImageModalSubtitle');
        this.imageMeta = document.getElementById('listImageMeta');
        this.imageFooter = document.getElementById('productImageModalFooter');
        this.imageCancelBtn = document.getElementById('productImageCancelBtn');
        this.selectedProductId = null;
        this.selectedInHeroSlider = false;
        this.isLoading = false;
        this.hasMore = true;
        this.start = 0;
        this.length = 10;
        this.draw = 1;
        this.imageUploadProductId = null;
        this.imageUploader = null;
        this.imageUploaderBound = false;
        this.imageUploadInProgress = false;
        this.imageModalState = {
            hasExistingImage: false,
            existingImageSrc: '',
            existingImageName: ''
        };

        this.init();
    }

    init() {
        this.createLoadMoreButton();
        this.loadData(true);
        this.initSearchListener();
        this.initListImageUploader();
        window.refreshContent = () => this.loadData(true);
    }

    createLoadMoreButton() {
        this.loadMoreBtn = document.createElement('button');
        this.loadMoreBtn.type = 'button';
        this.loadMoreBtn.className = 'm3-btn-outlined';
        this.loadMoreBtn.style.cssText = 'display:none; margin: 0 auto 120px;';
        this.loadMoreBtn.innerHTML = `<i class="bi bi-arrow-down-circle" style="margin-right:8px;"></i>${this.escapeHtml(PRODUCT_LANG.load_more)}`;
        this.loadMoreBtn.addEventListener('click', () => this.loadData());
        this.container.insertAdjacentElement('afterend', this.loadMoreBtn);
    }

    initSearchListener() {
        const searchInput = document.getElementById('searchInput');
        if (!searchInput) {
            return;
        }

        let timeout = null;
        searchInput.addEventListener('input', (event) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                const filterSearch = document.getElementById('filterSearch');
                if (filterSearch) {
                    filterSearch.value = event.target.value;
                }
                this.loadData(true);
            }, 350);
        });
    }

    async loadData(reset = false) {
        if (this.isLoading || (!this.hasMore && !reset)) {
            return;
        }

        this.isLoading = true;
        this.toggleLoader(true);
        this.toggleLoadMore(false);

        if (reset) {
            const filterLengthInput = document.getElementById('filterLength');
            if (filterLengthInput && filterLengthInput.value) {
                this.length = parseInt(filterLengthInput.value) || 10;
            }
            this.start = 0;
            this.draw = 1;
            this.hasMore = true;
            this.selectedProductId = null;
            this.selectedInHeroSlider = false;
            this.container.innerHTML = '';
            this.container.style.display = '';
            this.emptyState.style.display = 'none';
            this.updateToolbar(false);
        }

        try {
            const formData = new FormData(this.filterForm);
            formData.set('start', this.start);
            formData.set('length', this.length);
            formData.set('draw', this.draw);

            const response = await fetch(DATA_URL, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            const items = Array.isArray(result.data) ? result.data : [];

            if (reset && items.length === 0) {
                this.container.style.display = 'none';
                this.emptyState.style.display = 'flex';
            } else {
                this.container.style.display = '';
                this.emptyState.style.display = 'none';
                this.renderCards(items);
            }

            this.start += items.length;
            this.draw += 1;
            this.hasMore = this.start < Number(result.recordsFiltered || 0);
        } catch (error) {
            console.error('Product list load error:', error);
            showSnackbar(PRODUCT_LANG.error_general, 'error');
        } finally {
            this.isLoading = false;
            this.toggleLoader(false);
            this.toggleLoadMore(this.hasMore);
        }
    }

    renderCards(items) {
        const fragment = document.createDocumentFragment();

        items.forEach((item) => {
            const card = document.createElement('article');
            card.className = 'm3-card content-card';
            card.dataset.id = String(item.id);
            card.dataset.imageSrc = item.image_url || '';
            card.dataset.imageName = item.image_url ? (item.image_url.split('/').pop() || item.name || '') : (item.name || '');
            card.dataset.inSlider = item.show_on_homepage === 'y' ? '1' : '0';
            card.addEventListener('click', () => this.selectCard(card, item.id));
            card.addEventListener('dblclick', () => {
                window.location.href = `${MODULE_URL}edit/${item.id}`;
            });

            // Status Logic
            let statusText = item.status === 'y' ? PRODUCT_LANG.active : PRODUCT_LANG.draft;
            let statusColor = item.status === 'y' ? "#2E7D32" : "#0B57D0";

            // Category Logic
            let categoryName = item.category_name || CATEGORY_MAP[String(item.category_id)] || PRODUCT_LANG.general || "General";

            const safeStatusText = this.escapeHtml(statusText);
            const safeCategoryName = this.escapeHtml(categoryName);
            const safeNameText = this.escapeHtml(item.name || '');
            const safeLang = this.escapeHtml(String(item.lang || '').toUpperCase());
            let rawDate = item.created_at || '';
            if (rawDate.includes(' ')) {
                rawDate = rawDate.split(' ')[0];
            }
            const safeDate = this.escapeHtml(rawDate);
            const safeSliderBadge = this.escapeHtml(PRODUCT_LANG.hero_slider_badge || 'Slider');
            const safeHighlightedBadge = this.escapeHtml(PRODUCT_LANG.highlighted || 'Öne Çıkarılan');

            const imgUrl = item.image_url || '';

            const imageHtml = `
                <div class="card-thumbnail" style="cursor:pointer; display:flex; align-items:center; justify-content:center; color:#999; ${imgUrl ? `background-image: url('${imgUrl}'); background-size: cover; background-position: center;` : 'background-color: #f8f2fa;'}; width: 80px; height: 80px; border-radius: 8px;">
                    ${!imgUrl ? '<i class="bi bi-box-seam" style="font-size: 24px;"></i>' : ''}
                </div>
            `;

            card.innerHTML = `
                <div class="card-content-layout" style="display:flex; gap:12px;">
                    <div class="product-thumb-wrapper" style="cursor: pointer; flex-shrink: 0;">
                        ${imageHtml}
                    </div>
                    
                    <div class="card-body-col" style="flex:1; display:flex; flex-direction:column; justify-content:space-between; min-width:0;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; width:100%;">
                             <div style="flex:1; min-width:0;">
                                <div class="meta-row" style="margin-top:0; margin-bottom:4px; display:flex; align-items:center; flex-wrap:wrap; gap:4px; font-size:12px; color: var(--md-sys-color-on-surface-variant);">
                                     <span class="chip" style="background:${statusColor}; color:#FFF; font-weight:bold; height:18px; border-radius:4px; padding:0 6px; font-size:10px; display:inline-flex; align-items:center;">${safeStatusText}</span>
                                     
                                     <span class="chip hero-slider-badge" style="display:${item.show_on_homepage === 'y' ? 'inline-flex' : 'none'}; align-items:center; gap:2px; background:#2E7D32; color:#FFF; border-radius:4px; padding:0 6px; font-size:10px; font-weight:bold; height:18px;"><i class="bi bi-stars"></i>${safeSliderBadge}</span>
                                     <span class="hero-slider-dot" style="display:none;"></span>
                                     
                                     ${item.highlighted === 'y' ? `<span class="chip" style="background:#2E7D32; color:#FFF; border-radius:4px; padding:0 6px; font-size:10px; font-weight:bold; height:18px; display:inline-flex; align-items:center;">${safeHighlightedBadge}</span>` : ''}
                                </div>
                                <h3 style="margin:4px 0; font-size:16px; font-weight:700; color: var(--md-sys-color-on-surface); word-break:break-word; line-height:1.2;">${safeNameText}</h3>
                             </div>
                             <button type="button" class="product-action-btn m3-icon-btn" style="margin-top:-8px; margin-right:-8px;" aria-label="More actions">
                                <i class="bi bi-three-dots-vertical"></i>
                             </button>
                        </div>
                    </div>
                </div>

                <div class="meta-row" style="margin-top:12px; display:flex; align-items:center; width:100%; font-size:12px; color: var(--md-sys-color-on-surface-variant); border-top: 1px solid var(--md-sys-color-outline-variant, #EADDFF); padding-top: 8px;">
                     <span style="display:flex; align-items:center; gap:4px; white-space:nowrap;"><i class="bi bi-translate" style="font-size:11px;"></i> ${safeLang}</span>
                     <span style="margin-left: 12px; display:flex; align-items:center; gap:4px; white-space:nowrap;"><i class="bi bi-calendar3" style="font-size:11px;"></i> ${safeDate}</span>
                     <span style="margin-left: auto; display:flex; align-items:center; gap:4px; white-space:nowrap;"><i class="bi bi-diagram-2" style="font-size:11px;"></i> ${safeCategoryName}</span>
                </div>
            `;

            const thumb = card.querySelector('.product-thumb-wrapper');
            if (thumb) {
                thumb.addEventListener('click', (event) => {
                    event.stopPropagation();
                    this.processImageUpload(item.id);
                });
            }

            const actionBtn = card.querySelector('.product-action-btn');
            if (actionBtn) {
                actionBtn.addEventListener('click', (event) => {
                    event.stopPropagation();
                    this.selectCard(card, item.id, { forceSelect: true });
                    this.openActionSheet(item.id, item.name || '');
                });
            }

            fragment.appendChild(card);
        });

        this.container.appendChild(fragment);
    }

    selectCard(card, productId, options = {}) {
        const isSelected = card.classList.contains('selected');
        document.querySelectorAll('.m3-card.selected, .content-card.selected').forEach((node) => node.classList.remove('selected'));

        if (isSelected && !options.forceSelect) {
            this.selectedProductId = null;
            this.selectedInHeroSlider = false;
            this.updateToolbar(false);
            return;
        }

        card.classList.add('selected');
        this.selectedProductId = String(productId);
        this.selectedInHeroSlider = card.dataset.inSlider === '1';
        this.updateToolbar(true);
        if (window.navigator.vibrate) {
            window.navigator.vibrate(10);
        }
    }

    openActionSheet(productId, title) {
        if (typeof openContextMenu !== 'function') {
            return;
        }

        openContextMenu(String(productId), title || '', false);

        const editAction = document.getElementById('contextActionEdit');
        const galleryAction = document.getElementById('contextActionGallery');
        const filesAction = document.getElementById('contextActionFiles');
        const videoAction = document.getElementById('contextActionVideo');
        const deleteAction = document.getElementById('contextActionDelete');

        if (editAction) {
            editAction.style.display = document.getElementById('toolbarEdit') ? 'flex' : 'none';
        }
        if (galleryAction) {
            galleryAction.style.display = document.getElementById('toolbarGallery') ? 'flex' : 'none';
        }
        if (filesAction) {
            filesAction.style.display = document.getElementById('toolbarFiles') ? 'flex' : 'none';
        }
        if (deleteAction) {
            deleteAction.style.display = document.getElementById('toolbarDelete') ? 'flex' : 'flex';
        }
        const statusAction = document.getElementById('contextActionStatus');
        if (statusAction) {
            statusAction.style.display = 'none';
        }
        if (videoAction) {
            videoAction.style.display = document.getElementById('toolbarVideo') ? 'flex' : 'none';
            const icon = videoAction.querySelector('.context-icon');
            const iconEl = videoAction.querySelector('i');
            const label = videoAction.querySelector('.context-action-label');
            if (icon) {
                icon.style.color = '#0b57d0';
                icon.style.background = 'rgba(11, 87, 208, 0.12)';
            }
            if (iconEl) {
                iconEl.className = 'bi bi-camera-video';
            }
            if (label) {
                label.textContent = PRODUCT_LANG.video_cta;
            }
        }
    }

    updateToolbar(active) {
        ['toolbarEdit', 'toolbarGallery', 'toolbarFiles', 'toolbarDelete', 'toolbarSlider', 'toolbarVideo'].forEach((id) => {
            const button = document.getElementById(id);
            if (!button) {
                return;
            }
            button.classList.toggle('active', active);
            button.style.pointerEvents = active ? 'auto' : 'none';
            button.style.opacity = active ? '1' : '0.3';
        });
        this.syncSliderToolbarState(active);
    }

    getSelectedCard() {
        if (!this.selectedProductId) {
            return null;
        }
        return document.querySelector(`.content-card[data-id="${this.selectedProductId}"]`);
    }

    syncSliderToolbarState(active) {
        const sliderBtn = document.getElementById('toolbarSlider');
        if (!sliderBtn) {
            return;
        }

        const label = active && this.selectedInHeroSlider
            ? PRODUCT_LANG.hero_slider_remove
            : PRODUCT_LANG.hero_slider_add;

        sliderBtn.classList.toggle('is-selected', Boolean(active && this.selectedInHeroSlider));
        sliderBtn.title = label;
        sliderBtn.setAttribute('aria-label', label);
    }

    applyHeroSliderState(card, inHeroSlider) {
        if (!card) {
            return;
        }

        card.dataset.inSlider = inHeroSlider ? '1' : '0';

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
        if (!this.selectedProductId) {
            showSnackbar(PRODUCT_LANG.select_record, 'info');
            return;
        }

        const body = new URLSearchParams();
        body.append('id', this.selectedProductId);

        try {
            const response = await fetch(`${MODULE_URL}toggle_hero_slider`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body
            });
            const result = await response.json();

            if (result.status !== 'success') {
                showSnackbar(result.message || PRODUCT_LANG.hero_slider_error, 'error');
                return;
            }

            this.selectedInHeroSlider = Boolean(result.in_hero_slider);
            this.applyHeroSliderState(this.getSelectedCard(), this.selectedInHeroSlider);
            this.syncSliderToolbarState(true);
            showSnackbar(
                result.message || (this.selectedInHeroSlider ? PRODUCT_LANG.hero_slider_added : PRODUCT_LANG.hero_slider_removed),
                'success'
            );
        } catch (error) {
            console.error('Product hero slider toggle error:', error);
            showSnackbar(PRODUCT_LANG.hero_slider_error, 'error');
        }
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
            hintRemoteFailed: data.hintRemoteFailed || PRODUCT_LANG.image_remote_failed || '',
            emptyName: data.emptyName || PRODUCT_LANG.image_empty || '',
            emptyMeta: data.emptyMeta || PRODUCT_LANG.image_drop_description || '',
            imageLabel: PRODUCT_LANG.image_title || ''
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
                    if (file && source !== 'existing' && source !== 'reset') {
                        this.submitListImageUpload();
                    }
                }
            });
        }

        if (!this.imageUploaderBound && this.imageUploader) {
            this.imageUploader.bind();
            this.imageUploaderBound = true;
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && this.imageModal?.classList.contains('active')) {
                this.closeImageUploader();
            }
        });
    }

    processImageUpload(productId) {
        const card = document.querySelector(`.product-card[data-id="${productId}"]`);
        const title = card?.querySelector('.product-name')?.textContent?.trim() || '';
        this.openImageUploader(productId, title);
    }

    openImageUploader(productId, title) {
        this.initListImageUploader();
        this.imageUploadProductId = String(productId);
        const card = document.querySelector(`.product-card[data-id="${productId}"]`);
        const existingImageSrc = card?.dataset.imageSrc || '';
        const existingImageName = card?.dataset.imageName || title || PRODUCT_LANG.image_title;
        this.imageModalState = {
            hasExistingImage: Boolean(existingImageSrc),
            existingImageSrc,
            existingImageName
        };

        if (this.imageModalTitle) {
            this.imageModalTitle.textContent = PRODUCT_LANG.image_title;
        }
        if (this.imageModalSubtitle) {
            this.imageModalSubtitle.textContent = '';
            this.imageModalSubtitle.hidden = true;
        }

        this.resetListImageUploader();

        if (this.imageModal) {
            document.body.classList.add('product-image-modal-open');
            this.imageModal.classList.add('active');
            this.imageModal.setAttribute('aria-hidden', 'false');
        }
    }

    closeImageUploader() {
        if (this.imageModal) {
            this.imageModal.classList.remove('active');
            this.imageModal.setAttribute('aria-hidden', 'true');
        }

        document.body.classList.remove('product-image-modal-open');
        this.imageUploadProductId = null;
        this.imageUploadInProgress = false;
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
                name: this.imageModalState.existingImageName || PRODUCT_LANG.image_title,
                metaText: ''
            });
        }
    }

    resetImageModalActions() {
        if (this.imageMeta) {
            this.imageMeta.hidden = true;
        }
        if (this.imageFooter) {
            this.imageFooter.classList.add('is-single-action');
        }
        if (this.imageCancelBtn && typeof adminLang !== 'undefined' && adminLang.context_menu?.cancel) {
            this.imageCancelBtn.textContent = adminLang.context_menu.cancel;
        }
    }

    updateImageModalActions(source, file) {
        const hasPendingFile = Boolean(file) && source !== 'existing';
        if (this.imageMeta) {
            this.imageMeta.hidden = !hasPendingFile;
        }
        if (this.imageFooter) {
            this.imageFooter.classList.toggle('is-single-action', !hasPendingFile);
        }
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
        if (!this.imageUploadProductId || !this.imageModalState.hasExistingImage) {
            return;
        }

        const result = await Swal.fire({
            title: PRODUCT_LANG.image_remove_title,
            text: PRODUCT_LANG.image_remove_confirm,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#B71C1C',
            cancelButtonColor: '#6750A4',
            confirmButtonText: PRODUCT_LANG.image_remove_confirm_text,
            cancelButtonText: PRODUCT_LANG.image_remove_cancel_text,
            background: 'var(--md-sys-color-surface-container-high, #fff)',
            color: 'var(--md-sys-color-on-surface, #1C1B1F)'
        });

        if (!result.isConfirmed) {
            return;
        }

        this.toggleLoader(true);
        try {
            const body = new URLSearchParams();
            body.append('id', this.imageUploadProductId);
            const response = await fetch(`${MODULE_URL}image_remove_image`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body
            });
            const text = (await response.text()).trim();
            if (text !== 'Oke') {
                showSnackbar(text || PRODUCT_LANG.error_general, 'error');
                return;
            }

            this.closeImageUploader();
            await this.loadData(true);
            showSnackbar(PRODUCT_LANG.image_remove_success, 'success');
        } catch (error) {
            console.error('Product image remove error:', error);
            showSnackbar(PRODUCT_LANG.error_general, 'error');
        } finally {
            this.toggleLoader(false);
        }
    }

    async submitListImageUpload() {
        if (this.imageUploadInProgress) {
            return;
        }

        const fileData = this.imageUploader?.getSelectedFile();
        if (!this.imageUploadProductId || !fileData) {
            this.imageUploader?.showFeedback(PRODUCT_LANG.error_upload, 'error');
            return;
        }

        const formData = new FormData();
        formData.append('image', fileData);
        formData.append('id', this.imageUploadProductId);

        this.imageUploadInProgress = true;
        this.toggleLoader(true);

        try {
            const response = await fetch(`${MODULE_URL}ajax_image_upload`, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.status !== 'success') {
                this.imageUploader?.showFeedback(result.message || PRODUCT_LANG.error_upload, 'error');
                showSnackbar(result.message || PRODUCT_LANG.error_upload, 'error');
                return;
            }

            this.closeImageUploader();
            await this.loadData(true);
            showSnackbar(result.message || PRODUCT_LANG.image_updated, 'success');
        } catch (error) {
            console.error('Product image upload error:', error);
            this.imageUploader?.showFeedback(PRODUCT_LANG.error_upload, 'error');
            showSnackbar(PRODUCT_LANG.error_upload, 'error');
        } finally {
            this.imageUploadInProgress = false;
            this.toggleLoader(false);
        }
    }

    async deleteSelected() {
        if (!this.selectedProductId) {
            showSnackbar(PRODUCT_LANG.select_record, 'info');
            return;
        }

        const result = await Swal.fire({
            title: PRODUCT_LANG.delete_confirm,
            icon: 'warning',
            showCancelButton: true,
            showDenyButton: false,
            confirmButtonColor: '#B71C1C',
            cancelButtonColor: '#6750A4',
            confirmButtonText: '<i class="bi bi-trash3"></i> ' + adminLang.context_menu.delete_confirm,
            cancelButtonText: '<i class="bi bi-x-lg"></i> ' + adminLang.context_menu.cancel,
            background: 'var(--md-sys-color-surface-container-high, #fff)',
            color: 'var(--md-sys-color-on-surface, #1C1B1F)'
        });

        if (!result.isConfirmed) {
            return;
        }

        const formData = new FormData();
        formData.append('query[]', this.selectedProductId);

        try {
            const response = await fetch(`${MODULE_URL}delete`, {
                method: 'POST',
                body: formData
            });
            const text = (await response.text()).trim();
            if (text !== 'Oke') {
                showSnackbar(text || PRODUCT_LANG.delete_failed, 'error');
                return;
            }

            this.selectedProductId = null;
            this.updateToolbar(false);
            this.loadData(true);
            showSnackbar(adminLang.context_menu.delete_success, 'success');
        } catch (error) {
            console.error('Product delete error:', error);
            showSnackbar(PRODUCT_LANG.delete_failed, 'error');
        }
    }

    openGalleryManager() {
        if (!this.selectedProductId) {
            showSnackbar(PRODUCT_LANG.select_record, 'info');
            return;
        }
        window.location.href = `${SITE_URL}mod_gallery/list/mod_product/${this.selectedProductId}`;
    }

    openFilesPicker() {
        if (!this.selectedProductId) {
            showSnackbar(PRODUCT_LANG.select_record, 'info');
            return;
        }

        window.currentMediaPickerAddUrl = `${SITE_URL}mod_attachment/list/mod_product/${this.selectedProductId}`;
        window.currentMediaPickerRelationUrl = MODULE_URL + 'ajax_update_relation';
        window.currentMediaPickerModuleName = 'mod_product';

        const addBtn = document.getElementById('selectPickerAddBtn');
        if (addBtn) {
            addBtn.style.display = 'block';
        }

        const pickerData = new FormData();
        pickerData.append('q', '');

        const currentData = new FormData();
        currentData.append('id', this.selectedProductId);

        Promise.all([
            fetch(`${MODULE_URL}ajax_get_files`, { method: 'POST', body: pickerData }).then((response) => response.json()),
            fetch(`${MODULE_URL}ajax_get_current_media`, { method: 'POST', body: currentData }).then((response) => response.json())
        ]).then(([files, currentMedia]) => {
            let select = document.getElementById('dynamicProductFileSelect');
            if (!select) {
                select = document.createElement('select');
                select.id = 'dynamicProductFileSelect';
                select.style.display = 'none';
                document.body.appendChild(select);

                select.addEventListener('change', () => {
                    const formData = new FormData();
                    formData.append('id', window.currentMediaPickerContentId);
                    formData.append('type', 'files');
                    formData.append('value', select.value || '0');

                    fetch(`${MODULE_URL}ajax_update_relation`, {
                        method: 'POST',
                        body: formData
                    }).then((response) => response.json()).then((result) => {
                        if (result.status !== 'success') {
                            showSnackbar(result.message || PRODUCT_LANG.relation_error, 'error');
                            return;
                        }
                        showSnackbar(result.message, 'success');
                        this.loadData(true);
                    }).catch((error) => {
                        console.error('Product file relation error:', error);
                        showSnackbar(PRODUCT_LANG.relation_error, 'error');
                    });
                });
            }

            select.innerHTML = '<option value="0">None</option>';
            files.forEach((item) => {
                const option = document.createElement('option');
                option.value = String(item.id || item.value || 0);
                option.textContent = item.text || item.name || String(item.id);
                select.appendChild(option);
            });

            select.value = String(currentMedia.file_id || 0);
            select.dataset.pickerTitle = PRODUCT_LANG.picker_files;
            window.currentMediaPickerContentId = this.selectedProductId;
            window.currentMediaPickerType = 'files';

            const pickerTitle = document.getElementById('selectPickerTitle');
            const pickerSubtitle = document.getElementById('selectPickerActiveLang');
            if (pickerTitle) {
                pickerTitle.textContent = PRODUCT_LANG.picker_files;
            }
            if (pickerSubtitle) {
                pickerSubtitle.textContent = '';
            }

            openSelectPickerSheet(select.id);
        }).catch((error) => {
            console.error('Product file picker error:', error);
            showSnackbar(PRODUCT_LANG.error_general, 'error');
        });
    }

    openVideoPicker() {
        if (!this.selectedProductId) {
            showSnackbar(PRODUCT_LANG.select_record, 'info');
            return;
        }

        window.currentMediaPickerAddUrl = `${SITE_URL}mod_video_embed/add`;

        const addBtn = document.getElementById('selectPickerAddBtn');
        if (addBtn) {
            addBtn.style.display = 'block';
        }

        const pickerData = new FormData();
        pickerData.append('q', '');

        const currentData = new FormData();
        currentData.append('id', this.selectedProductId);

        Promise.all([
            fetch(`${MODULE_URL}all_video`, { method: 'POST', body: pickerData }).then((response) => response.json()),
            fetch(`${MODULE_URL}ajax_get_current_media`, { method: 'POST', body: currentData }).then((response) => response.json())
        ]).then(([videos, currentMedia]) => {
            let select = document.getElementById('dynamicProductVideoSelect');
            if (!select) {
                select = document.createElement('select');
                select.id = 'dynamicProductVideoSelect';
                select.style.display = 'none';
                document.body.appendChild(select);

                select.addEventListener('change', () => {
                    const formData = new FormData();
                    formData.append('id', window.currentMediaPickerContentId);
                    formData.append('type', 'video');
                    formData.append('value', select.value || '0');

                    fetch(`${MODULE_URL}ajax_update_relation`, {
                        method: 'POST',
                        body: formData
                    }).then((response) => response.json()).then((result) => {
                        if (result.status !== 'success') {
                            showSnackbar(result.message || PRODUCT_LANG.relation_error, 'error');
                            return;
                        }
                        showSnackbar(result.message, 'success');
                        this.loadData(true);
                    }).catch((error) => {
                        console.error('Product video relation error:', error);
                        showSnackbar(PRODUCT_LANG.relation_error, 'error');
                    });
                });
            }

            select.innerHTML = '';
            videos.forEach((item) => {
                const option = document.createElement('option');
                option.value = item.value || item.id;
                option.textContent = item.name || item.text || item.title || String(item.id);
                select.appendChild(option);
            });

            select.value = '0';
            select.dataset.pickerTitle = PRODUCT_LANG.picker_video;
            window.currentMediaPickerContentId = this.selectedProductId;
            window.currentMediaPickerType = 'video';

            const pickerTitle = document.getElementById('selectPickerTitle');
            const pickerSubtitle = document.getElementById('selectPickerActiveLang');
            if (pickerTitle) {
                pickerTitle.textContent = PRODUCT_LANG.picker_video;
            }
            if (pickerSubtitle) {
                pickerSubtitle.textContent = Array.isArray(currentMedia.video) && currentMedia.video.length > 0
                    ? String(currentMedia.video.length)
                    : '';
            }

            openSelectPickerSheet(select.id);
        }).catch((error) => {
            console.error('Product video picker error:', error);
            showSnackbar(PRODUCT_LANG.error_general, 'error');
        });
    }

    toggleLoader(show) {
        if (this.loader) {
            this.loader.style.display = show ? 'flex' : 'none';
        }
    }

    toggleLoadMore(show) {
        if (!this.loadMoreBtn) {
            return;
        }
        this.loadMoreBtn.style.display = show ? 'inline-flex' : 'none';
        if (show) {
            this.loadMoreBtn.innerHTML = `<i class="bi bi-arrow-down-circle" style="margin-right:8px;"></i>${this.escapeHtml(PRODUCT_LANG.load_more)}`;
        }
    }

    escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.productListManager = new ProductListManager();
});

window.editSelected = function() {
    const manager = window.productListManager;
    if (!manager || !manager.selectedProductId) {
        showSnackbar(PRODUCT_LANG.select_record, 'info');
        return;
    }
    window.location.href = `${MODULE_URL}edit/${manager.selectedProductId}`;
};

window.gallerySelected = function() {
    window.productListManager?.openGalleryManager();
};

window.filesSelected = function() {
    window.productListManager?.openFilesPicker();
};

window.videoSelected = function() {
    window.productListManager?.openVideoPicker();
};

window.deleteSelected = function() {
    window.productListManager?.deleteSelected();
};

window.sliderSelected = function() {
    window.productListManager?.toggleHeroSlider();
};



window.handleContextAction = function(action) {
    const id = window.currentContentId;
    const manager = window.productListManager;
    if (!id || !manager) {
        closeContextMenu();
        return;
    }

    switch (action) {
        case 'edit':
            window.location.href = `${MODULE_URL}edit/${id}`;
            break;
        case 'gallery':
            closeContextMenu();
            manager.openGalleryManager();
            return;
        case 'files':
            closeContextMenu();
            manager.openFilesPicker();
            return;
        case 'video':
            closeContextMenu();
            manager.openVideoPicker();
            return;
        case 'delete':
            closeContextMenu();
            manager.deleteSelected();
            return;
        default:
            closeContextMenu();
            return;
    }

    closeContextMenu();
};
