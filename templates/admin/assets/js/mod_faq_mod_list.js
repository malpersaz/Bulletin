/**
 * mod_faq_mod - admin list behavior (card list, selection, search, delete).
 * Configuration is read from data-* attributes on #faqModShell.
 */
(function () {
    'use strict';

    let faqModSelectedId = null;
    let faqModRecords = [];
    let cfg = null;
    let listEl = null;
    let emptyEl = null;

    function renderFaqModCard(row) {
        const id = row[1];
        const name = row[2];
        const modName = row[3];
        const modKey = row[4];
        const modValue = row[5];
        const searchText = [id, name, modName, modKey, modValue].join(' ').toLowerCase();
        const labels = cfg.labels;

        return `
            <article class="faq-mod-card" id="faq-mod-${id}" data-id="${id}" data-search="${searchText}">
                <div class="faq-mod-top">
                    <div class="faq-mod-icon"><i class="bi bi-patch-question"></i></div>
                    <div>
                        <h3 class="faq-mod-title">${name}</h3>
                        <div class="faq-mod-meta">
                            <span class="faq-mod-badge">#${id}</span>
                            <span>${modName}</span>
                        </div>
                    </div>
                </div>
                <div class="faq-mod-grid">
                    <div class="faq-mod-field">
                        <label>${labels.modName}</label>
                        <div>${modName}</div>
                    </div>
                    <div class="faq-mod-field">
                        <label>${labels.modKey}</label>
                        <div>${modKey}</div>
                    </div>
                    <div class="faq-mod-field">
                        <label>${labels.modValue}</label>
                        <div>${modValue}</div>
                    </div>
                    <div class="faq-mod-field">
                        <label>${labels.name}</label>
                        <div>${name}</div>
                    </div>
                </div>
            </article>
        `;
    }

    function updateFaqModToolbar(active) {
        ['toolbarEdit', 'toolbarDelete'].forEach((id) => {
            const button = document.getElementById(id);
            if (button) {
                button.disabled = !active;
            }
        });
    }

    function showEmpty() {
        listEl.innerHTML = '';
        emptyEl.classList.add('is-visible');
    }

    function hideEmpty() {
        emptyEl.classList.remove('is-visible');
    }

    async function loadFaqModRecords() {
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
            faqModRecords = payload.data || [];
            faqModSelectedId = null;
            updateFaqModToolbar(false);

            if (!faqModRecords.length) {
                showEmpty();
                return;
            }

            hideEmpty();
            listEl.innerHTML = faqModRecords.map(renderFaqModCard).join('');
            filterFaqModCards(document.getElementById('searchInput')?.value || '');
        } catch (error) {
            showSnackbar(String(error.message || error), 'error');
        }
    }

    function selectFaqMod(id) {
        document.querySelectorAll('.faq-mod-card.selected').forEach((element) => {
            element.classList.remove('selected');
        });

        if (faqModSelectedId === id) {
            faqModSelectedId = null;
            updateFaqModToolbar(false);
            return;
        }

        faqModSelectedId = id;
        document.getElementById('faq-mod-' + id)?.classList.add('selected');
        updateFaqModToolbar(true);
    }

    function editSelected() {
        if (faqModSelectedId) {
            location.href = cfg.editUrl + faqModSelectedId;
        }
    }

    function deleteSelected() {
        if (!faqModSelectedId) {
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
            body.append('delete[]', faqModSelectedId);

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

                    showSnackbar(cfg.messages.deleteSuccess, 'success');
                    loadFaqModRecords();
                })
                .catch((error) => showSnackbar(String(error.message || error), 'error'));
        });
    }

    function filterFaqModCards(value) {
        const term = String(value || '').toLowerCase().trim();
        const cards = document.querySelectorAll('.faq-mod-card');
        let visible = 0;

        cards.forEach((card) => {
            const matched = !term || (card.dataset.search || '').includes(term);
            card.style.display = matched ? 'grid' : 'none';
            if (matched) {
                visible += 1;
            }
        });

        if (emptyEl) {
            emptyEl.classList.toggle('is-visible', visible === 0);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const shell = document.getElementById('faqModShell');
        if (!shell) {
            return;
        }

        cfg = {
            addUrl: shell.dataset.addUrl,
            datatableUrl: shell.dataset.datatableUrl,
            editUrl: shell.dataset.editUrl,
            deleteUrl: shell.dataset.deleteUrl,
            labels: JSON.parse(shell.dataset.labels || '{}'),
            messages: JSON.parse(shell.dataset.messages || '{}')
        };
        listEl = document.getElementById('faqModList');
        emptyEl = document.getElementById('faqModEmpty');

        loadFaqModRecords();

        // Card selection via event delegation (no inline handlers)
        listEl.addEventListener('click', function (event) {
            const card = event.target.closest('.faq-mod-card');
            if (card) {
                selectFaqMod(card.dataset.id);
            }
        });

        document.getElementById('toolbarEdit')?.addEventListener('click', editSelected);
        document.getElementById('toolbarDelete')?.addEventListener('click', deleteSelected);
        document.getElementById('mainFab')?.addEventListener('click', function () {
            location.href = cfg.addUrl;
        });

        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            let timer;
            searchInput.addEventListener('input', function () {
                clearTimeout(timer);
                const value = this.value;
                timer = setTimeout(function () {
                    if (faqModRecords.length) {
                        filterFaqModCards(value);
                    } else {
                        loadFaqModRecords();
                    }
                }, 180);
            });
        }
    });
})();
