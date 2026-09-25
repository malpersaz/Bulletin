/**
 * mod_announcements.js
 * Material 3 Phone-First Announcements List & Form Manager
 * Unified image upload, 3:4 aspect-ratio Cropper, mobile synchronization and touch interactions.
 */

function getAnnouncementModuleUrl() {
    if (typeof window.MODULE_URL !== 'undefined' && window.MODULE_URL) return window.MODULE_URL;
    const container = document.getElementById('announcements-list-container');
    if (container && container.dataset.moduleUrl) return container.dataset.moduleUrl;
    const siteUrl = (typeof window.SITE_URL !== 'undefined' && window.SITE_URL) ? window.SITE_URL : '/';
    return siteUrl + 'mod_announcements/';
}

function getAnnouncementDataUrl() {
    if (typeof window.DATA_URL !== 'undefined' && window.DATA_URL) return window.DATA_URL;
    const container = document.getElementById('announcements-list-container');
    if (container && container.dataset.dataUrl) return container.dataset.dataUrl;
    return getAnnouncementModuleUrl() + 'datatable_json';
}

function getAnnouncementLang() {
    if (typeof window.ANNOUNCEMENT_LANG !== 'undefined' && window.ANNOUNCEMENT_LANG) return window.ANNOUNCEMENT_LANG;
    const al = (typeof window.adminLang !== 'undefined' && window.adminLang) ? window.adminLang : {};
    const mod = al.mod_announcements || al.active_module || {};
    const ctx = al.context_menu || {};
    const dt = al.datatable || {};

    const cr = mod.wizard?.cropper || {};

    return {
        active: mod.status_badge?.active || mod.grid?.approve?.label || 'Active',
        draft: mod.status_badge?.draft || mod.grid?.unapprove?.label || 'Draft',
        general: mod.status_badge?.general || 'General',
        confirm_delete: mod.delete_confirm || ctx.delete_confirm || 'Are you sure you want to delete this announcement?',
        confirm_publish: mod.quick_publish_confirm || 'Do you want to publish this announcement?',
        confirm_unpublish: mod.quick_unpublish_confirm || 'Do you want to unpublish this announcement?',
        error_general: mod.error || 'An error occurred',
        error_upload: mod.message?.error?.add || 'Upload failed',
        cancel: mod.button?.cancel || ctx.cancel || 'Cancel',
        delete_confirm_text: mod.button?.yes || ctx.delete_confirm || 'Yes, Delete',
        delete_cancel_text: mod.button?.no || ctx.cancel || 'Cancel',
        image_remove_title: mod.button?.delete || 'Remove Image',
        image_remove_confirm: mod.remove_image || 'Are you sure you want to remove this image?',
        image_remove_success: mod.remove_image_success || 'Image removed successfully',
        image_updated: mod.image_updated || 'Image updated successfully',
        image_title: mod.wizard?.step?.image || 'Cover Image',
        save_image: mod.button?.save || 'Save Image',
        upload_change_image: mod.upload_change_image || 'Upload / Change Image',
        loading: dt.loadingRecords || mod.grid?.loading || 'Loading...',
        cropper: {
            title: cr.title || 'Crop Image (3:4)',
            instruction: cr.instruction || 'Adjust and crop the image to the 3:4 announcement aspect ratio.',
            zoom_in: cr.zoom_in || 'Zoom In',
            zoom_out: cr.zoom_out || 'Zoom Out',
            rotate_left: cr.rotate_left || 'Rotate Left',
            rotate_right: cr.rotate_right || 'Rotate Right',
            reset: cr.reset || 'Reset',
            apply: cr.apply || 'Crop & Save Image',
            cancel: cr.cancel || 'Cancel'
        },
        grid: {
            load_more: mod.grid?.load_more || 'Load More'
        }
    };
}

/**
 * Mobile-friendly modal dialog helper
 */
async function announcementConfirm(title, text, confirmBtnText, cancelBtnText) {
    if (typeof Swal !== 'undefined') {
        const result = await Swal.fire({
            title: title || 'Are you sure?',
            text: text || '',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#6750a4',
            cancelButtonColor: '#79747e',
            confirmButtonText: confirmBtnText || 'Yes',
            cancelButtonText: cancelBtnText || 'Cancel',
            reverseButtons: true,
            customClass: {
                popup: 'm3-swal-popup',
                confirmButton: 'm3-btn-filled',
                cancelButton: 'm3-btn-outlined'
            }
        });
        return result.isConfirmed;
    }
    return window.confirm(text || title || 'Are you sure?');
}

function announcementToast(title, icon = 'success') {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            toast: true,
            position: 'top',
            icon: icon,
            title: title,
            showConfirmButton: false,
            showCancelButton: false,
            showDenyButton: false,
            timer: 1500,
            timerProgressBar: false
        });
    }
}

/**
 * Announcements Manager (List Screen)
 */
class AnnouncementsManager {
    constructor() {
        this.container = document.getElementById('announcements-list-container');
        this.loader = document.getElementById('infinite-loader');
        this.emptyState = document.getElementById('empty-state');
        this.emptyIcon = document.getElementById('emptyStateIcon');
        this.emptyTitle = document.getElementById('emptyStateTitle');
        this.emptyDesc = document.getElementById('emptyStateDesc');
        this.emptyAddBtn = document.getElementById('emptyStateAddBtn');
        this.emptyClearBtn = document.getElementById('emptyStateClearBtn');
        this.filterForm = document.getElementById('filterForm');
        this.imageModal = document.getElementById('announcementImageModal');
        this.imageModalTitle = document.getElementById('announcementImageModalTitle');
        this.imageModalSubtitle = document.getElementById('announcementImageModalSubtitle');
        this.imageMeta = document.getElementById('listImageMeta');
        this.imageFooter = document.getElementById('announcementImageModalFooter');
        this.imageCancelBtn = document.getElementById('announcementImageCancelBtn');
        this.imageSaveBtn = document.getElementById('announcementImageSaveBtn');

        // Cropper Elements for Quick Image Modal
        this.uploadShell = document.getElementById('listImageShell');
        this.cropperContainer = document.getElementById('announcementCropperContainer');
        this.cropperImage = document.getElementById('announcementCropperImage');
        this.cropApplyBtn = document.getElementById('announcementCropApplyBtn');
        this.cropperInstance = null;
        this.originalFile = null;
        this.originalFileName = 'announcement_image.jpg';

        this.isLoading = false;
        this.hasMore = true;
        this.selectedId = null;
        this.imageUploadId = null;
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
        this.initActionListeners();
        window.refreshContent = () => this.loadData(true);
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
            const cropTarget = e.target.closest('[data-crop-action]');
            if (cropTarget) {
                const cropAction = cropTarget.dataset.cropAction;
                this.handleCropperAction(cropAction);
                return;
            }

            const target = e.target.closest('[data-action]');
            if (!target) return;

            const action = target.dataset.action;
            if (action === 'close-image-uploader') {
                this.closeImageUploader();
            } else if (action === 'save-image-upload') {
                this.saveCroppedAnnouncementImage();
            } else if (action === 'open-gallery') {
                window.gallerySelected?.();
            } else if (action === 'open-files') {
                window.filesSelected?.();
            } else if (action === 'edit-announcement') {
                window.editSelected?.();
            } else if (action === 'delete-announcement') {
                window.deleteSelected?.();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.imageModal && this.imageModal.classList.contains('active')) {
                this.closeImageUploader();
            }
        });
    }

    createLoadMoreButton() {
        this.loadMoreBtn = document.createElement('button');
        this.loadMoreBtn.className = 'm3-btn-outlined content-load-more-btn is-hidden';

        let btnText = 'Load More';
        const lang = getAnnouncementLang();
        if (lang.grid && lang.grid.load_more) {
            btnText = lang.grid.load_more;
        }
        this.loadMoreBtn.innerHTML = `<i class="bi bi-arrow-down-circle"></i><span>${btnText}</span>`;

        if (this.container && this.loader) {
            this.container.parentNode.insertBefore(this.loadMoreBtn, this.loader);
        }

        this.loadMoreBtn.onclick = () => {
            this.loadData();
        };
    }

    initSearchListener() {
        const searchInput = document.getElementById('searchInput');
        if (!searchInput) return;

        let timeout = null;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                const searchVal = e.target.value.trim();
                const filterSearch = document.getElementById('filterSearch');
                if (filterSearch) filterSearch.value = searchVal;
                this.loadData(true);
            }, 350);
        });
    }

    initEmptyStateListeners() {
        if (!this.emptyClearBtn) return;
        this.emptyClearBtn.addEventListener('click', () => {
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input'));
            }
            const filterSearch = document.getElementById('filterSearch');
            if (filterSearch) filterSearch.value = '';

            const allFilterBtn = document.querySelector('.content-stat-card[data-status-filter="all"]');
            if (allFilterBtn) allFilterBtn.click();
            else this.loadData(true);
        });
    }

    initPullToRefresh() {
        const ptrSpinner = document.getElementById('ptr-spinner');
        if (!ptrSpinner) return;

        let startY = 0;
        let pullDistance = 0;
        const threshold = 70;
        let isPulling = false;

        window.addEventListener('touchstart', (e) => {
            if (window.scrollY === 0 && !this.isLoading) {
                startY = e.touches[0].pageY;
                isPulling = true;
            }
        }, { passive: true });

        window.addEventListener('touchmove', (e) => {
            if (!isPulling) return;
            const currentY = e.touches[0].pageY;
            pullDistance = currentY - startY;

            if (pullDistance > 0 && window.scrollY === 0) {
                const rotation = Math.min(pullDistance * 2, 360);
                const opacity = Math.min(pullDistance / threshold, 1);
                ptrSpinner.style.transform = `translateY(${Math.min(pullDistance * 0.4, 40)}px) rotate(${rotation}deg)`;
                ptrSpinner.style.opacity = opacity;
            }
        }, { passive: true });

        window.addEventListener('touchend', () => {
            if (!isPulling) return;
            isPulling = false;

            if (pullDistance > threshold && window.scrollY === 0) {
                ptrSpinner.style.transform = 'translateY(30px)';
                ptrSpinner.style.opacity = '1';
                ptrSpinner.classList.add('is-refreshing');
                if (window.navigator.vibrate) window.navigator.vibrate(10);

                this.loadData(true).finally(() => {
                    setTimeout(() => {
                        ptrSpinner.style.transform = '';
                        ptrSpinner.style.opacity = '0';
                        ptrSpinner.classList.remove('is-refreshing');
                    }, 300);
                });
            } else {
                ptrSpinner.style.transform = '';
                ptrSpinner.style.opacity = '0';
            }
            pullDistance = 0;
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
        if (this.loadMoreBtn) {
            this.loadMoreBtn.disabled = true;
            this.loadMoreBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>...`;
        }

        if (reset) {
            this.start = 0;
            if (this.container) this.container.innerHTML = '';
            this.hasMore = true;
            this.draw = 1;
            this.selectedId = null;
            this.updateToolbar(false);
            if (this.emptyState) {
                this.emptyState.classList.add('is-hidden');
                this.emptyState.style.display = 'none';
            }
            if (this.loader) {
                this.loader.classList.remove('is-hidden');
                this.loader.style.display = 'flex';
            }
            if (this.loadMoreBtn) {
                this.loadMoreBtn.classList.add('is-hidden');
                this.loadMoreBtn.style.display = 'none';
            }
        } else {
            if (this.loader) {
                this.loader.classList.remove('is-hidden');
                this.loader.style.display = 'flex';
            }
        }

        try {
            const formData = new FormData(this.filterForm || undefined);
            formData.set('start', this.start);
            formData.set('length', this.length);
            formData.set('draw', this.draw);

            const dataUrl = getAnnouncementDataUrl();
            const response = await fetch(dataUrl, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const result = await response.json();

            // Update stats cards in UI
            if (result.totalAnnouncements !== undefined || result.total !== undefined) {
                const total = result.totalAnnouncements ?? result.total ?? 0;
                const published = result.publishedAnnouncements ?? result.published ?? 0;
                const draft = result.draftAnnouncements ?? result.draft ?? 0;

                const statTotal = document.getElementById('statTotalAnnouncements');
                const statPub = document.getElementById('statPublishedAnnouncements');
                const statDraft = document.getElementById('statDraftAnnouncements');

                if (statTotal) statTotal.textContent = total;
                if (statPub) statPub.textContent = published;
                if (statDraft) statDraft.textContent = draft;
            }

            const items = result.data || [];
            if (reset && items.length === 0) {
                this.updateEmptyState();
                this.hasMore = false;
            } else {
                if (this.emptyState) {
                    this.emptyState.classList.add('is-hidden');
                    this.emptyState.style.display = 'none';
                }
                this.renderCards(items);
                this.start += items.length;
                this.draw += 1;
                this.hasMore = this.start < Number(result.recordsFiltered || result.recordsTotal || 0);
            }

            if (this.loadMoreBtn) {
                if (this.hasMore) {
                    this.loadMoreBtn.classList.remove('is-hidden');
                    this.loadMoreBtn.style.display = 'flex';
                    this.loadMoreBtn.disabled = false;
                    let btnText = 'Load More';
                    const lang = getAnnouncementLang();
                    if (lang.grid && lang.grid.load_more) btnText = lang.grid.load_more;
                    this.loadMoreBtn.innerHTML = `<i class="bi bi-arrow-down-circle"></i><span>${btnText}</span>`;
                } else {
                    this.loadMoreBtn.classList.add('is-hidden');
                    this.loadMoreBtn.style.display = 'none';
                }
            }
        } catch (e) {
            console.error('Error loading announcements:', e);
            if (reset && this.emptyState) {
                this.updateEmptyState();
            }
        } finally {
            this.isLoading = false;
            if (this.loader) {
                this.loader.classList.add('is-hidden');
                this.loader.style.display = 'none';
            }
        }
    }

    updateEmptyState() {
        if (!this.emptyState) return;
        this.emptyState.classList.remove('is-hidden');
        this.emptyState.style.display = 'grid';

        const filterSearch = document.getElementById('filterSearch');
        const hasSearch = filterSearch && filterSearch.value.trim() !== '';

        if (hasSearch) {
            if (this.emptyTitle) this.emptyTitle.textContent = this.emptyState.dataset.searchTitle || 'No Results Found';
            if (this.emptyDesc) this.emptyDesc.textContent = this.emptyState.dataset.searchDesc || 'No announcements match your search criteria.';
            if (this.emptyClearBtn) this.emptyClearBtn.classList.remove('is-hidden');
            if (this.emptyAddBtn) this.emptyAddBtn.classList.add('is-hidden');
        } else {
            if (this.emptyTitle) this.emptyTitle.textContent = this.emptyState.dataset.initialTitle || 'No Announcements Found';
            if (this.emptyDesc) this.emptyDesc.textContent = this.emptyState.dataset.initialDesc || 'No announcements have been created yet.';
            if (this.emptyClearBtn) this.emptyClearBtn.classList.add('is-hidden');
            if (this.emptyAddBtn) this.emptyAddBtn.classList.remove('is-hidden');
        }
    }

    renderCards(items) {
        if (!this.container) return;
        const moduleUrl = getAnnouncementModuleUrl();
        const lang = getAnnouncementLang();

        items.forEach(item => {
            const id = this.stripHtml(item[1] || item[0] || '');
            const name = this.stripHtml(item[2] || item[1] || '');
            const imageHtml = item[3] || item[2] || '';
            const langCode = this.stripHtml(item[4] || item[3] || '').toUpperCase();
            const date = this.stripHtml(item[5] || item[4] || '');
            const statusHtml = item[6] || item[5] || '';

            // Extract Image Src from imageHtml
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = imageHtml;
            const imgTag = tempDiv.querySelector('img');
            const imgSrc = imgTag ? imgTag.src : '';

            // Extract Status
            const isSuccess = statusHtml.indexOf('label-success') !== -1 || statusHtml.indexOf('badge-success') !== -1 || statusHtml.indexOf('y') !== -1;
            const statusClass = isSuccess ? 'status-active' : 'status-draft';
            const statusText = isSuccess ? (lang.active || 'Active') : (lang.draft || 'Draft');

            const thumbInner = imgSrc 
                ? `<img src="${this.escapeHtml(imgSrc)}" class="card-thumb-img" alt="${this.escapeHtml(name)}" loading="lazy">` 
                : `<div class="thumb-empty-placeholder"><i class="bi bi-megaphone"></i><span class="thumb-add-badge"><i class="bi bi-plus"></i></span></div>`;

            const card = document.createElement('div');
            card.className = 'content-card';
            card.dataset.id = id;

            card.innerHTML = `
                <div class="card-content-layout">
                    <div class="card-thumbnail ${imgSrc ? '' : 'empty-thumb'}" 
                         data-action="upload-image" 
                         data-id="${this.escapeHtml(id)}"
                         data-img-src="${this.escapeHtml(imgSrc)}"
                         data-img-name="${this.escapeHtml(name)}"
                         role="button"
                         tabindex="0"
                         aria-label="${this.escapeHtml(lang.upload_change_image || 'Upload / Change Image')}"
                         title="${this.escapeHtml(lang.upload_change_image || 'Upload / Change Image')}">
                        ${thumbInner}
                        <div class="thumb-edit-overlay"><i class="bi bi-camera-fill"></i></div>
                    </div>
                    <div class="card-body-col">
                        <div class="card-header-row">
                            <div class="card-header-main">
                                <div class="card-meta-top">
                                    <span class="card-status-pill ${statusClass}">${this.escapeHtml(statusText)}</span>
                                    <span class="meta-separator">•</span>
                                    <span class="card-lang-pill">${this.escapeHtml(langCode)}</span>
                                </div>
                                <h3 class="card-title-text">${this.escapeHtml(name)}</h3>
                            </div>
                        </div>
                        <div class="card-meta-bottom">
                            <span class="meta-item"><i class="bi bi-calendar3"></i> ${this.escapeHtml(date)}</span>
                        </div>
                    </div>
                </div>
            `;

            // Card interactions
            card.addEventListener('click', (e) => {
                const imgThumb = e.target.closest('[data-action="upload-image"]');
                if (imgThumb) {
                    e.stopPropagation();
                    this.openImageUploader(id, imgSrc, name);
                    return;
                }
                this.selectCard(card, id);
            });

            // Keyboard accessibility for image thumbnail
            const thumbEl = card.querySelector('.card-thumbnail');
            if (thumbEl) {
                thumbEl.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        e.stopPropagation();
                        this.openImageUploader(id, imgSrc, name);
                    }
                });
            }

            card.addEventListener('dblclick', () => {
                location.href = `${moduleUrl}edit/${id}`;
            });

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
        const buttons = ['toolbarEdit', 'toolbarGallery', 'toolbarFiles', 'toolbarDelete'];
        buttons.forEach(btnId => {
            const btn = document.getElementById(btnId);
            if (!btn) return;
            if (active) {
                btn.classList.remove('is-disabled');
                btn.classList.add('active');
            } else {
                btn.classList.add('is-disabled');
                btn.classList.remove('active');
            }
        });
    }

    // Quick Image Modal & 3:4 Cropper Logic
    initListImageUploader() {
        const fileInput = document.getElementById('listImgInput');
        const urlInput = document.getElementById('listImageUrlInput');
        const importBtn = document.getElementById('listImageUrlImportBtn');
        const dropzone = document.getElementById('listImageDropzone');

        if (fileInput) {
            fileInput.addEventListener('change', (e) => {
                const file = e.target.files && e.target.files[0];
                if (file) {
                    this.originalFile = file;
                    this.originalFileName = file.name || 'announcement_image.jpg';
                    this.startCropperFromFile(file);
                }
            });
        }

        if (importBtn && urlInput) {
            importBtn.addEventListener('click', () => {
                const url = urlInput.value.trim();
                if (url) {
                    this.startCropperFromUrl(url);
                }
            });
        }

        if (dropzone) {
            ['dragenter', 'dragover'].forEach(eventName => {
                dropzone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.add('is-dragover');
                }, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.remove('is-dragover');
                }, false);
            });

            dropzone.addEventListener('drop', (e) => {
                const dt = e.dataTransfer;
                const files = dt.files;
                if (files && files.length > 0) {
                    const file = files[0];
                    if (file && file.type.startsWith('image/')) {
                        this.originalFile = file;
                        this.originalFileName = file.name || 'announcement_image.jpg';
                        this.startCropperFromFile(file);
                    }
                }
            }, false);
        }
    }

    startCropperFromFile(file) {
        if (!file || !file.type.startsWith('image/')) return;
        const reader = new FileReader();
        reader.onload = (e) => {
            this.openCropperView(e.target.result);
        };
        reader.readAsDataURL(file);
    }

    async startCropperFromUrl(url) {
        try {
            const res = await fetch(url, { mode: 'cors' });
            if (res.ok) {
                const blob = await res.blob();
                const ext = (url.split('.').pop() || 'jpg').split('?')[0];
                this.originalFile = new File([blob], `remote_image.${ext}`, { type: blob.type || 'image/jpeg' });
                this.originalFileName = `remote_image.${ext}`;
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.openCropperView(e.target.result);
                };
                reader.readAsDataURL(blob);
                return;
            }
        } catch (e) {
            // Fallback for CORS restricted sources
        }
        this.originalFile = null;
        this.originalFileName = url.split('/').pop().split('?')[0] || 'remote_announcement.jpg';
        this.openCropperView(url);
    }

    openCropperView(imageSrc) {
        if (this.uploadShell) this.uploadShell.hidden = true;
        if (this.cropperContainer) this.cropperContainer.hidden = false;
        if (this.imageSaveBtn) this.imageSaveBtn.hidden = true;
        if (this.imageFooter) this.imageFooter.hidden = true;

        if (!this.cropperImage) return;
        this.cropperImage.src = imageSrc;

        const startCropper = () => {
            this.destroyCropper();
            if (typeof Cropper !== 'undefined') {
                this.cropperInstance = new Cropper(this.cropperImage, {
                    aspectRatio: 600 / 780, // Exact 3:4 portrait ratio for announcements
                    viewMode: 1,
                    autoCropArea: 0.95,
                    dragMode: 'move',
                    center: true,
                    guides: true,
                    background: false,
                    responsive: true,
                    cropBoxResizable: true,
                    toggleDragModeOnDblclick: false
                });
            } else {
                console.warn('Cropper.js is not loaded');
            }
        };

        if (this.cropperImage.complete) {
            startCropper();
        } else {
            this.cropperImage.onload = startCropper;
        }
    }

    destroyCropper() {
        if (this.cropperInstance) {
            this.cropperInstance.destroy();
            this.cropperInstance = null;
        }
    }

    resetCropperToUploadView() {
        this.destroyCropper();
        this.originalFile = null;
        if (this.uploadShell) this.uploadShell.hidden = false;
        if (this.cropperContainer) this.cropperContainer.hidden = true;
        if (this.imageFooter) this.imageFooter.hidden = false;
        if (this.imageSaveBtn) this.imageSaveBtn.hidden = true;

        const fileInput = document.getElementById('listImgInput');
        if (fileInput) fileInput.value = '';
        const urlInput = document.getElementById('listImageUrlInput');
        if (urlInput) urlInput.value = '';
    }

    handleCropperAction(action) {
        if (!action) return;
        switch (action) {
            case 'zoom-in':
                this.cropperInstance?.zoom(0.1);
                break;
            case 'zoom-out':
                this.cropperInstance?.zoom(-0.1);
                break;
            case 'rotate-left':
                this.cropperInstance?.rotate(-90);
                break;
            case 'rotate-right':
                this.cropperInstance?.rotate(90);
                break;
            case 'reset':
                this.cropperInstance?.reset();
                break;
            case 'cancel-crop':
                this.resetCropperToUploadView();
                break;
            case 'apply-crop':
                this.saveCroppedAnnouncementImage();
                break;
        }
    }

    async saveCroppedAnnouncementImage() {
        if (!this.imageUploadId) return;
        const lang = getAnnouncementLang();

        if (this.cropApplyBtn) {
            this.cropApplyBtn.disabled = true;
            this.cropApplyBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>...`;
        }

        let croppedBlob = null;
        if (this.cropperInstance) {
            const canvas = this.cropperInstance.getCroppedCanvas({
                width: 600,
                height: 780,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
                fillColor: '#ffffff'
            });
            if (canvas) {
                croppedBlob = await new Promise(resolve => canvas.toBlob(resolve, 'image/webp', 0.92));
            }
        }

        if (!croppedBlob && this.originalFile) {
            croppedBlob = this.originalFile;
        }

        if (!croppedBlob) {
            alert(lang.error_upload || 'No image to save');
            if (this.cropApplyBtn) {
                this.cropApplyBtn.disabled = false;
                this.cropApplyBtn.innerHTML = `<i class="bi bi-check-lg"></i><span>${lang.cropper?.apply || 'Crop & Save Image'}</span>`;
            }
            return;
        }

        const formData = new FormData();
        formData.append('id', this.imageUploadId);
        const baseName = (this.originalFileName || 'announcement_image').replace(/\.[^/.]+$/, "");
        formData.append('image', croppedBlob, `${baseName}.webp`);

        // Preserve and send original uncropped file
        if (this.originalFile) {
            formData.append('image_original', this.originalFile, this.originalFileName);
        }

        const moduleUrl = getAnnouncementModuleUrl();
        try {
            const response = await fetch(`${moduleUrl}ajax_image_upload`, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const result = await response.json();
            if (result && result.status === 'success') {
                if (window.navigator.vibrate) window.navigator.vibrate(15);
                this.closeImageUploader();
                this.loadData(true);
                announcementToast(lang.image_updated || 'Image updated successfully', 'success');
            } else {
                alert(result.message || lang.error_upload || 'Upload failed');
            }
        } catch (e) {
            console.error('Upload error:', e);
            alert(lang.error_upload || 'Upload failed');
        } finally {
            if (this.cropApplyBtn) {
                this.cropApplyBtn.disabled = false;
                this.cropApplyBtn.innerHTML = `<i class="bi bi-check-lg"></i><span>${lang.cropper?.apply || 'Crop & Save Image'}</span>`;
            }
        }
    }

    openImageUploader(id, imgSrc, name) {
        this.imageUploadId = id;
        this.imageModalState = {
            hasExistingImage: Boolean(imgSrc),
            existingImageSrc: imgSrc || '',
            existingImageName: name || ''
        };

        document.body.classList.add('announcement-image-modal-open');
        this.resetCropperToUploadView();

        if (this.imageModalSubtitle) {
            this.imageModalSubtitle.textContent = name ? `– ${name}` : '';
            this.imageModalSubtitle.hidden = !name;
        }

        const previewCard = document.getElementById('listImagePreviewCard');
        const previewImg = document.getElementById('listImgPreview');
        const emptyState = document.getElementById('listImageEmptyState');
        const fileNameEl = document.getElementById('listImageFileName');
        const fileDetailsEl = document.getElementById('listImageFileDetails');
        const removeBtn = previewCard?.querySelector('.wizard-image-remove-btn');

        if (imgSrc) {
            if (previewCard) previewCard.hidden = false;
            if (previewImg) {
                previewImg.src = imgSrc;
                previewImg.hidden = false;
            }
            if (emptyState) emptyState.hidden = true;
            if (fileNameEl) fileNameEl.textContent = name || 'Current Image';
            if (fileDetailsEl) fileDetailsEl.textContent = '3:4 Announcement Ratio';
            if (this.imageMeta) this.imageMeta.hidden = false;
            if (removeBtn) removeBtn.hidden = false;
        } else {
            if (previewCard) previewCard.hidden = true;
            if (previewImg) {
                previewImg.src = '';
                previewImg.hidden = true;
            }
            if (emptyState) emptyState.hidden = false;
            if (this.imageMeta) this.imageMeta.hidden = true;
        }

        if (this.imageSaveBtn) {
            this.imageSaveBtn.disabled = true;
            this.imageSaveBtn.hidden = true;
        }

        if (this.imageModal) {
            this.imageModal.classList.add('active');
            this.imageModal.setAttribute('aria-hidden', 'false');
        }
    }

    closeImageUploader() {
        this.destroyCropper();
        document.body.classList.remove('announcement-image-modal-open');

        if (this.imageModal) {
            this.imageModal.classList.remove('active');
            this.imageModal.setAttribute('aria-hidden', 'true');
        }
        this.imageUploadId = null;
        this.originalFile = null;
        const fileInput = document.getElementById('listImgInput');
        if (fileInput) fileInput.value = '';
        const urlInput = document.getElementById('listImageUrlInput');
        if (urlInput) urlInput.value = '';
    }

    async handleListImageRemoveAction() {
        if (!this.imageUploadId) return;
        const lang = getAnnouncementLang();
        const confirmed = await announcementConfirm(
            lang.image_remove_title || 'Remove Image',
            lang.image_remove_confirm || 'Are you sure you want to remove this image?',
            lang.delete_confirm_text || 'Yes, Remove',
            lang.cancel || 'Cancel'
        );
        if (!confirmed) return;

        const moduleUrl = getAnnouncementModuleUrl();
        const formData = new FormData();
        formData.append('id', this.imageUploadId);

        try {
            const response = await fetch(`${moduleUrl}image_remove_image`, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (response.ok) {
                if (window.navigator.vibrate) window.navigator.vibrate(10);
                this.closeImageUploader();
                this.loadData(true);
                announcementToast(lang.image_remove_success || 'Image removed successfully', 'success');
            } else {
                alert(lang.error_general || 'Failed to remove image');
            }
        } catch (e) {
            console.error(e);
            alert(lang.error_general || 'Failed to remove image');
        }
    }
}

/**
 * Announcement Form Manager (Add & Edit Screens)
 */
class AnnouncementFormManager {
    constructor() {
        this.form = document.getElementById('announcementAddForm') || document.getElementById('announcementEditForm');
        if (!this.form) return;

        this.nameInput = document.getElementById('announcement-name');
        this.seoInput = document.getElementById('announcement-seo');
        this.summaryInput = document.getElementById('announcement-summary');
        this.fileInput = document.getElementById('editImgInput');
        this.originalFileInput = document.getElementById('editImgOriginalInput');
        this.urlInput = document.getElementById('editImageUrlInput');
        this.importBtn = document.getElementById('editImageUrlImportBtn');
        this.dropzone = document.getElementById('editImageDropzone');
        this.uploadShell = document.getElementById('editImageShell');
        this.cropperContainer = document.getElementById('announcementFormCropperContainer');
        this.cropperImage = document.getElementById('announcementFormCropperImage');
        this.previewCard = document.getElementById('editImagePreviewCard');
        this.previewImg = document.getElementById('editImgPreview');
        this.emptyState = document.getElementById('editImageEmptyState');
        this.fileNameEl = document.getElementById('editImageFileName');
        this.fileDetailsEl = document.getElementById('editImageFileDetails');

        this.cropperInstance = null;
        this.originalFile = null;
        this.originalFileName = 'announcement_image.jpg';
        this.croppedBlob = null;
        this.isSubmitting = false;

        this.init();
    }

    init() {
        this.initMetaCounters();
        this.initSelectionCards();
        this.initImageInteractions();
        this.initFormSubmit();
        if (typeof window.initRichEditor === 'function') {
            window.initRichEditor('#announcement-content', 400);
        }
    }

    initMetaCounters() {
        if (this.nameInput) {
            this.nameInput.addEventListener('input', () => {
                this.syncSeo();
                this.updateCharCounter('announcement-name', 'name-counter', 255);
            });
            this.updateCharCounter('announcement-name', 'name-counter', 255);
        }
        if (this.seoInput) {
            this.seoInput.addEventListener('input', () => {
                this.seoInput.dataset.touched = '1';
                this.updateCharCounter('announcement-seo', 'seo-counter', 255);
            });
            this.updateCharCounter('announcement-seo', 'seo-counter', 255);
        }
        if (this.summaryInput) {
            this.summaryInput.addEventListener('input', () => {
                this.updateCharCounter('announcement-summary', 'summary-counter', 250);
            });
            this.updateCharCounter('announcement-summary', 'summary-counter', 250);
        }
    }

    syncSeo() {
        if (!this.nameInput || !this.seoInput || this.seoInput.dataset.touched) return;
        this.seoInput.value = typeof makeLegacySeoValue === 'function'
            ? makeLegacySeoValue(this.nameInput.value)
            : this.nameInput.value.replace(/['’"”„“]/g, '').replace(/[^\p{L}\p{N}\s_-]/gu, ' ').trim().replace(/[\s-]+/g, '_').replace(/_+/g, '_');
        this.updateCharCounter('announcement-seo', 'seo-counter', 255);
    }

    updateCharCounter(inputId, counterId, maxLen) {
        const input = document.getElementById(inputId);
        const counter = document.getElementById(counterId);
        if (input && counter) {
            const len = input.value.length;
            counter.textContent = `${len} / ${maxLen}`;
            if (len > maxLen) {
                counter.style.color = 'var(--md-sys-color-error, #b3261e)';
            } else {
                counter.style.color = 'var(--md-sys-color-on-surface-variant)';
            }
        }
    }

    initSelectionCards() {
        document.querySelectorAll('[data-selection-group]').forEach((group) => {
            group.addEventListener('change', (event) => {
                if (event.target.type !== 'radio') return;
                group.querySelectorAll('.selection-card').forEach((card) => {
                    const isActive = card.contains(event.target);
                    card.classList.toggle('active', isActive);
                    const icon = card.querySelector('.selection-check-icon');
                    if (icon) icon.style.display = isActive ? 'block' : 'none';
                });
            });
        });
    }

    initImageInteractions() {
        if (this.fileInput) {
            this.fileInput.addEventListener('change', (e) => {
                const file = e.target.files && e.target.files[0];
                if (file) {
                    this.originalFile = file;
                    this.originalFileName = file.name || 'announcement_image.jpg';
                    this.startCropperFromFile(file);
                }
            });
        }

        if (this.importBtn && this.urlInput) {
            this.importBtn.addEventListener('click', () => {
                const url = this.urlInput.value.trim();
                if (url) {
                    this.startCropperFromUrl(url);
                }
            });
        }

        if (this.dropzone) {
            ['dragenter', 'dragover'].forEach(eventName => {
                this.dropzone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    this.dropzone.classList.add('is-dragover');
                }, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                this.dropzone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    this.dropzone.classList.remove('is-dragover');
                }, false);
            });

            this.dropzone.addEventListener('drop', (e) => {
                const dt = e.dataTransfer;
                const files = dt.files;
                if (files && files.length > 0) {
                    const file = files[0];
                    if (file && file.type.startsWith('image/')) {
                        this.originalFile = file;
                        this.originalFileName = file.name || 'announcement_image.jpg';
                        this.startCropperFromFile(file);
                    }
                }
            }, false);
        }

        document.addEventListener('click', (e) => {
            const cropTarget = e.target.closest('[data-form-crop-action]');
            if (cropTarget) {
                const cropAction = cropTarget.dataset.formCropAction;
                this.handleCropperAction(cropAction);
            }
        });
    }

    startCropperFromFile(file) {
        if (!file || !file.type.startsWith('image/')) return;
        const reader = new FileReader();
        reader.onload = (e) => {
            this.openCropperView(e.target.result);
        };
        reader.readAsDataURL(file);
    }

    async startCropperFromUrl(url) {
        try {
            const res = await fetch(url, { mode: 'cors' });
            if (res.ok) {
                const blob = await res.blob();
                const ext = (url.split('.').pop() || 'jpg').split('?')[0];
                this.originalFile = new File([blob], `remote_image.${ext}`, { type: blob.type || 'image/jpeg' });
                this.originalFileName = `remote_image.${ext}`;
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.openCropperView(e.target.result);
                };
                reader.readAsDataURL(blob);
                return;
            }
        } catch (e) {
            // CORS fallback
        }
        this.originalFile = null;
        this.originalFileName = url.split('/').pop().split('?')[0] || 'remote_announcement.jpg';
        this.openCropperView(url);
    }

    openCropperView(imageSrc) {
        if (this.uploadShell) this.uploadShell.hidden = true;
        if (this.cropperContainer) this.cropperContainer.hidden = false;
        if (!this.cropperImage) return;

        this.cropperImage.src = imageSrc;

        const startCropper = () => {
            this.destroyCropper();
            if (typeof Cropper !== 'undefined') {
                this.cropperInstance = new Cropper(this.cropperImage, {
                    aspectRatio: 600 / 780, // Exact 3:4 portrait ratio for announcements
                    viewMode: 1,
                    autoCropArea: 0.95,
                    dragMode: 'move',
                    center: true,
                    guides: true,
                    background: false,
                    responsive: true,
                    cropBoxResizable: true,
                    toggleDragModeOnDblclick: false
                });
            } else {
                console.warn('Cropper.js is not loaded');
            }
        };

        if (this.cropperImage.complete) {
            startCropper();
        } else {
            this.cropperImage.onload = startCropper;
        }
    }

    destroyCropper() {
        if (this.cropperInstance) {
            this.cropperInstance.destroy();
            this.cropperInstance = null;
        }
    }

    resetCropperToUploadView() {
        this.destroyCropper();
        this.originalFile = null;
        this.croppedBlob = null;
        if (this.uploadShell) this.uploadShell.hidden = false;
        if (this.cropperContainer) this.cropperContainer.hidden = true;

        if (this.fileInput) this.fileInput.value = '';
        if (this.originalFileInput) this.originalFileInput.value = '';
        if (this.urlInput) this.urlInput.value = '';
    }

    handleCropperAction(action) {
        if (!action) return;
        switch (action) {
            case 'zoom-in':
                this.cropperInstance?.zoom(0.1);
                break;
            case 'zoom-out':
                this.cropperInstance?.zoom(-0.1);
                break;
            case 'rotate-left':
                this.cropperInstance?.rotate(-90);
                break;
            case 'rotate-right':
                this.cropperInstance?.rotate(90);
                break;
            case 'reset':
                this.cropperInstance?.reset();
                break;
            case 'cancel-crop':
                this.resetCropperToUploadView();
                break;
            case 'apply-crop':
                this.applyCropPreview();
                break;
        }
    }

    async applyCropPreview() {
        if (!this.cropperInstance) return;

        const canvas = this.cropperInstance.getCroppedCanvas({
            width: 600,
            height: 780,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
            fillColor: '#ffffff'
        });

        if (canvas) {
            this.croppedBlob = await new Promise(resolve => canvas.toBlob(resolve, 'image/webp', 0.92));
            const previewDataUrl = canvas.toDataURL('image/webp', 0.92);

            if (this.previewCard) this.previewCard.hidden = false;
            if (this.previewImg) {
                this.previewImg.src = previewDataUrl;
                this.previewImg.hidden = false;
            }
            if (this.emptyState) this.emptyState.hidden = true;
            if (this.fileNameEl) this.fileNameEl.textContent = this.originalFileName || 'Cropped Image (3:4)';
            if (this.fileDetailsEl) this.fileDetailsEl.textContent = `${(this.croppedBlob.size / 1024).toFixed(1)} KB (3:4)`;

            if (this.uploadShell) this.uploadShell.hidden = false;
            if (this.cropperContainer) this.cropperContainer.hidden = true;
            if (window.navigator.vibrate) window.navigator.vibrate(5);
        }
    }

    initFormSubmit() {
        if (!this.form) return;

        this.form.addEventListener('submit', async (e) => {
            if (this.isSubmitting) return;

            // Trigger TinyMCE save if present
            if (typeof tinymce !== 'undefined') {
                tinymce.triggerSave();
            }

            // If cropper is still active in view, automatically apply crop before submit
            if (this.cropperInstance && !this.croppedBlob) {
                e.preventDefault();
                await this.applyCropPreview();
            }

            if (this.croppedBlob && this.fileInput) {
                e.preventDefault();
                this.isSubmitting = true;

                const baseName = (this.originalFileName || 'announcement_image').replace(/\.[^/.]+$/, "");
                const croppedFile = new File([this.croppedBlob], `${baseName}.webp`, { type: 'image/webp' });

                // Attach cropped file to fileInput via DataTransfer
                try {
                    const dt = new DataTransfer();
                    dt.items.add(croppedFile);
                    this.fileInput.files = dt.files;
                } catch (err) {
                    console.warn('DataTransfer not supported:', err);
                }

                // Attach original file to originalFileInput via DataTransfer if available
                if (this.originalFile && this.originalFileInput) {
                    try {
                        const dtOrig = new DataTransfer();
                        dtOrig.items.add(this.originalFile);
                        this.originalFileInput.files = dtOrig.files;
                    } catch (err) {
                        console.warn('DataTransfer orig not supported:', err);
                    }
                }

                const submitBtn = document.getElementById('announcementSubmitBtn');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>...`;
                }

                this.form.submit();
            }
        });
    }

    async removeImage() {
        const shell = this.uploadShell;
        const removeUrl = shell?.dataset?.removeUrl;
        const removeId = shell?.dataset?.removeId;
        const confirmMsg = shell?.dataset?.removeConfirm || 'Are you sure you want to remove this image?';
        const lang = getAnnouncementLang();

        if (removeUrl && removeId) {
            const confirmed = await announcementConfirm(
                lang.image_remove_title || 'Remove Image',
                confirmMsg,
                lang.delete_confirm_text || 'Yes, Remove',
                lang.cancel || 'Cancel'
            );
            if (!confirmed) return;

            const formData = new FormData();
            formData.append('id', removeId);

            try {
                const res = await fetch(removeUrl, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (res.ok) {
                    if (window.navigator.vibrate) window.navigator.vibrate(10);
                    announcementToast(lang.image_remove_success || 'Image removed successfully', 'success');
                    this.clearImagePreview();
                } else {
                    alert('Failed to remove image');
                }
            } catch (e) {
                console.error(e);
                alert('Failed to remove image');
            }
        } else {
            this.clearImagePreview();
        }
    }

    clearImagePreview() {
        this.resetCropperToUploadView();
        if (this.previewCard) this.previewCard.hidden = true;
        if (this.previewImg) {
            this.previewImg.src = '';
            this.previewImg.hidden = true;
        }
        if (this.emptyState) this.emptyState.hidden = false;
        if (this.fileNameEl) this.fileNameEl.textContent = 'No image selected';
        if (this.fileDetailsEl) this.fileDetailsEl.textContent = 'Upload file, paste, or drag and drop.';
    }
}

// Global instance initialization
let announcementsManager;
let announcementFormManager;

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('announcements-list-container')) {
        announcementsManager = new AnnouncementsManager();
        window.announcementsManager = announcementsManager;
    }

    if (document.getElementById('announcementAddForm') || document.getElementById('announcementEditForm')) {
        announcementFormManager = new AnnouncementFormManager();
        window.announcementFormManager = announcementFormManager;
    }
});

// Global Toolbar Action Handlers
function editSelected() {
    if (window.announcementsManager && window.announcementsManager.selectedId) {
        const moduleUrl = getAnnouncementModuleUrl();
        location.href = `${moduleUrl}edit/${window.announcementsManager.selectedId}`;
    }
}

function gallerySelected() {
    if (window.announcementsManager && window.announcementsManager.selectedId) {
        const siteUrl = (typeof window.SITE_URL !== 'undefined' && window.SITE_URL) ? window.SITE_URL : '/';
        const url = `${siteUrl}mod_gallery/list/mod_announcements/${window.announcementsManager.selectedId}`;
        if (window.AFModalEngine) {
            AFModalEngine.push(url, { skipHistory: true });
        } else {
            location.href = url;
        }
    }
}

function filesSelected() {
    if (window.announcementsManager && window.announcementsManager.selectedId) {
        const siteUrl = (typeof window.SITE_URL !== 'undefined' && window.SITE_URL) ? window.SITE_URL : '/';
        const url = `${siteUrl}mod_attachment/list/mod_announcements/${window.announcementsManager.selectedId}`;
        if (window.AFModalEngine) {
            AFModalEngine.push(url, { skipHistory: true });
        } else {
            location.href = url;
        }
    }
}

async function deleteSelected() {
    if (!window.announcementsManager || !window.announcementsManager.selectedId) return;
    const lang = getAnnouncementLang();
    const confirmed = await announcementConfirm(
        lang.button?.delete || 'Delete Announcement',
        lang.confirm_delete || 'Are you sure you want to delete this record?',
        lang.delete_confirm_text || 'Yes, Delete',
        lang.cancel || 'Cancel'
    );
    if (!confirmed) return;

    const moduleUrl = getAnnouncementModuleUrl();
    const formData = new FormData();
    formData.append('delete[]', window.announcementsManager.selectedId);

    try {
        const res = await fetch(`${moduleUrl}delete`, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const text = await res.text();
        if (text.trim() === 'OK' || res.ok) {
            if (window.navigator.vibrate) window.navigator.vibrate(10);
            window.announcementsManager.loadData(true);
        } else {
            alert(text || lang.error_general || 'An error occurred.');
        }
    } catch (e) {
        console.error(e);
        alert(lang.error_general || 'An error occurred.');
    }
}
