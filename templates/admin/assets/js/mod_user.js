/**
 * Admin User Module Logic - Material 3 Phone-First Super Polish
 * Strictly separated JavaScript without any inline script tags or inline event handlers.
 */

(function () {
    'use strict';

    class UserManager {
        constructor() {
            this.container = document.getElementById('userListContainer');
            this.loader = document.getElementById('infiniteLoader');
            this.emptyState = document.getElementById('emptyState');
            this.loadMoreBtn = document.getElementById('loadMoreBtn');
            this.filterForm = document.getElementById('filterForm');
            this.ptrSpinner = document.getElementById('ptrSpinner');
            this.toolbar = document.getElementById('userToolbar');

            if (!this.container) return;

            this.moduleUrl = this.container.dataset.moduleUrl || '/mod_user/';
            this.dataUrl = this.container.dataset.dataUrl || '/mod_user/datatable_json';
            this.imageUrl = this.container.dataset.imageUrl || '/mod_user/thumb/';

            this.lang = this.initLang();
            this.isLoading = false;
            this.hasMore = true;
            this.start = 0;
            this.length = 15;
            this.draw = 1;
            this.activeStatusFilter = 'all';

            this.selectedUserId = null;
            this.selectedCard = null;
            this.selectedStatus = null;

            this.init();
        }

        initLang() {
            const userLang = window.adminLang?.mod_user || {};
            return {
                confirm_delete: userLang.remove_record || 'Are you sure you want to delete this user?',
                confirm_activate: userLang.grid?.status?.value || 'Are you sure you want to activate this user?',
                confirm_deactivate: userLang.grid?.deactivate_confirm?.value || 'Are you sure you want to deactivate this user?',
                confirm_yes: userLang.button?.yes || 'Yes',
                confirm_no: userLang.button?.no || 'No',
                activate: userLang.grid?.status?.key || userLang.grid?.activate || 'Activate',
                deactivate: userLang.grid?.deactivate_confirm?.key || userLang.grid?.deactivate || 'Deactivate',
                status_active: userLang.grid?.active || userLang.active_users || 'Active',
                status_email_pending: userLang.grid?.waiting_email_activation || 'Waiting for Email',
                status_admin_pending: userLang.grid?.waiting_admin_activation || 'Waiting for Admin',
                status_pending: userLang.pending_users || 'Pending',
                ctx_edit: userLang.button?.edit || 'Edit',
                ctx_delete: userLang.button?.delete || 'Delete'
            };
        }

        init() {
            this.bindEvents();
            this.loadData();
        }

        bindEvents() {
            // Stats Filter Chips
            const statCards = document.querySelectorAll('[data-status-filter]');
            statCards.forEach(card => {
                card.addEventListener('click', (e) => {
                    const filter = card.dataset.statusFilter;
                    this.setStatusFilter(filter, card);
                });
            });

            // Search Input with Debounce
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                let debounceTimeout = null;
                searchInput.addEventListener('input', (e) => {
                    clearTimeout(debounceTimeout);
                    debounceTimeout = setTimeout(() => {
                        const filterSearch = document.getElementById('filterSearch');
                        if (filterSearch) {
                            filterSearch.value = e.target.value.trim();
                            this.loadData(true);
                        }
                    }, 400);
                });
            }

            // Clear Search Buttons
            const clearSearchBtns = document.querySelectorAll('[data-action="clear-search"]');
            clearSearchBtns.forEach(btn => {
                btn.addEventListener('click', () => this.clearSearch());
            });

            // Load More Button
            if (this.loadMoreBtn) {
                this.loadMoreBtn.addEventListener('click', () => this.loadData());
            }

            // Bottom Toolbar Action Buttons
            const toolbarEdit = document.getElementById('toolbarEdit');
            if (toolbarEdit) {
                toolbarEdit.addEventListener('click', () => this.editSelected());
            }

            const toolbarActivate = document.getElementById('toolbarActivate');
            if (toolbarActivate) {
                toolbarActivate.addEventListener('click', () => this.activateSelected());
            }

            const toolbarAcl = document.getElementById('toolbarAcl');
            if (toolbarAcl) {
                toolbarAcl.addEventListener('click', () => this.aclSelected());
            }

            const toolbarDelete = document.getElementById('toolbarDelete');
            if (toolbarDelete) {
                toolbarDelete.addEventListener('click', () => this.deleteSelected());
            }

            // Card Delegated Actions (Selection & 3-Dots Action Menu)
            this.container.addEventListener('click', (e) => {
                const actionBtn = e.target.closest('[data-action="open-context-menu"]');
                if (actionBtn) {
                    e.stopPropagation();
                    const card = actionBtn.closest('.user-card');
                    if (card) {
                        const id = card.dataset.id;
                        const name = card.dataset.name;
                        const status = card.dataset.status;
                        this.openActionSheet(id, name, status);
                    }
                    return;
                }

                const card = e.target.closest('.user-card');
                if (card) {
                    const id = card.dataset.id;
                    const status = card.dataset.status;
                    this.selectUser(id, card, status);
                }
            });

            // Pull to refresh hook
            window.refreshContent = () => this.loadData(true);

            // Context Action Override for this module
            window.handleContextAction = (action) => this.handleContextAction(action);
        }

        setStatusFilter(filter, activeCard) {
            document.querySelectorAll('[data-status-filter]').forEach(el => el.classList.remove('active'));
            if (activeCard) {
                activeCard.classList.add('active');
            }

            this.activeStatusFilter = filter;
            const filterStatus = document.getElementById('filterStatus');
            if (filterStatus) {
                filterStatus.value = filter;
            }

            this.loadData(true);
        }

        clearSearch() {
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.value = '';
            }
            const filterSearch = document.getElementById('filterSearch');
            if (filterSearch) {
                filterSearch.value = '';
            }
            this.loadData(true);
        }

        async loadData(reset = false) {
            if (this.isLoading || (!this.hasMore && !reset)) return;
            this.isLoading = true;

            if (this.loadMoreBtn) {
                this.loadMoreBtn.disabled = true;
            }

            if (reset) {
                this.start = 0;
                this.container.innerHTML = '';
                this.hasMore = true;
                this.draw = 1;
                if (this.emptyState) this.emptyState.classList.add('is-hidden');
                this.selectedUserId = null;
                this.selectedCard = null;
                this.selectedStatus = null;
                this.updateToolbar(false);
            }

            if (this.loader) this.loader.classList.remove('is-hidden');

            const formData = new FormData(this.filterForm || undefined);
            formData.set('start', this.start);
            formData.set('length', this.length);
            formData.set('draw', this.draw);

            try {
                const response = await fetch(this.dataUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.status === 401) {
                    const data = await response.json();
                    if (data.redirect) window.location.href = data.redirect;
                    return;
                }

                const data = await response.json();
                this.renderCards(data.data || []);
                this.start += this.length;
                this.draw++;

                if (this.start >= (data.recordsFiltered || 0)) {
                    this.hasMore = false;
                }

                if (!data.data || data.data.length === 0 || data.recordsFiltered === 0) {
                    if (this.emptyState) this.emptyState.classList.remove('is-hidden');
                } else {
                    if (this.emptyState) this.emptyState.classList.add('is-hidden');
                }
            } catch (error) {
                console.error('mod_user loadData error:', error);
            } finally {
                this.isLoading = false;
                if (this.loader) this.loader.classList.add('is-hidden');
                if (this.loadMoreBtn) {
                    this.loadMoreBtn.classList.toggle('is-hidden', !this.hasMore);
                    this.loadMoreBtn.disabled = false;
                }
            }
        }

        renderCards(items) {
            if (!items || items.length === 0) return;
            const fragment = document.createDocumentFragment();

            items.forEach(item => {
                // Item mapping from datatable_json:
                // 0: Checkbox HTML, 1: ID, 2: Username, 3: Email, 4: Name, 5: Last Name,
                // 6: Status HTML, 7: Group ID, 8: Image, 9: Actions HTML,
                // 10: Group Name, 11: Created At, 12: Phone
                const id = this.stripHtml(item[1]);
                const username = this.stripHtml(item[2]);
                const email = this.stripHtml(item[3]);
                const name = this.stripHtml(item[4]);
                const lastName = this.stripHtml(item[5]);
                const statusHtml = item[6] || '';
                const image = this.stripHtml(item[8]);
                const groupName = item[10] ? this.stripHtml(item[10]) : '';
                const createdAt = item[11] ? this.stripHtml(item[11]) : '';
                const phone = item[12] ? this.stripHtml(item[12]) : '';
                const rawStatus = item[13] || (statusHtml.includes('success') ? 'y' : (statusHtml.includes(this.lang.status_admin_pending) ? 'a' : 'n'));

                // Status Evaluation
                let statusClass = 'inactive';
                let statusText = this.lang.status_email_pending;
                let statusCode = 'n';
                let dotClass = 'dot-inactive';

                if (rawStatus === 'y') {
                    statusClass = 'active';
                    statusText = this.lang.status_active;
                    statusCode = 'y';
                    dotClass = '';
                } else if (rawStatus === 'a') {
                    statusClass = 'pending';
                    statusText = this.lang.status_admin_pending;
                    statusCode = 'a';
                    dotClass = 'dot-pending';
                }

                // Avatar Fallback Initial
                const displayName = (name || username || '?').trim();
                const initial = displayName.charAt(0).toUpperCase();
                let avatarInner = '';

                if (image && image !== 'avatar.png') {
                    const avatarSrc = `${this.imageUrl}${image}`;
                    avatarInner = `<img src="${avatarSrc}" alt="${this.escapeHtml(displayName)}" onerror="this.onerror=null;this.parentElement.innerHTML='${initial}'">`;
                } else {
                    avatarInner = initial;
                }

                const fullName = `${name} ${lastName}`.trim() || username;

                const card = document.createElement('div');
                card.className = 'user-card';
                card.dataset.id = id;
                card.dataset.name = fullName;
                card.dataset.status = statusCode;

                card.innerHTML = `
                    <div class="user-avatar-wrap">
                        <div class="user-avatar">${avatarInner}</div>
                        <span class="user-online-dot ${dotClass}"></span>
                    </div>
                    <div class="user-info">
                        <div class="user-name-row">
                            <span class="user-name">${this.escapeHtml(fullName)}</span>
                            ${groupName ? `<span class="user-group-badge">${this.escapeHtml(groupName)}</span>` : ''}
                        </div>
                        <div class="user-meta-row">
                            <span class="user-meta-item"><i class="bi bi-at"></i> ${this.escapeHtml(username)}</span>
                            ${email ? `<span class="user-meta-item"><i class="bi bi-envelope"></i> ${this.escapeHtml(email)}</span>` : ''}
                            ${phone ? `<span class="user-meta-item"><i class="bi bi-telephone"></i> ${this.escapeHtml(phone)}</span>` : ''}
                            ${createdAt ? `<span class="user-meta-item"><i class="bi bi-calendar3"></i> ${this.escapeHtml(createdAt)}</span>` : ''}
                        </div>
                    </div>
                    <div class="user-right-col">
                        <span class="user-status ${statusClass}">${statusText}</span>
                        <button type="button" class="m3-icon-btn" data-action="open-context-menu" aria-label="Actions">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                    </div>
                `;

                fragment.appendChild(card);
            });

            this.container.appendChild(fragment);
        }

        selectUser(id, cardElement, status) {
            const isCurrent = cardElement.classList.contains('selected');
            document.querySelectorAll('.user-card.selected').forEach(el => el.classList.remove('selected'));

            if (!isCurrent) {
                cardElement.classList.add('selected');
                this.selectedUserId = id;
                this.selectedCard = cardElement;
                this.selectedStatus = status;
                this.updateToolbar(true);
                if (navigator.vibrate) navigator.vibrate(10);
            } else {
                this.selectedUserId = null;
                this.selectedCard = null;
                this.selectedStatus = null;
                this.updateToolbar(false);
            }
        }

        updateToolbar(active) {
            const ids = ['toolbarEdit', 'toolbarActivate', 'toolbarAcl', 'toolbarDelete'];
            ids.forEach(btnId => {
                const btn = document.getElementById(btnId);
                if (!btn) return;
                if (active) {
                    btn.classList.add('active', 'is-active');
                    btn.classList.remove('is-disabled');
                } else {
                    btn.classList.remove('active', 'is-active');
                    btn.classList.add('is-disabled');
                }
            });

            const activateIcon = document.getElementById('toolbarActivateIcon');
            const activateText = document.getElementById('toolbarActivateText');
            if (activateIcon && activateText && active) {
                if (this.selectedStatus === 'y') {
                    activateIcon.className = 'bi bi-eye-slash-fill';
                    activateText.textContent = this.lang.deactivate;
                } else {
                    activateIcon.className = 'bi bi-eye-fill';
                    activateText.textContent = this.lang.activate;
                }
            }
        }

        openActionSheet(id, title, status) {
            this.selectedUserId = id;
            this.selectedStatus = status;

            if (typeof openContextMenu === 'function') {
                openContextMenu(id, title, status === 'y', ['gallery', 'files', 'faq']);

                setTimeout(() => {
                    const editItem = document.querySelector('#context-menu-sheet .context-action-item:first-child .context-action-label');
                    if (editItem) editItem.textContent = this.lang.ctx_edit;

                    const statusAction = document.getElementById('contextActionStatus');
                    if (statusAction) {
                        const label = statusAction.querySelector('.nav-item-label');
                        const icon = statusAction.querySelector('.nav-item-icon');
                        if (label && icon) {
                            if (status === 'y') {
                                label.textContent = this.lang.deactivate;
                                icon.className = 'bi bi-eye-slash nav-item-icon';
                            } else {
                                label.textContent = this.lang.activate;
                                icon.className = 'bi bi-eye nav-item-icon';
                            }
                        }
                    }

                    const deleteItem = document.querySelector('#context-menu-sheet .action-danger .context-action-label');
                    if (deleteItem) deleteItem.textContent = this.lang.ctx_delete;
                }, 15);
            }
        }

        handleContextAction(action) {
            const id = window.currentContentId || this.selectedUserId;
            if (!id) return;

            switch (action) {
                case 'edit':
                    window.location.href = `${this.moduleUrl}edit/${id}`;
                    break;
                case 'delete':
                    this.selectedUserId = id;
                    this.deleteSelected();
                    break;
                case 'status':
                    const card = document.querySelector(`.user-card[data-id="${id}"]`);
                    this.selectedUserId = id;
                    this.selectedStatus = card ? card.dataset.status : 'n';
                    this.activateSelected();
                    break;
            }

            if (typeof closeContextMenu === 'function') closeContextMenu();
        }

        editSelected() {
            if (this.selectedUserId) {
                window.location.href = `${this.moduleUrl}edit/${this.selectedUserId}`;
            }
        }

        aclSelected() {
            if (this.selectedUserId) {
                window.location.href = `${this.moduleUrl}acl`;
            }
        }

        activateSelected() {
            if (!this.selectedUserId) return;

            const isActive = this.selectedStatus === 'y';
            const endpoint = isActive ? 'deactivate' : 'status';
            const confirmMsg = isActive ? this.lang.confirm_deactivate : this.lang.confirm_activate;

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'question',
                    text: confirmMsg,
                    showCancelButton: true,
                    confirmButtonColor: '#6750a4',
                    cancelButtonColor: '#79747e',
                    confirmButtonText: this.lang.confirm_yes,
                    cancelButtonText: this.lang.confirm_no
                }).then(result => {
                    if (result.isConfirmed) {
                        this.executeStatusToggle(endpoint);
                    }
                });
            } else if (confirm(confirmMsg)) {
                this.executeStatusToggle(endpoint);
            }
        }

        executeStatusToggle(endpoint) {
            const formData = new FormData();
            formData.append('query[]', this.selectedUserId);

            fetch(`${this.moduleUrl}${endpoint}`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(r => r.text())
                .then(msg => {
                    if (msg === 'Success') {
                        this.selectedUserId = null;
                        this.updateToolbar(false);
                        this.loadData(true);
                    } else if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', text: msg });
                    } else {
                        alert(msg);
                    }
                })
                .catch(err => {
                    console.error('Status toggle error:', err);
                });
        }

        deleteSelected() {
            if (!this.selectedUserId) return;

            const confirmMsg = this.lang.confirm_delete;

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    text: confirmMsg,
                    showCancelButton: true,
                    confirmButtonColor: '#ba1a1a',
                    cancelButtonColor: '#79747e',
                    confirmButtonText: this.lang.confirm_yes,
                    cancelButtonText: this.lang.confirm_no
                }).then(result => {
                    if (result.isConfirmed) {
                        this.executeDelete();
                    }
                });
            } else if (confirm(confirmMsg)) {
                this.executeDelete();
            }
        }

        executeDelete() {
            const formData = new FormData();
            formData.append('delete[]', this.selectedUserId);

            fetch(`${this.moduleUrl}delete`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(r => r.text())
                .then(msg => {
                    if (msg === 'Success') {
                        this.selectedUserId = null;
                        this.updateToolbar(false);
                        this.loadData(true);
                    } else if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', text: msg });
                    } else {
                        alert(msg);
                    }
                })
                .catch(err => {
                    console.error('Delete error:', err);
                });
        }

        stripHtml(html) {
            if (!html) return '';
            const tmp = document.createElement('div');
            tmp.innerHTML = html;
            return (tmp.textContent || tmp.innerText || '').trim();
        }

        escapeHtml(str) {
            if (!str) return '';
            return str
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    }

    class UserFormManager {
        constructor() {
            this.form = document.getElementById('addForm') || document.getElementById('editForm') || document.getElementById('settingsForm');
            if (!this.form) return;

            this.alertContainer = document.getElementById('formAlert');
            this.submitBtn = document.getElementById('submitBtn') || this.form.querySelector('button[type="submit"]');
            this.countryPickerBtn = document.getElementById('countryPickerBtn');
            this.countryModal = null;

            this.countries = [
                { name: 'Turkey', code: '+90', flag: '🇹🇷' },
                { name: 'Iraq', code: '+964', flag: '🇮🇶' },
                { name: 'Syria', code: '+963', flag: '🇸🇾' },
                { name: 'Iran', code: '+98', flag: '🇮🇷' },
                { name: 'Germany', code: '+49', flag: '🇩🇪' },
                { name: 'Sweden', code: '+46', flag: '🇸🇪' },
                { name: 'France', code: '+33', flag: '🇫🇷' },
                { name: 'United Kingdom', code: '+44', flag: '🇬🇧' },
                { name: 'United States', code: '+1', flag: '🇺🇸' },
                { name: 'Canada', code: '+1', flag: '🇨🇦' },
                { name: 'Netherlands', code: '+31', flag: '🇳🇱' },
                { name: 'Switzerland', code: '+41', flag: '🇨🇭' },
                { name: 'Austria', code: '+43', flag: '🇦🇹' },
                { name: 'Belgium', code: '+32', flag: '🇧🇪' },
                { name: 'Denmark', code: '+45', flag: '🇩🇰' },
                { name: 'Norway', code: '+47', flag: '🇳🇴' },
                { name: 'Finland', code: '+358', flag: '🇫🇮' },
                { name: 'Russia', code: '+7', flag: '🇷🇺' },
                { name: 'Azerbaijan', code: '+994', flag: '🇦🇿' },
                { name: 'Georgia', code: '+995', flag: '🇬🇪' },
                { name: 'Armenia', code: '+374', flag: '🇦🇲' },
                { name: 'Greece', code: '+30', flag: '🇬🇷' },
                { name: 'Cyprus', code: '+357', flag: '🇨🇾' },
                { name: 'Italy', code: '+39', flag: '🇮🇹' },
                { name: 'Spain', code: '+34', flag: '🇪🇸' },
                { name: 'Portugal', code: '+351', flag: '🇵🇹' },
                { name: 'Poland', code: '+48', flag: '🇵🇱' },
                { name: 'Ukraine', code: '+380', flag: '🇺🇦' },
                { name: 'Australia', code: '+61', flag: '🇦🇺' },
                { name: 'Japan', code: '+81', flag: '🇯🇵' },
                { name: 'South Korea', code: '+82', flag: '🇰🇷' },
                { name: 'China', code: '+86', flag: '🇨🇳' },
                { name: 'India', code: '+91', flag: '🇮🇳' },
                { name: 'Pakistan', code: '+92', flag: '🇵🇰' },
                { name: 'Afghanistan', code: '+93', flag: '🇦🇫' },
                { name: 'Saudi Arabia', code: '+966', flag: '🇸🇦' },
                { name: 'United Arab Emirates', code: '+971', flag: '🇦🇪' },
                { name: 'Kuwait', code: '+965', flag: '🇰🇼' },
                { name: 'Qatar', code: '+974', flag: '🇶🇦' },
                { name: 'Lebanon', code: '+961', flag: '🇱🇧' },
                { name: 'Jordan', code: '+962', flag: '🇯🇴' },
                { name: 'Egypt', code: '+20', flag: '🇪🇬' },
                { name: 'Morocco', code: '+212', flag: '🇲🇦' },
                { name: 'Tunisia', code: '+216', flag: '🇹🇳' },
                { name: 'Algeria', code: '+213', flag: '🇩🇿' },
                { name: 'Brazil', code: '+55', flag: '🇧🇷' },
                { name: 'Mexico', code: '+52', flag: '🇲🇽' },
                { name: 'Argentina', code: '+54', flag: '🇦🇷' },
                { name: 'South Africa', code: '+27', flag: '🇿🇦' }
            ];

            this.init();
        }

        init() {
            this.bindTabSwitching();
            this.bindAvatarControls();
            this.bindPasswordControls();
            this.bindPhoneControls();
            this.bindSocialControls();
            this.bindCharCounters();
            this.bindLiveHeroSync();
            this.bindFormSubmission();
        }

        bindTabSwitching() {
            const tabButtons = document.querySelectorAll('[data-action="switch-tab"]');
            tabButtons.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const targetId = btn.dataset.tabTarget;
                    this.switchTab(targetId, btn);
                });
            });
        }

        switchTab(targetId, activeBtn) {
            const tabButtons = document.querySelectorAll('[data-action="switch-tab"]');
            const tabPanes = document.querySelectorAll('.m3-tab-content');

            tabButtons.forEach(btn => {
                const isActive = (btn === activeBtn || btn.dataset.tabTarget === targetId);
                btn.classList.toggle('active', isActive);
                btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });

            tabPanes.forEach(pane => {
                pane.classList.toggle('active', pane.id === targetId);
            });
        }

        bindAvatarControls() {
            const avatarInput = document.getElementById('image');
            const previewContainer = document.getElementById('avatarPreviewContainer');
            const deleteInput = document.getElementById('deleteImageInput');

            // Select avatar trigger
            document.querySelectorAll('[data-action="select-avatar"]').forEach(el => {
                el.addEventListener('click', () => {
                    if (avatarInput) avatarInput.click();
                });
            });

            // Preview avatar on file change
            if (avatarInput) {
                avatarInput.addEventListener('change', () => {
                    if (!avatarInput.files || !avatarInput.files[0]) return;
                    const file = avatarInput.files[0];

                    if (file.size > 2 * 1024 * 1024) {
                        this.showAlert('danger', window.adminLang?.mod_user?.image_size_error || 'File size exceeds 2MB limit');
                        avatarInput.value = '';
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.renderAvatarPreview(e.target.result);
                        if (deleteInput) deleteInput.value = '0';
                    };
                    reader.readAsDataURL(file);
                });
            }

            // Delete avatar
            document.querySelectorAll('[data-action="delete-avatar"]').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (avatarInput) avatarInput.value = '';
                    if (deleteInput) deleteInput.value = '1';
                    this.resetAvatarPreview();
                });
            });
        }

        renderAvatarPreview(src) {
            const container = document.getElementById('avatarPreviewContainer');
            if (!container) return;

            container.innerHTML = `
                <img src="${src}" id="avatarPreview" class="m3-hero-avatar" alt="Avatar">
                <button type="button" class="m3-hero-avatar-btn remove-btn" data-action="delete-avatar" title="Remove Avatar">
                    <i class="bi bi-trash"></i>
                </button>
            `;

            const newDeleteBtn = container.querySelector('[data-action="delete-avatar"]');
            if (newDeleteBtn) {
                newDeleteBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const avatarInput = document.getElementById('image');
                    const deleteInput = document.getElementById('deleteImageInput');
                    if (avatarInput) avatarInput.value = '';
                    if (deleteInput) deleteInput.value = '1';
                    this.resetAvatarPreview();
                });
            }
        }

        resetAvatarPreview() {
            const container = document.getElementById('avatarPreviewContainer');
            if (!container) return;

            container.innerHTML = `
                <div class="m3-hero-avatar-placeholder" data-action="select-avatar" role="button" tabindex="0">
                    <i class="bi bi-person-fill"></i>
                </div>
                <button type="button" class="m3-hero-avatar-btn upload-btn" data-action="select-avatar" title="Upload Avatar">
                    <i class="bi bi-camera-fill"></i>
                </button>
            `;

            container.querySelectorAll('[data-action="select-avatar"]').forEach(el => {
                el.addEventListener('click', () => {
                    const avatarInput = document.getElementById('image');
                    if (avatarInput) avatarInput.click();
                });
            });
        }

        bindPasswordControls() {
            // Toggle visibility
            document.querySelectorAll('[data-action="toggle-password"]').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const targetId = btn.dataset.target || 'password';
                    const input = document.getElementById(targetId);
                    if (!input) return;

                    const isPassword = input.type === 'password';
                    input.type = isPassword ? 'text' : 'password';

                    const icon = btn.querySelector('i');
                    if (icon) {
                        icon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
                    }
                });
            });

            // Live password strength
            const passwordInputs = document.querySelectorAll('[data-action="check-strength"]');
            passwordInputs.forEach(input => {
                input.addEventListener('input', () => {
                    this.evaluatePasswordStrength(input.value);
                });
            });
        }

        evaluatePasswordStrength(password) {
            const progressBar = document.getElementById('strength-progress');
            const progressText = document.getElementById('strength-text');
            if (!progressBar) return;

            if (!password) {
                progressBar.className = 'strength-progress';
                progressBar.style.width = '0%';
                if (progressText) progressText.textContent = '';
                return;
            }

            let score = 0;
            if (password.length >= 8) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[a-z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[^A-Za-z0-9]/.test(password)) score++;

            progressBar.className = 'strength-progress';
            if (score <= 2) {
                progressBar.classList.add('weak');
                if (progressText) {
                    progressText.textContent = 'Weak';
                    progressText.style.color = '#ba1a1a';
                }
            } else if (score === 3) {
                progressBar.classList.add('fair');
                if (progressText) {
                    progressText.textContent = 'Fair';
                    progressText.style.color = '#d97706';
                }
            } else if (score === 4) {
                progressBar.classList.add('good');
                if (progressText) {
                    progressText.textContent = 'Good';
                    progressText.style.color = '#2563eb';
                }
            } else {
                progressBar.classList.add('strong');
                if (progressText) {
                    progressText.textContent = 'Strong';
                    progressText.style.color = '#16a34a';
                }
            }
        }

        bindPhoneControls() {
            if (this.countryPickerBtn) {
                this.countryPickerBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.openCountryPicker();
                });
            }

            const phoneInput = document.getElementById('phone');
            if (phoneInput) {
                phoneInput.addEventListener('input', () => {
                    const val = phoneInput.value.trim();
                    if (val.startsWith('+')) {
                        // Match country code
                        const matched = this.countries.find(c => val.startsWith(c.code));
                        if (matched) {
                            this.setCountryCode(matched.code);
                            phoneInput.value = val.substring(matched.code.length).trim();
                        }
                    }
                });
            }
        }

        openCountryPicker() {
            if (document.querySelector('.af-country-sheet-backdrop')) return;

            const backdrop = document.createElement('div');
            backdrop.className = 'af-country-sheet-backdrop';

            const currentCode = document.getElementById('country_code')?.value || '+90';

            backdrop.innerHTML = `
                <div class="af-country-sheet" role="dialog" aria-modal="true">
                    <div class="af-sheet-header">
                        <h4 class="af-sheet-title">${window.adminLang?.mod_user?.admin?.select_country_code || 'Select Country Code'}</h4>
                        <button type="button" class="af-sheet-close-btn" data-action="close-sheet">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="af-sheet-search-wrap">
                        <input type="text" class="af-sheet-search-input" id="countrySearchInput" placeholder="${window.adminLang?.mod_user?.admin?.search_country_or_code || 'Search country or code...'}" autocomplete="off">
                    </div>
                    <div class="af-sheet-list" id="countrySheetList"></div>
                </div>
            `;

            document.body.appendChild(backdrop);
            this.renderCountryList(this.countries, currentCode);

            // Search filter
            const searchInput = backdrop.querySelector('#countrySearchInput');
            searchInput.focus();
            searchInput.addEventListener('input', (e) => {
                const q = e.target.value.toLowerCase().trim();
                const filtered = this.countries.filter(c =>
                    c.name.toLowerCase().includes(q) || c.code.includes(q)
                );
                this.renderCountryList(filtered, currentCode);
            });

            // Close sheet
            backdrop.querySelector('[data-action="close-sheet"]').addEventListener('click', () => {
                backdrop.remove();
            });

            backdrop.addEventListener('click', (e) => {
                if (e.target === backdrop) backdrop.remove();
            });
        }

        renderCountryList(list, currentCode) {
            const listContainer = document.getElementById('countrySheetList');
            if (!listContainer) return;

            if (list.length === 0) {
                listContainer.innerHTML = `<div style="text-align: center; padding: 24px; color: #888;">${window.adminLang?.mod_user?.admin?.no_country_found || 'No matching country found'}</div>`;
                return;
            }

            listContainer.innerHTML = list.map(c => `
                <button type="button" class="af-country-item ${c.code === currentCode ? 'selected' : ''}" data-country-code="${c.code}">
                    <span class="af-country-item-name">${c.flag} ${c.name}</span>
                    <span class="af-country-item-code">${c.code}</span>
                </button>
            `).join('');

            listContainer.querySelectorAll('.af-country-item').forEach(btn => {
                btn.addEventListener('click', () => {
                    const code = btn.dataset.countryCode;
                    this.setCountryCode(code);
                    document.querySelector('.af-country-sheet-backdrop')?.remove();
                });
            });
        }

        setCountryCode(code) {
            const countryInput = document.getElementById('country_code');
            const countryDisplay = document.getElementById('countryDisplay');
            if (countryInput) countryInput.value = code;
            if (countryDisplay) countryDisplay.textContent = code;
        }

        bindSocialControls() {
            // Edit button or preview click toggles drawer
            document.querySelectorAll('[data-action="edit-social-link"]').forEach(trigger => {
                trigger.addEventListener('click', (e) => {
                    e.preventDefault();
                    const key = trigger.dataset.key;
                    const drawer = document.getElementById(`social_editor_${key}`);
                    if (!drawer) return;

                    const isHidden = drawer.hasAttribute('hidden');
                    if (isHidden) {
                        drawer.removeAttribute('hidden');
                        const input = drawer.querySelector('.social-url-input');
                        if (input) input.focus();
                    } else {
                        drawer.setAttribute('hidden', '');
                    }
                });
            });

            // Apply button
            document.querySelectorAll('[data-action="apply-social-link"]').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const key = btn.dataset.key;
                    const urlInput = document.getElementById(`social_url_${key}`);
                    const hiddenInput = document.getElementById(`social_input_${key}`);
                    const previewText = document.getElementById(`social_preview_${key}`);
                    const activeSwitch = document.getElementById(`social_active_${key}`);
                    const itemRow = btn.closest('.settings-social-item');
                    const drawer = document.getElementById(`social_editor_${key}`);

                    const val = urlInput?.value.trim() || '';
                    if (hiddenInput) hiddenInput.value = val;

                    if (val !== '') {
                        if (previewText) previewText.textContent = val;
                        if (activeSwitch) activeSwitch.checked = true;
                        if (itemRow) itemRow.classList.add('has-value');
                    } else {
                        if (previewText) previewText.innerHTML = '<span class="social-placeholder">https://...</span>';
                        if (activeSwitch) activeSwitch.checked = false;
                        if (itemRow) itemRow.classList.remove('has-value');
                    }

                    if (drawer) drawer.setAttribute('hidden', '');
                });
            });

            // Clear button
            document.querySelectorAll('[data-action="clear-social-link"]').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const key = btn.dataset.key;
                    const urlInput = document.getElementById(`social_url_${key}`);
                    const hiddenInput = document.getElementById(`social_input_${key}`);
                    const previewText = document.getElementById(`social_preview_${key}`);
                    const activeSwitch = document.getElementById(`social_active_${key}`);
                    const itemRow = btn.closest('.settings-social-item');
                    const drawer = document.getElementById(`social_editor_${key}`);

                    if (urlInput) urlInput.value = '';
                    if (hiddenInput) hiddenInput.value = '';
                    if (previewText) previewText.innerHTML = '<span class="social-placeholder">https://...</span>';
                    if (activeSwitch) activeSwitch.checked = false;
                    if (itemRow) itemRow.classList.remove('has-value');
                    if (drawer) drawer.setAttribute('hidden', '');
                });
            });
        }

        bindCharCounters() {
            document.querySelectorAll('[data-char-limit]').forEach(field => {
                const max = parseInt(field.dataset.charLimit, 10) || 255;
                const counterId = field.dataset.counterId;
                const counter = counterId ? document.getElementById(counterId) : null;

                field.addEventListener('input', () => {
                    const len = field.value.length;
                    if (counter) {
                        counter.textContent = `${len} / ${max}`;
                        if (len > max) {
                            counter.style.color = '#ba1a1a';
                        } else {
                            counter.style.color = 'var(--md-sys-color-on-surface-variant, #49454f)';
                        }
                    }
                });
            });
        }

        bindLiveHeroSync() {
            const nameInput = document.getElementById('name');
            const lastNameInput = document.getElementById('last_name');
            const usernameInput = document.getElementById('username');
            const heroName = document.getElementById('heroNamePreview');
            const heroSubtitle = document.getElementById('heroSubtitlePreview');

            const updateHero = () => {
                const name = nameInput?.value.trim() || '';
                const lastName = lastNameInput?.value.trim() || '';
                const username = usernameInput?.value.trim() || '';

                if (heroName) {
                    heroName.textContent = (name || lastName) ? `${name} ${lastName}`.trim() : (username || 'New User');
                }
                if (heroSubtitle) {
                    heroSubtitle.textContent = username ? `@${username}` : '@user';
                }
            };

            if (nameInput) nameInput.addEventListener('input', updateHero);
            if (lastNameInput) lastNameInput.addEventListener('input', updateHero);
            if (usernameInput) usernameInput.addEventListener('input', updateHero);
        }

        bindFormSubmission() {
            this.form.addEventListener('submit', (e) => {
                e.preventDefault();

                // Validate client side required inputs
                const invalidField = this.form.querySelector(':invalid');
                if (invalidField) {
                    // Find which tab this field is inside
                    const tabPane = invalidField.closest('.m3-tab-content');
                    if (tabPane && !tabPane.classList.contains('active')) {
                        const tabBtn = document.querySelector(`[data-tab-target="${tabPane.id}"]`);
                        if (tabBtn) this.switchTab(tabPane.id, tabBtn);
                    }
                    invalidField.focus();
                    invalidField.reportValidity?.();
                    return;
                }

                this.submitAjax();
            });
        }

        submitAjax() {
            const formData = new FormData(this.form);
            const redirectUrl = this.form.dataset.redirectUrl || '/mod_user/list';
            const actionUrl = this.form.action || window.location.href;

            this.setButtonLoading(true);

            fetch(actionUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(async (response) => {
                    const text = await response.text();
                    try {
                        return JSON.parse(text);
                    } catch (parseErr) {
                        return {
                            success: response.ok,
                            message: text
                        };
                    }
                })
                .then((data) => {
                    this.setButtonLoading(false);

                    if (data.success) {
                        this.showAlert('success', data.message || 'Operation successful!');
                        const targetUrl = data.redirect_url || data.redirect || redirectUrl;
                        setTimeout(() => {
                            window.location.href = targetUrl;
                        }, 1200);
                    } else {
                        const errors = data.errors || [data.message || 'Validation error occurred'];
                        this.showErrors(errors);
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                })
                .catch((err) => {
                    this.setButtonLoading(false);
                    console.error('Form submission error:', err);
                    this.showAlert('danger', 'A network error occurred. Please try again.');
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
        }

        setButtonLoading(isLoading) {
            if (!this.submitBtn) return;

            const icon = this.submitBtn.querySelector('i');
            const textSpan = this.submitBtn.querySelector('span');

            if (isLoading) {
                this.submitBtn.disabled = true;
                if (icon) {
                    icon.dataset.originalClass = icon.className;
                    icon.className = 'btn-spinner';
                }
            } else {
                this.submitBtn.disabled = false;
                if (icon && icon.dataset.originalClass) {
                    icon.className = icon.dataset.originalClass;
                }
            }
        }

        showAlert(type, message) {
            if (!this.alertContainer) return;
            const iconClass = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill';
            this.alertContainer.innerHTML = `
                <div class="banner-m3 banner-${type}">
                    <i class="bi ${iconClass}"></i>
                    <span>${message}</span>
                </div>
            `;
        }

        showErrors(errors) {
            if (!this.alertContainer) return;
            const items = (Array.isArray(errors) ? errors : [errors]).map(err => `
                <div class="error-item">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span>${err}</span>
                </div>
            `).join('');

            this.alertContainer.innerHTML = `
                <div class="banner-m3 banner-danger">
                    ${items}
                </div>
            `;
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        window.userManager = new UserManager();
        window.userFormManager = new UserFormManager();
    });
})();
