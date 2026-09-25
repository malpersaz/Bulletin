/**
 * Amed Framework
 * Affiliations Admin JavaScript (MD3 Phone-First Architecture)
 * templates/admin/assets/js/affiliations.js
 * Version: 1.0
 */

(function () {
    'use strict';

    function initAffiliationsAdmin() {
        const page = document.querySelector('.affil-page');
        if (!page) return;

        if (page.dataset.affilInit === 'true') return;
        page.dataset.affilInit = 'true';

        // Configuration & Endpoints
        const siteUrl = (typeof SITE_URL !== 'undefined' && SITE_URL) ? SITE_URL : (window.location.origin + '/');
        const listUrl = page.dataset.listUrl || (siteUrl + 'mod_settings/ajax_affiliation_list');
        const saveUrl = page.dataset.saveUrl || (siteUrl + 'mod_settings/ajax_affiliation_save');
        const toggleUrl = page.dataset.toggleUrl || (siteUrl + 'mod_settings/ajax_affiliation_toggle_status');
        const reorderUrl = page.dataset.reorderUrl || (siteUrl + 'mod_settings/ajax_affiliation_reorder');
        const deleteUrl = page.dataset.deleteUrl || (siteUrl + 'mod_settings/ajax_affiliation_delete');
        const staticUrl = page.dataset.staticUrl || (siteUrl + 'static/mod_settings/');

        // Localized Strings
        const strings = {
            addTitle: page.dataset.addTitle || 'Add Institution',
            editTitle: page.dataset.editTitle || 'Edit Institution',
            deleteConfirm: page.dataset.deleteConfirm || 'Are you sure you want to delete this affiliation link?',
            deleteBtn: page.dataset.deleteBtn || 'Delete',
            cancelBtn: page.dataset.cancelBtn || 'Cancel',
            saveBtn: page.dataset.saveBtn || 'Save',
            loadingText: page.dataset.loadingText || 'Saving...',
            noRecords: page.dataset.noRecords || 'No records found.'
        };

        // DOM References
        const listContainer = document.getElementById('affilCardsList');
        const emptyState = document.getElementById('affilEmptyState');
        const searchInput = document.getElementById('affilSearchInput');
        const searchClearBtn = document.getElementById('clearAffilSearchBtn');
        const statPills = document.querySelectorAll('.affil-stat-pill');
        const statAllCount = document.getElementById('affilStatAllCount');
        const statActiveCount = document.getElementById('affilStatActiveCount');
        const statInactiveCount = document.getElementById('affilStatInactiveCount');

        // Modal References
        const modalBackdrop = document.getElementById('affilItemModal');
        const modalTitle = document.getElementById('affilModalTitle');
        const modalForm = document.getElementById('affilItemForm');
        const itemIdInput = document.getElementById('affil_item_id');
        const nameInput = document.getElementById('affil_name_input');
        const urlInput = document.getElementById('affil_url_input');
        const logoFileInput = document.getElementById('affil_logo_file');
        const activeInput = document.getElementById('affil_active_input');
        const saveBtn = document.getElementById('saveAffilBtn');
        const previewImg = document.getElementById('affilLogoImgPreview');
        const placeholderIcon = document.getElementById('affilLogoPlaceholder');

        // Add Triggers
        const openAddBtn = document.getElementById('openAddAffilBtn');
        const emptyAddBtn = document.getElementById('emptyAddAffilBtn');
        const finalBarAddBtn = document.getElementById('finalBarAddAffilBtn');

        let currentFilter = 'all';
        let currentSearch = '';

        // Toast Notification
        function showToast(message, type) {
            let container = document.querySelector('.affil-toast-container');
            if (!container) {
                container = document.createElement('div');
                container.className = 'affil-toast-container';
                document.body.appendChild(container);
            }

            const toast = document.createElement('div');
            toast.className = 'affil-toast ' + (type === 'error' ? 'error' : 'success');

            const icon = document.createElement('i');
            icon.className = 'bi ' + (type === 'error' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill');

            const span = document.createElement('span');
            span.textContent = message;

            toast.appendChild(icon);
            toast.appendChild(span);
            container.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-10px)';
                setTimeout(() => toast.remove(), 250);
            }, 3000);
        }

        // Update counts
        function updateCounts() {
            const cards = listContainer.querySelectorAll('.affil-card');
            let total = 0;
            let active = 0;
            let inactive = 0;

            cards.forEach(card => {
                total++;
                if (card.dataset.active === 'y') {
                    active++;
                } else {
                    inactive++;
                }
            });

            if (statAllCount) statAllCount.textContent = total;
            if (statActiveCount) statActiveCount.textContent = active;
            if (statInactiveCount) statInactiveCount.textContent = inactive;

            if (emptyState) {
                if (total === 0) {
                    emptyState.classList.remove('is-hidden');
                } else {
                    emptyState.classList.add('is-hidden');
                }
            }
        }

        // Filter and Search logic
        function applyFilters() {
            const query = currentSearch.toLowerCase().trim();
            const cards = listContainer.querySelectorAll('.affil-card');

            cards.forEach(card => {
                const name = (card.dataset.name || '').toLowerCase();
                const url = (card.dataset.url || '').toLowerCase();
                const active = card.dataset.active || 'y';

                const matchesFilter = (currentFilter === 'all') ||
                    (currentFilter === 'active' && active === 'y') ||
                    (currentFilter === 'inactive' && active === 'n');

                const matchesSearch = !query || name.includes(query) || url.includes(query);

                if (matchesFilter && matchesSearch) {
                    card.classList.remove('is-hidden');
                } else {
                    card.classList.add('is-hidden');
                }
            });
        }

        // Modal Open / Close
        function openModal(itemData) {
            modalForm.reset();
            if (itemData) {
                modalTitle.textContent = strings.editTitle;
                itemIdInput.value = itemData.id || '0';
                nameInput.value = itemData.name || '';
                urlInput.value = itemData.url || '';
                activeInput.checked = (itemData.active === 'y');
                
                if (itemData.logo && itemData.logo.trim() !== '') {
                    previewImg.src = staticUrl + itemData.logo;
                    previewImg.classList.remove('is-hidden');
                    placeholderIcon.classList.add('is-hidden');
                } else {
                    previewImg.src = '';
                    previewImg.classList.add('is-hidden');
                    placeholderIcon.classList.remove('is-hidden');
                }
            } else {
                modalTitle.textContent = strings.addTitle;
                itemIdInput.value = '0';
                activeInput.checked = true;
                previewImg.src = '';
                previewImg.classList.add('is-hidden');
                placeholderIcon.classList.remove('is-hidden');
            }

            modalBackdrop.classList.add('is-open');
            setTimeout(() => nameInput.focus(), 150);
        }

        function closeModal() {
            modalBackdrop.classList.remove('is-open');
        }

        // Preview local file when chosen
        if (logoFileInput) {
            logoFileInput.addEventListener('change', function () {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        previewImg.src = e.target.result;
                        previewImg.classList.remove('is-hidden');
                        placeholderIcon.classList.add('is-hidden');
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        // Search Input Handlers
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                currentSearch = this.value;
                if (searchClearBtn) {
                    if (currentSearch.length > 0) {
                        searchClearBtn.classList.remove('is-hidden');
                    } else {
                        searchClearBtn.classList.add('is-hidden');
                    }
                }
                applyFilters();
            });
        }

        if (searchClearBtn) {
            searchClearBtn.addEventListener('click', function () {
                searchInput.value = '';
                currentSearch = '';
                searchClearBtn.classList.add('is-hidden');
                applyFilters();
                searchInput.focus();
            });
        }

        // Filter Pills
        statPills.forEach(pill => {
            pill.addEventListener('click', function () {
                statPills.forEach(p => p.classList.remove('active'));
                this.classList.add('active');
                currentFilter = this.dataset.filter || 'all';
                applyFilters();
            });
        });

        // Add Buttons
        if (openAddBtn) openAddBtn.addEventListener('click', () => openModal(null));
        if (emptyAddBtn) emptyAddBtn.addEventListener('click', () => openModal(null));
        if (finalBarAddBtn) finalBarAddBtn.addEventListener('click', () => openModal(null));

        // Close Modal Handlers
        modalBackdrop.addEventListener('click', function (e) {
            if (e.target === modalBackdrop || e.target.closest('[data-action="close-modal"]')) {
                closeModal();
            }
        });

        // Form Submit (Save / Update)
        modalForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(modalForm);
            if (!activeInput.checked) {
                formData.set('is_active', 'n');
            } else {
                formData.set('is_active', 'y');
            }

            saveBtn.disabled = true;
            const originalBtnHtml = saveBtn.innerHTML;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> ' + strings.loadingText;

            fetch(saveUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalBtnHtml;

                if (data.status === 'success') {
                    showToast(data.message || 'Saved successfully.', 'success');
                    closeModal();
                    // Reload page or refresh cards list
                    window.location.reload();
                } else {
                    showToast(data.message || 'Error occurred.', 'error');
                }
            })
            .catch(err => {
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalBtnHtml;
                showToast('Network error occurred.', 'error');
            });
        });

        // Card Click Delegation: Edit, Delete, Toggle, Reorder
        listContainer.addEventListener('click', function (e) {
            const card = e.target.closest('.affil-card');
            if (!card) return;

            const id = card.dataset.id;

            // Toggle switch click
            if (e.target.matches('input[type="checkbox"]')) {
                const isChecked = e.target.checked;
                const newStatus = isChecked ? 'y' : 'n';
                
                const formData = new FormData();
                formData.append('id', id);
                formData.append('is_active', newStatus);

                fetch(toggleUrl, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        card.dataset.active = newStatus;
                        const statusTag = card.querySelector('.affil-status-tag');
                        if (newStatus === 'y') {
                            card.classList.remove('is-inactive');
                            if (statusTag) {
                                statusTag.className = 'affil-status-tag active';
                                statusTag.textContent = 'Active';
                            }
                        } else {
                            card.classList.add('is-inactive');
                            if (statusTag) {
                                statusTag.className = 'affil-status-tag inactive';
                                statusTag.textContent = 'Inactive';
                            }
                        }
                        updateCounts();
                        applyFilters();
                    } else {
                        e.target.checked = !isChecked;
                        showToast(data.message || 'Update failed', 'error');
                    }
                })
                .catch(() => {
                    e.target.checked = !isChecked;
                    showToast('Network error occurred.', 'error');
                });
                return;
            }

            // Edit button click
            if (e.target.closest('.btn-edit')) {
                openModal({
                    id: card.dataset.id,
                    name: card.dataset.name,
                    url: card.dataset.url,
                    logo: card.dataset.logo,
                    active: card.dataset.active
                });
                return;
            }

            // Delete button click
            if (e.target.closest('.btn-delete')) {
                if (confirm(strings.deleteConfirm)) {
                    const formData = new FormData();
                    formData.append('id', id);

                    fetch(deleteUrl, {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            showToast(data.message || 'Deleted successfully.', 'success');
                            card.remove();
                            reindexCards();
                            updateCounts();
                        } else {
                            showToast(data.message || 'Delete failed', 'error');
                        }
                    })
                    .catch(() => showToast('Network error occurred.', 'error'));
                }
                return;
            }

            // Reorder Up
            if (e.target.closest('.reorder-up')) {
                const prev = card.previousElementSibling;
                if (prev && prev.classList.contains('affil-card')) {
                    listContainer.insertBefore(card, prev);
                    saveOrder();
                }
                return;
            }

            // Reorder Down
            if (e.target.closest('.reorder-down')) {
                const next = card.nextElementSibling;
                if (next && next.classList.contains('affil-card')) {
                    listContainer.insertBefore(next, card);
                    saveOrder();
                }
                return;
            }
        });

        function reindexCards() {
            const cards = listContainer.querySelectorAll('.affil-card');
            cards.forEach((c, idx) => {
                const badge = c.querySelector('.affil-order-badge');
                if (badge) badge.textContent = '#' + (idx + 1);

                const upBtn = c.querySelector('.reorder-up');
                const downBtn = c.querySelector('.reorder-down');
                if (upBtn) upBtn.disabled = (idx === 0);
                if (downBtn) downBtn.disabled = (idx === cards.length - 1);
            });
        }

        function saveOrder() {
            reindexCards();
            const cards = listContainer.querySelectorAll('.affil-card');
            const orders = [];
            cards.forEach((c, idx) => {
                orders.push({
                    id: parseInt(c.dataset.id, 10),
                    order: idx + 1
                });
            });

            const formData = new FormData();
            formData.append('orders', JSON.stringify(orders));

            fetch(reorderUrl, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status !== 'success') {
                    showToast('Failed to save order', 'error');
                }
            })
            .catch(() => showToast('Network error occurred.', 'error'));
        }

        // Initialize state
        updateCounts();
        reindexCards();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAffiliationsAdmin);
    } else {
        initAffiliationsAdmin();
    }
})();
