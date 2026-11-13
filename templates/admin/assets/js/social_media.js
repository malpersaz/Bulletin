/**
 * Amed Framework
 * Social Media Admin JavaScript (MD3 Phone-First Architecture)
 * templates/admin/assets/js/social_media.js
 * Version: 2.5
 */

(function () {
    'use strict';

    function initSocialMediaAdmin() {
        const page = document.querySelector('.social-page');
        if (!page) return;

        if (page.dataset.socialInit === 'true') return;
        page.dataset.socialInit = 'true';

        // Configuration & Endpoints
        const siteUrl = (typeof SITE_URL !== 'undefined' && SITE_URL) ? SITE_URL : (window.location.origin + '/');
        const listUrl = page.dataset.listUrl || (siteUrl + 'mod_settings/ajax_social_list');
        const saveUrl = page.dataset.saveUrl || (siteUrl + 'mod_settings/ajax_social_save');
        const toggleUrl = page.dataset.toggleUrl || (siteUrl + 'mod_settings/ajax_social_toggle_status');
        const reorderUrl = page.dataset.reorderUrl || (siteUrl + 'mod_settings/ajax_social_reorder');
        const deleteUrl = page.dataset.deleteUrl || (siteUrl + 'mod_settings/ajax_social_delete');

        // Localized Strings
        const strings = {
            addTitle: page.dataset.addTitle || 'Add New Link',
            editTitle: page.dataset.editTitle || 'Edit',
            deleteTitle: page.dataset.deleteTitle || 'Delete',
            deleteConfirm: page.dataset.deleteConfirm || 'Are you sure you want to delete this social media link?',
            deleteBtn: page.dataset.deleteBtn || 'Delete',
            cancelBtn: page.dataset.cancelBtn || 'Cancel',
            saveBtn: page.dataset.saveBtn || 'Save',
            loadingText: page.dataset.loadingText || 'Saving...',
            noRecords: page.dataset.noRecords || 'No records found.',
            noUrl: page.dataset.noUrl || 'No URL configured'
        };

        // DOM References
        const listContainer = document.getElementById('socialCardsList');
        const emptyState = document.getElementById('socialEmptyState');
        const searchInput = document.getElementById('socialSearchInput');
        const searchClearBtn = document.getElementById('clearSocialSearchBtn');
        const statPills = document.querySelectorAll('.social-stat-pill');
        const statAllCount = document.getElementById('statAllCount');
        const statActiveCount = document.getElementById('statActiveCount');
        const statInactiveCount = document.getElementById('statInactiveCount');

        // Modal References
        const modalBackdrop = document.getElementById('socialItemModal');
        const modalTitle = document.getElementById('socialModalTitle');
        const modalForm = document.getElementById('socialItemForm');
        const itemIdInput = document.getElementById('social_item_id');
        const platformInput = document.getElementById('social_platform_input');
        const urlInput = document.getElementById('social_url_input');
        const iconInput = document.getElementById('social_icon_input');
        const activeInput = document.getElementById('social_active_input');
        const iconPreview = document.getElementById('iconPreview');
        const saveBtn = document.getElementById('saveSocialBtn');
        const presetChips = document.querySelectorAll('.social-preset-chip');

        // Add Triggers
        const openAddBtn = document.getElementById('openAddSocialBtn');
        const emptyAddBtn = document.getElementById('emptyAddBtn');
        const finalBarAddBtn = document.getElementById('finalBarAddBtn');

        // Move all modal elements to root wrapper so they escape .app-scroll-frame stacking context
        const rootContainer = document.querySelector('.mobile-wrapper') || document.querySelector('.phone-simulator') || document.body;
        document.querySelectorAll('.social-modal-backdrop').forEach(modal => {
            if (modal.parentElement !== rootContainer) {
                rootContainer.appendChild(modal);
            }
        });

        let currentFilter = 'all';
        let currentSearch = '';

        // Known Brand Color & Icon mappings
        const brandIcons = {
            'x': 'bi bi-twitter-x',
            'twitter': 'bi bi-twitter-x',
            'facebook': 'bi bi-facebook',
            'instagram': 'bi bi-instagram',
            'linkedin': 'bi bi-linkedin',
            'youtube': 'bi bi-youtube',
            'tiktok': 'bi bi-tiktok',
            'spotify': 'bi bi-spotify',
            'whatsapp': 'bi bi-whatsapp',
            'telegram': 'bi bi-telegram',
            'pinterest': 'bi bi-pinterest',
            'github': 'bi bi-github',
            'discord': 'bi bi-discord',
            'threads': 'bi bi-threads',
            'medium': 'bi bi-medium'
        };

        // Helper: Show Toast Notification
        function showToast(message, type) {
            let container = document.querySelector('.social-toast-container');
            if (!container) {
                container = document.createElement('div');
                container.className = 'social-toast-container';
                document.body.appendChild(container);
            }

            const toast = document.createElement('div');
            toast.className = 'social-toast ' + (type === 'error' ? 'error' : 'success');

            const icon = document.createElement('i');
            icon.className = 'bi ' + (type === 'error' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill');

            const span = document.createElement('span');
            span.textContent = message;

            toast.appendChild(icon);
            toast.appendChild(span);
            container.appendChild(toast);

            if (navigator.vibrate) {
                navigator.vibrate(type === 'error' ? [50, 50, 50] : 15);
            }

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-10px)';
                setTimeout(() => toast.remove(), 250);
            }, 3000);
        }

        // Helper: Escape HTML
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Helper: Sanitize URL
        function sanitizeUrl(url) {
            if (!url) return '';
            const trimmed = String(url).trim();
            if (/^javascript:/i.test(trimmed)) {
                return '#';
            }
            return trimmed;
        }

        // Update Counter Pills and Visibility
        function updateStatsAndVisibility() {
            if (!listContainer) return;
            const cards = Array.from(listContainer.querySelectorAll('.social-card'));
            let activeTotal = 0;
            let inactiveTotal = 0;
            let visibleCount = 0;

            cards.forEach((card, index) => {
                const isActive = card.dataset.active === 'y';
                const platform = (card.dataset.platform || '').toLowerCase();
                const url = (card.dataset.url || '').toLowerCase();

                if (isActive) activeTotal++;
                else inactiveTotal++;

                // Order badge update
                const orderBadge = card.querySelector('.social-order-badge');
                if (orderBadge) {
                    orderBadge.textContent = '#' + (index + 1);
                }

                // Reorder button states
                const upBtn = card.querySelector('.reorder-up');
                const downBtn = card.querySelector('.reorder-down');
                if (upBtn) upBtn.disabled = index === 0;
                if (downBtn) downBtn.disabled = index === cards.length - 1;

                // Match filter
                const matchesFilter = (currentFilter === 'all') ||
                    (currentFilter === 'active' && isActive) ||
                    (currentFilter === 'inactive' && !isActive);

                // Match search
                const matchesSearch = !currentSearch ||
                    platform.includes(currentSearch) ||
                    url.includes(currentSearch);

                if (matchesFilter && matchesSearch) {
                    card.classList.remove('is-filtered-out');
                    visibleCount++;
                } else {
                    card.classList.add('is-filtered-out');
                }
            });

            if (statAllCount) statAllCount.textContent = cards.length;
            if (statActiveCount) statActiveCount.textContent = activeTotal;
            if (statInactiveCount) statInactiveCount.textContent = inactiveTotal;

            if (emptyState) {
                if (cards.length === 0 || visibleCount === 0) {
                    emptyState.classList.remove('is-hidden');
                } else {
                    emptyState.classList.add('is-hidden');
                }
            }
        }

        // Build a Card Element from Data
        function createCardElement(item) {
            const card = document.createElement('div');
            const isActive = item.is_active === 'y';
            const platformLower = (item.platform || '').toLowerCase();
            const brandKey = brandIcons[platformLower] ? platformLower : 'link';
            const iconClass = item.icon_class || brandIcons[platformLower] || 'bi bi-link-45deg';

            card.className = 'social-card' + (isActive ? '' : ' is-inactive');
            card.dataset.id = item.id;
            card.dataset.platform = item.platform || '';
            card.dataset.url = item.url || '';
            card.dataset.icon = iconClass;
            card.dataset.active = item.is_active || 'y';

            // Top Row
            const topRow = document.createElement('div');
            topRow.className = 'social-card-top';

            const meta = document.createElement('div');
            meta.className = 'social-card-meta';

            const avatar = document.createElement('div');
            avatar.className = 'social-brand-avatar';
            avatar.dataset.brand = brandKey;

            const iconEl = document.createElement('i');
            iconEl.className = iconClass;
            avatar.appendChild(iconEl);

            const info = document.createElement('div');
            info.className = 'social-card-info';

            const titleRow = document.createElement('div');
            titleRow.className = 'social-platform-title-row';

            const nameEl = document.createElement('h4');
            nameEl.className = 'social-platform-name';
            nameEl.textContent = item.platform || '';

            const statusTag = document.createElement('span');
            statusTag.className = 'social-status-tag ' + (isActive ? 'active' : 'inactive');
            statusTag.textContent = isActive ? 'Active' : 'Inactive';

            titleRow.appendChild(nameEl);
            titleRow.appendChild(statusTag);
            info.appendChild(titleRow);

            meta.appendChild(avatar);
            meta.appendChild(info);

            // Switch
            const switchWrap = document.createElement('div');
            switchWrap.className = 'social-switch-control';

            const switchLabel = document.createElement('label');
            switchLabel.className = 'social-switch';
            switchLabel.title = isActive ? 'Active' : 'Inactive';

            const switchInput = document.createElement('input');
            switchInput.type = 'checkbox';
            switchInput.checked = isActive;
            switchInput.setAttribute('aria-label', (item.platform || '') + ' status');

            const switchTrack = document.createElement('span');
            switchTrack.className = 'social-switch-track';

            switchLabel.appendChild(switchInput);
            switchLabel.appendChild(switchTrack);
            switchWrap.appendChild(switchLabel);

            topRow.appendChild(meta);
            topRow.appendChild(switchWrap);

            // Middle Row: URL
            const urlContainer = document.createElement('div');
            urlContainer.className = 'social-url-container';

            const urlWrap = document.createElement('div');
            urlWrap.className = 'social-url-box-wrap';

            if (item.url && item.url.trim() !== '') {
                const urlBox = document.createElement('div');
                urlBox.className = 'social-url-box social-url-editable';
                urlBox.setAttribute('data-action', 'click-edit');
                urlBox.setAttribute('title', strings.editTitle);

                const textWrap = document.createElement('div');
                textWrap.className = 'social-url-text-wrap';

                const linkIcon = document.createElement('i');
                linkIcon.className = 'bi bi-link-45deg social-url-icon';

                const textSpan = document.createElement('span');
                textSpan.className = 'social-url-text';
                textSpan.textContent = item.url;

                textWrap.appendChild(linkIcon);
                textWrap.appendChild(textSpan);
                urlBox.appendChild(textWrap);
                urlWrap.appendChild(urlBox);

                const extBtn = document.createElement('a');
                extBtn.href = sanitizeUrl(item.url);
                extBtn.target = '_blank';
                extBtn.rel = 'noopener noreferrer';
                extBtn.className = 'social-url-external-btn';
                extBtn.setAttribute('title', 'Visit Profile');
                extBtn.setAttribute('aria-label', 'Visit Profile');
                extBtn.innerHTML = '<i class="bi bi-box-arrow-up-right"></i>';
                urlWrap.appendChild(extBtn);
            } else {
                const emptyBox = document.createElement('div');
                emptyBox.className = 'social-url-empty social-url-editable';
                emptyBox.setAttribute('data-action', 'click-edit');
                emptyBox.setAttribute('title', strings.editTitle);

                const warnIcon = document.createElement('i');
                warnIcon.className = 'bi bi-exclamation-circle';

                const warnText = document.createElement('span');
                warnText.textContent = strings.noUrl;

                emptyBox.appendChild(warnIcon);
                emptyBox.appendChild(warnText);
                urlWrap.appendChild(emptyBox);
            }
            urlContainer.appendChild(urlWrap);

            // Bottom Row: Actions
            const bottomRow = document.createElement('div');
            bottomRow.className = 'social-card-bottom';

            const reorderGroup = document.createElement('div');
            reorderGroup.className = 'social-reorder-group';

            const orderBadge = document.createElement('span');
            orderBadge.className = 'social-order-badge';
            orderBadge.textContent = '#' + (item.display_order || '1');

            const upBtn = document.createElement('button');
            upBtn.type = 'button';
            upBtn.className = 'social-action-btn icon-only reorder-up';
            upBtn.title = 'Move Up';
            upBtn.setAttribute('aria-label', 'Move Up');
            upBtn.innerHTML = '<i class="bi bi-arrow-up"></i>';

            const downBtn = document.createElement('button');
            downBtn.type = 'button';
            downBtn.className = 'social-action-btn icon-only reorder-down';
            downBtn.title = 'Move Down';
            downBtn.setAttribute('aria-label', 'Move Down');
            downBtn.innerHTML = '<i class="bi bi-arrow-down"></i>';

            reorderGroup.appendChild(orderBadge);
            reorderGroup.appendChild(upBtn);
            reorderGroup.appendChild(downBtn);

            const actionsRight = document.createElement('div');
            actionsRight.className = 'social-card-actions-right';

            const apiBtn = document.createElement('button');
            apiBtn.type = 'button';
            apiBtn.className = 'social-action-btn btn-api';
            apiBtn.setAttribute('data-action', 'open-api-modal');
            apiBtn.setAttribute('title', 'API');
            apiBtn.setAttribute('aria-label', 'API');
            apiBtn.innerHTML = '<i class="bi bi-code-slash"></i> <span>API</span>';

            const bufferBtn = document.createElement('button');
            bufferBtn.type = 'button';
            bufferBtn.className = 'social-action-btn btn-buffer';
            bufferBtn.setAttribute('data-action', 'open-buffer-modal');
            bufferBtn.setAttribute('title', 'Buffer');
            bufferBtn.setAttribute('aria-label', 'Buffer');
            bufferBtn.innerHTML = '<i class="bi bi-layers"></i> <span>Buffer</span>';

            const iftttBtn = document.createElement('button');
            iftttBtn.type = 'button';
            iftttBtn.className = 'social-action-btn btn-ifttt';
            iftttBtn.setAttribute('data-action', 'open-ifttt-modal');
            iftttBtn.setAttribute('title', 'IFTTT');
            iftttBtn.setAttribute('aria-label', 'IFTTT');
            iftttBtn.innerHTML = '<i class="bi bi-arrow-repeat"></i> <span>IFTTT</span>';

            actionsRight.appendChild(apiBtn);
            actionsRight.appendChild(bufferBtn);
            actionsRight.appendChild(iftttBtn);

            bottomRow.appendChild(reorderGroup);
            bottomRow.appendChild(actionsRight);

            card.appendChild(topRow);
            card.appendChild(urlContainer);
            card.appendChild(bottomRow);

            // Wire Card Events
            const editableUrl = card.querySelector('.social-url-editable');
            if (editableUrl) {
                editableUrl.addEventListener('click', () => {
                    openEditModal({
                        id: card.dataset.id,
                        platform: card.dataset.platform,
                        url: card.dataset.url,
                        icon_class: card.dataset.icon,
                        is_active: card.dataset.active
                    });
                });
            }

            apiBtn.addEventListener('click', () => openApiModal(card));
            bufferBtn.addEventListener('click', () => openBufferModal(card));
            iftttBtn.addEventListener('click', () => openIftttModal(card));

            switchInput.addEventListener('change', async function () {
                const newActive = this.checked ? 'y' : 'n';
                const body = new URLSearchParams();
                body.set('id', item.id);
                body.set('is_active', newActive);

                try {
                    const res = await fetch(toggleUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                        body: body.toString()
                    });
                    const payload = await res.json();
                    if (payload && payload.status === 'success') {
                        card.dataset.active = newActive;
                        if (newActive === 'y') {
                            card.classList.remove('is-inactive');
                            statusTag.className = 'social-status-tag active';
                            statusTag.textContent = 'Active';
                        } else {
                            card.classList.add('is-inactive');
                            statusTag.className = 'social-status-tag inactive';
                            statusTag.textContent = 'Inactive';
                        }
                        updateStatsAndVisibility();
                        showToast(payload.message || 'Status updated', 'success');
                    } else {
                        throw new Error(payload.message || 'Error updating status');
                    }
                } catch (err) {
                    this.checked = !this.checked;
                    showToast(err.message || 'Connection error', 'error');
                }
            });

            upBtn.addEventListener('click', () => {
                const prev = card.previousElementSibling;
                if (prev && prev.classList.contains('social-card')) {
                    listContainer.insertBefore(card, prev);
                    updateStatsAndVisibility();
                    saveReorder();
                }
            });

            downBtn.addEventListener('click', () => {
                const next = card.nextElementSibling;
                if (next && next.classList.contains('social-card')) {
                    listContainer.insertBefore(next, card);
                    updateStatsAndVisibility();
                    saveReorder();
                }
            });

            return card;
        }

        // Save reordered array to server
        async function saveReorder() {
            if (!listContainer) return;
            const cards = Array.from(listContainer.querySelectorAll('.social-card'));
            const orders = cards.map((card, idx) => ({
                id: parseInt(card.dataset.id, 10),
                order: idx + 1
            }));

            const body = new URLSearchParams();
            body.set('orders', JSON.stringify(orders));

            try {
                await fetch(reorderUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                    body: body.toString()
                });
            } catch (err) {
                console.error('Failed to save reorder:', err);
            }
        }

        // Load items from AJAX
        async function loadItems() {
            try {
                const res = await fetch(listUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const payload = await res.json();
                if (payload && payload.status === 'success') {
                    renderItems(payload.data || []);
                } else {
                    showToast(payload.message || 'Failed to load social links', 'error');
                }
            } catch (err) {
                console.error('Failed to load items:', err);
            }
        }

        // Render entire list
        function renderItems(items) {
            if (!listContainer) return;
            listContainer.innerHTML = '';
            items.forEach(item => {
                const card = createCardElement(item);
                listContainer.appendChild(card);
            });
            updateStatsAndVisibility();
        }

        // Modal Controls
        function openAddModal() {
            if (!modalBackdrop) return;
            modalTitle.textContent = strings.addTitle;
            itemIdInput.value = '0';
            platformInput.value = '';
            urlInput.value = '';
            iconInput.value = '';
            activeInput.checked = true;
            updateIconPreview('');
            presetChips.forEach(c => c.classList.remove('active'));
            modalBackdrop.classList.add('open');
            setTimeout(() => platformInput.focus(), 150);
        }

        function openEditModal(data) {
            if (!modalBackdrop) return;
            modalTitle.textContent = strings.editTitle;
            itemIdInput.value = data.id || '0';
            platformInput.value = data.platform || '';
            urlInput.value = data.url || '';
            iconInput.value = data.icon_class || '';
            activeInput.checked = (data.is_active === 'y');
            updateIconPreview(data.icon_class || brandIcons[(data.platform || '').toLowerCase()] || '');

            presetChips.forEach(chip => {
                if (chip.dataset.platform === (data.platform || '').toLowerCase()) {
                    chip.classList.add('active');
                } else {
                    chip.classList.remove('active');
                }
            });

            modalBackdrop.classList.add('open');
            setTimeout(() => urlInput.focus(), 150);
        }

        function closeModal() {
            if (modalBackdrop) {
                modalBackdrop.classList.remove('open');
            }
            const otherModals = document.querySelectorAll('.social-modal-backdrop');
            otherModals.forEach(m => m.classList.remove('open'));
        }

        function openApiModal(card) {
            const apiModal = document.getElementById('socialApiModal');
            if (!apiModal) return;
            const platform = card.dataset.platform || 'Social';
            const cleanPlatform = platform.toLowerCase();
            const iconClass = card.dataset.icon || brandIcons[cleanPlatform] || 'bi bi-link-45deg';

            const nameElem = document.getElementById('apiPlatformName');
            if (nameElem) nameElem.textContent = platform;

            const iconElem = document.getElementById('apiPlatformIcon');
            if (iconElem) iconElem.className = iconClass;

            const avatarElem = document.getElementById('apiPlatformAvatar');
            if (avatarElem) avatarElem.setAttribute('data-brand', cleanPlatform);

            apiModal.classList.add('open');
        }

        function openBufferModal(card) {
            const bufferModal = document.getElementById('socialBufferModal');
            if (!bufferModal) return;
            bufferModal.classList.add('open');
        }

        function openIftttModal(card) {
            const iftttModal = document.getElementById('socialIftttModal');
            if (!iftttModal) return;
            iftttModal.classList.add('open');
        }

        // Setup Guide & Recipe Modal Trigger
        const openSetupGuideBtn = document.getElementById('openSetupGuideBtn');
        if (openSetupGuideBtn) {
            openSetupGuideBtn.addEventListener('click', () => {
                const recipesModal = document.getElementById('socialRecipesModal');
                if (recipesModal) {
                    recipesModal.classList.add('open');
                }
            });
        }

        // Copy Feed URL to Clipboard with rich feedback
        const copyFeedButtons = document.querySelectorAll('.social-bridge-copy-btn');
        copyFeedButtons.forEach(btn => {
            btn.addEventListener('click', function () {
                const targetSelector = this.dataset.copyTarget;
                const targetElem = document.querySelector(targetSelector);
                if (targetElem) {
                    const text = targetElem.textContent.trim();
                    const originalHtml = this.innerHTML;
                    const successFeedback = () => {
                        this.innerHTML = '<i class="bi bi-check2"></i> <span>Kopyalandı!</span>';
                        this.style.background = '#dcfce7';
                        this.style.color = '#15803d';
                        showToast('Feed URL kopyalandı!', 'success');
                        setTimeout(() => {
                            this.innerHTML = originalHtml;
                            this.style.background = '';
                            this.style.color = '';
                        }, 2000);
                    };

                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(text).then(successFeedback).catch(() => {
                            showToast(text, 'success');
                        });
                    } else {
                        showToast(text, 'success');
                    }
                }
            });
        });

        // Close modal when clicking backdrop or close button
        document.addEventListener('click', e => {
            if (e.target.matches('[data-action="close-modal"]') || e.target.closest('[data-action="close-modal"]')) {
                closeModal();
            }
            if (e.target.classList.contains('social-modal-backdrop')) {
                closeModal();
            }
        });

        // Icon Preview Updater
        function updateIconPreview(cls) {
            if (!iconPreview) return;
            iconPreview.className = cls || 'bi bi-link-45deg';
        }

        if (iconInput) {
            iconInput.addEventListener('input', e => {
                updateIconPreview(e.target.value.trim());
            });
        }

        // Preset platform chips in modal
        presetChips.forEach(chip => {
            chip.addEventListener('click', () => {
                presetChips.forEach(c => c.classList.remove('active'));
                chip.classList.add('active');

                const platform = chip.dataset.platform;
                const icon = chip.dataset.icon;
                const prefix = chip.dataset.prefix || 'https://';

                if (platformInput) {
                    platformInput.value = chip.querySelector('span') ? chip.querySelector('span').textContent.trim() : platform;
                }
                if (iconInput) {
                    iconInput.value = icon || '';
                    updateIconPreview(icon || '');
                }
                if (urlInput) {
                    if (!urlInput.value || urlInput.value === 'https://' || urlInput.value.includes('instagram.com') || urlInput.value.includes('x.com') || urlInput.value.includes('facebook.com')) {
                        urlInput.value = prefix;
                    }
                    urlInput.focus();
                }
            });
        });

        // Form Submit for Add/Edit
        if (modalForm) {
            modalForm.addEventListener('submit', async e => {
                e.preventDefault();
                if (saveBtn) {
                    saveBtn.disabled = true;
                    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> <span>' + escapeHtml(strings.loadingText) + '</span>';
                }

                const formData = new FormData(modalForm);
                if (!formData.get('is_active')) {
                    formData.set('is_active', 'n');
                }

                try {
                    const res = await fetch(saveUrl, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: formData
                    });
                    const payload = await res.json();
                    if (payload && payload.status === 'success') {
                        closeModal();
                        showToast(payload.message || 'Saved successfully', 'success');
                        loadItems();
                    } else {
                        throw new Error(payload.message || 'Error saving item');
                    }
                } catch (err) {
                    showToast(err.message || 'Failed to save', 'error');
                } finally {
                    if (saveBtn) {
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = '<i class="bi bi-check-lg"></i> <span>' + escapeHtml(strings.saveBtn) + '</span>';
                    }
                }
            });
        }

        // Add Button Triggers
        if (openAddBtn) openAddBtn.addEventListener('click', openAddModal);
        if (emptyAddBtn) emptyAddBtn.addEventListener('click', openAddModal);
        if (finalBarAddBtn) finalBarAddBtn.addEventListener('click', openAddModal);

        // Filter Pills Trigger
        statPills.forEach(pill => {
            pill.addEventListener('click', () => {
                statPills.forEach(p => p.classList.remove('active'));
                pill.classList.add('active');
                currentFilter = pill.dataset.filter || 'all';
                updateStatsAndVisibility();
            });
        });

        // Search Input Trigger
        if (searchInput) {
            searchInput.addEventListener('input', e => {
                currentSearch = e.target.value.trim().toLowerCase();
                if (searchClearBtn) {
                    searchClearBtn.classList.toggle('is-hidden', !currentSearch);
                }
                updateStatsAndVisibility();
            });
        }

        if (searchClearBtn) {
            searchClearBtn.addEventListener('click', () => {
                if (searchInput) {
                    searchInput.value = '';
                    currentSearch = '';
                    searchClearBtn.classList.add('is-hidden');
                    updateStatsAndVisibility();
                    searchInput.focus();
                }
            });
        }

        // Delegated click handler on card container for foolproof modal triggers
        if (listContainer) {
            listContainer.addEventListener('click', e => {
                const apiBtn = e.target.closest('.btn-api, [data-action="open-api-modal"]');
                if (apiBtn) {
                    e.preventDefault();
                    const card = apiBtn.closest('.social-card');
                    if (card) openApiModal(card);
                    return;
                }

                const bufferBtn = e.target.closest('.btn-buffer, [data-action="open-buffer-modal"]');
                if (bufferBtn) {
                    e.preventDefault();
                    const card = bufferBtn.closest('.social-card');
                    if (card) openBufferModal(card);
                    return;
                }

                const iftttBtn = e.target.closest('.btn-ifttt, [data-action="open-ifttt-modal"]');
                if (iftttBtn) {
                    e.preventDefault();
                    const card = iftttBtn.closest('.social-card');
                    if (card) openIftttModal(card);
                    return;
                }

                const editableUrl = e.target.closest('.social-url-editable');
                if (editableUrl) {
                    e.preventDefault();
                    const card = editableUrl.closest('.social-card');
                    if (card) {
                        openEditModal({
                            id: card.dataset.id,
                            platform: card.dataset.platform,
                            url: card.dataset.url,
                            icon_class: card.dataset.icon,
                            is_active: card.dataset.active
                        });
                    }
                    return;
                }
            });
        }

        // Check if cards already exist from server side rendering
        const initialCards = listContainer ? listContainer.querySelectorAll('.social-card') : [];
        if (initialCards.length > 0) {
            initialCards.forEach((card, index) => {
                const switchInput = card.querySelector('.social-switch input');
                const apiBtn = card.querySelector('.btn-api');
                const bufferBtn = card.querySelector('.btn-buffer');
                const iftttBtn = card.querySelector('.btn-ifttt');
                const upBtn = card.querySelector('.reorder-up');
                const downBtn = card.querySelector('.reorder-down');
                const id = parseInt(card.dataset.id, 10);
                const statusTag = card.querySelector('.social-status-tag');
                const editableUrl = card.querySelector('.social-url-editable');

                if (switchInput) {
                    switchInput.addEventListener('change', async function () {
                        const newActive = this.checked ? 'y' : 'n';
                        const body = new URLSearchParams();
                        body.set('id', id);
                        body.set('is_active', newActive);

                        try {
                            const res = await fetch(toggleUrl, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                                body: body.toString()
                            });
                            const payload = await res.json();
                            if (payload && payload.status === 'success') {
                                card.dataset.active = newActive;
                                if (newActive === 'y') {
                                    card.classList.remove('is-inactive');
                                    if (statusTag) {
                                        statusTag.className = 'social-status-tag active';
                                        statusTag.textContent = 'Active';
                                    }
                                } else {
                                    card.classList.add('is-inactive');
                                    if (statusTag) {
                                        statusTag.className = 'social-status-tag inactive';
                                        statusTag.textContent = 'Inactive';
                                    }
                                }
                                updateStatsAndVisibility();
                                showToast(payload.message || 'Status updated', 'success');
                            } else {
                                throw new Error(payload.message || 'Error updating status');
                            }
                        } catch (err) {
                            this.checked = !this.checked;
                            showToast(err.message || 'Connection error', 'error');
                        }
                    });
                }

                // Click on URL box opens edit modal
                if (editableUrl) {
                    editableUrl.addEventListener('click', () => {
                        openEditModal({
                            id: card.dataset.id,
                            platform: card.dataset.platform,
                            url: card.dataset.url,
                            icon_class: card.dataset.icon,
                            is_active: card.dataset.active
                        });
                    });
                }

                if (apiBtn) {
                    apiBtn.addEventListener('click', () => openApiModal(card));
                }

                if (bufferBtn) {
                    bufferBtn.addEventListener('click', () => openBufferModal(card));
                }

                if (iftttBtn) {
                    iftttBtn.addEventListener('click', () => openIftttModal(card));
                }

                if (upBtn) {
                    upBtn.addEventListener('click', () => {
                        const prev = card.previousElementSibling;
                        if (prev && prev.classList.contains('social-card')) {
                            listContainer.insertBefore(card, prev);
                            updateStatsAndVisibility();
                            saveReorder();
                        }
                    });
                }

                if (downBtn) {
                    downBtn.addEventListener('click', () => {
                        const next = card.nextElementSibling;
                        if (next && next.classList.contains('social-card')) {
                            listContainer.insertBefore(next, card);
                            updateStatsAndVisibility();
                            saveReorder();
                        }
                    });
                }
            });

            updateStatsAndVisibility();
        } else {
            loadItems();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSocialMediaAdmin);
    } else {
        initSocialMediaAdmin();
    }
})();
