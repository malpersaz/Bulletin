/**
 * mod_acl.js - JavaScript module for mod_acl management
 */

class AclManager {
    constructor(config = {}) {
        this.container = document.getElementById('acl-list-container');
        this.loader = document.getElementById('infinite-loader');
        this.emptyState = document.getElementById('empty-state');
        this.filterForm = document.getElementById('filterForm');
        this.toolbarDelete = document.getElementById('toolbarDelete');
        this.searchInput = document.getElementById('searchInput');
        this.filterSearch = document.getElementById('filterSearch');

        this.siteAddress = config.siteAddress || '/';
        this.lang = config.lang || {};
        this.permissions = config.permissions || {};

        this.isLoading = false;
        this.hasMore = true;
        this.selectedIds = [];
        this.start = 0;
        this.length = 15;
        this.draw = 1;

        if (this.container) {
            this.initList();
        }
    }

    initList() {
        this.createLoadMoreButton();
        this.loadData();
        this.initSearchListener();
        this.bindToolbarEvents();
    }

    createLoadMoreButton() {
        this.loadMoreBtn = document.createElement('button');
        this.loadMoreBtn.className = 'acl-load-more-btn';
        this.loadMoreBtn.innerHTML = `<i class="bi bi-arrow-down-circle acl-load-more-icon"></i>${this.lang.page || 'Page'}`;
        if (this.container && this.loader && this.container.parentNode) {
            this.container.parentNode.insertBefore(this.loadMoreBtn, this.loader);
        }
        this.loadMoreBtn.addEventListener('click', () => this.loadData());
    }

    async loadData(reset = false) {
        if (this.isLoading || (!this.hasMore && !reset)) return;
        this.isLoading = true;

        if (this.loadMoreBtn) {
            this.loadMoreBtn.disabled = true;
        }

        if (reset) {
            this.start = 0;
            if (this.container) this.container.innerHTML = '';
            this.hasMore = true;
            this.draw = 1;
            if (this.emptyState) this.emptyState.style.display = 'none';
            this.selectedIds = [];
            this.updateToolbar();
        }
        if (this.loader) this.loader.style.display = 'flex';

        const formData = this.filterForm ? new FormData(this.filterForm) : new FormData();
        formData.set('start', this.start);
        formData.set('length', this.length);
        formData.set('draw', this.draw);

        try {
            const response = await fetch(`${this.siteAddress}mod_acl/datatable_json`, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            this.renderCards(data.data);
            this.start += this.length;
            this.draw++;

            if (this.start >= data.recordsFiltered) {
                this.hasMore = false;
            }
            if (data.recordsFiltered === 0 && this.emptyState) {
                this.emptyState.style.display = 'flex';
            }
        } catch (error) {
            console.error('Data Load Error:', error);
        } finally {
            this.isLoading = false;
            if (this.loader) this.loader.style.display = 'none';
            if (this.loadMoreBtn) {
                this.loadMoreBtn.style.display = this.hasMore ? 'block' : 'none';
                this.loadMoreBtn.disabled = false;
            }
        }
    }

    renderCards(items) {
        if (!items || items.length === 0 || !this.container) return;
        const fragment = document.createDocumentFragment();

        items.forEach(item => {
            const id = this.stripHtml(item[1]);
            const name = this.stripHtml(item[2]);

            const card = document.createElement('div');
            card.className = 'acl-card';
            card.dataset.id = id;

            if (this.selectedIds.includes(id)) {
                card.classList.add('selected');
            }

            const firstLetter = name ? name.charAt(0) : '?';

            let actionButtonsHtml = '';
            if (this.permissions.canEdit) {
                actionButtonsHtml += `
                    <a href="${this.siteAddress}mod_acl/edit/${encodeURIComponent(id)}" class="acl-action-btn edit" title="${this.escapeHtml(this.lang.edit || 'Edit')}">
                        <i class="bi bi-pencil-square"></i>
                    </a>
                `;
            }
            if (this.permissions.canDelete) {
                actionButtonsHtml += `
                    <button type="button" class="acl-action-btn delete" data-action="delete-single" data-id="${encodeURIComponent(id)}" title="${this.escapeHtml(this.lang.delete || 'Delete')}">
                        <i class="bi bi-trash"></i>
                    </button>
                `;
            }

            card.innerHTML = `
                <div class="acl-avatar">${this.escapeHtml(firstLetter)}</div>
                <div class="acl-info">
                    <div class="acl-name">${this.escapeHtml(name)}</div>
                    <div class="acl-id-tag">ID: ${this.escapeHtml(id)}</div>
                </div>
                <div class="acl-actions">
                    ${actionButtonsHtml}
                </div>
            `;

            card.addEventListener('click', (e) => {
                if (e.target.closest('.acl-action-btn')) {
                    return;
                }
                this.toggleSelection(id, card);
            });

            const deleteBtn = card.querySelector('[data-action="delete-single"]');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.deleteSingle(id);
                });
            }

            fragment.appendChild(card);
        });

        this.container.appendChild(fragment);
    }

    toggleSelection(id, cardElement) {
        const index = this.selectedIds.indexOf(id);
        if (index > -1) {
            this.selectedIds.splice(index, 1);
            cardElement.classList.remove('selected');
        } else {
            this.selectedIds.push(id);
            cardElement.classList.add('selected');
        }
        this.updateToolbar();
    }

    updateToolbar() {
        if (!this.toolbarDelete) return;

        if (this.selectedIds.length > 0) {
            this.toolbarDelete.style.pointerEvents = 'auto';
            this.toolbarDelete.style.opacity = '1';
        } else {
            this.toolbarDelete.style.pointerEvents = 'none';
            this.toolbarDelete.style.opacity = '0.3';
        }
    }

    initSearchListener() {
        if (!this.searchInput || !this.filterSearch) return;

        let debounceTimeout;
        this.searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimeout);
            debounceTimeout = setTimeout(() => {
                this.filterSearch.value = this.searchInput.value;
                this.loadData(true);
            }, 300);
        });
    }

    bindToolbarEvents() {
        if (this.toolbarDelete) {
            this.toolbarDelete.addEventListener('click', () => {
                this.deleteSelected();
            });
        }
    }

    stripHtml(html) {
        if (!html) return '';
        const doc = new DOMParser().parseFromString(html, 'text/html');
        return doc.body.textContent || '';
    }

    escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    async deleteSingle(id) {
        if (typeof Swal === 'undefined') {
            if (confirm(this.lang.removeRecord || 'Are you sure you want to delete this record?')) {
                await this.executeDelete([id]);
            }
            return;
        }

        Swal.fire({
            title: this.lang.confirmTitle || 'Confirm',
            text: this.lang.removeRecord || 'Are you sure you want to delete this record?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ba1a1a',
            cancelButtonColor: 'var(--md-sys-color-outline)',
            confirmButtonText: this.lang.yes || 'Yes',
            cancelButtonText: this.lang.no || 'No'
        }).then(async (result) => {
            if (result.isConfirmed) {
                await this.executeDelete([id]);
            }
        });
    }

    deleteSelected() {
        if (this.selectedIds.length === 0) return;

        const countText = `${this.selectedIds.length} ${this.lang.records || 'records'}`;

        if (typeof Swal === 'undefined') {
            if (confirm(`${this.lang.confirmTitle || 'Confirm'}: ${countText}`)) {
                this.executeDelete(this.selectedIds);
            }
            return;
        }

        Swal.fire({
            title: this.lang.confirmTitle || 'Confirm',
            text: countText,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ba1a1a',
            cancelButtonColor: 'var(--md-sys-color-outline)',
            confirmButtonText: this.lang.yes || 'Yes',
            cancelButtonText: this.lang.no || 'No'
        }).then(async (result) => {
            if (result.isConfirmed) {
                await this.executeDelete(this.selectedIds);
            }
        });
    }

    async executeDelete(ids) {
        const formData = new FormData();
        ids.forEach(id => {
            formData.append('delete[]', id);
        });

        try {
            const response = await fetch(`${this.siteAddress}mod_acl/delete`, {
                method: 'POST',
                body: formData
            });
            const result = await response.text();
            if (result.trim() === 'Success') {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: this.lang.successTitle || 'Success',
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
                this.loadData(true);
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: this.lang.errorTitle || 'Error',
                        text: result
                    });
                } else {
                    alert(result);
                }
            }
        } catch (error) {
            console.error('Delete Request Failed:', error);
        }
    }
}

/**
 * Initialize Form Interactivity (Role Selection Cards & Submit Handlers)
 */
function initAclFormInteractivity() {
    const selectionCards = document.querySelectorAll('.acl-selection-card');
    selectionCards.forEach(card => {
        const checkbox = card.querySelector('.acl-hidden-checkbox');

        // Set initial state based on checkbox
        if (checkbox && checkbox.checked) {
            card.classList.add('active');
        }

        card.addEventListener('click', (e) => {
            if (e.target !== checkbox) {
                if (checkbox) {
                    checkbox.checked = !checkbox.checked;
                    checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
            if (checkbox) {
                if (checkbox.checked) {
                    card.classList.add('active');
                } else {
                    card.classList.remove('active');
                }
            }
        });
    });

    const submitAddBtn = document.getElementById('submitAddBtn');
    if (submitAddBtn) {
        submitAddBtn.addEventListener('click', () => {
            submitAclForm('addForm');
        });
    }

    const submitEditBtn = document.getElementById('submitEditBtn');
    if (submitEditBtn) {
        submitEditBtn.addEventListener('click', () => {
            submitAclForm('editForm');
        });
    }
}

function submitAclForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    form.submit();
}

/**
 * Handle server-rendered flash messages via config
 */
function handleAclFlashMessages() {
    if (typeof window.MOD_ACL_FLASH === 'undefined' || typeof Swal === 'undefined') return;

    const flash = window.MOD_ACL_FLASH;

    if (flash.errors && flash.errors.length > 0) {
        let errorHtml = '<div style="text-align: left;">';
        flash.errors.forEach(err => {
            errorHtml += `<div style="margin-bottom: 8px;"><i class="bi bi-exclamation-triangle-fill" style="color: #ba1a1a;"></i> <b>${err}</b></div>`;
        });
        errorHtml += '</div>';

        Swal.fire({
            icon: 'warning',
            html: errorHtml,
            confirmButtonText: flash.buttonOk || 'OK',
            confirmButtonColor: '#ba1a1a'
        });
    } else if (flash.error) {
        Swal.fire({
            icon: 'warning',
            html: `<b><i class="bi bi-exclamation-triangle-fill"></i> ${flash.error}</b>`,
            confirmButtonText: flash.buttonOk || 'OK',
            confirmButtonColor: '#ba1a1a'
        });
    }

    if (flash.redirectAfter) {
        setTimeout(() => {
            window.location.href = flash.redirectAfter;
        }, 2000);
    }
}

// Global initialization on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    initAclFormInteractivity();
    handleAclFlashMessages();

    if (document.getElementById('acl-list-container')) {
        window.aclManager = new AclManager(window.MOD_ACL_CONFIG || {});
    }
});
