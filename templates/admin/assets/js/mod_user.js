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

    document.addEventListener('DOMContentLoaded', () => {
        window.userManager = new UserManager();
    });
})();
