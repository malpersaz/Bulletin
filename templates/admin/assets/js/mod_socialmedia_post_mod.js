/**
 * mod_socialmedia_post_mod.js - Host Module Registry Admin Behavior
 * Strictly external JS, zero inline styles (.sp-hidden toggle only).
 */
(function () {
    'use strict';

    let selectedId = null;
    let records = [];
    let cfg = null;
    let listEl = null;
    let emptyEl = null;

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function showToast(message, type) {
        if (typeof showSnackbar === 'function') {
            showSnackbar(message, type || 'info');
        } else if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: type === 'error' ? 'error' : 'success',
                text: message,
                timer: 2000,
                showConfirmButton: false
            });
        }
    }

    function updateToolbar(active) {
        ['toolbarEdit', 'toolbarDelete'].forEach((id) => {
            const btn = document.getElementById(id);
            if (btn) {
                btn.disabled = !active;
            }
        });
    }

    function renderCard(row) {
        const id = row[1];
        const name = escapeHtml(row[2]);
        const modName = escapeHtml(row[3]);
        const modKey = escapeHtml(row[4]);
        const modValue = escapeHtml(row[5]);
        const searchText = [id, row[2], row[3], row[4], row[5]].join(' ').toLowerCase();
        const labels = cfg.labels || {};

        return `
            <article class="sp-mod-card" id="sp-mod-${escapeHtml(id)}" data-id="${escapeHtml(id)}" data-search="${escapeHtml(searchText)}">
                <div class="sp-mod-top">
                    <div class="sp-mod-icon"><i class="bi bi-share"></i></div>
                    <div>
                        <h3 class="sp-mod-title">${name}</h3>
                        <div class="sp-mod-meta">
                            <span>#${escapeHtml(id)}</span>
                            <span>${modName}</span>
                        </div>
                    </div>
                </div>
                <div class="sp-mod-grid">
                    <div class="sp-mod-field">
                        <label>${escapeHtml(labels.modName || '')}</label>
                        <div>${modName}</div>
                    </div>
                    <div class="sp-mod-field">
                        <label>${escapeHtml(labels.modKey || '')}</label>
                        <div>${modKey}</div>
                    </div>
                    <div class="sp-mod-field">
                        <label>${escapeHtml(labels.modValue || '')}</label>
                        <div>${modValue}</div>
                    </div>
                    <div class="sp-mod-field">
                        <label>${escapeHtml(labels.name || '')}</label>
                        <div>${name}</div>
                    </div>
                </div>
            </article>
        `;
    }

    function filterCards(termRaw) {
        const term = String(termRaw || '').toLowerCase().trim();
        const cards = document.querySelectorAll('.sp-mod-card');
        let visible = 0;

        cards.forEach((card) => {
            const matched = !term || (card.dataset.search || '').includes(term);
            card.classList.toggle('sp-hidden', !matched);
            if (matched) {
                visible += 1;
            }
        });

        if (emptyEl) {
            emptyEl.classList.toggle('sp-hidden', visible > 0);
        }
    }

    function toggleSelect(id) {
        document.querySelectorAll('.sp-mod-card.selected').forEach((el) => {
            el.classList.remove('selected');
        });

        if (selectedId === id) {
            selectedId = null;
            updateToolbar(false);
            return;
        }

        selectedId = id;
        document.getElementById('sp-mod-' + id)?.classList.add('selected');
        updateToolbar(true);
    }

    async function loadRecords() {
        const body = new URLSearchParams();
        body.set('start', '0');
        body.set('length', '100');
        body.set('draw', '1');

        try {
            const response = await fetch(cfg.datatableUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
            });
            const payload = await response.json();
            records = payload.data || [];
            selectedId = null;
            updateToolbar(false);

            if (!records.length) {
                listEl.innerHTML = '';
                emptyEl?.classList.remove('sp-hidden');
                return;
            }

            emptyEl?.classList.add('sp-hidden');
            listEl.innerHTML = records.map(renderCard).join('');
            filterCards(document.getElementById('searchInput')?.value || '');
        } catch (error) {
            showToast(String(error.message || error), 'error');
        }
    }

    function editSelected() {
        if (selectedId) {
            window.location.href = cfg.editUrl + selectedId;
        }
    }

    function deleteSelected() {
        if (!selectedId || typeof Swal === 'undefined') {
            return;
        }

        Swal.fire({
            icon: 'warning',
            text: cfg.messages.removeRecord,
            showCancelButton: true,
            confirmButtonText: cfg.messages.yes,
            cancelButtonText: cfg.messages.no
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            const body = new URLSearchParams();
            body.append('delete[]', selectedId);

            fetch(cfg.deleteUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
            })
                .then((response) => response.text())
                .then((text) => {
                    if (text.trim() !== 'OK') {
                        throw new Error(text);
                    }
                    showToast(cfg.messages.deleteSuccess, 'success');
                    loadRecords();
                })
                .catch((error) => showToast(String(error.message || error), 'error'));
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const shell = document.getElementById('socialPostModShell');
        if (!shell) {
            return;
        }

        cfg = {
            datatableUrl: shell.dataset.datatableUrl || '',
            editUrl: shell.dataset.editUrl || '',
            deleteUrl: shell.dataset.deleteUrl || '',
            labels: JSON.parse(shell.dataset.labels || '{}'),
            messages: JSON.parse(shell.dataset.messages || '{}')
        };

        listEl = document.getElementById('socialPostModList');
        emptyEl = document.getElementById('socialPostModEmpty');

        document.getElementById('searchInput')?.addEventListener('input', function (event) {
            filterCards(event.target.value);
        });

        listEl?.addEventListener('click', function (event) {
            const card = event.target.closest('.sp-mod-card');
            if (card) {
                toggleSelect(card.dataset.id);
            }
        });

        document.getElementById('toolbarEdit')?.addEventListener('click', editSelected);
        document.getElementById('toolbarDelete')?.addEventListener('click', deleteSelected);

        loadRecords();
    });
})();
