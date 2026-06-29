/**
 * mod_video_embed.js
 * Handles fetching data from existing Datatable JSON endpoint and rendering M3 Cards.
 * Modeled after mod_content.js
 */

class VideoManager {
    constructor() {
        this.container = document.getElementById('content-list-container');
        this.loader = document.getElementById('infinite-loader');
        this.emptyState = document.getElementById('empty-state');

        this.isLoading = false;
        this.hasMore = true;
        this.selectedVideoId = null;

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

        let btnText = 'Load More';
        if (typeof VIDEO_LANG !== 'undefined' && VIDEO_LANG.grid && VIDEO_LANG.grid.load_more) {
            btnText = VIDEO_LANG.grid.load_more;
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
            this.emptyState.style.display = 'none';
            this.loader.style.display = 'flex';
            if (this.loadMoreBtn) this.loadMoreBtn.style.display = 'none';
        } else {
            this.loader.style.display = 'flex';
        }

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

            this.start += this.length;
            this.draw++;

            if (this.start >= data.recordsFiltered) {
                this.hasMore = false;
                this.loader.style.display = 'none';
            }

            if (data.recordsFiltered === 0) {
                this.emptyState.style.display = 'flex';
            }

        } catch (error) {
            console.error('Data Load Error:', error);
        } finally {
            this.isLoading = false;
            this.loader.style.display = 'none';

            if (this.loadMoreBtn) {
                this.loadMoreBtn.style.display = (this.hasMore) ? 'block' : 'none';
                this.loadMoreBtn.disabled = false;
                let btnText = 'Load More';
                if (typeof VIDEO_LANG !== 'undefined' && VIDEO_LANG.grid && VIDEO_LANG.grid.load_more) {
                    btnText = VIDEO_LANG.grid.load_more;
                }
                this.loadMoreBtn.innerHTML = `<i class="bi bi-arrow-down-circle" style="margin-right:8px;"></i>${btnText}`;
            }
        }
    }

    renderCards(items) {
        if (!items || items.length === 0) return;

        const fragment = document.createDocumentFragment();

        items.forEach((item) => {
            // Data Mapping from datatable_json (index.php)
            // 0: Checkbox HTML
            // 1: ID
            // 2: Name
            // 3: Image HTML (img tag)
            // 4: Sure (duration)
            // 5: Lang
            // 6: created_at (date)
            // 7: Status HTML (status badge)
            // 8: Actions HTML

            const id = this.stripHtml(item[1]);
            const titleText = this.stripHtml(item[2]);
            const imgHtml = item[3];
            const duration = this.stripHtml(item[4]);
            const lang = this.stripHtml(item[5]);
            const date = this.stripHtml(item[6]);
            const statusHtml = item[7];

            // Extract Image Src
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = imgHtml;
            const imgTag = tempDiv.querySelector('img');
            const imgSrc = imgTag ? imgTag.src : '';

            // Extract status from badge HTML
            const isPublished = statusHtml.includes('success');
            const statusVal = isPublished ? 'y' : 'n';

            // Status Logic
            let statusText = (typeof VIDEO_LANG !== 'undefined') ? VIDEO_LANG.draft : "Draft";
            let statusColor = "#0B57D0";
            if (isPublished) {
                statusText = (typeof VIDEO_LANG !== 'undefined') ? VIDEO_LANG.active : "Active";
                statusColor = "#2E7D32";
            }

            // Card Element
            const card = document.createElement('div');
            card.className = 'm3-card content-card';
            card.dataset.id = id;
            card.dataset.status = statusVal;
            card.onclick = () => window.videoManager.selectVideo(id, card);

            card.innerHTML = `
                <div class="card-content-layout">
                    <!-- Thumbnail -->
                    <div class="card-thumbnail" 
                         onclick="event.stopPropagation(); window.videoManager.processImageUpload('${id}', ${imgSrc ? 'true' : 'false'})"
                         style="cursor:pointer; display:flex; align-items:center; justify-content:center; color:#999; position:relative; ${imgSrc ? `background-image: url('${imgSrc}'); background-size: cover; background-position: center;` : 'background-color: #f0f0f0;'}">
                        ${!imgSrc ? '<i class="bi bi-youtube" style="font-size: 24px; color: #FF0000;"></i>' : ''}
                        ${duration ? `<span style="position:absolute; bottom:4px; right:4px; background:rgba(0,0,0,0.75); color:white; font-size:10px; padding:1px 6px; border-radius:4px; font-weight:600;">${duration}</span>` : ''}
                    </div>
                    
                    <div class="card-body-col">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                             <div>
                                <div class="meta-row" style="margin-top:0; margin-bottom:4px; display:flex; align-items:center; flex-wrap:wrap; gap:4px; font-size:12px; color: var(--md-sys-color-on-surface-variant);">
                                    <span class="chip" style="background:${statusColor}; color:#FFF; font-weight:bold; height:18px; border-radius:4px; padding:0 6px; font-size:10px; display:inline-flex; align-items:center;">${statusText}</span>
                                </div>
                                <h3 style="margin:0; font-size:16px; font-weight:500; line-height:1.4; color: var(--md-sys-color-on-surface); text-align: left;">${titleText}</h3>
                             </div>
                             <button class="m3-icon-btn" style="margin-top:-8px; margin-right:-8px;" onclick="event.stopPropagation(); window.videoManager.openActionSheet('${id}', '${titleText.replace(/'/g, "\\\'")}', '${statusVal}')">
                                <i class="bi bi-three-dots-vertical"></i>
                             </button>
                        </div>

                        <div class="meta-row" style="margin-top:8px;">
                             <span style="display:flex; align-items:center; gap:4px;"><i class="bi bi-translate" style="font-size:11px;"></i> ${lang}</span>
                             <span style="margin-left: 12px; display:flex; align-items:center; gap:4px;"><i class="bi bi-calendar3" style="font-size:11px;"></i> ${date}</span>
                             ${duration ? `<span style="margin-left: auto; display:flex; align-items:center; gap:4px;"><i class="bi bi-clock" style="font-size:11px;"></i> ${duration}</span>` : ''}
                        </div>
                    </div>
                </div>
            `;

            fragment.appendChild(card);
        });

        this.container.appendChild(fragment);
    }

    stripHtml(html) {
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

    async processImageUpload(id, hasImage = false) {
        if (hasImage) {
            const result = await Swal.fire({
                title: VIDEO_LANG.information,
                text: VIDEO_LANG.image_update_confirm,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ba1a1a',
                cancelButtonColor: '#6e7881',
                confirmButtonText: VIDEO_LANG.sheet.ok,
                cancelButtonText: VIDEO_LANG.button.cancel
            });

            if (!result.isConfirmed) {
                return;
            }
        }

        let input = document.getElementById('ajax_image_input_video');
        if (!input) {
            input = document.createElement('input');
            input.type = 'file';
            input.id = 'ajax_image_input_video';
            input.name = 'image';
            input.style.display = 'none';
            input.accept = 'image/*';
            document.body.appendChild(input);
        }

        const newInput = input.cloneNode(true);
        input.parentNode.replaceChild(newInput, input);
        input = newInput;

        input.onchange = async () => {
            if (input.files && input.files[0]) {
                const fileData = input.files[0];
                const formData = new FormData();
                formData.append('image', fileData);
                formData.append('id', id);

                this.loader.style.display = 'flex';

                try {
                    const response = await fetch(`${MODULE_URL}ajax_image_upload`, {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();

                    if (result.status === 'success') {
                        this.loadData(true);
                    } else {
                        showSnackbar(result.message || VIDEO_LANG.error_general, 'error');
                    }
                } catch (err) {
                    console.error('Upload error:', err);
                    showSnackbar(VIDEO_LANG.error_general, 'error');
                } finally {
                    this.loader.style.display = 'none';
                    input.value = '';
                }
            }
        };

        input.click();
    }

    openActionSheet(id, title, status = 'n') {
        window.currentContentId = id;
        const isPublished = (status === 'y');

        if (typeof openContextMenu === 'function') {
            openContextMenu(id, title, isPublished, ['gallery', 'files']);
        }
    }

    // --- Card Selection & Toolbar ---
    selectVideo(id, cardElement) {
        const isCurrent = cardElement.classList.contains('selected');

        document.querySelectorAll('.m3-card.selected, .content-card.selected').forEach(el => {
            el.classList.remove('selected');
        });

        if (!isCurrent) {
            cardElement.classList.add('selected');
            this.selectedVideoId = id;
            this.selectedStatus = cardElement.dataset.status || 'n';
            this.updateToolbar(true);
            if (window.navigator.vibrate) window.navigator.vibrate(10);
        } else {
            this.selectedVideoId = null;
            this.selectedStatus = null;
            this.updateToolbar(false);
        }
    }

    updateToolbar(active) {
        const ids = ['toolbarEdit', 'toolbarImage', 'toolbarPublish', 'toolbarDelete'];
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

        // Publish/Unpublish button icon & text update
        const publishIcon = document.getElementById('toolbarPublishIcon');
        const publishText = document.getElementById('toolbarPublishText');
        const publishBtn = document.getElementById('toolbarPublish');
        if (publishBtn && active) {
            publishBtn.classList.toggle('is-published', this.selectedStatus === 'y');
        }
        if (publishIcon && publishText && active) {
            if (this.selectedStatus === 'y') {
                // Currently published -> show "Unpublish"
                publishIcon.className = 'bi bi-eye-slash-fill';
                publishText.textContent = (typeof VIDEO_LANG !== 'undefined') ? VIDEO_LANG.unpublish : 'Neweşîne';
            } else {
                // Currently draft -> show "Publish"
                publishIcon.className = 'bi bi-eye-fill';
                publishText.textContent = (typeof VIDEO_LANG !== 'undefined') ? VIDEO_LANG.publish : 'Biweşîne';
            }
        }
    }
}

// Initialize on load
document.addEventListener('DOMContentLoaded', () => {
    window.videoManager = new VideoManager();
});

// --- Global toolbar action functions ---
window.editSelected = function () {
    const mgr = window.videoManager;
    if (mgr && mgr.selectedVideoId) {
        window.location.href = MODULE_URL + 'edit/' + mgr.selectedVideoId;
    }
};

window.imageSelected = function () {
    const mgr = window.videoManager;
    if (mgr && mgr.selectedVideoId) {
        mgr.processImageUpload(mgr.selectedVideoId);
    }
};

window.publishToggleSelected = function () {
    const mgr = window.videoManager;
    if (mgr && mgr.selectedVideoId) {
        const currentStatus = mgr.selectedStatus || 'n';
        window.toggleVideoStatus(mgr.selectedVideoId, currentStatus);
    }
};

window.deleteSelected = function () {
    const mgr = window.videoManager;
    if (!mgr || !mgr.selectedVideoId) return;
    const id = mgr.selectedVideoId;

    Swal.fire({
        icon: 'warning',
        text: VIDEO_LANG.confirm_delete,
        showCancelButton: true,
        confirmButtonColor: '#ba1a1a',
        cancelButtonColor: '#6e7881',
        confirmButtonText: VIDEO_LANG.confirm_yes || 'Erê',
        cancelButtonText: VIDEO_LANG.confirm_no || 'Na'
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
                        mgr.selectedVideoId = null;
                        mgr.updateToolbar(false);
                        mgr.loadData(true);
                    } else {
                        Swal.fire({ icon: 'error', text: msg });
                    }
                })
                .catch(err => {
                    console.error('Delete error:', err);
                    Swal.fire({ icon: 'error', text: VIDEO_LANG.error_general });
                });
        }
    });
};

// Context Menu Actions
window.handleContextAction = function (action) {
    const id = window.currentContentId;
    if (!id) return;

    switch (action) {
        case 'edit':
            window.location.href = `${MODULE_URL}edit/${id}`;
            break;
        case 'delete':
            Swal.fire({
                icon: 'warning',
                text: VIDEO_LANG.confirm_delete,
                showCancelButton: true,
                confirmButtonColor: '#ba1a1a',
                cancelButtonColor: '#6e7881',
                confirmButtonText: VIDEO_LANG.confirm_yes || 'Erê',
                cancelButtonText: VIDEO_LANG.confirm_no || 'Na'
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
                            if (msg === 'Oke') {
                                window.videoManager.loadData(true);
                                closeContextMenu();
                            } else {
                                Swal.fire({ icon: 'error', text: msg });
                            }
                        })
                        .catch(err => {
                            console.error('Delete error:', err);
                            Swal.fire({ icon: 'error', text: VIDEO_LANG.error_general });
                        });
                }
            });
            break;
        case 'gallery':
            window.location.href = `${SITE_URL}mod_gallery/list/mod_video_embed/${id}`;
            break;
        case 'files':
            window.location.href = `${SITE_URL}mod_attachment/list/mod_video_embed/${id}`;
            break;
        case 'status':
            const currentStatus = activeContextItem.status ? 'y' : 'n';
            window.toggleVideoStatus(id, currentStatus);
            break;
    }
    closeContextMenu();
};

window.toggleVideoStatus = function (id, currentStatus) {
    if (typeof event !== 'undefined') event.stopPropagation();
    const endpoint = currentStatus === 'y' ? 'unpublish' : 'publish';
    const confirmMsg = currentStatus === 'y' ? VIDEO_LANG.confirm_unpublish : VIDEO_LANG.confirm_publish;

    Swal.fire({
        icon: 'question',
        text: confirmMsg,
        showCancelButton: true,
        confirmButtonColor: '#6750a4',
        cancelButtonColor: '#6e7881',
        confirmButtonText: VIDEO_LANG.confirm_yes || 'Erê',
        cancelButtonText: VIDEO_LANG.confirm_no || 'Na'
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
                    if (msg === 'Oke') {
                        window.videoManager.loadData(true);
                    } else {
                        Swal.fire({ icon: 'error', text: msg });
                    }
                });
        }
    });
};
