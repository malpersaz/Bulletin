/**
 * mod_socialmedia_post.js - Library List, Card Actions & Add/Edit Autofill
 * Phone-first admin behavior with zero inline style mutations.
 */
(function () {
    'use strict';

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function notify(message, type) {
        if (typeof showSnackbar === 'function') {
            showSnackbar(message, type || 'info');
            return;
        }
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: type === 'error' ? 'error' : 'success',
                text: message,
                timer: 2200,
                showConfirmButton: false
            });
        }
    }

    function getProviderIcon(provider) {
        if (provider === 'x') return 'bi-twitter-x';
        if (provider === 'facebook') return 'bi-facebook';
        if (provider === 'instagram') return 'bi-instagram';
        return 'bi-share';
    }

    async function parseJsonSafe(res, fallbackMsg) {
        const raw = await res.text();
        let payload = null;
        try {
            payload = raw ? JSON.parse(raw) : null;
        } catch (err) {
            throw new Error(fallbackMsg || 'Invalid server response');
        }
        if (!payload || typeof payload !== 'object') {
            throw new Error(fallbackMsg || 'Invalid server response');
        }
        if (payload.code === 'session_expired' && payload.redirect) {
            window.location.href = payload.redirect;
            throw new Error(payload.message || 'Session expired');
        }
        return payload;
    }

    /* ======================================================================
       1. Library Card List (#socialPostListShell)
       ====================================================================== */
    function initListPage() {
        const shell = document.getElementById('socialPostListShell');
        if (!shell) {
            return;
        }

        const cfg = {
            gridUrl: shell.dataset.gridUrl || '',
            editUrl: shell.dataset.editUrl || '',
            deleteUrl: shell.dataset.deleteUrl || '',
            toggleUrl: shell.dataset.toggleUrl || '',
            labels: JSON.parse(shell.dataset.labels || '{}'),
            messages: JSON.parse(shell.dataset.messages || '{}')
        };

        const listEl = document.getElementById('socialPostList');
        const emptyEl = document.getElementById('socialPostEmpty');
        const loadMoreWrap = document.getElementById('socialPostLoadMoreWrap');
        const loadMoreBtn = document.getElementById('socialPostLoadMoreBtn');
        const searchInput = document.getElementById('spSearchInput');

        const state = {
            provider: 'all',
            lang: shell.dataset.defaultLang || 'all',
            status: 'all',
            q: '',
            offset: 0,
            limit: 20,
            selectedId: null,
            items: []
        };

        function updateToolbar(item) {
            const editBtn = document.getElementById('toolbarEdit');
            const deleteBtn = document.getElementById('toolbarDelete');
            const toggleBtn = document.getElementById('toolbarToggleStatus');
            const hasItem = Boolean(item);
            if (editBtn) editBtn.disabled = !hasItem;
            if (deleteBtn) deleteBtn.disabled = !hasItem;
            if (toggleBtn) toggleBtn.disabled = !hasItem;
        }

        function renderCard(item) {
            const isSelected = String(state.selectedId) === String(item.id);
            const isActive = item.status === 'y';
            const statusClass = isActive ? 'is-active' : 'is-draft';
            const statusText = isActive ? (cfg.labels.active || 'Active') : (cfg.labels.draft || 'Draft');
            const thumbHtml = item.thumb_url
                ? `<img class="sp-card-thumb" src="${escapeHtml(item.thumb_url)}" alt="">`
                : '';
            const handleHtml = item.handle
                ? `<div class="sp-card-handle">@${escapeHtml(item.handle)}</div>`
                : '';
            const contentSnippet = item.content
                ? `<p class="sp-card-snippet">${escapeHtml(item.content)}</p>`
                : '';

            return `
                <article class="sp-card ${isSelected ? 'is-selected' : ''}" data-id="${escapeHtml(item.id)}">
                    <div class="sp-card-head">
                        <div class="sp-card-badges">
                            <span class="sp-provider-badge" data-provider="${escapeHtml(item.provider)}">
                                <i class="bi ${getProviderIcon(item.provider)}"></i>
                                ${escapeHtml(item.provider_label)}
                            </span>
                            <span class="sp-media-badge">${escapeHtml(item.media_type)}</span>
                            <button type="button" class="sp-token-btn" data-copy-token="${escapeHtml(item.token)}" title="${escapeHtml(cfg.labels.copyToken || 'Copy')}">
                                <i class="bi bi-clipboard"></i>
                                <span>${escapeHtml(item.token)}</span>
                            </button>
                        </div>
                        <div class="sp-card-actions">
                            <button type="button" class="sp-status-btn ${statusClass}" data-toggle-status="${escapeHtml(item.id)}">
                                <i class="bi ${isActive ? 'bi-check-circle-fill' : 'bi-pause-circle'}"></i>
                                <span>${escapeHtml(statusText)}</span>
                            </button>
                            <button type="button" class="sp-icon-btn" data-open-menu="${escapeHtml(item.id)}" aria-label="Actions">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                        </div>
                    </div>
                    <div class="sp-card-body">
                        ${thumbHtml}
                        <div class="sp-card-info">
                            <h3 class="sp-card-title">${escapeHtml(item.name)}</h3>
                            ${handleHtml}
                            ${contentSnippet}
                        </div>
                    </div>
                    <div class="sp-card-foot">
                        <span>${escapeHtml(String(item.lang || '').toUpperCase())}</span>
                        <span>${escapeHtml(item.created_at || '')}</span>
                    </div>
                </article>
            `;
        }

        function renderList(append) {
            if (!append) {
                listEl.innerHTML = '';
            }
            if (!state.items.length) {
                emptyEl?.classList.remove('sp-hidden');
                loadMoreWrap?.classList.add('sp-hidden');
                return;
            }
            emptyEl?.classList.add('sp-hidden');
            listEl.innerHTML = state.items.map(renderCard).join('');
        }

        async function fetchItems(append) {
            if (!append) {
                state.offset = 0;
                state.items = [];
                state.selectedId = null;
                updateToolbar(null);
            }

            const params = new URLSearchParams();
            params.set('provider', state.provider);
            params.set('lang', state.lang);
            params.set('status', state.status === 'all' ? '' : state.status);
            params.set('q', state.q);
            params.set('offset', String(state.offset));
            params.set('limit', String(state.limit));

            try {
                const res = await fetch(cfg.gridUrl + '?' + params.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const payload = await parseJsonSafe(res, cfg.messages.error || 'Error');
                if (payload.status !== 'success') {
                    throw new Error(payload.message || cfg.messages.error || 'Error');
                }
                const incoming = payload.items || [];
                state.items = append ? state.items.concat(incoming) : incoming;
                state.offset = state.items.length;
                renderList(append);
                loadMoreWrap?.classList.toggle('sp-hidden', !payload.has_more);
            } catch (err) {
                notify(String(err.message || err), 'error');
            }
        }

        function findItem(id) {
            return state.items.find((row) => String(row.id) === String(id)) || null;
        }

        function toggleSelection(id) {
            state.selectedId = (String(state.selectedId) === String(id)) ? null : id;
            updateToolbar(findItem(state.selectedId));
            listEl.querySelectorAll('.sp-card').forEach((card) => {
                card.classList.toggle('is-selected', String(card.dataset.id) === String(state.selectedId));
            });
        }

        async function toggleItemStatus(id) {
            const body = new URLSearchParams();
            body.set('id', String(id));
            try {
                const res = await fetch(cfg.toggleUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: body.toString()
                });
                const payload = await parseJsonSafe(res, cfg.messages.error || 'Error');
                if (payload.status !== 'success') {
                    throw new Error(payload.message || cfg.messages.error);
                }
                const target = findItem(id);
                if (target) {
                    target.status = payload.new_status;
                }
                renderList(false);
                notify(payload.message || cfg.messages.saveSuccess, 'success');
            } catch (err) {
                notify(String(err.message || err), 'error');
            }
        }

        function deleteItem(id) {
            if (!id || typeof Swal === 'undefined') {
                return;
            }
            Swal.fire({
                icon: 'warning',
                text: cfg.messages.deleteConfirm,
                showCancelButton: true,
                confirmButtonText: cfg.messages.yes,
                cancelButtonText: cfg.messages.no
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }
                const body = new URLSearchParams();
                body.append('delete[]', String(id));
                fetch(cfg.deleteUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body.toString()
                })
                    .then((res) => res.text())
                    .then((text) => {
                        if (text.trim() !== 'OK') {
                            throw new Error(text);
                        }
                        notify(cfg.messages.deleteSuccess, 'success');
                        fetchItems(false);
                    })
                    .catch((err) => notify(String(err.message || err), 'error'));
            });
        }

        shell.querySelectorAll('[data-provider-filter]').forEach((btn) => {
            btn.addEventListener('click', function () {
                shell.querySelectorAll('[data-provider-filter]').forEach((el) => el.classList.remove('is-active'));
                btn.classList.add('is-active');
                state.provider = btn.dataset.providerFilter || 'all';
                fetchItems(false);
            });
        });

        shell.querySelectorAll('[data-status-filter]').forEach((btn) => {
            btn.addEventListener('click', function () {
                shell.querySelectorAll('[data-status-filter]').forEach((el) => el.classList.remove('is-active'));
                btn.classList.add('is-active');
                state.status = btn.dataset.statusFilter || 'all';
                fetchItems(false);
            });
        });

        let searchTimer = null;
        searchInput?.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                state.q = searchInput.value.trim();
                fetchItems(false);
            }, 220);
        });

        loadMoreBtn?.addEventListener('click', () => fetchItems(true));

        listEl?.addEventListener('click', function (event) {
            const copyBtn = event.target.closest('[data-copy-token]');
            if (copyBtn) {
                event.stopPropagation();
                const token = copyBtn.dataset.copyToken || '';
                if (navigator.clipboard && token) {
                    navigator.clipboard.writeText(token).catch(() => {});
                }
                notify(cfg.messages.tokenCopied || token, 'success');
                return;
            }

            const statusBtn = event.target.closest('[data-toggle-status]');
            if (statusBtn) {
                event.stopPropagation();
                toggleItemStatus(statusBtn.dataset.toggleStatus);
                return;
            }

            const menuBtn = event.target.closest('[data-open-menu]');
            if (menuBtn) {
                event.stopPropagation();
                const item = findItem(menuBtn.dataset.openMenu);
                if (item && typeof openContextMenu === 'function') {
                    toggleSelection(item.id);
                    openContextMenu(item.id, item.name, item.status === 'y', ['gallery', 'files', 'faq', 'social_post']);
                }
                return;
            }

            const card = event.target.closest('.sp-card');
            if (card) {
                toggleSelection(card.dataset.id);
            }
        });

        window.handleContextAction = function (action) {
            if (typeof closeContextMenu === 'function') {
                closeContextMenu();
            }
            const id = window.currentContentId || state.selectedId;
            if (!id) return;
            if (action === 'edit') {
                window.location.href = cfg.editUrl + id;
            } else if (action === 'delete') {
                deleteItem(id);
            } else if (action === 'status') {
                toggleItemStatus(id);
            }
        };

        document.getElementById('toolbarEdit')?.addEventListener('click', () => {
            if (state.selectedId) {
                window.location.href = cfg.editUrl + state.selectedId;
            }
        });
        document.getElementById('toolbarDelete')?.addEventListener('click', () => {
            if (state.selectedId) {
                deleteItem(state.selectedId);
            }
        });
        document.getElementById('toolbarToggleStatus')?.addEventListener('click', () => {
            if (state.selectedId) {
                toggleItemStatus(state.selectedId);
            }
        });

        fetchItems(false);
    }

    /* ======================================================================
       2. Add / Edit Form Autofill (#socialPostFormShell)
       ====================================================================== */
    function initFormPage() {
        const formShell = document.getElementById('socialPostFormShell');
        if (!formShell) {
            return;
        }

        const autofillUrl = formShell.dataset.autofillUrl || '';
        const messages = JSON.parse(formShell.dataset.messages || '{}');

        const urlInput = document.getElementById('spUrlInput');
        const pasteBtn = document.getElementById('spPasteBtn');
        const clearBtn = document.getElementById('spClearBtn');
        const fetchBtn = document.getElementById('spFetchBtn');
        const langSelect = document.getElementById('spLangSelect');

        const providerInput = document.getElementById('spProviderInput');
        const mediaTypeSelect = document.getElementById('spMediaTypeSelect');
        const postIdInput = document.getElementById('spPostIdInput');
        const nameInput = document.getElementById('spNameInput');
        const handleInput = document.getElementById('spHandleInput');
        const contentInput = document.getElementById('spContentInput');
        const embedInput = document.getElementById('spEmbedHtmlInput');
        const autofillImageInput = document.getElementById('spAutofillImageInput');
        const removeImageInput = document.getElementById('spRemoveImageInput');

        const thumbPreview = document.getElementById('spThumbPreview');
        const thumbImg = document.getElementById('spThumbImg');
        const removeThumbBtn = document.getElementById('spRemoveThumbBtn');
        const detectedPill = document.getElementById('spDetectedProviderPill');

        function setLoading(loading) {
            if (fetchBtn) {
                fetchBtn.disabled = loading;
            }
        }

        function applyPreviewImage(url, fileName) {
            if (autofillImageInput && fileName !== undefined) {
                autofillImageInput.value = fileName || '';
            }
            if (removeImageInput) {
                removeImageInput.value = '0';
            }
            if (thumbImg && thumbPreview) {
                if (url) {
                    thumbImg.src = url;
                    thumbPreview.classList.remove('sp-hidden');
                } else {
                    thumbImg.removeAttribute('src');
                    thumbPreview.classList.add('sp-hidden');
                }
            }
        }

        async function runAutofill() {
            const rawUrl = (urlInput?.value || '').trim();
            if (!rawUrl) {
                notify(messages.missingSource || 'Paste a URL first.', 'error');
                return;
            }

            setLoading(true);
            try {
                const body = new URLSearchParams();
                body.set('url', rawUrl);
                if (langSelect?.value) {
                    body.set('lang', langSelect.value);
                }

                const res = await fetch(autofillUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: body.toString()
                });
                const payload = await parseJsonSafe(res, messages.fetchFailed || 'Error');
                if (payload.status !== 'success' || !payload.data) {
                    throw new Error(payload.message || messages.fetchFailed);
                }

                const data = payload.data;
                if (urlInput && data.url) urlInput.value = data.url;
                if (providerInput && data.provider) providerInput.value = data.provider;
                if (mediaTypeSelect && data.media_type) mediaTypeSelect.value = data.media_type;
                if (postIdInput && data.post_id) postIdInput.value = data.post_id;
                if (nameInput && data.name) nameInput.value = data.name;
                if (handleInput && data.handle !== undefined) handleInput.value = data.handle;
                if (contentInput && data.content !== undefined) contentInput.value = data.content;
                if (embedInput && data.embed_html !== undefined) embedInput.value = data.embed_html;

                if (detectedPill && data.provider_label) {
                    detectedPill.textContent = `${data.provider_label} / ${data.media_type || 'post'}`;
                    detectedPill.classList.remove('sp-hidden');
                }

                if (data.image_url) {
                    applyPreviewImage(data.image_url, data.image);
                }

                notify(payload.message || messages.autofillSuccess, 'success');
            } catch (err) {
                notify(String(err.message || err), 'error');
            } finally {
                setLoading(false);
            }
        }

        pasteBtn?.addEventListener('click', async function () {
            if (!navigator.clipboard || !urlInput) {
                return;
            }
            try {
                const text = await navigator.clipboard.readText();
                if (text) {
                    urlInput.value = text.trim();
                    runAutofill();
                }
            } catch (e) {
                urlInput.focus();
            }
        });

        clearBtn?.addEventListener('click', function () {
            if (urlInput) {
                urlInput.value = '';
                urlInput.focus();
            }
        });

        fetchBtn?.addEventListener('click', runAutofill);

        urlInput?.addEventListener('paste', function () {
            setTimeout(() => {
                if ((urlInput.value || '').trim().startsWith('http')) {
                    runAutofill();
                }
            }, 60);
        });

        removeThumbBtn?.addEventListener('click', function () {
            if (autofillImageInput) autofillImageInput.value = '';
            if (removeImageInput) removeImageInput.value = '1';
            if (thumbImg) thumbImg.removeAttribute('src');
            thumbPreview?.classList.add('sp-hidden');
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initListPage();
        initFormPage();
    });
})();
