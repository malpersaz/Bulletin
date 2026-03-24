/**
 * AKM Business - Private Messaging (mod_user_pm)
 * Modernization with M3 Design & Infinite Scroll
 * @date 2026-03-12
 */

class MessageManager {
    constructor() {
        this.container = document.getElementById('message-list-container');
        this.loader = document.getElementById('infinite-loader');
        this.emptyState = document.getElementById('empty-state');
        this.searchInput = document.getElementById('searchInput');

        this.isLoading = false;
        this.hasMore = true;
        
        this.start = 0;
        this.length = 10;
        this.draw = 1;

        this.selectedMessageId = null;
        this.selectedUsername = null;
        this.selectedKonu = null;

        this.init();
    }

    init() {
        if (!this.container) return;
        
        this.createLoadMoreButton();
        this.loadData();
        this.initSearchListener();
        this.initPullToRefresh();
    }

    escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    createLoadMoreButton() {
        this.loadMoreBtn = document.createElement('button');
        this.loadMoreBtn.className = 'm3-btn-outlined';
        this.loadMoreBtn.style.cssText = 'display:none; margin: 20px auto; width: fit-content; cursor:pointer;';
        this.loadMoreBtn.innerHTML = `<i class="bi bi-arrow-down-circle" style="margin-right:8px;"></i>${PM_LANG.load_more}`;

        if (this.container) {
            this.container.parentNode.appendChild(this.loadMoreBtn);
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
            this.loadMoreBtn.innerHTML = `${PM_LANG.loading}`;
        }
        
        if (this.loader) this.loader.style.display = 'flex';

        if (reset) {
            this.start = 0;
            this.container.innerHTML = '';
            this.hasMore = true;
            this.draw = 1;
            if (this.emptyState) this.emptyState.style.display = 'none';
        }

        const formData = new FormData(document.getElementById('filterForm'));
        formData.set('start', this.start);
        formData.set('length', this.length);
        formData.set('draw', this.draw);

        try {
            const response = await fetch(SITE_URL + 'mod_user_pm/datatable_json', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            this.renderMessages(data.data);

            this.start += this.length;
            this.draw++;
            this.hasMore = this.start < data.recordsFiltered;

            if (this.loadMoreBtn) {
                this.loadMoreBtn.style.display = this.hasMore ? 'block' : 'none';
                this.loadMoreBtn.disabled = false;
                this.loadMoreBtn.innerHTML = `<i class="bi bi-arrow-down-circle" style="margin-right:8px;"></i>${PM_LANG.load_more}`;
            }

            this.updateEmptyState(data.recordsFiltered === 0);

        } catch (error) {
            console.error('Error loading messages:', error);
        } finally {
            this.isLoading = false;
            if (this.loader) this.loader.style.display = 'none';
        }
    }

    renderMessages(messages) {
        const fragment = document.createDocumentFragment();
        
        messages.forEach(msg => {
            const id = msg[1];
            const subject = msg[2];
            const statusHtml = msg[3];
            const createdAt = msg[4];
            const username = msg[5] || 'Bikarhêner';
            const image = msg[6];
            const messageText = msg[7] || '';

            const isUnread = statusHtml.includes('danger') || statusHtml.includes(PM_LANG.unread);
            const initial = (username || '?').charAt(0).toUpperCase();
            let avatarHtml = '';
            if (image && image !== '' && image !== 'avatar.png') {
                avatarHtml = `<img src="${RADRES_URL}mod_user/thumb/${this.escapeHtml(image)}" alt="${this.escapeHtml(username)}" style="width:100%; height:100%; object-fit:cover;" onerror="this.parentElement.innerHTML='${this.escapeHtml(initial)}'">`;
            } else {
                avatarHtml = this.escapeHtml(initial);
            }

            const card = document.createElement('article');
            card.className = `pm-inbox-card${isUnread ? ' is-unread' : ''}`;
            card.id = `alan_${id}`;
            card.onclick = () => window.location.href = `mod_user_pm/inbox_read/${id}`;

            const relativeDate = this.formatRelativeDate(createdAt);
            const statusLabel = isUnread ? PM_LANG.unread : PM_LANG.read_status;
            const preview = this.escapeHtml(this.buildPreview(messageText, subject));
            const safeUsername = this.escapeHtml(username);
            const safeSubject = this.escapeHtml(subject);
            const encodedUsername = encodeURIComponent(username);
            const encodedSubject = encodeURIComponent(subject);

            card.innerHTML = `
                <div class="pm-inbox-topline">
                    <div class="pm-inbox-avatar">
                        ${avatarHtml}
                    </div>
                    <div class="pm-inbox-sender">
                        <span class="pm-inbox-sender-label">${PM_LANG.sender_label}</span>
                        <h2>${safeUsername}</h2>
                    </div>
                    <div class="pm-inbox-date">
                        ${isUnread ? `<span class="pm-inbox-badge">${PM_LANG.new_tag}</span>` : ''}
                        <time>${relativeDate}</time>
                    </div>
                </div>

                <div class="pm-inbox-subject">
                    <div class="pm-inbox-subject-line">${safeSubject}</div>
                    <div class="pm-inbox-preview">${preview}</div>
                    <div class="pm-inbox-status-row">
                        <span class="pm-inbox-status${isUnread ? ' is-unread' : ''}">${statusLabel}</span>
                        <button class="pm-inbox-menu" type="button"
                                onclick="event.stopPropagation(); window.messageManager.openActionSheet('${id}', '${encodedUsername}', '${encodedSubject}', '${safeUsername}', '${safeSubject}')">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                    </div>
                </div>
            `;
            fragment.appendChild(card);
        });

        this.container.appendChild(fragment);
    }

    buildPreview(message, fallbackSubject) {
        const normalized = String(message || '')
            .replace(/\s+/g, ' ')
            .trim();
        if (normalized.length > 0) {
            return normalized.length > 96 ? `${normalized.slice(0, 96)}...` : normalized;
        }

        const fallback = String(fallbackSubject || '').trim();
        return fallback.length > 96 ? `${fallback.slice(0, 96)}...` : fallback;
    }

    formatRelativeDate(value) {
        const parsed = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(parsed.getTime())) {
            return value;
        }

        const now = new Date();
        const pad = num => String(num).padStart(2, '0');
        const formatTime = date => `${pad(date.getHours())}:${pad(date.getMinutes())}`;
        const startOfToday = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const startOfTarget = new Date(parsed.getFullYear(), parsed.getMonth(), parsed.getDate());
        const diffDays = Math.round((startOfToday - startOfTarget) / 86400000);

        if (diffDays === 0) {
            return `${PM_LANG.today_label} ${formatTime(parsed)}`;
        }

        if (diffDays === 1) {
            return `${PM_LANG.yesterday_label} ${formatTime(parsed)}`;
        }

        return parsed.toLocaleDateString('tr-TR', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    openActionSheet(id, encodedUsername, encodedSubject, labelUsername, labelSubject) {
        this.selectedMessageId = id;
        this.selectedUsername = encodedUsername;
        this.selectedKonu = encodedSubject;

        const sheet = document.getElementById('context-menu-sheet');
        const overlay = document.getElementById('context-menu-overlay');
        const title = document.getElementById('contextMenuTitle');
        const safeUsername = this.escapeHtml(labelUsername);
        const safeSubject = this.escapeHtml(labelSubject);

        if (!sheet || !overlay || !title) {
            this.openActionDialog(id, encodedUsername, encodedSubject, safeUsername, safeSubject);
            return;
        }

        title.innerHTML = `<span style="color:var(--md-sys-color-primary);">${safeUsername}</span><br><small style="font-size:13px; color:var(--md-sys-color-outline); font-weight:400;">${safeSubject}</small>`;

        const actionContainer = sheet.querySelector('div[style*="padding: 4px 0;"]');
        if (!actionContainer) {
            this.openActionDialog(id, encodedUsername, encodedSubject, safeUsername, safeSubject);
            return;
        }

        actionContainer.innerHTML = `
            <div class="context-action-item" onclick="window.messageManager.handleAction('read')">
                <div class="context-icon" style="background: var(--md-sys-color-secondary-container); color: var(--md-sys-color-on-secondary-container);">
                    <i class="bi bi-eye"></i>
                </div>
                <span class="context-action-label">${PM_LANG.read}</span>
            </div>
            <div class="context-action-item" onclick="window.messageManager.handleAction('reply')">
                <div class="context-icon" style="background: var(--md-sys-color-primary-container); color: var(--md-sys-color-on-primary-container);">
                    <i class="bi bi-reply"></i>
                </div>
                <span class="context-action-label">${PM_LANG.reply}</span>
            </div>
            <div class="context-divider"></div>
            <div class="context-action-item action-danger" onclick="window.messageManager.handleAction('delete')">
                <div class="context-icon icon-delete">
                    <i class="bi bi-trash3"></i>
                </div>
                <span class="context-action-label">${PM_LANG.delete}</span>
            </div>
        `;

        sheet.classList.add('active');
        overlay.classList.add('active');
    }

    openActionDialog(id, encodedUsername, encodedSubject, safeUsername, safeSubject) {
        Swal.fire({
            title: safeUsername,
            html: `<small style="color:#637166;">${safeSubject}</small>`,
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonText: PM_LANG.read,
            denyButtonText: PM_LANG.reply,
            cancelButtonText: PM_LANG.delete,
            confirmButtonColor: '#234f3e',
            denyButtonColor: '#356850',
            cancelButtonColor: '#ad2d2d'
        }).then(result => {
            if (result.isConfirmed) {
                window.location.href = `mod_user_pm/inbox_read/${id}`;
                return;
            }

            if (result.isDenied) {
                window.location.href = `mod_user_pm/send/${encodedUsername}/${encodedSubject}`;
                return;
            }

            if (result.dismiss === Swal.DismissReason.cancel) {
                deleteMessage(id, 'inbox');
            }
        });
    }

    handleAction(action) {
        this.closeActionSheet();
        const id = this.selectedMessageId;
        const username = this.selectedUsername;
        const konu = this.selectedKonu;

        switch(action) {
            case 'read':
                window.location.href = `mod_user_pm/inbox_read/${id}`;
                break;
            case 'reply':
                window.location.href = `mod_user_pm/send/${username}/${konu}`;
                break;
            case 'delete':
                deleteMessage(id, 'inbox');
                break;
        }
    }

    closeActionSheet() {
        const sheet = document.getElementById('context-menu-sheet');
        const overlay = document.getElementById('context-menu-overlay');
        if (sheet) sheet.classList.remove('active');
        if (overlay) overlay.classList.remove('active');
    }

    initSearchListener() {
        if (!this.searchInput) return;

        let debounceTimer;
        this.searchInput.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const searchValue = e.target.value.trim();
                const filterSearchInput = document.getElementById('filterSearch');
                if (filterSearchInput) {
                    filterSearchInput.value = searchValue;
                    this.loadData(true);
                }
            }, 500);
        });

        const clearSearchBtn = document.getElementById('clearSearchBtn');
        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', () => {
                window.setTimeout(() => this.loadData(true), 0);
            });
        }
    }

    updateEmptyState(showEmpty) {
        if (!this.emptyState) return;

        if (!showEmpty) {
            this.emptyState.style.display = 'none';
            return;
        }

        const hasSearch = Boolean((this.searchInput?.value || '').trim());
        const title = this.emptyState.querySelector('h3');
        const desc = this.emptyState.querySelector('p');

        if (title) {
            title.textContent = hasSearch
                ? this.emptyState.dataset.searchTitle
                : this.emptyState.dataset.emptyTitle;
        }

        if (desc) {
            desc.textContent = hasSearch
                ? this.emptyState.dataset.searchDesc
                : this.emptyState.dataset.emptyDesc;
        }

        this.emptyState.style.display = 'flex';
    }

    initPullToRefresh() {
        const scrollFrame = document.querySelector('.app-scroll-frame');
        if (!scrollFrame) return;

        let touchStart = 0;
        scrollFrame.addEventListener('touchstart', e => {
            if (scrollFrame.scrollTop === 0) touchStart = e.touches[0].clientY;
        });

        scrollFrame.addEventListener('touchend', e => {
            const touchEnd = e.changedTouches[0].clientY;
            if (scrollFrame.scrollTop === 0 && touchEnd - touchStart > 100) {
                this.loadData(true);
            }
        });
    }
}

window.closeContextMenu = function() {
    if (window.messageManager) window.messageManager.closeActionSheet();
};

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('message-list-container')) {
        window.messageManager = new MessageManager();
    }

    if (document.getElementById('recipient') || document.getElementById('recipients')) {
        initRecipientSelect();
    }
});

function initRecipientSelect() {
    const recipientInput = document.getElementById('recipient');
    const recipientsInput = document.getElementById('recipients');
    if (!recipientInput && !recipientsInput) return;
    
    const config = {
        valueField: 'name',
        labelField: 'name',
        searchField: 'name',
        loadThrottle: 300,
        load: function(query, callback) {
            if (!query.length) return callback();
            fetch('mod_user_pm/allin', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'q=' + encodeURIComponent(query)
            })
            .then(response => response.json())
            .then(json => callback(json))
            .catch(() => callback());
        }
    };
    if (recipientInput && typeof TomSelect !== 'undefined') new TomSelect('#recipient', { ...config, maxItems: 1 });
    if (recipientsInput && typeof TomSelect !== 'undefined') new TomSelect('#recipients', { ...config, plugins: ['remove_button'], persist: false, create: false });
}

function deleteMessage(id, type) {
    Swal.fire({
        title: PM_LANG.confirm_title || 'Ma hûn bawer in?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: 'var(--md-sys-color-error, #B3261E)',
        cancelButtonColor: 'var(--md-sys-color-primary, #6750A4)',
        confirmButtonText: PM_LANG.confirm_yes || 'Erê',
        cancelButtonText: PM_LANG.confirm_no || 'Betal',
        customClass: { popup: 'm3-swal-popup' }
    }).then((result) => {
        if (result.isConfirmed) {
            const url = type === 'inbox' ? 'mod_user_pm/inbox_delete' : 'mod_user_pm/sent_delete';
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + id
            })
            .then(response => response.text())
            .then(msg => {
                if (msg.trim() === 'Oke') {
                    showSnackbar(PM_LANG.delete_success || 'Hate jêbirin!', 'success');
                    const element = document.getElementById('alan_' + id);
                    if (element) {
                        element.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                        element.style.opacity = '0';
                        element.style.transform = 'translateX(20px)';
                        setTimeout(() => element.remove(), 500);
                    }
                } else {
                    showSnackbar(msg, 'error');
                }
            });
        }
    });
}
