/**
 * mod_attachment — Externalized Controller Script
 * Supports both standalone admin view and in-app AFModalEngine (<dialog>) view.
 */
(function () {
    'use strict';

    let selectedAttachmentId = null;
    let currentScope = 'all';

    function getShellConfig() {
        const shell = document.getElementById('attachmentShellRoot') || document.getElementById('attachmentUploadRoot');
        if (!shell) {
            return {
                siteUrl: (typeof SITE_URL !== 'undefined' && SITE_URL) ? SITE_URL : '/',
                modName: 'mod_attachment',
                vid: '0',
                sid: '',
                lang: {}
            };
        }
        return {
            siteUrl: shell.dataset.siteUrl || ((typeof SITE_URL !== 'undefined' && SITE_URL) ? SITE_URL : '/'),
            modName: shell.dataset.modName || 'mod_attachment',
            vid: shell.dataset.vid || '0',
            sid: shell.dataset.sid || '',
            lang: {
                newBadge: shell.dataset.langNew || 'NEW',
                fileNamePrompt: shell.dataset.langFileName || 'File Name',
                fileEdited: shell.dataset.langFileEdited || 'File Edited',
                fileDeleted: shell.dataset.langFileDeleted || 'File Deleted',
                uploadFailed: shell.dataset.langUploadFailed || 'Upload failed',
                deleteTitle: shell.dataset.langDeleteTitle || 'Delete File',
                deleteConfirm: shell.dataset.langDeleteConfirm || 'Are you sure you want to delete this file?',
                yes: shell.dataset.langYes || 'Yes',
                cancel: shell.dataset.langCancel || 'Cancel',
                waiting: shell.dataset.langWaiting || 'Uploading...'
            }
        };
    }

    function notifyUser(message, type) {
        if (typeof showSnackbar === 'function') {
            showSnackbar(message, type);
        } else if (typeof showToast === 'function') {
            showToast(message, type);
        } else if (window.AFModalEngine && typeof window.AFModalEngine.showToast === 'function') {
            window.AFModalEngine.showToast(message, type);
        }
    }

    function normalizeAttachmentName(value) {
        return String(value || '').trim().toLocaleLowerCase();
    }

    function updateAttachmentToolbar(active) {
        const deleteBtn = document.getElementById('toolbarDelete');
        if (!deleteBtn) return;

        if (active) {
            deleteBtn.classList.remove('is-disabled');
            deleteBtn.classList.add('active');
        } else {
            deleteBtn.classList.add('is-disabled');
            deleteBtn.classList.remove('active');
        }
    }

    function updateAttachmentEmptyState() {
        const cards = Array.from(document.querySelectorAll('#attachment-list .attachment-card'));
        const scopeCards = cards.filter((card) => !card.classList.contains('hidden-by-scope'));
        const visibleCards = scopeCards.filter((card) => !card.classList.contains('hidden-by-search'));
        const hasCardsInScope = scopeCards.length > 0;

        const countNode = document.getElementById('attachmentCount');
        if (countNode) {
            countNode.textContent = String(visibleCards.length);
        }

        const totalAllNode = document.getElementById('attachmentScopeAllCount');
        if (totalAllNode) {
            totalAllNode.textContent = String(cards.length);
        }

        const totalRecordNode = document.getElementById('attachmentScopeRecordCount');
        if (totalRecordNode) {
            const recCount = cards.filter((c) => c.dataset.isRecord === '1').length;
            totalRecordNode.textContent = String(recCount);
        }

        const searchInput = document.getElementById('attachmentModalSearchInput') || document.getElementById('searchInput');
        const hasSearch = Boolean(searchInput && searchInput.value.trim());

        const empty = document.getElementById('attachment-empty');
        if (empty) {
            empty.classList.toggle('is-hidden', hasCardsInScope);
        }

        const searchEmpty = document.getElementById('search-empty');
        if (searchEmpty) {
            const showSearchEmpty = hasCardsInScope && hasSearch && visibleCards.length === 0;
            searchEmpty.classList.toggle('is-hidden', !showSearchEmpty);
        }
    }

    function applyAttachmentFilters() {
        const searchInput = document.getElementById('attachmentModalSearchInput') || document.getElementById('searchInput');
        const term = String(searchInput ? searchInput.value : '').toLocaleLowerCase().trim();
        const clearBtn = document.getElementById('attachmentSearchClearBtn');
        if (clearBtn) {
            clearBtn.classList.toggle('is-hidden', !term);
        }

        document.querySelectorAll('#attachment-list .attachment-card').forEach((card) => {
            const scopeMatch = currentScope === 'all' || card.dataset.isRecord === '1';
            const searchMatch = !term || String(card.dataset.search || '').toLocaleLowerCase().includes(term);
            card.classList.toggle('hidden-by-scope', !scopeMatch);
            card.classList.toggle('hidden-by-search', !searchMatch);
        });

        updateAttachmentEmptyState();
    }

    function setAttachmentScope(scope) {
        currentScope = scope === 'record' ? 'record' : 'all';
        document.querySelectorAll('.attachment-scope-tab').forEach((tab) => {
            tab.classList.toggle('active', tab.dataset.scope === currentScope);
        });
        applyAttachmentFilters();
    }

    function selectAttachment(id) {
        const targetCard = document.getElementById('attachment-' + id);
        if (!targetCard) return;

        document.querySelectorAll('#attachment-list .attachment-card.selected').forEach((card) => {
            if (card !== targetCard) {
                card.classList.remove('selected');
            }
        });

        const alreadySelected = targetCard.classList.contains('selected');
        targetCard.classList.toggle('selected', !alreadySelected);
        selectedAttachmentId = alreadySelected ? null : String(id);
        updateAttachmentToolbar(Boolean(selectedAttachmentId));
    }

    function closeAttachmentMenus() {
        document.querySelectorAll('#attachment-list .attachment-inline-edit').forEach((item) => {
            item.classList.add('is-hidden');
        });
    }

    function toggleAttachmentEdit(id) {
        const panel = document.getElementById('attachment-edit-' + id);
        if (!panel) return;

        const willOpen = panel.classList.contains('is-hidden');
        closeAttachmentMenus();
        if (willOpen) {
            panel.classList.remove('is-hidden');
            const input = document.getElementById('attachment-input-' + id);
            if (input) {
                input.focus();
                input.select();
            }
        }
    }

    function cancelAttachmentEdit(id) {
        const input = document.getElementById('attachment-input-' + id);
        if (input) {
            input.value = input.defaultValue;
        }
        const panel = document.getElementById('attachment-edit-' + id);
        if (panel) {
            panel.classList.add('is-hidden');
        }
    }

    function saveAttachmentName(id) {
        const cfg = getShellConfig();
        const input = document.getElementById('attachment-input-' + id);
        if (!input) return;

        const value = input.value.trim();
        if (!value) {
            notifyUser(cfg.lang.fileNamePrompt, 'warning');
            input.focus();
            return;
        }

        const card = document.getElementById('attachment-' + id);
        const modName = (card && card.dataset.modName) ? card.dataset.modName : cfg.modName;
        const recordId = (card && card.dataset.vid) ? card.dataset.vid : cfg.vid;

        const body = new URLSearchParams();
        body.set('id', String(id));
        body.set('name', value);
        body.set('vid', String(recordId));
        body.set('mod_name', modName);
        body.set('response_format', 'json');

        fetch(cfg.siteUrl + 'mod_attachment/edit', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: body.toString()
        })
            .then((response) => response.text())
            .then((raw) => {
                let ok = false;
                if (raw.trim() === 'OK') {
                    ok = true;
                } else {
                    try {
                        const parsed = JSON.parse(raw);
                        if (parsed && parsed.status === 'success') {
                            ok = true;
                        } else if (parsed && parsed.message) {
                            throw new Error(parsed.message);
                        }
                    } catch (e) {
                        if (e.message && e.message !== 'Unexpected token') {
                            throw e;
                        }
                    }
                }
                if (!ok) {
                    throw new Error(raw || 'Update failed');
                }

                if (card) {
                    const displayEl = card.querySelector('[data-display]');
                    if (displayEl) {
                        displayEl.textContent = value;
                    }
                    card.dataset.fileName = value.toLocaleLowerCase();
                    card.dataset.search = [value, input.defaultValue, card.dataset.search || ''].join(' ').toLocaleLowerCase();
                }
                input.defaultValue = value;
                const panel = document.getElementById('attachment-edit-' + id);
                if (panel) {
                    panel.classList.add('is-hidden');
                }
                applyAttachmentFilters();
                notifyUser(cfg.lang.fileEdited, 'success');

                if (typeof window.refreshActiveMediaPicker === 'function') {
                    window.refreshActiveMediaPicker();
                }
            })
            .catch((error) => notifyUser(String(error.message || error), 'error'));
    }

    function executeDeleteAttachment(id) {
        const cfg = getShellConfig();
        const card = document.getElementById('attachment-' + id);
        const modName = (card && card.dataset.modName) ? card.dataset.modName : cfg.modName;
        const recordId = (card && card.dataset.vid) ? card.dataset.vid : cfg.vid;

        const body = new URLSearchParams();
        body.set('id', String(id));
        body.set('vid', String(recordId));
        body.set('mod_name', modName);
        body.set('response_format', 'json');

        fetch(cfg.siteUrl + 'mod_attachment/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: body.toString()
        })
            .then((response) => response.text())
            .then((raw) => {
                let ok = false;
                if (raw.trim() === 'OK') {
                    ok = true;
                } else {
                    try {
                        const parsed = JSON.parse(raw);
                        if (parsed && parsed.status === 'success') {
                            ok = true;
                        } else if (parsed && parsed.message) {
                            throw new Error(parsed.message);
                        }
                    } catch (e) {
                        if (e.message && e.message !== 'Unexpected token') {
                            throw e;
                        }
                    }
                }
                if (!ok) {
                    throw new Error(raw || 'Delete failed');
                }

                if (card) {
                    card.remove();
                }
                if (String(selectedAttachmentId) === String(id)) {
                    selectedAttachmentId = null;
                    updateAttachmentToolbar(false);
                }
                applyAttachmentFilters();
                notifyUser(cfg.lang.fileDeleted, 'success');

                if (typeof window.refreshActiveMediaPicker === 'function') {
                    window.refreshActiveMediaPicker();
                }
            })
            .catch((error) => notifyUser(String(error.message || error), 'error'));
    }

    function deleteAttachment(id) {
        if (!id) return;
        const cfg = getShellConfig();
        const openDialog = document.getElementById('modal-dynamic-dialog');
        const swalTarget = (openDialog && openDialog.open) ? openDialog : document.body;

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                target: swalTarget,
                icon: 'warning',
                title: cfg.lang.deleteTitle,
                text: cfg.lang.deleteConfirm,
                showCancelButton: true,
                confirmButtonText: cfg.lang.yes,
                cancelButtonText: cfg.lang.cancel
            }).then((result) => {
                if (result.isConfirmed) {
                    executeDeleteAttachment(id);
                }
            });
        } else if (window.confirm(cfg.lang.deleteConfirm)) {
            executeDeleteAttachment(id);
        }
    }

    function markFreshAttachments(fileNames) {
        if (!Array.isArray(fileNames) || !fileNames.length) return;
        const cfg = getShellConfig();
        const list = document.getElementById('attachment-list');
        if (!list) return;

        const wanted = new Set(fileNames.map(normalizeAttachmentName));
        const matchedCards = [];

        Array.from(list.querySelectorAll('.attachment-card')).forEach((card) => {
            const fileName = normalizeAttachmentName(card.dataset.fileName);
            const originalName = normalizeAttachmentName(card.dataset.originalName);
            if (wanted.has(fileName) || wanted.has(originalName)) {
                matchedCards.push(card);
            }
        });

        matchedCards.reverse().forEach((card) => {
            card.classList.add('is-new');
            const meta = card.querySelector('.attachment-meta');
            if (meta && !meta.querySelector('.attachment-fresh-badge')) {
                const badge = document.createElement('span');
                badge.className = 'attachment-fresh-badge';
                badge.textContent = cfg.lang.newBadge;
                meta.prepend(badge);
            }
            list.prepend(card);
        });
        applyAttachmentFilters();
    }

    function refreshAttachmentList(onDone, freshFiles = []) {
        const cfg = getShellConfig();
        const isGlobal = cfg.modName === 'mod_attachment' || !cfg.vid || cfg.vid === '0';
        const listUrl = isGlobal
            ? `${cfg.siteUrl}mod_attachment/list?ajax=1`
            : `${cfg.siteUrl}mod_attachment/list/${cfg.modName}/${cfg.vid}?ajax=1`;

        fetch(listUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then((response) => response.text())
            .then((html) => {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const nextList = doc.getElementById('attachment-list');
                const currentList = document.getElementById('attachment-list');

                if (nextList && currentList) {
                    currentList.innerHTML = nextList.innerHTML;
                }

                selectedAttachmentId = null;
                updateAttachmentToolbar(false);
                markFreshAttachments(freshFiles);
                applyAttachmentFilters();
                if (typeof window.refreshActiveMediaPicker === 'function') {
                    window.refreshActiveMediaPicker();
                }
                if (typeof onDone === 'function') {
                    onDone();
                }
            })
            .catch((error) => {
                notifyUser(String(error.message || error), 'error');
                if (typeof onDone === 'function') {
                    onDone(error);
                }
            });
    }

    function uploadAttachmentFiles(files) {
        const uploadList = Array.from(files || []);
        if (!uploadList.length) return;

        const cfg = getShellConfig();
        let completed = 0;
        let hasError = false;
        const uploadedNames = uploadList.map((f) => f.name);

        uploadList.forEach((file) => {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('vid', cfg.vid);
            formData.append('isim', 'file');
            if (cfg.sid) {
                formData.append('sid', cfg.sid);
            }
            formData.append('mod_name', cfg.modName);
            formData.append('response_format', 'json');

            fetch(cfg.siteUrl + 'mod_attachment/upload', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
                .then((r) => r.json())
                .then((res) => {
                    if (!res || res.status !== 'success') {
                        throw new Error((res && res.message) ? res.message : cfg.lang.uploadFailed);
                    }
                    notifyUser(res.name || file.name, 'success');
                })
                .catch((error) => {
                    hasError = true;
                    notifyUser(String(error.message || error), 'error');
                })
                .finally(() => {
                    completed += 1;
                    if (completed === uploadList.length && !hasError) {
                        refreshAttachmentList(null, uploadedNames);
                    }
                });
        });
    }

    function bindUploadPage() {
        const uploadRoot = document.getElementById('attachmentUploadRoot');
        if (!uploadRoot || uploadRoot.dataset.bound === 'true') return;
        uploadRoot.dataset.bound = 'true';

        const cfg = getShellConfig();
        const uploadInput = document.getElementById('uploadInput');
        const uploadQueue = document.getElementById('uploadQueue');
        const uploadDropzone = document.getElementById('uploadDropzone');

        function createUploadItem(file) {
            const article = document.createElement('article');
            article.className = 'upload-item';
            article.innerHTML = `
                <div class="upload-item-top">
                    <div>
                        <div class="upload-name"></div>
                        <div class="upload-size"></div>
                    </div>
                    <i class="bi bi-file-earmark-arrow-up"></i>
                </div>
                <div class="upload-progress"><span></span></div>
                <div class="upload-state"></div>
            `;
            article.querySelector('.upload-name').textContent = file.name;
            article.querySelector('.upload-size').textContent = `${Math.max(1, Math.round(file.size / 1024))} KB`;
            article.querySelector('.upload-state').textContent = cfg.lang.waiting;
            if (uploadQueue) {
                uploadQueue.prepend(article);
            }
            return article;
        }

        function handleQueueUpload(files) {
            Array.from(files || []).forEach((file) => {
                const item = createUploadItem(file);
                const progressBar = item.querySelector('.upload-progress > span');
                const state = item.querySelector('.upload-state');

                const formData = new FormData();
                formData.append('file', file);
                formData.append('vid', cfg.vid);
                formData.append('isim', 'file');
                if (cfg.sid) formData.append('sid', cfg.sid);
                formData.append('mod_name', cfg.modName);

                const xhr = new XMLHttpRequest();
                xhr.open('POST', cfg.siteUrl + 'mod_attachment/upload');

                xhr.upload.addEventListener('progress', (event) => {
                    if (!event.lengthComputable) return;
                    const percent = Math.round((event.loaded / event.total) * 100);
                    progressBar.style.width = `${percent}%`;
                    state.textContent = `${percent}%`;
                });

                xhr.onload = function () {
                    const responseText = (xhr.responseText || '').trim();
                    if (xhr.status >= 200 && xhr.status < 300 && responseText && responseText !== 'ERROR') {
                        progressBar.style.width = '100%';
                        state.textContent = responseText;
                        notifyUser(responseText, 'success');
                        if (typeof window.refreshActiveMediaPicker === 'function') {
                            window.refreshActiveMediaPicker();
                        }
                        return;
                    }
                    state.textContent = responseText || cfg.lang.uploadFailed;
                    notifyUser(responseText || cfg.lang.uploadFailed, 'error');
                };

                xhr.onerror = function () {
                    state.textContent = cfg.lang.uploadFailed;
                    notifyUser(cfg.lang.uploadFailed, 'error');
                };

                xhr.send(formData);
            });
        }

        if (uploadInput) {
            uploadInput.addEventListener('change', (e) => {
                if (e.target.files && e.target.files.length) {
                    handleQueueUpload(e.target.files);
                    e.target.value = '';
                }
            });
        }

        if (uploadDropzone) {
            ['dragenter', 'dragover'].forEach((evtName) => {
                uploadDropzone.addEventListener(evtName, (evt) => {
                    evt.preventDefault();
                    uploadDropzone.classList.add('dragover');
                });
            });
            ['dragleave', 'drop'].forEach((evtName) => {
                uploadDropzone.addEventListener(evtName, (evt) => {
                    evt.preventDefault();
                    uploadDropzone.classList.remove('dragover');
                });
            });
            uploadDropzone.addEventListener('drop', (evt) => {
                if (evt.dataTransfer && evt.dataTransfer.files && evt.dataTransfer.files.length) {
                    handleQueueUpload(evt.dataTransfer.files);
                }
            });
        }
    }

    function initAttachmentModule() {
        bindUploadPage();

        const shell = document.getElementById('attachmentShellRoot');
        if (!shell || shell.dataset.bound === 'true') return;
        shell.dataset.bound = 'true';

        selectedAttachmentId = null;
        currentScope = 'all';

        const inlineInput = document.getElementById('attachmentInlineInput');
        if (inlineInput) {
            inlineInput.addEventListener('change', (e) => {
                if (e.target.files && e.target.files.length) {
                    uploadAttachmentFiles(e.target.files);
                    e.target.value = '';
                }
            });
        }

        const modalSearch = document.getElementById('attachmentModalSearchInput');
        if (modalSearch) {
            modalSearch.addEventListener('input', () => applyAttachmentFilters());
        }

        const headerSearch = document.getElementById('searchInput');
        if (headerSearch && !headerSearch.dataset.attachmentBound) {
            headerSearch.dataset.attachmentBound = 'true';
            headerSearch.addEventListener('input', () => applyAttachmentFilters());
        }

        shell.addEventListener('click', (e) => {
            if (e.target.closest('.attachment-input')) {
                e.stopPropagation();
                return;
            }

            const actionEl = e.target.closest('[data-action]');
            if (actionEl) {
                const action = actionEl.dataset.action;
                const id = actionEl.dataset.id;

                if (action === 'open-upload') {
                    e.preventDefault();
                    e.stopPropagation();
                    window.lastFileInputTime = Date.now();
                    document.getElementById('attachmentInlineInput')?.click();
                    return;
                }
                if (action === 'toggle-rename' && id) {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleAttachmentEdit(id);
                    return;
                }
                if (action === 'cancel-rename' && id) {
                    e.preventDefault();
                    e.stopPropagation();
                    cancelAttachmentEdit(id);
                    return;
                }
                if (action === 'save-rename' && id) {
                    e.preventDefault();
                    e.stopPropagation();
                    saveAttachmentName(id);
                    return;
                }
                if (action === 'delete-attachment' && id) {
                    e.preventDefault();
                    e.stopPropagation();
                    deleteAttachment(id);
                    return;
                }
                if (action === 'scope-filter') {
                    e.preventDefault();
                    e.stopPropagation();
                    setAttachmentScope(actionEl.dataset.scope || 'all');
                    return;
                }
                if (action === 'clear-attachment-search') {
                    e.preventDefault();
                    e.stopPropagation();
                    if (modalSearch) modalSearch.value = '';
                    if (headerSearch) headerSearch.value = '';
                    applyAttachmentFilters();
                    return;
                }
                if (action === 'select-attachment' && id) {
                    selectAttachment(id);
                    return;
                }
            }
        });

        shell.addEventListener('keydown', (e) => {
            if (e.target.matches('.attachment-input')) {
                const id = e.target.id.replace('attachment-input-', '');
                if (e.key === 'Enter') {
                    e.preventDefault();
                    saveAttachmentName(id);
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    cancelAttachmentEdit(id);
                }
            }
        });

        const toolbar = document.querySelector('.attachment-toolbar');
        if (toolbar && toolbar.dataset.bound !== 'true') {
            toolbar.dataset.bound = 'true';
            toolbar.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-action]');
                if (!btn) return;
                const action = btn.dataset.action;
                if (action === 'delete-selected-attachment') {
                    if (selectedAttachmentId) {
                        deleteAttachment(selectedAttachmentId);
                    }
                } else if (action === 'open-upload') {
                    window.lastFileInputTime = Date.now();
                    (document.getElementById('attachmentInlineInput') || document.getElementById('uploadInput'))?.click();
                } else if (action === 'nav-url' && btn.dataset.url) {
                    window.location.href = btn.dataset.url;
                } else if (action === 'nav-back') {
                    if (window.AFModalEngine && window.AFModalEngine.stack && window.AFModalEngine.stack.length > 0) {
                        window.AFModalEngine.back();
                    } else {
                        window.history.back();
                    }
                }
            });
        }

        const fab = document.getElementById('mainFab');
        if (fab && fab.dataset.bound !== 'true') {
            fab.dataset.bound = 'true';
            fab.addEventListener('click', () => {
                window.lastFileInputTime = Date.now();
                document.getElementById('attachmentInlineInput')?.click();
            });
        }

        applyAttachmentFilters();
    }

    window.initAttachmentModule = initAttachmentModule;
    window.clearSearch = function () {
        const modalSearch = document.getElementById('attachmentModalSearchInput');
        const headerSearch = document.getElementById('searchInput');
        if (modalSearch) modalSearch.value = '';
        if (headerSearch) headerSearch.value = '';
        applyAttachmentFilters();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAttachmentModule);
    } else {
        initAttachmentModule();
    }
})();
