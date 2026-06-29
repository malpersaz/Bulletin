/**
 * Gallery Management Module JavaScript
 * Amed Framework CMS - Modern M3 UI
 */

const GalleryManager = {
    config: {},
    items: [],
    selectedFiles: [],
    selectedImageIds: [],
    selectionMode: false,
    activeImageId: null,
    uploadCounter: 0,
    isUploading: false,
    autoStartTimer: null,
    beforeUnloadRegistered: false,

    init: function(config) {
        this.config = config || {};
        this.items = [];
        this.selectedFiles = [];
        this.selectedImageIds = [];
        this.selectionMode = false;
        this.activeImageId = null;
        this.uploadCounter = 0;
        this.isUploading = false;
        this.autoStartTimer = null;
        if (!this.beforeUnloadRegistered) {
            window.addEventListener('beforeunload', this.handleBeforeUnload.bind(this));
            this.beforeUnloadRegistered = true;
        }
        this.updateUploadSheetState();
    },

    escapeHtml: function(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    },

    escapeJsString: function(value) {
        return String(value ?? '')
            .replace(/\\/g, '\\\\')
            .replace(/'/g, "\\'");
    },

    formatFileSize: function(size) {
        const bytes = Number(size || 0);
        if (bytes < 1024 * 1024) {
            return `${(bytes / 1024).toFixed(1)} KB`;
        }

        return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
    },

    notify: function(message, type) {
        if (!message) {
            return;
        }

        if (typeof showSnackbar === 'function') {
            showSnackbar(message, type === 'error' ? 'error' : type === 'warning' ? 'warning' : type === 'success' ? 'success' : 'info');
            return;
        }

        if (typeof Amed !== 'undefined' && Amed && typeof Amed.notify === 'function') {
            Amed.notify(message, type);
            return;
        }

        const fallback = type === 'error' ? 'error' : 'log';
        console[fallback](message);
    },

    getQueueStatusLabel: function(status) {
        switch (status) {
            case 'uploading':
                return this.config.lang.queue_uploading;
            case 'success':
                return this.config.lang.queue_success;
            case 'error':
                return this.config.lang.queue_failed;
            case 'waiting':
            default:
                return this.config.lang.queue_waiting;
        }
    },

    formatCountLabel: function(count) {
        return this.config.lang.images_summary.replace('%s', count);
    },

    formatSelectionLabel: function(count) {
        return this.config.lang.selected_summary.replace('%s', count);
    },

    updateVisibleImageCount: function(count) {
        const heroCount = document.getElementById('galleryVisibleCount');
        const toolbarCount = document.getElementById('galleryToolbarCount');
        const label = this.formatCountLabel(count);

        if (heroCount) {
            heroCount.textContent = label;
        }

        if (toolbarCount) {
            toolbarCount.textContent = label;
        }
    },

    updateSelectionUI: function() {
        const board = document.getElementById('galleryBoard');
        const toggle = document.getElementById('gallerySelectionToggle');
        const countPill = document.getElementById('gallerySelectionCount');
        const countLabel = countPill ? countPill.querySelector('span') : null;
        const countSheetPill = document.getElementById('gallerySelectionCountSheet');
        const countSheetLabel = countSheetPill ? countSheetPill.querySelector('span') : null;
        const hint = document.getElementById('galleryBoardHint');
        const bottomBar = document.querySelector('.gallery-bottom-bar');

        if (board) {
            board.classList.toggle('is-selection-mode', this.selectionMode);
        }

        if (toggle) {
            toggle.innerHTML = this.selectionMode
                ? `<i class="bi bi-x-lg"></i>`
                : `<i class="bi bi-check2-square"></i>`;
            toggle.setAttribute('title', this.selectionMode ? this.config.lang.done_selecting : this.config.lang.select_mode);
            toggle.setAttribute('aria-label', this.selectionMode ? this.config.lang.done_selecting : this.config.lang.select_mode);
        }

        if (countPill && countLabel) {
            countLabel.textContent = this.formatSelectionLabel(this.selectedImageIds.length);
            countPill.classList.toggle('is-visible', this.selectionMode);
        }

        if (countSheetPill && countSheetLabel) {
            countSheetLabel.textContent = this.formatSelectionLabel(this.selectedImageIds.length);
            countSheetPill.classList.toggle('is-visible', this.selectionMode);
        }

        if (hint) {
            hint.textContent = this.selectionMode
                ? this.config.lang.selected_summary.replace('%s', this.selectedImageIds.length)
                : this.config.lang.edit_hint;
        }

        if (bottomBar) {
            bottomBar.classList.toggle('is-hidden', this.selectionMode);
        }

        this.setSelectionSheetOpen(this.selectionMode);
    },

    getImageElements: function() {
        return Array.from(document.querySelectorAll('.gallery-item'));
    },

    getVisibleImageElements: function() {
        return this.getImageElements().filter((item) => !item.classList.contains('is-hidden'));
    },

    getImageElementById: function(id) {
        return document.getElementById(`image-${id}`);
    },

    getImageData: function(id) {
        const item = this.getImageElementById(id);
        if (!item) {
            return null;
        }

        const visibleItems = this.getVisibleImageElements();
        const visibleIndex = visibleItems.findIndex((element) => String(element.dataset.id) === String(id));

        return {
            id: String(item.dataset.id || ''),
            name: String(item.dataset.originalName || ''),
            originalUrl: String(item.dataset.originalUrl || ''),
            thumbUrl: String(item.dataset.thumbUrl || ''),
            position: visibleIndex >= 0 ? visibleIndex + 1 : null,
            totalVisible: visibleItems.length
        };
    },

    clearActiveImageHighlight: function() {
        document.querySelectorAll('.gallery-item.is-active').forEach((item) => item.classList.remove('is-active'));
    },

    updateDetailPanel: function() {
        const detailImage = document.getElementById('galleryDetailImage');
        const detailEmpty = document.getElementById('galleryDetailEmpty');
        const detailTitle = document.getElementById('galleryDetailTitle');
        const detailFilename = document.getElementById('galleryDetailFilename');
        const detailId = document.getElementById('galleryDetailId');
        const detailPosition = document.getElementById('galleryDetailPosition');
        const data = this.activeImageId ? this.getImageData(this.activeImageId) : null;

        this.clearActiveImageHighlight();

        if (!data) {
            this.activeImageId = null;

            if (detailImage) {
                detailImage.removeAttribute('src');
                detailImage.style.display = 'none';
            }

            if (detailEmpty) {
                detailEmpty.style.display = 'block';
                detailEmpty.textContent = this.config.lang.detail_empty;
            }

            if (detailTitle) {
                detailTitle.textContent = this.config.lang.detail_title;
            }

            if (detailFilename) {
                detailFilename.textContent = '-';
            }

            if (detailId) {
                detailId.textContent = '-';
            }

            if (detailPosition) {
                detailPosition.textContent = '-';
            }

            return;
        }

        const activeElement = this.getImageElementById(data.id);
        if (activeElement) {
            activeElement.classList.add('is-active');
        }

        if (detailImage) {
            detailImage.src = data.originalUrl;
            detailImage.style.display = 'block';
        }

        if (detailEmpty) {
            detailEmpty.style.display = 'none';
        }

        if (detailTitle) {
            detailTitle.textContent = data.name;
        }

        if (detailFilename) {
            detailFilename.textContent = data.name;
        }

        if (detailId) {
            detailId.textContent = data.id;
        }

        if (detailPosition) {
            detailPosition.textContent = data.position && data.totalVisible
                ? `${data.position} / ${data.totalVisible}`
                : '-';
        }
    },

    setDetailSheetOpen: function(isOpen) {
        const sheet = document.getElementById('galleryDetailSheet');
        if (!sheet) {
            return;
        }

        sheet.classList.toggle('active', Boolean(isOpen));
    },

    closeDetailSheet: function() {
        this.setDetailSheetOpen(false);
    },

    setSelectionSheetOpen: function(isOpen) {
        const sheet = document.getElementById('gallerySelectionSheet');
        if (!sheet) {
            return;
        }

        sheet.classList.toggle('active', Boolean(isOpen));
    },

    exitSelectionMode: function() {
        if (!this.selectionMode) {
            return;
        }

        this.selectionMode = false;
        this.clearSelection();
    },

    setToolsSheetOpen: function(isOpen) {
        const sheet = document.getElementById('galleryToolsSheet');
        const bottomBar = document.querySelector('.gallery-bottom-bar');
        if (!sheet) {
            return;
        }

        sheet.classList.toggle('active', Boolean(isOpen));
        if (bottomBar && !this.selectionMode) {
            bottomBar.classList.toggle('is-hidden', Boolean(isOpen));
        }
    },

    openToolsSheet: function() {
        this.exitSelectionMode();
        this.setDetailSheetOpen(false);
        this.setToolsSheetOpen(true);
    },

    closeToolsSheet: function() {
        this.setToolsSheetOpen(false);
    },

    openImageDetail: function(id) {
        this.activeImageId = String(id);
        this.updateDetailPanel();
        this.setDetailSheetOpen(true);
    },

    openFirstVisibleImage: function() {
        const firstVisible = this.getVisibleImageElements()[0];
        if (firstVisible) {
            this.activeImageId = firstVisible.dataset.id;
            this.updateDetailPanel();
        } else {
            this.updateDetailPanel();
        }
    },

    handleImageCardClick: function(id) {
        if (this.selectionMode) {
            this.toggleImageSelection(id);
            return;
        }

        this.openImageDetail(id);
    },

    getActiveImageIdFromVisibleList: function(direction) {
        const visibleItems = this.getVisibleImageElements();
        if (visibleItems.length === 0) {
            return null;
        }

        const currentIndex = visibleItems.findIndex((item) => String(item.dataset.id) === String(this.activeImageId));
        if (currentIndex === -1) {
            return visibleItems[0].dataset.id;
        }

        const nextIndex = (currentIndex + direction + visibleItems.length) % visibleItems.length;
        return visibleItems[nextIndex].dataset.id;
    },

    showPreviousImage: function() {
        const id = this.getActiveImageIdFromVisibleList(-1);
        if (id) {
            this.openImageDetail(id);
        }
    },

    showNextImage: function() {
        const id = this.getActiveImageIdFromVisibleList(1);
        if (id) {
            this.openImageDetail(id);
        }
    },

    toggleSelectionMode: function() {
        this.selectionMode = !this.selectionMode;
        if (!this.selectionMode) {
            this.clearSelection();
        }
        this.setDetailSheetOpen(false);
        this.setToolsSheetOpen(false);
        this.updateSelectionUI();
    },

    toggleImageSelection: function(id) {
        if (!this.selectionMode) {
            return;
        }

        const item = this.getImageElementById(id);
        const button = item ? item.querySelector('.gallery-item__select') : null;
        const index = this.selectedImageIds.indexOf(String(id));

        if (index >= 0) {
            this.selectedImageIds.splice(index, 1);
            if (item) item.classList.remove('is-selected');
            if (button) button.classList.remove('is-selected');
        } else {
            this.selectedImageIds.push(String(id));
            if (item) item.classList.add('is-selected');
            if (button) button.classList.add('is-selected');
        }

        this.updateSelectionUI();
    },

    clearSelection: function() {
        this.selectedImageIds = [];
        document.querySelectorAll('.gallery-item.is-selected').forEach((item) => item.classList.remove('is-selected'));
        document.querySelectorAll('.gallery-item__select.is-selected').forEach((item) => item.classList.remove('is-selected'));
        this.updateSelectionUI();
    },

    selectAllVisible: function() {
        if (!this.selectionMode) {
            return;
        }

        this.selectedImageIds = [];
        this.getImageElements().forEach((item) => {
            const isVisible = !item.classList.contains('is-hidden');
            const selectButton = item.querySelector('.gallery-item__select');

            item.classList.toggle('is-selected', isVisible);
            if (selectButton) {
                selectButton.classList.toggle('is-selected', isVisible);
            }

            if (isVisible) {
                this.selectedImageIds.push(String(item.dataset.id));
            }
        });

        this.updateSelectionUI();
    },

    syncActiveImageAfterFilter: function() {
        const visibleItems = this.getVisibleImageElements();

        if (visibleItems.length === 0) {
            this.activeImageId = null;
            this.updateDetailPanel();
            return;
        }

        const activeStillVisible = visibleItems.some((item) => String(item.dataset.id) === String(this.activeImageId));
        if (!activeStillVisible) {
            this.activeImageId = visibleItems[0].dataset.id;
        }

        this.updateDetailPanel();
    },

    filterImages: function(query) {
        const normalized = String(query || '').trim().toLowerCase();
        const items = this.getImageElements();
        const emptyState = document.getElementById('galleryEmptySearch');
        let visible = 0;

        items.forEach((item) => {
            const name = String(item.dataset.name || '').toLowerCase();
            const matches = normalized === '' || name.includes(normalized);
            item.classList.toggle('is-hidden', !matches);
            if (matches) {
                visible += 1;
            }
        });

        this.updateVisibleImageCount(visible);
        this.syncActiveImageAfterFilter();

        if (emptyState) {
            emptyState.classList.toggle('is-visible', items.length > 0 && visible === 0);
        }
    },

    sortImages: function(sortKey) {
        const grid = document.getElementById('galleryGrid');
        if (!grid) {
            return;
        }

        const items = Array.from(grid.querySelectorAll('.gallery-item'));
        items.sort((a, b) => {
            const nameA = String(a.dataset.originalName || '').toLowerCase();
            const nameB = String(b.dataset.originalName || '').toLowerCase();
            const idA = Number(a.dataset.id || 0);
            const idB = Number(b.dataset.id || 0);

            switch (sortKey) {
                case 'oldest':
                    return idA - idB;
                case 'name_asc':
                    return nameA.localeCompare(nameB);
                case 'name_desc':
                    return nameB.localeCompare(nameA);
                case 'newest':
                default:
                    return idB - idA;
            }
        });

        items.forEach((item) => grid.appendChild(item));
        this.filterImages(document.getElementById('galleryImageSearch')?.value || '');
    },

    loadGalleries: async function() {
        const grid = document.getElementById('galleryGrid');
        if (!grid) {
            return;
        }

        try {
            const response = await fetch(this.config.listUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: new URLSearchParams({ query: '' }).toString()
            });
            const result = await response.json();
            this.items = Array.isArray(result.data) ? result.data : [];
            this.renderGalleries(this.items);
        } catch (error) {
            console.error('Failed to load galleries', error);
            grid.innerHTML = `
                <div style="grid-column: 1/-1; text-align: center; color: var(--md-sys-color-error); padding: 40px;">
                    ${this.escapeHtml(this.config.lang.load_failed)}
                </div>
            `;
        }
    },

    renderGalleries: function(items) {
        const grid = document.getElementById('galleryGrid');
        if (!grid) {
            return;
        }

        if (items.length === 0) {
            grid.innerHTML = `
                <div style="grid-column: 1/-1; padding: 100px; text-align: center; color: var(--md-sys-color-on-surface-variant);">
                    <i class="bi bi-folder2-open" style="font-size: 48px; opacity: 0.5;"></i>
                    <p style="margin-top: 16px;">${this.escapeHtml(this.config.currentQuery ? this.config.lang.no_results : this.config.lang.no_galleries)}</p>
                </div>
            `;
            return;
        }

        grid.innerHTML = items.map((item) => this.renderCard(item)).join('');
    },

    renderCard: function(item) {
        const previews = Array.isArray(item.preview_images) ? item.preview_images : [];
        let layersHtml = '';

        for (let i = 0; i < 3; i++) {
            const previewIndex = 2 - i;
            const imgUrl = previews[previewIndex];
            if (imgUrl) {
                const fullImgUrl = `${this.config.siteAdres}static/mod_gallery/${this.escapeHtml(item.mod_name)}/thumb/${this.escapeHtml(imgUrl)}`;
                layersHtml += `<div class="image-stack-layer" style="background-image: url('${fullImgUrl}');"></div>`;
            } else {
                if (i === 2) {
                    layersHtml += `<div class="image-stack-layer" style="display: flex; align-items: center; justify-content: center; color: var(--md-sys-color-outline); font-size: 24px;">
                        <i class="bi bi-image"></i>
                    </div>`;
                } else {
                    layersHtml += `<div class="image-stack-layer"></div>`;
                }
            }
        }

        const isPublished = Number(item.image_count) > 0;
        const statusClass = isPublished ? 'active' : 'draft';
        const statusLabel = isPublished ? this.config.lang.status_active : this.config.lang.status_draft;

        return `
            <div class="gallery-card" id="gallery-card-${this.escapeHtml(item.mod_name)}-${this.escapeHtml(item.mod_key)}" onclick="GalleryManager.selectCard(this, '${this.escapeJsString(item.mod_name)}', '${this.escapeJsString(item.mod_key)}')" ondblclick="location.href='${this.config.baseUrl}list/${this.escapeHtml(item.mod_name)}/${this.escapeHtml(item.mod_key)}'">
                <div class="image-stack-container">
                    ${layersHtml}
                </div>
                <div class="card-details">
                    <div class="card-header-row">
                        <h4 class="card-title">${this.escapeHtml(item.name)}</h4>
                    </div>
                    <div class="card-meta">${this.escapeHtml(item.mod_name)} / ${this.escapeHtml(item.mod_key)}</div>
                    <div class="badge-row">
                        <span class="status-badge ${statusClass}">${this.escapeHtml(statusLabel)}</span>
                        <span class="count-badge">
                            <i class="bi bi-image"></i> ${this.escapeHtml(item.image_count)} ${this.escapeHtml(this.config.lang.images)}
                        </span>
                    </div>
                </div>
            </div>
        `;
    },

    selectedCard: null,

    selectCard: function(element, mod, vid) {
        const isSelected = element.classList.contains('selected');

        // Clear previous selections
        document.querySelectorAll('.gallery-card').forEach((c) => c.classList.remove('selected'));

        if (!isSelected) {
            element.classList.add('selected');
            this.selectedCard = { mod: mod, vid: vid };
            this.updateToolbarState(true);

            // Micro-vibration support
            if (window.navigator.vibrate) {
                window.navigator.vibrate(10);
            }

            // Sync status badge to status button if needed
            const isPublished = element.querySelector('.status-badge').classList.contains('active');
            const statusBtn = document.getElementById('toolbarStatus');
            if (statusBtn) {
                statusBtn.classList.toggle('is-published', isPublished);
            }
        } else {
            this.selectedCard = null;
            this.updateToolbarState(false);
        }
    },

    updateToolbarState: function(active) {
        const ids = ['toolbarManage', 'toolbarStatus', 'toolbarDelete'];
        ids.forEach((btnId) => {
            const btn = document.getElementById(btnId);
            if (!btn) {
                return;
            }

            if (active) {
                btn.style.pointerEvents = 'auto';
                btn.style.opacity = '1';
            } else {
                btn.style.pointerEvents = 'none';
                btn.style.opacity = '0.3';
                btn.classList.remove('is-published');
            }
        });
    },

    toolbarManage: function() {
        if (!this.selectedCard) {
            return;
        }
        location.href = `${this.config.baseUrl}list/${encodeURIComponent(this.selectedCard.mod)}/${encodeURIComponent(this.selectedCard.vid)}`;
    },

    toolbarStatus: function() {
        if (!this.selectedCard) {
            return;
        }
        this.notify('Statûya wênedankê li gorî hejmara wêneyan (Active/Draft) bixweber tê kontrolkirin.', 'info');
    },

    toolbarDelete: function() {
        if (!this.selectedCard) {
            return;
        }

        if (!confirm('Gelo tu dixwazî hemû wêneyên di vê wênedankê de jê bibî? Ev kiryar nayê vegerandin.')) {
            return;
        }

        const self = this;
        fetch(`${this.config.baseUrl}sil_all`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: new URLSearchParams({
                mod_name: this.selectedCard.mod,
                vid: this.selectedCard.vid
            }).toString()
        })
        .then((response) => response.json())
        .then((result) => {
            if (result.status === 'success') {
                self.notify(result.message, 'success');
                self.selectedCard = null;
                self.updateToolbarState(false);
                self.loadGalleries();
            } else {
                self.notify(result.message, 'error');
            }
        })
        .catch((err) => {
            console.error('Delete all failed', err);
            self.notify('Jêbirin nehat kirin!', 'error');
        });
    },

    search: function(query) {
        const normalized = String(query || '').trim().toLowerCase();
        this.config.currentQuery = normalized;

        const filtered = this.items.filter((item) => {
            const haystacks = [
                item.name,
                item.mod_name,
                String(item.mod_key),
                String(item.mod_value || '')
            ];

            return haystacks.some((haystack) => String(haystack).toLowerCase().includes(normalized));
        });

        this.renderGalleries(filtered);
    },

    handleFileSelect: function(input) {
        const files = input.files ? Array.from(input.files) : [];
        this.addFiles(files);
        input.value = '';
    },

    createUploadEntry: function(file) {
        return {
            id: `upload-${this.uploadCounter++}`,
            file: file,
            status: 'waiting',
            progress: 0,
            previewUrl: URL.createObjectURL(file)
        };
    },

    addFiles: function(files) {
        if (!Array.isArray(files) || files.length === 0) {
            return;
        }

        files.forEach((file) => {
            if (!file || !String(file.type || '').startsWith('image/')) {
                return;
            }

            const duplicate = this.selectedFiles.some((entry) => (
                entry.file.name === file.name &&
                entry.file.size === file.size &&
                entry.file.lastModified === file.lastModified
            ));

            if (!duplicate) {
                this.selectedFiles.push(this.createUploadEntry(file));
            }
        });

        this.renderUploadPreview();
        this.scheduleAutoStart();
    },

    scheduleAutoStart: function() {
        if (this.isUploading) {
            return;
        }

        if (this.autoStartTimer) {
            window.clearTimeout(this.autoStartTimer);
        }

        this.autoStartTimer = window.setTimeout(() => {
            this.autoStartTimer = null;
            this.startUpload();
        }, 80);
    },

    renderUploadPreview: function() {
        const preview = document.getElementById('uploadPreview');
        const queueList = document.getElementById('uploadQueueList');
        const queueSummary = document.getElementById('uploadQueueSummary');
        const uploadBtn = document.getElementById('uploadBtn');
        const uploadOverall = document.getElementById('uploadOverall');
        const uploadOverallTitle = document.getElementById('uploadOverallTitle');
        const uploadOverallMeta = document.getElementById('uploadOverallMeta');
        const uploadOverallBar = document.getElementById('uploadOverallBar');
        const uploadSafety = document.getElementById('uploadSafetyBanner');
        const uploadSafetyTitle = document.getElementById('uploadSafetyTitle');
        const uploadSafetyText = document.getElementById('uploadSafetyText');

        if (!preview || !queueList || !queueSummary || !uploadBtn) {
            return;
        }

        queueList.innerHTML = '';

        if (this.selectedFiles.length === 0) {
            preview.classList.remove('is-visible');
            uploadBtn.disabled = true;
            if (uploadOverall) {
                uploadOverall.hidden = true;
            }
            if (uploadSafety) {
                uploadSafety.hidden = true;
            }
            this.updateUploadSheetState();
            return;
        }

        preview.classList.add('is-visible');
        const metrics = this.getUploadMetrics();
        const waitingCount = metrics.waitingCount;
        const uploadingCount = metrics.uploadingCount;
        const successCount = metrics.successCount;
        const errorCount = metrics.errorCount;

        uploadBtn.disabled = this.isUploading || waitingCount === 0;
        if (uploadingCount > 0) {
            queueSummary.textContent = this.config.lang.upload_processing_current
                .replace('%s', metrics.processedCount + 1)
                .replace('%s', metrics.totalCount);
        } else if (errorCount > 0 && waitingCount === 0) {
            queueSummary.textContent = this.config.lang.upload_progress_with_errors
                .replace('%s', metrics.processedCount)
                .replace('%s', metrics.totalCount)
                .replace('%s', errorCount);
        } else {
            queueSummary.textContent = waitingCount === metrics.totalCount
                ? this.config.lang.queue_ready.replace('%s', waitingCount)
                : this.config.lang.upload_remaining.replace('%s', waitingCount);
        }

        if (uploadOverall && uploadOverallTitle && uploadOverallMeta && uploadOverallBar) {
            uploadOverall.hidden = false;
            if (this.isUploading) {
                uploadOverallTitle.textContent = this.config.lang.upload_status_active;
            } else if (errorCount > 0) {
                uploadOverallTitle.textContent = this.config.lang.upload_status_attention;
            } else if (successCount === metrics.totalCount) {
                uploadOverallTitle.textContent = this.config.lang.upload_status_complete;
            } else {
                uploadOverallTitle.textContent = this.config.lang.upload_status_ready;
            }

            uploadOverallMeta.textContent = errorCount > 0
                ? this.config.lang.upload_progress_with_errors
                    .replace('%s', metrics.processedCount)
                    .replace('%s', metrics.totalCount)
                    .replace('%s', errorCount)
                : this.config.lang.upload_progress
                    .replace('%s', metrics.processedCount)
                    .replace('%s', metrics.totalCount);

            uploadOverallBar.style.width = `${metrics.totalProgress}%`;
            uploadOverall.classList.toggle('is-uploading', this.isUploading);
            uploadOverall.classList.toggle('is-complete', !this.isUploading && successCount === metrics.totalCount && errorCount === 0);
            uploadOverall.classList.toggle('is-error', errorCount > 0);
        }

        if (uploadSafety && uploadSafetyTitle && uploadSafetyText) {
            uploadSafety.hidden = !this.isUploading;
            uploadSafetyTitle.textContent = this.config.lang.upload_status_active;
            uploadSafetyText.textContent = this.config.lang.upload_sheet_lock;
        }

        this.selectedFiles.forEach((entry) => {
            const statusClass = entry.status === 'waiting' ? '' : ` is-${entry.status}`;
            const normalizedProgress = Math.max(0, Math.min(100, Number(entry.progress || 0)));
            const progressValue = entry.status === 'success'
                ? 100
                : entry.status === 'waiting'
                    ? 0
                    : normalizedProgress;
            const progressText = entry.status === 'waiting'
                ? this.config.lang.queue_waiting
                : `${Math.round(progressValue)}%`;
            const removeButton = entry.status === 'uploading'
                ? ''
                : `
                    <button type="button" class="m3-icon-btn" aria-label="${this.escapeHtml(this.config.lang.cancel)}" onclick="GalleryManager.removeSelectedFile('${this.escapeJsString(entry.id)}')">
                        <i class="bi bi-x-lg"></i>
                    </button>
                `;

            queueList.insertAdjacentHTML('beforeend', `
                <div class="upload-queue-item">
                    <div class="upload-queue-item__thumb">
                        <img src="${this.escapeHtml(entry.previewUrl)}" alt="${this.escapeHtml(entry.file.name)}">
                    </div>
                    <div>
                        <div class="upload-queue-item__name">${this.escapeHtml(entry.file.name)}</div>
                        <div class="upload-queue-item__meta">
                            <span>${this.escapeHtml(this.formatFileSize(entry.file.size))}</span>
                            <span class="upload-queue-item__status${statusClass}">${this.escapeHtml(this.getQueueStatusLabel(entry.status))}</span>
                        </div>
                        <div class="upload-queue-item__progress">
                            <div class="upload-queue-item__progress-bar" style="width: ${progressValue}%;"></div>
                        </div>
                        <div class="upload-queue-item__progress-label">${this.escapeHtml(progressText)}</div>
                    </div>
                    ${removeButton}
                </div>
            `);
        });

        this.updateUploadSheetState();
    },

    getUploadMetrics: function() {
        const totalCount = this.selectedFiles.length;
        const waitingCount = this.selectedFiles.filter((entry) => entry.status === 'waiting').length;
        const uploadingCount = this.selectedFiles.filter((entry) => entry.status === 'uploading').length;
        const successCount = this.selectedFiles.filter((entry) => entry.status === 'success').length;
        const errorCount = this.selectedFiles.filter((entry) => entry.status === 'error').length;
        const processedCount = successCount + errorCount;
        const totalBytes = this.selectedFiles.reduce((sum, entry) => sum + Number(entry.file?.size || 0), 0);
        const loadedBytes = this.selectedFiles.reduce((sum, entry) => {
            const size = Number(entry.file?.size || 0);
            if (entry.status === 'success') {
                return sum + size;
            }
            if (entry.status === 'uploading') {
                return sum + (size * Number(entry.progress || 0) / 100);
            }
            return sum;
        }, 0);

        return {
            totalCount,
            waitingCount,
            uploadingCount,
            successCount,
            errorCount,
            processedCount,
            totalProgress: totalBytes > 0 ? Math.round((loadedBytes / totalBytes) * 100) : 0
        };
    },

    updateUploadSheetState: function() {
        const sheet = document.getElementById('uploadSheet');
        const uploadArea = document.getElementById('uploadArea');
        const closeButtons = document.querySelectorAll('[data-upload-close]');
        const closeIcon = document.getElementById('uploadSheetCloseBtn');
        const uploadBtn = document.getElementById('uploadBtn');

        if (sheet) {
            sheet.classList.toggle('is-uploading', this.isUploading);
        }

        if (uploadArea) {
            uploadArea.classList.toggle('is-disabled', this.isUploading);
        }

        closeButtons.forEach((button) => {
            button.disabled = this.isUploading;
            button.setAttribute('aria-disabled', this.isUploading ? 'true' : 'false');
        });

        if (closeIcon) {
            closeIcon.setAttribute('title', this.isUploading ? this.config.lang.upload_sheet_lock : this.config.lang.cancel);
        }

        if (uploadBtn && !this.isUploading) {
            uploadBtn.innerHTML = `<i class="bi bi-cloud-upload"></i><span>${this.escapeHtml(this.config.lang.upload_button)}</span>`;
        }
    },

    handleBeforeUnload: function(event) {
        if (!this.isUploading) {
            return undefined;
        }

        event.preventDefault();
        event.returnValue = this.config.lang.upload_leave_warning;
        return event.returnValue;
    },

    removeSelectedFile: function(id) {
        const index = this.selectedFiles.findIndex((entry) => entry.id === id);
        if (index === -1) {
            return;
        }

        URL.revokeObjectURL(this.selectedFiles[index].previewUrl);
        this.selectedFiles.splice(index, 1);
        this.renderUploadPreview();
    },

    resetUpload: function() {
        const input = document.getElementById('fileInput');
        if (input) {
            input.value = '';
        }

        this.selectedFiles.forEach((entry) => {
            URL.revokeObjectURL(entry.previewUrl);
        });

        this.selectedFiles = [];
        this.isUploading = false;
        if (this.autoStartTimer) {
            window.clearTimeout(this.autoStartTimer);
            this.autoStartTimer = null;
        }

        const preview = document.getElementById('uploadPreview');
        const queueList = document.getElementById('uploadQueueList');
        if (preview) {
            preview.classList.remove('is-visible');
        }

        if (queueList) {
            queueList.innerHTML = '';
        }

        const btn = document.getElementById('uploadBtn');
        if (btn) {
            btn.disabled = true;
        }
        this.updateUploadSheetState();
    },

    handleDrop: function(event) {
        event.preventDefault();
        this.setUploadDragState(false);
        const files = event.dataTransfer && event.dataTransfer.files ? Array.from(event.dataTransfer.files) : [];
        this.addFiles(files);
    },

    setUploadDragState: function(isActive) {
        const uploadArea = document.getElementById('uploadArea');
        if (!uploadArea) {
            return;
        }

        uploadArea.classList.toggle('is-dragover', isActive);
    },

    startUpload: async function() {
        if (this.isUploading) {
            return;
        }

        if (this.autoStartTimer) {
            window.clearTimeout(this.autoStartTimer);
            this.autoStartTimer = null;
        }

        const pendingEntries = this.selectedFiles.filter((entry) => entry.status === 'waiting' || entry.status === 'error');
        if (pendingEntries.length === 0) {
            return;
        }

        const btn = document.getElementById('uploadBtn');
        const originalHtml = btn ? btn.innerHTML : '';

        this.isUploading = true;
        if (btn) {
            btn.disabled = true;
        }
        this.updateUploadSheetState();

        let hasError = false;
        let successCount = 0;

        for (let i = 0; i < pendingEntries.length; i++) {
            const entry = pendingEntries[i];
            const file = entry.file;

            entry.status = 'uploading';
            entry.progress = 0;
            this.renderUploadPreview();

            if (btn) {
                btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="margin-right: 8px;"></span>${this.escapeHtml(this.config.lang.uploading)} (${i + 1}/${pendingEntries.length})`;
            }

            const formData = new FormData();
            formData.append('isim', 'image');
            formData.append('image', file);
            formData.append('vid', this.config.vid);
            formData.append('mod_name', this.config.mod_name);

            try {
                const result = await this.uploadFileEntry(entry, formData);

                if (result.status !== 'success') {
                    entry.status = 'error';
                    entry.progress = 0;
                    this.renderUploadPreview();
                    this.notify(result.message ? result.message : this.config.lang.upload_failed, 'error');
                    hasError = true;
                    continue;
                }

                entry.status = 'success';
                entry.progress = 100;
                this.renderUploadPreview();
                successCount += 1;
            } catch (error) {
                entry.status = 'error';
                entry.progress = 0;
                this.renderUploadPreview();
                console.error('Upload failed', error);
                this.notify(this.config.lang.upload_failed, 'error');
                hasError = true;
            }
        }

        this.isUploading = false;
        this.renderUploadPreview();
        if (btn) {
            btn.innerHTML = originalHtml;
            btn.disabled = this.selectedFiles.every((entry) => entry.status !== 'waiting' && entry.status !== 'error');
        }
        this.updateUploadSheetState();

        if (hasError) {
            this.notify(
                this.config.lang.upload_progress_with_errors
                    .replace('%s', successCount + this.selectedFiles.filter((entry) => entry.status === 'error').length)
                    .replace('%s', pendingEntries.length)
                    .replace('%s', this.selectedFiles.filter((entry) => entry.status === 'error').length),
                'warning'
            );
            return;
        }

        if (successCount === 0) {
            return;
        }

        this.notify(this.config.lang.upload_complete, 'success');
        setTimeout(() => {
            location.reload();
        }, 900);
    },

    uploadFileEntry: function(entry, formData) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', this.config.baseUrl + 'up', true);
            xhr.responseType = 'json';

            xhr.upload.addEventListener('progress', (event) => {
                if (!event.lengthComputable) {
                    return;
                }

                entry.progress = Math.round((event.loaded / event.total) * 100);
                this.renderUploadPreview();
            });

            xhr.addEventListener('load', () => {
                if (xhr.status < 200 || xhr.status >= 300) {
                    reject(new Error(`HTTP ${xhr.status}`));
                    return;
                }

                const response = xhr.response || JSON.parse(xhr.responseText || '{}');
                resolve(response);
            });

            xhr.addEventListener('error', () => {
                reject(new Error('Network error'));
            });

            xhr.send(formData);
        });
    },

    deleteImageRequest: async function(id) {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('vid', this.config.vid);
        formData.append('mod_name', this.config.mod_name);

        const response = await fetch(this.config.baseUrl + 'sil', {
            method: 'POST',
            body: formData
        });

        return response.json();
    },

    removeImageFromDom: function(id) {
        const item = this.getImageElementById(id);
        if (!item) {
            return;
        }

        item.remove();
        this.selectedImageIds = this.selectedImageIds.filter((selectedId) => selectedId !== String(id));

        if (String(this.activeImageId) === String(id)) {
            const replacement = this.getVisibleImageElements()[0];
            this.activeImageId = replacement ? replacement.dataset.id : null;
        }

        this.filterImages(document.getElementById('galleryImageSearch')?.value || '');
        this.updateSelectionUI();

        if (!this.activeImageId) {
            this.closeDetailSheet();
        }
    },

    deleteImage: async function(id) {
        const result = await Swal.fire({
            title: this.config.lang.delete_title,
            text: this.config.lang.delete_confirm,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: 'var(--md-sys-color-error)',
            cancelButtonColor: 'var(--md-sys-color-primary)',
            confirmButtonText: this.config.lang.yes,
            cancelButtonText: this.config.lang.cancel,
            background: 'var(--md-sys-color-surface-container-high)',
            color: 'var(--md-sys-color-on-surface)',
            borderRadius: '28px'
        });

        if (!result.isConfirmed) {
            return;
        }

        try {
            const res = await this.deleteImageRequest(id);
            if (res.status === 'success') {
                this.removeImageFromDom(id);
                Amed.notify(res.message ? res.message : this.config.lang.image_deleted, 'success');
            } else {
                Amed.notify(res.message, 'error');
            }
        } catch (error) {
            console.error('Delete failed', error);
            Amed.notify(this.config.lang.delete_failed, 'error');
        }
    },

    editName: async function(id, currentName) {
        const { value: newName } = await Swal.fire({
            title: this.config.lang.edit_title,
            input: 'text',
            inputLabel: this.config.lang.image_name,
            inputValue: currentName,
            showCancelButton: true,
            confirmButtonColor: 'var(--md-sys-color-primary)',
            cancelButtonColor: 'var(--md-sys-color-error)',
            confirmButtonText: this.config.lang.save,
            cancelButtonText: this.config.lang.cancel,
            background: 'var(--md-sys-color-surface-container-high)',
            color: 'var(--md-sys-color-on-surface)',
            borderRadius: '28px'
        });

        if (!newName || newName === currentName) {
            return;
        }

        const formData = new FormData();
        formData.append('id', id);
        formData.append('name', newName);

        try {
            const response = await fetch(this.config.baseUrl + 'addegis', {
                method: 'POST',
                body: formData
            });
            const res = await response.json();

            if (res.status === 'success') {
                const item = this.getImageElementById(id);
                if (item) {
                    const nameElement = item.querySelector('.item-name');
                    if (nameElement) {
                        nameElement.textContent = newName;
                    }
                    item.dataset.name = newName.toLowerCase();
                    item.dataset.originalName = newName;
                    item.setAttribute('ondblclick', `GalleryManager.editName('${this.escapeJsString(id)}', '${this.escapeJsString(newName)}')`);
                }

                if (String(this.activeImageId) === String(id)) {
                    this.updateDetailPanel();
                }

                Amed.notify(res.message ? res.message : this.config.lang.image_edited, 'success');
            } else {
                Amed.notify(res.message ? res.message : this.config.lang.rename_failed, 'error');
            }
        } catch (error) {
            console.error('Update failed', error);
            Amed.notify(this.config.lang.rename_failed, 'error');
        }
    },

    editActiveImageName: function() {
        if (!this.activeImageId) {
            return;
        }

        const data = this.getImageData(this.activeImageId);
        if (data) {
            this.editName(data.id, data.name);
        }
    },

    viewOriginal: function(url) {
        Swal.fire({
            imageUrl: url,
            imageAlt: this.config.lang.preview_alt,
            width: 'auto',
            padding: '0',
            showConfirmButton: false,
            showCloseButton: true,
            background: 'transparent',
            customClass: {
                image: 'img-fluid rounded-4 shadow-lg'
            }
        });
    },

    viewActiveImageOriginal: function() {
        const data = this.activeImageId ? this.getImageData(this.activeImageId) : null;
        if (data) {
            this.viewOriginal(data.originalUrl);
        }
    },

    deleteActiveImage: function() {
        if (this.activeImageId) {
            this.deleteImage(this.activeImageId);
        }
    },

    bulkDeleteSelected: async function() {
        if (this.selectedImageIds.length === 0) {
            return;
        }

        const result = await Swal.fire({
            title: this.config.lang.bulk_delete_title,
            text: this.config.lang.bulk_delete_confirm.replace('%s', this.selectedImageIds.length),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: 'var(--md-sys-color-error)',
            cancelButtonColor: 'var(--md-sys-color-primary)',
            confirmButtonText: this.config.lang.bulk_delete,
            cancelButtonText: this.config.lang.cancel,
            background: 'var(--md-sys-color-surface-container-high)',
            color: 'var(--md-sys-color-on-surface)',
            borderRadius: '28px'
        });

        if (!result.isConfirmed) {
            return;
        }

        const ids = [...this.selectedImageIds];
        for (const id of ids) {
            try {
                const res = await this.deleteImageRequest(id);
                if (res.status === 'success') {
                    this.removeImageFromDom(id);
                } else {
                    Amed.notify(res.message, 'error');
                    break;
                }
            } catch (error) {
                console.error('Bulk delete failed', error);
                Amed.notify(this.config.lang.delete_failed, 'error');
                break;
            }
        }

        this.updateSelectionUI();
    }
};

window.GalleryManager = GalleryManager;
