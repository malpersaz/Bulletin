/**
 * Amed Framework
 * MCP API Keys admin logic
 * templates/admin/assets/js/mod_mcp.js
 */

(function () {
    'use strict';

    let langData = {};

    document.addEventListener('DOMContentLoaded', function () {
        const langInput = document.getElementById('mcpLangData');
        if (langInput) {
            langData = {
                copied: langInput.dataset.copied || '',
                deleteConfirm: langInput.dataset.deleteConfirm || '',
                createdTitle: langInput.dataset.createdTitle || '',
                createdWarning: langInput.dataset.createdWarning || '',
                copyKey: langInput.dataset.copyKey || '',
                networkError: langInput.dataset.networkError || ''
            };
        }

        const createForm = document.getElementById('mcpCreateForm');
        if (createForm) {
            createForm.addEventListener('submit', onCreateSubmit);
        }

        document.querySelectorAll('.mcp-copy-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                copyValue(btn.dataset.copySource || '');
            });
        });

        document.querySelectorAll('.mcp-toggle-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                toggleKey(btn);
            });
        });

        document.querySelectorAll('.mcp-delete-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                deleteKey(btn);
            });
        });

        document.querySelectorAll('.mcp-oauth-toggle-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                toggleOauthClient(btn);
            });
        });

        document.querySelectorAll('.mcp-oauth-delete-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                deleteOauthClient(btn);
            });
        });
    });

    function onCreateSubmit(event) {
        event.preventDefault();

        const nameInput = document.getElementById('mcpKeyName');
        const groupInput = document.getElementById('mcpKeyGroup');
        const submitBtn = document.getElementById('mcpCreateSubmit');
        if (!nameInput || !groupInput || !submitBtn) {
            return;
        }

        submitBtn.disabled = true;

        const formData = new FormData();
        formData.append('create', 'true');
        formData.append('name', nameInput.value);
        formData.append('group_id', groupInput.value);
        formData.append('ajax_request', 'true');

        fetch(SITE_URL + 'mod_mcp/create', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.status === 'success' && data.api_key) {
                    showRevealCard(data.api_key, data.key_id);
                    nameInput.value = '';
                } else {
                    showAlert(data.message || langData.networkError, 'error');
                }
            })
            .catch(function () {
                showAlert(langData.networkError, 'error');
            })
            .finally(function () {
                submitBtn.disabled = false;
            });
    }

    function showRevealCard(apiKey, keyId) {
        const list = document.getElementById('mcpKeyList');
        if (!list) {
            return;
        }

        const existing = document.querySelector('.mcp-reveal-card');
        if (existing) {
            existing.remove();
        }

        const reveal = document.createElement('div');
        reveal.className = 'mcp-reveal-card';
        reveal.dataset.keyId = String(keyId || '');

        const title = document.createElement('div');
        title.className = 'mcp-reveal-title';
        title.textContent = langData.createdTitle;

        const warning = document.createElement('div');
        warning.className = 'mcp-reveal-warning';
        warning.textContent = langData.createdWarning;

        const keyBox = document.createElement('div');
        keyBox.className = 'mcp-reveal-key';
        keyBox.textContent = apiKey;

        const copyBtn = document.createElement('button');
        copyBtn.type = 'button';
        copyBtn.className = 'mcp-btn mcp-btn-secondary mcp-copy-btn';
        copyBtn.textContent = langData.copyKey;
        copyBtn.addEventListener('click', function () {
            copyValueFrom(apiKey);
        });

        reveal.appendChild(title);
        reveal.appendChild(warning);
        reveal.appendChild(keyBox);
        reveal.appendChild(copyBtn);
        list.parentNode.insertBefore(reveal, list);

        copyValueFrom(apiKey);
        reveal.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function copyValue(selector) {
        const input = document.querySelector(selector);
        if (input && input.value) {
            copyValueFrom(input.value);
        }
    }

    function copyValueFrom(value) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(value).then(function () {
                showAlert(langData.copied, 'success');
            });
            return;
        }
        const scratch = document.createElement('textarea');
        scratch.value = value;
        document.body.appendChild(scratch);
        scratch.select();
        document.execCommand('copy');
        scratch.remove();
        showAlert(langData.copied, 'success');
    }

    function toggleKey(btn) {
        const formData = new FormData();
        formData.append('toggle', 'true');
        formData.append('id', btn.dataset.keyId);
        formData.append('ajax_request', 'true');

        btn.disabled = true;

        fetch(SITE_URL + 'mod_mcp/toggle', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.status === 'success') {
                    window.location.reload();
                } else {
                    showAlert(data.message || langData.networkError, 'error');
                    btn.disabled = false;
                }
            })
            .catch(function () {
                showAlert(langData.networkError, 'error');
                btn.disabled = false;
            });
    }

    function deleteKey(btn) {
        if (!window.confirm(langData.deleteConfirm)) {
            return;
        }

        const formData = new FormData();
        formData.append('delete', 'true');
        formData.append('id', btn.dataset.keyId);
        formData.append('ajax_request', 'true');

        btn.disabled = true;

        fetch(SITE_URL + 'mod_mcp/delete', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.status === 'success') {
                    const card = btn.closest('.mcp-key-card');
                    if (card) {
                        card.remove();
                    }
                } else {
                    showAlert(data.message || langData.networkError, 'error');
                    btn.disabled = false;
                }
            })
            .catch(function () {
                showAlert(langData.networkError, 'error');
                btn.disabled = false;
            });
    }

    function toggleOauthClient(btn) {
        const formData = new FormData();
        formData.append('oauth_toggle', 'true');
        formData.append('id', btn.dataset.clientId);
        formData.append('ajax_request', 'true');

        btn.disabled = true;

        fetch(SITE_URL + 'mod_mcp/oauthToggle', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.status === 'success') {
                    window.location.reload();
                } else {
                    showAlert(data.message || langData.networkError, 'error');
                    btn.disabled = false;
                }
            })
            .catch(function () {
                showAlert(langData.networkError, 'error');
                btn.disabled = false;
            });
    }

    function deleteOauthClient(btn) {
        if (!window.confirm(langData.oauthDeleteConfirm)) {
            return;
        }

        const formData = new FormData();
        formData.append('oauth_delete', 'true');
        formData.append('id', btn.dataset.clientId);
        formData.append('ajax_request', 'true');

        btn.disabled = true;

        fetch(SITE_URL + 'mod_mcp/oauthDelete', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.status === 'success') {
                    const card = btn.closest('.mcp-key-card');
                    if (card) {
                        card.remove();
                    }
                } else {
                    showAlert(data.message || langData.networkError, 'error');
                    btn.disabled = false;
                }
            })
            .catch(function () {
                showAlert(langData.networkError, 'error');
                btn.disabled = false;
            });
    }

    function showAlert(message, kind) {
        if (typeof window.Swal !== 'undefined' && message !== '') {
            window.Swal.fire({
                toast: true,
                position: 'bottom',
                timer: 3000,
                showConfirmButton: false,
                icon: kind === 'success' ? 'success' : 'error',
                title: message
            });
        }
    }
})();
