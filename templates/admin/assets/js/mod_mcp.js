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
                endpointCopied: langInput.dataset.endpointCopied || langInput.dataset.copied || '',
                deleteConfirm: langInput.dataset.deleteConfirm || '',
                oauthDeleteConfirm: langInput.dataset.oauthDeleteConfirm || langInput.dataset.deleteConfirm || '',
                createdTitle: langInput.dataset.createdTitle || '',
                createdWarning: langInput.dataset.createdWarning || '',
                copyKey: langInput.dataset.copyKey || '',
                revealClose: langInput.dataset.revealClose || 'OK',
                networkError: langInput.dataset.networkError || ''
            };
        }

        const createForm = document.getElementById('mcpCreateForm');
        if (createForm) {
            createForm.addEventListener('submit', onCreateSubmit);
        }

        document.querySelectorAll('.mcp-copy-btn').forEach(function (btn) {
            bindCopyButton(btn);
        });

        document.querySelectorAll('.mcp-reveal-dismiss-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const card = btn.closest('.mcp-reveal-card');
                if (card) {
                    card.remove();
                }
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

    function bindCopyButton(btn) {
        btn.addEventListener('click', function () {
            const directText = btn.dataset.copyText || '';
            const customMsg = btn.dataset.copiedMsg || langData.copied;
            if (directText !== '') {
                copyValueFrom(directText, customMsg, btn);
                return;
            }
            const sourceSelector = btn.dataset.copySource || '';
            if (sourceSelector !== '') {
                const input = document.querySelector(sourceSelector);
                if (input && input.value) {
                    copyValueFrom(input.value, customMsg, btn);
                }
            }
        });
    }

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
        const createCard = document.querySelector('.mcp-create-card');
        if (!createCard || !createCard.parentNode) {
            return;
        }

        const existing = document.querySelector('.mcp-reveal-card');
        if (existing) {
            existing.remove();
        }

        const reveal = document.createElement('section');
        reveal.className = 'mcp-reveal-card';
        reveal.dataset.keyId = String(keyId || '');

        const header = document.createElement('div');
        header.className = 'mcp-reveal-header';

        const iconWrap = document.createElement('span');
        iconWrap.className = 'mcp-reveal-icon';
        const shieldIcon = document.createElement('i');
        shieldIcon.className = 'bi bi-shield-check';
        shieldIcon.setAttribute('aria-hidden', 'true');
        iconWrap.appendChild(shieldIcon);

        const headingGroup = document.createElement('div');
        headingGroup.className = 'mcp-reveal-heading-group';

        const title = document.createElement('div');
        title.className = 'mcp-reveal-title';
        title.textContent = langData.createdTitle;

        const warning = document.createElement('div');
        warning.className = 'mcp-reveal-warning';
        warning.textContent = langData.createdWarning;

        headingGroup.appendChild(title);
        headingGroup.appendChild(warning);
        header.appendChild(iconWrap);
        header.appendChild(headingGroup);

        const keyBox = document.createElement('div');
        keyBox.className = 'mcp-reveal-key';
        keyBox.textContent = apiKey;

        const actions = document.createElement('div');
        actions.className = 'mcp-reveal-actions';

        const copyBtn = document.createElement('button');
        copyBtn.type = 'button';
        copyBtn.className = 'mcp-btn mcp-btn-secondary mcp-copy-btn';
        const copyIcon = document.createElement('i');
        copyIcon.className = 'bi bi-copy';
        copyIcon.setAttribute('aria-hidden', 'true');
        const copyLabel = document.createElement('span');
        copyLabel.textContent = langData.copyKey;
        copyBtn.appendChild(copyIcon);
        copyBtn.appendChild(copyLabel);
        copyBtn.addEventListener('click', function () {
            copyValueFrom(apiKey, langData.copied, copyBtn);
        });

        const dismissBtn = document.createElement('button');
        dismissBtn.type = 'button';
        dismissBtn.className = 'mcp-btn mcp-btn-ghost mcp-reveal-dismiss-btn';
        const checkIcon = document.createElement('i');
        checkIcon.className = 'bi bi-check2';
        checkIcon.setAttribute('aria-hidden', 'true');
        const dismissLabel = document.createElement('span');
        dismissLabel.textContent = langData.revealClose;
        dismissBtn.appendChild(checkIcon);
        dismissBtn.appendChild(dismissLabel);
        dismissBtn.addEventListener('click', function () {
            reveal.remove();
            window.location.reload();
        });

        actions.appendChild(copyBtn);
        actions.appendChild(dismissBtn);

        reveal.appendChild(header);
        reveal.appendChild(keyBox);
        reveal.appendChild(actions);

        createCard.parentNode.insertBefore(reveal, createCard);

        copyValueFrom(apiKey, langData.copied, copyBtn);
        reveal.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function copyValueFrom(value, message, triggerBtn) {
        const toastMsg = message || langData.copied;
        const onCopied = function () {
            showAlert(toastMsg, 'success');
            if (triggerBtn) {
                const icon = triggerBtn.querySelector('i');
                if (icon) {
                    const prevClass = icon.className;
                    icon.className = 'bi bi-check2';
                    setTimeout(function () {
                        icon.className = prevClass;
                    }, 1600);
                }
            }
        };

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(value).then(onCopied);
            return;
        }
        const scratch = document.createElement('textarea');
        scratch.value = value;
        document.body.appendChild(scratch);
        scratch.select();
        document.execCommand('copy');
        scratch.remove();
        onCopied();
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
                    syncSummaryCounts();
                    showAlert(data.message || '', 'success');
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
                    syncSummaryCounts();
                    showAlert(data.message || '', 'success');
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

    function syncSummaryCounts() {
        const keysList = document.getElementById('mcpKeysList');
        const oauthList = document.getElementById('mcpOauthList');
        if (keysList) {
            const keyCards = keysList.querySelectorAll('.mcp-key-card');
            const activeKeyCards = keysList.querySelectorAll('.mcp-key-card:not(.mcp-key-card-revoked)');
            const totalStat = document.getElementById('mcpStatTotalKeys');
            const activeStat = document.getElementById('mcpStatActiveKeys');
            const sectionCount = keysList.closest('.mcp-section') ? keysList.closest('.mcp-section').querySelector('.mcp-section-count') : null;
            if (totalStat) {
                totalStat.textContent = String(keyCards.length);
            }
            if (activeStat) {
                activeStat.textContent = String(activeKeyCards.length);
            }
            if (sectionCount) {
                sectionCount.textContent = String(keyCards.length);
            }
        }
        if (oauthList) {
            const clientCards = oauthList.querySelectorAll('.mcp-key-card');
            const oauthStat = document.getElementById('mcpStatOauthClients');
            const sectionCount = oauthList.closest('.mcp-section') ? oauthList.closest('.mcp-section').querySelector('.mcp-section-count') : null;
            if (oauthStat) {
                oauthStat.textContent = String(clientCards.length);
            }
            if (sectionCount) {
                sectionCount.textContent = String(clientCards.length);
            }
        }
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
