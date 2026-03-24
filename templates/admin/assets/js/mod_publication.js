/**
 * mod_publication.js
 * Handles fetching data from existing Datatable JSON endpoint and rendering M3 Cards.
 */

class ContentManager {
    constructor() {
        this.container = document.getElementById('content-list-container');
        this.loader = document.getElementById('infinite-loader');
        this.emptyState = document.getElementById('empty-state');

        this.isLoading = false;
        this.hasMore = true;
        this.selectedContentId = null;

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
            const authorName = item[9] || '';

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

            // HTML Structure (Updated to match Prototype)
            card.innerHTML = `
                <div class="card-content-layout">
                    <!-- Thumbnail -->
                    <div class="card-thumbnail" 
                         onclick="event.stopPropagation(); window.contentManager.processImageUpload('${id}')"
                         style="cursor:pointer; display:flex; align-items:center; justify-content:center; color:#999; ${imgSrc ? `background-image: url('${imgSrc}'); background-size: cover; background-position: center;` : 'background-color: #f0f0f0;'}">
                        ${!imgSrc ? '<i class="bi bi-image" style="font-size: 24px;"></i>' : ''}
                    </div>
                    
                    <div class="card-body-col">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                             <div>
                                <div class="meta-row" style="margin-top:0; margin-bottom:4px;">
                                    <span style="color:${statusColor}; font-weight:500;">${statusText}</span>
                                    <span>•</span>
                                    <span>${categoryName}</span>
                                </div>
                                <h3 style="margin:0; font-size:16px; font-weight:500; line-height:1.4; color: var(--md-sys-color-on-surface); text-align: left;">${titleText}</h3>
                             </div>
                             <button class="m3-icon-btn" style="margin-top:-8px; margin-right:-8px;" onclick="event.stopPropagation(); window.contentManager.openActionSheet('${id}', '${titleText.replace(/'/g, "\\\'")}', '${statusRaw}')">
                                <i class="bi bi-three-dots-vertical"></i>
                             </button>
                        </div>

                        <div class="meta-row" style="margin-top:8px;">
                             <span style="display:flex; align-items:center; gap:4px;"><i class="bi bi-translate" style="font-size:11px;"></i> ${lang}</span>
                             <span style="margin-left: 12px; display:flex; align-items:center; gap:4px;"><i class="bi bi-calendar3" style="font-size:11px;"></i> ${date}</span>
                             <span style="margin-left: auto; font-size: 12px; color: var(--md-sys-color-on-surface-variant);">${authorName}</span>
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

    initScrollListener() {
        const scrollFrame = document.querySelector('.app-scroll-frame');
        if (scrollFrame) {
            let scrollTimeout = null;
            scrollFrame.addEventListener('scroll', () => {
                // Throttle: Tenê dema ku scroll raweste, load bike
                if (scrollTimeout) clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(() => {
                    // IMPORTANT: Pêşî kontrol bike ka hîn dane hene
                    if (!this.hasMore || this.isLoading) {
                        return;
                    }
                    // Threshold check: Ensure we are really at the bottom
                    // Also check if content is actually taller than viewport
                    if (scrollFrame.scrollHeight <= scrollFrame.clientHeight) {
                        return; // Don't auto load if content fits (unless we want to fill empty space, but user complained about auto-load)
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

    processImageUpload(id) {
        let input = document.getElementById('ajax_image_input_modern');
        if (!input) {
            input = document.createElement('input');
            input.type = 'file';
            input.id = 'ajax_image_input_modern';
            input.name = 'image';
            input.style.display = 'none';
            input.accept = 'image/*';
            document.body.appendChild(input);
        }

        // Unbind previous handlers
        const newInput = input.cloneNode(true);
        input.parentNode.replaceChild(newInput, input);
        input = newInput;

        input.onchange = async () => {
            if (input.files && input.files[0]) {
                const fileData = input.files[0];
                const formData = new FormData();
                formData.append('image', fileData);
                formData.append('id', id);

                // UI Feedback (Simple loader for now, could be improved)
                this.loader.style.display = 'flex';

                try {
                    const response = await fetch(`${MODULE_URL}ajax_image_upload`, {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();

                    if (result.status === 'success') {
                        // Refresh to show new image
                        this.loadData(true);
                    } else {
                        alert(result.message || CONTENT_LANG.error_upload);
                    }
                } catch (err) {
                    console.error('Upload error:', err);
                    alert(CONTENT_LANG.error_upload);
                } finally {
                    this.loader.style.display = 'none';
                    input.value = ''; // Reset
                }
            }
        };

        input.click();
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
            this.updateToolbar(true);
            if (window.navigator.vibrate) window.navigator.vibrate(10);
        } else {
            this.selectedContentId = null;
            this.updateToolbar(false);
        }
    }

    updateToolbar(active) {
        const ids = ['toolbarEdit', 'toolbarGallery', 'toolbarFiles', 'toolbarDelete'];
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
        window.location.href = SITE_URL + 'mod_gallery/list/mod_publication/' + mgr.selectedContentId;
    }
};

window.filesSelected = function () {
    const mgr = window.contentManager;
    if (mgr && mgr.selectedContentId) {
        window.location.href = SITE_URL + 'mod_attachment/list/mod_publication/' + mgr.selectedContentId;
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
                    if (msg === 'Oke') {
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
                            if (msg === 'Oke') {
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
        case 'gallery':
            window.location.href = `${SITE_URL}mod_gallery/list/mod_publication/${id}`;
            break;
        case 'files':
            window.location.href = `${SITE_URL}mod_attachment/list/mod_publication/${id}`;
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
    const endpoint = currentStatus === 'y' ? 'unpublish' : 'publish';
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
                    if (msg === 'Oke') {
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
