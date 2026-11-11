/**
 * mod_faq_list.js - Global FAQ management list behavior.
 * Configuration is read from data-* attributes on #faqGlobalShell.
 * Actions are strictly id-scoped (toggle status, delete); editing a
 * host record's question set happens in the scoped modal editor.
 */
(function () {
    'use strict';

    let shell = null;
    let messages = {};

    function postForm(url, params) {
        const body = new URLSearchParams();
        Object.keys(params).forEach(function (key) {
            body.append(key, params[key]);
        });
        body.append('ajax_request', 'true');
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        }).then(function (response) {
            return response.json();
        });
    }

    function notify(message, type) {
        if (typeof Swal === 'undefined') {
            return;
        }
        Swal.fire({
            toast: true,
            position: 'bottom',
            timer: 2400,
            timerProgressBar: true,
            showConfirmButton: false,
            icon: type === 'error' ? 'error' : 'success',
            title: message
        });
    }

    function paintStatus(row, button, status) {
        row.dataset.status = status;
        button.dataset.status = status;
        const pill = button.querySelector('.faq-status-pill');
        if (pill) {
            pill.classList.toggle('status-published', status === 'y');
            pill.classList.toggle('status-draft', status !== 'y');
            pill.textContent = status === 'y' ? messages.published : messages.draft;
        }
    }

    function toggleStatus(button) {
        const id = button.dataset.id;
        const next = button.dataset.status === 'y' ? 'n' : 'y';
        button.disabled = true;
        postForm(shell.dataset.toggleUrl, { id: id, status: next })
            .then(function (json) {
                button.disabled = false;
                if (json.status !== 'success') {
                    notify(json.message || messages.errorStatus, 'error');
                    return;
                }
                paintStatus(button.closest('.faq-global-row'), button, next);
                notify(json.message || messages.successStatus, 'success');
            })
            .catch(function () {
                button.disabled = false;
                notify(messages.errorStatus, 'error');
            });
    }

    function removeRow(group, row) {
        row.remove();
        const rows = group.querySelectorAll('.faq-global-row');
        const countBadge = group.querySelector('.faq-global-count-badge');
        if (countBadge) {
            countBadge.textContent = String(rows.length);
        }
        if (rows.length === 0) {
            group.remove();
        }
        updateEmptyStates('', false);
    }

    function deleteRow(button) {
        const id = button.dataset.id;
        Swal.fire({
            icon: 'warning',
            title: messages.confirmDelete,
            showCancelButton: true,
            confirmButtonText: messages.confirmYes,
            cancelButtonText: messages.confirmCancel,
            confirmButtonColor: '#b3261e'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            postForm(shell.dataset.deleteUrl, { id: id })
                .then(function (json) {
                    if (json.status !== 'success') {
                        notify(json.message || messages.errorDelete, 'error');
                        return;
                    }
                    removeRow(button.closest('.faq-global-group'), button.closest('.faq-global-row'));
                    notify(json.message || messages.successDelete, 'success');
                })
                .catch(function () {
                    notify(messages.errorDelete, 'error');
                });
        });
    }

    function updateEmptyStates(query, anyVisible) {
        const listEmpty = document.getElementById('faqGlobalEmpty');
        const searchEmpty = document.getElementById('faqGlobalSearchEmpty');
        const hasGroups = document.querySelectorAll('.faq-global-group').length > 0;
        if (listEmpty) {
            listEmpty.classList.toggle('is-visible', !hasGroups);
        }
        if (searchEmpty) {
            searchEmpty.hidden = !(hasGroups && query !== '' && !anyVisible);
        }
    }

    function filterGroups(value) {
        const query = value.trim().toLowerCase();
        let anyVisible = false;
        document.querySelectorAll('.faq-global-group').forEach(function (group) {
            const groupMatch = query === '' || (group.dataset.search || '').indexOf(query) !== -1;
            let visibleRows = 0;
            group.querySelectorAll('.faq-global-row').forEach(function (row) {
                const rowMatch = query === '' || groupMatch || (row.dataset.search || '').indexOf(query) !== -1;
                row.hidden = !rowMatch;
                if (rowMatch) {
                    visibleRows += 1;
                }
            });
            const visible = visibleRows > 0;
            group.hidden = !visible;
            if (visible) {
                anyVisible = true;
            }
        });
        updateEmptyStates(query, anyVisible);
    }

    function bindEvents() {
        shell.addEventListener('click', function (event) {
            const toggleButton = event.target.closest('[data-action="faq-global-toggle"]');
            if (toggleButton) {
                toggleStatus(toggleButton);
                return;
            }
            const deleteButton = event.target.closest('[data-action="faq-global-delete"]');
            if (deleteButton) {
                deleteRow(deleteButton);
            }
        });

        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            let timer;
            searchInput.addEventListener('input', function () {
                clearTimeout(timer);
                const value = this.value;
                timer = setTimeout(function () {
                    filterGroups(value);
                }, 180);
            });
        }

        const backPanel = document.querySelector('[data-action="faq-global-back-panel"]');
        if (backPanel) {
            backPanel.addEventListener('click', function () {
                window.location.href = (typeof SITE_URL !== 'undefined' ? SITE_URL : '/') + 'mod_user/panel';
            });
        }

        const reloadButton = document.querySelector('[data-action="faq-global-reload"]');
        if (reloadButton) {
            reloadButton.addEventListener('click', function () {
                window.location.reload();
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        shell = document.getElementById('faqGlobalShell');
        if (!shell) {
            return;
        }
        try {
            messages = JSON.parse(shell.dataset.messages || '{}');
        } catch (error) {
            messages = {};
        }
        messages.confirmDelete = messages.confirmDelete || messages.confirm_delete || '';
        messages.confirmYes = messages.confirmYes || messages.confirm_yes || '';
        messages.confirmCancel = messages.confirmCancel || messages.confirm_cancel || '';
        messages.successStatus = messages.successStatus || messages.success_status || '';
        messages.errorStatus = messages.errorStatus || messages.error_status || '';
        messages.successDelete = messages.successDelete || messages.success_delete || '';
        messages.errorDelete = messages.errorDelete || messages.error_delete || '';

        bindEvents();
        updateEmptyStates('', false);
    });
})();
