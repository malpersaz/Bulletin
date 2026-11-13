/**
 * social_post_picker.js - Inline [socialpost:N] Bottom-Sheet & Host Editor Bridge
 * Auto-mounts a phone-first hairline bar on registered host content editors
 * (mod_news, mod_publication, mod_event, mod_announcements, mod_page).
 * Zero inline styles: toggles visibility exclusively via CSS classes.
 */
(function () {
    'use strict';

    const HOST_MODULES = new Set([
        'mod_news',
        'mod_publication',
        'mod_event',
        'mod_announcements',
        'mod_page'
    ]);

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function getAdminBaseUrl() {
        if (typeof window.SITE_URL === 'string' && window.SITE_URL.trim() !== '') {
            return window.SITE_URL;
        }
        if (typeof SITE_URL !== 'undefined' && SITE_URL) {
            return SITE_URL;
        }
        const baseEl = document.querySelector('base[href]');
        if (baseEl && baseEl.getAttribute('href')) {
            return baseEl.getAttribute('href');
        }
        const body = document.body;
        if (body?.dataset?.adminUrl) {
            return body.dataset.adminUrl;
        }
        const localeMatch = (window.location.pathname || '').match(/^\/([a-z]{2,3})(?:\/|$)/i);
        return localeMatch ? ('/' + localeMatch[1] + '/') : '/';
    }

    async function fetchJson(url, options) {
        const res = await fetch(url, options);
        const raw = await res.text();
        let payload = null;
        try {
            payload = raw ? JSON.parse(raw) : null;
        } catch (err) {
            console.error('[SocialPostPicker] Non-JSON response (HTTP ' + res.status + ') from ' + url + ':', raw.slice(0, 2000));
            throw new Error((getSheetLang().quick_save_error || 'Invalid server response') + ' (HTTP ' + res.status + ')');
        }
        if (!payload || typeof payload !== 'object') {
            console.error('[SocialPostPicker] Empty response (HTTP ' + res.status + ') from ' + url);
            throw new Error((getSheetLang().quick_save_error || 'Invalid server response') + ' (HTTP ' + res.status + ', empty)');
        }
        if (payload.code === 'session_expired' && payload.redirect) {
            window.location.href = payload.redirect;
            throw new Error(payload.message || 'Session expired');
        }
        return payload;
    }

    function getSheetLang() {
        const sp = window.adminLang?.mod_socialmedia_post;
        return sp?.sheet || {};
    }

    function getModuleLabels() {
        return window.adminLang?.mod_socialmedia_post || {};
    }

    function detectRouteModule() {
        const path = window.location.pathname || '';
        for (const modName of HOST_MODULES) {
            if (path.includes('/' + modName + '/')) {
                const idMatch = path.match(new RegExp('/' + modName + '/edit/(\\d+)'));
                return {
                    modName,
                    vid: idMatch ? parseInt(idMatch[1], 10) : 0
                };
            }
        }
        return { modName: '', vid: 0 };
    }

    function extractTokensFromHtml(html) {
        const ids = [];
        const regex = /\[socialpost:(\d+)\]/gi;
        let match;
        while ((match = regex.exec(String(html || ''))) !== null) {
            const id = parseInt(match[1], 10);
            if (id > 0 && !ids.includes(id)) {
                ids.push(id);
            }
        }
        return ids;
    }

    function getProviderIcon(provider) {
        if (provider === 'x') return 'bi-twitter-x';
        if (provider === 'facebook') return 'bi-facebook';
        if (provider === 'instagram') return 'bi-instagram';
        return 'bi-share';
    }

    function notify(msg, type) {
        if (typeof showSnackbar === 'function') {
            showSnackbar(msg, type || 'info');
            return;
        }
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: type === 'error' ? 'error' : 'success',
                text: msg,
                timer: 2000,
                showConfirmButton: false
            });
        }
    }

    const Picker = {
        initialized: false,
        sheetEl: null,
        overlayEl: null,
        state: {
            mode: 'editor',
            targetModule: '',
            vid: 0,
            lang: 'en',
            activeTab: 'new',
            providerFilter: 'all',
            query: '',
            libraryOffset: 0,
            libraryItems: [],
            boundItems: [],
            previewData: null,
            activeTextarea: null,
            activeHostForm: null,
            activeBar: null
        },

        init() {
            if (this.initialized) {
                return;
            }
            this.initialized = true;
            this.sheetEl = document.getElementById('socialPostSheet');
            this.overlayEl = document.getElementById('socialPostSheetOverlay');

            if (this.sheetEl) {
                this.bindSheetEvents();
            }

            this.mountAllEditors();
        },

        baseUrl(action) {
            return getAdminBaseUrl().replace(/\/+$/, '') + '/mod_socialmedia_post/' + action;
        },

        getTextareaContent(textarea) {
            if (!textarea) return '';
            if (window.tinymce && textarea.id) {
                const ed = window.tinymce.get(textarea.id);
                if (ed && !ed.destroyed) {
                    return ed.getContent();
                }
            }
            return textarea.value || '';
        },

        insertTokenIntoEditor(token) {
            const textarea = this.state.activeTextarea;
            if (!textarea || !token) {
                return false;
            }
            const htmlBlock = '<p>' + token + '</p>';

            if (window.tinymce && textarea.id) {
                const ed = window.tinymce.get(textarea.id);
                if (ed && !ed.destroyed) {
                    ed.focus();
                    ed.insertContent(htmlBlock);
                    ed.save();
                    textarea.dispatchEvent(new Event('input', { bubbles: true }));
                    return true;
                }
            }

            const val = textarea.value || '';
            const start = typeof textarea.selectionStart === 'number' ? textarea.selectionStart : val.length;
            const end = typeof textarea.selectionEnd === 'number' ? textarea.selectionEnd : val.length;
            const prefix = start > 0 && !val.slice(0, start).endsWith('\n') ? '\n' : '';
            const suffix = end < val.length && !val.slice(end).startsWith('\n') ? '\n' : '';
            textarea.value = val.slice(0, start) + prefix + htmlBlock + suffix + val.slice(end);
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
            textarea.dispatchEvent(new Event('change', { bubbles: true }));
            return true;
        },

        syncHiddenInputs(form, items) {
            if (!form) return;
            form.querySelectorAll('input[data-sp-hidden-id="1"]').forEach((el) => el.remove());
            (items || []).forEach((item) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'social_post_ids[]';
                input.value = String(item.id);
                input.dataset.spHiddenId = '1';
                form.appendChild(input);
            });
        },

        renderBarChips(barEl, items, hostForm) {
            if (!barEl) return;
            const countEl = barEl.querySelector('[data-sp-count]');
            const chipsEl = barEl.querySelector('[data-sp-chips]');
            const sheetLang = getSheetLang();

            if (countEl) {
                countEl.textContent = String((items || []).length);
            }
            if (chipsEl) {
                chipsEl.innerHTML = (items || []).map((item) => `
                    <span class="sp-bound-chip" data-sp-chip-id="${escapeHtml(item.id)}">
                        <i class="bi ${getProviderIcon(item.provider)}"></i>
                        <span class="sp-bound-chip-label">${escapeHtml(item.name || item.token)}</span>
                        <span class="sp-bound-chip-code">${escapeHtml(item.token)}</span>
                        <button type="button" class="sp-token-btn" data-sp-chip-insert="${escapeHtml(item.token)}" title="${escapeHtml(sheetLang.insert_chip_button || 'Insert')}">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </span>
                `).join('');
            }
            if (hostForm) {
                this.syncHiddenInputs(hostForm, items);
            }
        },

        async refreshBarForEditor(barEl) {
            if (!barEl) return;
            const modName = barEl.dataset.targetModule || '';
            const vid = parseInt(barEl.dataset.vid || '0', 10);
            const textareaId = barEl.dataset.textareaId || '';
            const textarea = textareaId ? document.getElementById(textareaId) : null;
            const hostForm = barEl.closest('form');
            const tokenIds = extractTokensFromHtml(this.getTextareaContent(textarea));

            if (!modName) return;

            const params = new URLSearchParams();
            params.set('mod_name', modName);
            if (vid > 0) {
                params.set('vid', String(vid));
            } else if (tokenIds.length) {
                params.set('post_ids', tokenIds.join(','));
            } else {
                this.renderBarChips(barEl, [], hostForm);
                return;
            }

            try {
                const payload = await fetchJson(this.baseUrl('ajax_get_bound') + '?' + params.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (payload.status === 'success') {
                    this.state.boundItems = payload.items || [];
                    this.renderBarChips(barEl, this.state.boundItems, hostForm);
                }
            } catch (e) {
                // Keep quiet on passive hydrate failures
            }
        },

        mountEditorBar(textarea, explicitConfig) {
            if (!textarea) return;
            const form = textarea.closest('form');
            if (!form) return;

            const routeInfo = detectRouteModule();
            const targetModule = explicitConfig?.targetModule
                || textarea.dataset.targetModule
                || form.dataset.targetModule
                || routeInfo.modName;

            if (!targetModule || !HOST_MODULES.has(targetModule)) {
                return;
            }

            const existingBar = form.querySelector('[data-social-post-bar]');
            if (existingBar) {
                if (!existingBar.dataset.spBoundEvents) {
                    this.bindBarEvents(existingBar, textarea, form);
                    this.refreshBarForEditor(existingBar);
                }
                return existingBar;
            }

            const vid = explicitConfig?.vid
                || parseInt(textarea.dataset.vid || form.dataset.vid || String(routeInfo.vid || 0), 10);

            if (!textarea.id) {
                textarea.id = 'spHostTextarea_' + Math.random().toString(36).slice(2, 8);
            }

            const modLang = getModuleLabels();
            const sheetLang = getSheetLang();
            const bar = document.createElement('div');
            bar.className = 'sp-editor-bar';
            bar.dataset.socialPostBar = '1';
            bar.dataset.targetModule = targetModule;
            bar.dataset.vid = String(vid || 0);
            bar.dataset.textareaId = textarea.id;
            bar.innerHTML = `
                <div class="sp-editor-bar-row">
                    <button type="button" class="sp-editor-trigger" data-sp-open-sheet="1">
                        <i class="bi bi-share"></i>
                        <span>${escapeHtml(modLang.named || 'Social Media Posts')}</span>
                        <span class="sp-count-pill" data-sp-count="1">0</span>
                        <i class="bi bi-plus-circle"></i>
                    </button>
                </div>
                <div class="sp-chips-row" data-sp-chips="1" aria-label="${escapeHtml(sheetLang.tab_linked || 'Linked')}"></div>
            `;

            const wrap = textarea.closest('.mod-news-editor-wrap') || textarea.parentElement;
            if (wrap && wrap.parentElement) {
                wrap.parentElement.insertBefore(bar, wrap);
            } else {
                textarea.before(bar);
            }

            this.bindBarEvents(bar, textarea, form);
            this.refreshBarForEditor(bar);
            return bar;
        },

        bindBarEvents(bar, textarea, form) {
            bar.dataset.spBoundEvents = '1';

            bar.addEventListener('click', (event) => {
                const insertBtn = event.target.closest('[data-sp-chip-insert]');
                if (insertBtn) {
                    event.preventDefault();
                    this.state.activeTextarea = textarea;
                    this.insertTokenIntoEditor(insertBtn.dataset.spChipInsert);
                    return;
                }

                const openBtn = event.target.closest('[data-sp-open-sheet]');
                if (openBtn) {
                    event.preventDefault();
                    const langSelect = form.querySelector('select[name="lang"], input[name="lang"]:checked, input[name="lang"]');
                    const currentLang = langSelect?.value || document.documentElement.lang || 'en';
                    this.openSheet({
                        mode: 'editor',
                        targetModule: bar.dataset.targetModule || '',
                        vid: parseInt(bar.dataset.vid || '0', 10),
                        lang: currentLang,
                        textareaEl: textarea,
                        hostForm: form,
                        barEl: bar
                    });
                }
            });
        },

        mountAllEditors(root) {
            const scope = root || document;
            scope.querySelectorAll('textarea[name="content"]').forEach((textarea) => {
                if (textarea.closest('#socialPostFormShell')) {
                    return;
                }
                this.mountEditorBar(textarea);
            });
        },

        openForListRecord(targetModule, vid, lang) {
            this.openSheet({
                mode: 'relation',
                targetModule,
                vid: parseInt(vid || '0', 10),
                lang: lang || document.documentElement.lang || 'en',
                textareaEl: null,
                hostForm: null,
                barEl: null
            });
        },

        openSheet(options) {
            if (!this.sheetEl || !this.overlayEl) {
                return;
            }

            this.state.mode = options.mode || 'editor';
            this.state.targetModule = options.targetModule || '';
            this.state.vid = parseInt(options.vid || '0', 10);
            this.state.lang = options.lang || 'en';
            this.state.activeTextarea = options.textareaEl || null;
            this.state.activeHostForm = options.hostForm || null;
            this.state.activeBar = options.barEl || null;
            this.state.previewData = null;

            const dynamicDialog = document.getElementById('modal-dynamic-dialog');
            const hostContainer = (dynamicDialog && dynamicDialog.open)
                ? dynamicDialog
                : (document.querySelector('.mobile-wrapper') || document.body);
            if (this.overlayEl.parentElement !== hostContainer) {
                hostContainer.appendChild(this.overlayEl);
            }
            if (this.sheetEl.parentElement !== hostContainer) {
                hostContainer.appendChild(this.sheetEl);
            }

            const subtitleEl = document.getElementById('spSheetSubtitle');
            const sheetLang = getSheetLang();
            if (subtitleEl) {
                subtitleEl.textContent = this.state.mode === 'editor'
                    ? (sheetLang.subtitle || '')
                    : (this.state.targetModule + ' #' + this.state.vid);
            }

            const saveBtnLabel = document.getElementById('spSheetQuickSaveLabel');
            if (saveBtnLabel) {
                saveBtnLabel.textContent = this.state.mode === 'editor'
                    ? (sheetLang.insert_button || 'Save & Insert')
                    : (sheetLang.bind_only_button || 'Save & Link');
            }

            this.resetNewTab();
            this.switchTab('new');
            this.loadBoundItems();

            this.overlayEl.classList.add('active');
            this.sheetEl.classList.add('active');
            this.sheetEl.setAttribute('aria-hidden', 'false');
        },

        closeSheet() {
            if (!this.sheetEl || !this.overlayEl) return;
            this.sheetEl.classList.remove('active');
            this.overlayEl.classList.remove('active');
            this.sheetEl.setAttribute('aria-hidden', 'true');
        },

        switchTab(tab) {
            this.state.activeTab = tab;
            this.sheetEl?.querySelectorAll('[data-sp-tab]').forEach((btn) => {
                btn.classList.toggle('is-active', btn.dataset.spTab === tab);
            });
            this.sheetEl?.querySelectorAll('[data-sp-panel]').forEach((panel) => {
                panel.classList.toggle('sp-hidden', panel.dataset.spPanel !== tab);
            });

            if (tab === 'library') {
                this.loadLibrary(false);
            } else if (tab === 'linked') {
                this.renderLinkedItems();
            }
        },

        setFeedback(message, type) {
            const box = document.getElementById('spSheetFeedback');
            if (!box) return;
            if (!message) {
                box.className = 'sp-sheet-feedback sp-hidden';
                box.textContent = '';
                return;
            }
            box.textContent = message;
            box.className = 'sp-sheet-feedback ' + (type === 'error' ? 'is-error' : 'is-success');
        },

        resetNewTab() {
            const urlInput = document.getElementById('spSheetUrlInput');
            const nameInput = document.getElementById('spSheetNameInput');
            const previewBox = document.getElementById('spSheetPreviewBox');
            if (urlInput) urlInput.value = '';
            if (nameInput) nameInput.value = '';
            this.state.previewData = null;
            previewBox?.classList.add('sp-hidden');
            this.setFeedback('');
        },

        async fetchPreviewInSheet() {
            const urlInput = document.getElementById('spSheetUrlInput');
            const url = (urlInput?.value || '').trim();
            if (!url) {
                this.setFeedback(getModuleLabels().autofill?.missing_source || 'URL required', 'error');
                return;
            }

            this.setFeedback('');
            const body = new URLSearchParams();
            body.set('url', url);
            body.set('lang', this.state.lang);

            try {
                const payload = await fetchJson(this.baseUrl('autofill_metadata'), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: body.toString()
                });
                if (payload.status !== 'success' || !payload.data) {
                    throw new Error(payload.message || getSheetLang().quick_save_error || 'Error');
                }
                const d = payload.data;
                this.state.previewData = d;
                const previewBox = document.getElementById('spSheetPreviewBox');
                const providerEl = document.getElementById('spSheetPreviewProvider');
                const thumbEl = document.getElementById('spSheetPreviewThumb');
                const nameInput = document.getElementById('spSheetNameInput');
                const captionEl = document.getElementById('spSheetPreviewCaption');

                if (providerEl) {
                    providerEl.textContent = `${d.provider_label || d.provider} · ${d.media_type || 'post'}`;
                }
                if (thumbEl) {
                    if (d.image_url) {
                        thumbEl.src = d.image_url;
                        thumbEl.classList.remove('sp-hidden');
                    } else {
                        thumbEl.removeAttribute('src');
                        thumbEl.classList.add('sp-hidden');
                    }
                }
                if (nameInput) {
                    nameInput.value = d.name || '';
                }
                if (captionEl) {
                    captionEl.textContent = d.content || (d.handle ? '@' + d.handle : '');
                }
                previewBox?.classList.remove('sp-hidden');
            } catch (err) {
                this.setFeedback(String(err.message || err), 'error');
            }
        },

        async quickSaveFromSheet() {
            const urlInput = document.getElementById('spSheetUrlInput');
            const nameInput = document.getElementById('spSheetNameInput');
            const rawUrl = (urlInput?.value || '').trim();
            if (!rawUrl) {
                this.setFeedback(getModuleLabels().autofill?.missing_source || 'URL required', 'error');
                return;
            }

            const btn = document.getElementById('spSheetQuickSaveBtn');
            if (btn) btn.disabled = true;

            try {
                const body = new URLSearchParams();
                body.set('url', rawUrl);
                body.set('lang', this.state.lang);
                body.set('status', 'y');
                if (this.state.targetModule) {
                    body.set('target_module', this.state.targetModule);
                }
                if (this.state.vid > 0) {
                    body.set('target_id', String(this.state.vid));
                }
                const titleVal = (nameInput?.value || '').trim();
                if (titleVal) {
                    body.set('name', titleVal);
                }
                if (this.state.previewData) {
                    if (this.state.previewData.content) body.set('content', this.state.previewData.content);
                    if (this.state.previewData.handle) body.set('handle', this.state.previewData.handle);
                    if (this.state.previewData.image) body.set('autofill_image', this.state.previewData.image);
                    if (this.state.previewData.media_type) body.set('media_type', this.state.previewData.media_type);
                }

                const payload = await fetchJson(this.baseUrl('quick_save'), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: body.toString()
                });
                if (payload.status !== 'success') {
                    throw new Error(payload.message || getSheetLang().quick_save_error);
                }

                this.addBoundLocalItem(payload.item);
                if (this.state.mode === 'editor' && payload.token) {
                    this.insertTokenIntoEditor(payload.token);
                }
                notify(payload.message || getSheetLang().saved_and_inserted, 'success');
                this.closeSheet();
            } catch (err) {
                this.setFeedback(String(err.message || err), 'error');
            } finally {
                if (btn) btn.disabled = false;
            }
        },

        addBoundLocalItem(item) {
            if (!item || !item.id) return;
            const exists = this.state.boundItems.some((row) => String(row.id) === String(item.id));
            if (!exists) {
                this.state.boundItems.push(item);
            }
            this.updateLinkedCount();
            if (this.state.activeBar) {
                this.renderBarChips(this.state.activeBar, this.state.boundItems, this.state.activeHostForm);
            }
        },

        removeBoundLocalItem(postId) {
            this.state.boundItems = this.state.boundItems.filter((row) => String(row.id) !== String(postId));
            this.updateLinkedCount();
            this.renderLinkedItems();
            if (this.state.activeBar) {
                this.renderBarChips(this.state.activeBar, this.state.boundItems, this.state.activeHostForm);
            }
        },

        updateLinkedCount() {
            const el = document.getElementById('spSheetLinkedCount');
            if (el) {
                el.textContent = String(this.state.boundItems.length);
            }
        },

        async loadBoundItems() {
            const params = new URLSearchParams();
            if (this.state.targetModule) {
                params.set('mod_name', this.state.targetModule);
            }
            if (this.state.vid > 0) {
                params.set('vid', String(this.state.vid));
            } else {
                const tokenIds = extractTokensFromHtml(this.getTextareaContent(this.state.activeTextarea));
                const hiddenIds = Array.from(this.state.activeHostForm?.querySelectorAll('input[data-sp-hidden-id="1"]') || [])
                    .map((el) => parseInt(el.value, 10))
                    .filter((n) => n > 0);
                const merged = Array.from(new Set([...tokenIds, ...hiddenIds]));
                if (!merged.length) {
                    this.state.boundItems = [];
                    this.updateLinkedCount();
                    this.renderLinkedItems();
                    return;
                }
                params.set('post_ids', merged.join(','));
            }

            try {
                const payload = await fetchJson(this.baseUrl('ajax_get_bound') + '?' + params.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (payload.status === 'success') {
                    this.state.boundItems = payload.items || [];
                    this.updateLinkedCount();
                    this.renderLinkedItems();
                    if (this.state.activeBar) {
                        this.renderBarChips(this.state.activeBar, this.state.boundItems, this.state.activeHostForm);
                    }
                }
            } catch (e) {
                // Keep UI responsive
            }
        },

        async loadLibrary(append) {
            if (!append) {
                this.state.libraryOffset = 0;
                this.state.libraryItems = [];
            }

            const params = new URLSearchParams();
            params.set('provider', this.state.providerFilter);
            params.set('q', this.state.query);
            params.set('offset', String(this.state.libraryOffset));
            params.set('limit', '15');

            try {
                const payload = await fetchJson(this.baseUrl('ajax_list_library') + '?' + params.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (payload.status !== 'success') {
                    return;
                }
                const incoming = payload.items || [];
                this.state.libraryItems = append ? this.state.libraryItems.concat(incoming) : incoming;
                this.state.libraryOffset = this.state.libraryItems.length;
                this.renderLibraryItems();
                const moreBtn = document.getElementById('spSheetLoadMoreBtn');
                moreBtn?.classList.toggle('sp-hidden', !payload.has_more);
            } catch (e) {
                // Keep UI responsive
            }
        },

        renderLibraryItems() {
            const container = document.getElementById('spSheetLibraryList');
            const emptyEl = document.getElementById('spSheetLibraryEmpty');
            if (!container) return;

            const sheetLang = getSheetLang();
            if (!this.state.libraryItems.length) {
                container.innerHTML = '';
                emptyEl?.classList.remove('sp-hidden');
                return;
            }
            emptyEl?.classList.add('sp-hidden');

            const boundSet = new Set(this.state.boundItems.map((i) => String(i.id)));
            container.innerHTML = this.state.libraryItems.map((item) => {
                const isLinked = boundSet.has(String(item.id));
                const primaryLabel = this.state.mode === 'editor'
                    ? (sheetLang.insert_chip_button || 'Insert')
                    : (isLinked ? (sheetLang.unlink_button || 'Unlink') : (sheetLang.link_button || 'Link'));
                const thumbHtml = item.thumb_url
                    ? `<img class="sp-card-thumb" src="${escapeHtml(item.thumb_url)}" alt="">`
                    : '';

                return `
                    <article class="sp-sheet-item" data-library-id="${escapeHtml(item.id)}">
                        <div class="sp-card-head">
                            <div class="sp-card-badges">
                                <span class="sp-provider-badge" data-provider="${escapeHtml(item.provider)}">
                                    <i class="bi ${getProviderIcon(item.provider)}"></i>
                                    ${escapeHtml(item.provider_label)}
                                </span>
                                <span class="sp-media-badge">${escapeHtml(item.media_type)}</span>
                            </div>
                            <span class="sp-bound-chip-code">${escapeHtml(item.token)}</span>
                        </div>
                        <div class="sp-card-body">
                            ${thumbHtml}
                            <div class="sp-card-info">
                                <h4 class="sp-card-title">${escapeHtml(item.name)}</h4>
                                ${item.handle ? `<div class="sp-card-handle">@${escapeHtml(item.handle)}</div>` : ''}
                            </div>
                        </div>
                        <div class="sp-sheet-item-actions">
                            <button type="button" class="sp-btn sp-btn-primary" data-sp-library-action="${escapeHtml(item.id)}">
                                ${escapeHtml(primaryLabel)}
                            </button>
                            ${this.state.mode === 'editor' ? `
                                <button type="button" class="sp-btn ${isLinked ? 'sp-btn-subtle' : ''}" data-sp-library-link="${escapeHtml(item.id)}" aria-label="${escapeHtml(sheetLang.link_button || 'Link')}">
                                    <i class="bi ${isLinked ? 'bi-check2' : 'bi-link-45deg'}"></i>
                                </button>
                            ` : ''}
                        </div>
                    </article>
                `;
            }).join('');
        },

        renderLinkedItems() {
            const container = document.getElementById('spSheetLinkedList');
            const emptyEl = document.getElementById('spSheetLinkedEmpty');
            if (!container) return;

            const sheetLang = getSheetLang();
            if (!this.state.boundItems.length) {
                container.innerHTML = '';
                emptyEl?.classList.remove('sp-hidden');
                return;
            }
            emptyEl?.classList.add('sp-hidden');

            container.innerHTML = this.state.boundItems.map((item) => `
                <article class="sp-sheet-item" data-linked-id="${escapeHtml(item.id)}">
                    <div class="sp-card-head">
                        <span class="sp-provider-badge" data-provider="${escapeHtml(item.provider)}">
                            <i class="bi ${getProviderIcon(item.provider)}"></i>
                            ${escapeHtml(item.provider_label)}
                        </span>
                        <span class="sp-bound-chip-code">${escapeHtml(item.token)}</span>
                    </div>
                    <div class="sp-card-info">
                        <h4 class="sp-card-title">${escapeHtml(item.name)}</h4>
                        ${item.handle ? `<div class="sp-card-handle">@${escapeHtml(item.handle)}</div>` : ''}
                    </div>
                    <div class="sp-sheet-item-actions">
                        ${this.state.mode === 'editor' ? `
                            <button type="button" class="sp-btn sp-btn-primary" data-sp-linked-insert="${escapeHtml(item.token)}">
                                ${escapeHtml(sheetLang.insert_chip_button || 'Insert')}
                            </button>
                        ` : `<span></span>`}
                        <button type="button" class="sp-btn sp-btn-danger" data-sp-linked-unlink="${escapeHtml(item.id)}">
                            ${escapeHtml(sheetLang.unlink_button || 'Unlink')}
                        </button>
                    </div>
                </article>
            `).join('');
        },

        async linkPost(postId, shouldInsert) {
            const item = this.state.libraryItems.find((row) => String(row.id) === String(postId))
                || this.state.boundItems.find((row) => String(row.id) === String(postId));

            if (this.state.vid > 0 && this.state.targetModule) {
                const body = new URLSearchParams();
                body.set('mod_name', this.state.targetModule);
                body.set('vid', String(this.state.vid));
                body.set('socialmedia_post_id', String(postId));

                try {
                    const payload = await fetchJson(this.baseUrl('ajax_link'), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: body.toString()
                    });
                    if (payload.status !== 'success') {
                        notify(payload.message || 'Error', 'error');
                        return;
                    }
                    this.state.boundItems = payload.items || this.state.boundItems;
                    this.updateLinkedCount();
                    if (this.state.activeBar) {
                        this.renderBarChips(this.state.activeBar, this.state.boundItems, this.state.activeHostForm);
                    }
                } catch (err) {
                    notify(String(err.message || err), 'error');
                    return;
                }
            } else if (item) {
                this.addBoundLocalItem(item);
            }

            if (shouldInsert && this.state.mode === 'editor') {
                this.insertTokenIntoEditor('[socialpost:' + postId + ']');
                this.closeSheet();
            } else {
                this.renderLibraryItems();
                this.renderLinkedItems();
            }
        },

        async unlinkPost(postId) {
            if (this.state.vid > 0 && this.state.targetModule) {
                const body = new URLSearchParams();
                body.set('mod_name', this.state.targetModule);
                body.set('vid', String(this.state.vid));
                body.set('socialmedia_post_id', String(postId));

                try {
                    const payload = await fetchJson(this.baseUrl('ajax_unlink'), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: body.toString()
                    });
                    if (payload.status === 'success') {
                        this.state.boundItems = payload.items || [];
                    }
                } catch (err) {
                    notify(String(err.message || err), 'error');
                    return;
                }
            }
            this.removeBoundLocalItem(postId);
            this.renderLibraryItems();
        },

        bindSheetEvents() {
            document.getElementById('spSheetCloseBtn')?.addEventListener('click', () => this.closeSheet());
            this.overlayEl?.addEventListener('click', () => this.closeSheet());
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && this.sheetEl?.classList.contains('active')) {
                    this.closeSheet();
                }
            });

            this.sheetEl?.querySelectorAll('[data-sp-tab]').forEach((tabBtn) => {
                tabBtn.addEventListener('click', () => this.switchTab(tabBtn.dataset.spTab));
            });

            document.getElementById('spSheetPasteBtn')?.addEventListener('click', async () => {
                const urlInput = document.getElementById('spSheetUrlInput');
                if (!navigator.clipboard || !urlInput) return;
                try {
                    const txt = await navigator.clipboard.readText();
                    if (txt) {
                        urlInput.value = txt.trim();
                        this.fetchPreviewInSheet();
                    }
                } catch (e) {
                    urlInput.focus();
                }
            });

            document.getElementById('spSheetFetchBtn')?.addEventListener('click', () => this.fetchPreviewInSheet());
            document.getElementById('spSheetQuickSaveBtn')?.addEventListener('click', () => this.quickSaveFromSheet());

            this.sheetEl?.querySelectorAll('[data-sp-sheet-provider]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    this.sheetEl.querySelectorAll('[data-sp-sheet-provider]').forEach((b) => b.classList.remove('is-active'));
                    btn.classList.add('is-active');
                    this.state.providerFilter = btn.dataset.spSheetProvider || 'all';
                    this.loadLibrary(false);
                });
            });

            let libTimer = null;
            document.getElementById('spSheetLibrarySearch')?.addEventListener('input', (event) => {
                clearTimeout(libTimer);
                libTimer = setTimeout(() => {
                    this.state.query = event.target.value.trim();
                    this.loadLibrary(false);
                }, 220);
            });

            document.getElementById('spSheetLoadMoreBtn')?.addEventListener('click', () => this.loadLibrary(true));

            document.getElementById('spSheetLibraryList')?.addEventListener('click', (event) => {
                const actionBtn = event.target.closest('[data-sp-library-action]');
                if (actionBtn) {
                    const id = actionBtn.dataset.spLibraryAction;
                    const isLinked = this.state.boundItems.some((r) => String(r.id) === String(id));
                    if (this.state.mode === 'relation' && isLinked) {
                        this.unlinkPost(id);
                    } else {
                        this.linkPost(id, this.state.mode === 'editor');
                    }
                    return;
                }

                const linkBtn = event.target.closest('[data-sp-library-link]');
                if (linkBtn) {
                    const id = linkBtn.dataset.spLibraryLink;
                    const isLinked = this.state.boundItems.some((r) => String(r.id) === String(id));
                    if (isLinked) {
                        this.unlinkPost(id);
                    } else {
                        this.linkPost(id, false);
                    }
                }
            });

            document.getElementById('spSheetLinkedList')?.addEventListener('click', (event) => {
                const insertBtn = event.target.closest('[data-sp-linked-insert]');
                if (insertBtn) {
                    this.insertTokenIntoEditor(insertBtn.dataset.spLinkedInsert);
                    this.closeSheet();
                    return;
                }

                const unlinkBtn = event.target.closest('[data-sp-linked-unlink]');
                if (unlinkBtn) {
                    this.unlinkPost(unlinkBtn.dataset.spLinkedUnlink);
                }
            });
        }
    };

    window.SocialPostPicker = Picker;

    document.addEventListener('DOMContentLoaded', function () {
        Picker.init();
    });
})();
