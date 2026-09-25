/**
 * Amed Framework
 * Cookie Policy & Inventory Admin JavaScript
 * templates/admin/assets/js/cookie_policy.js
 * Version: 2.0
 */

(function () {
    'use strict';

    function initCookiePolicyAdmin() {
        var page = document.querySelector('.cookie-page');
        if (!page) return;

        // Prevent duplicate listener bindings
        if (page.dataset.cookieInit === 'true') return;
        page.dataset.cookieInit = 'true';

        var textForm = document.getElementById('cookiePolicyTextForm');
        var itemModal = document.getElementById('cookieItemModal');
        var itemForm = document.getElementById('cookieItemForm');
        var addBtn = document.getElementById('addCookieBtn');
        var modalCloseBtns = document.querySelectorAll('[data-action="close-cookie-modal"]');
        var filterChips = document.querySelectorAll('.cookie-chip');
        var cookieList = document.getElementById('cookieList');
        var emptyState = document.getElementById('cookieEmptyState');

        var editTitle = page.dataset.editTitle || 'Edit Cookie';
        var deleteTitle = page.dataset.deleteTitle || 'Delete Cookie';
        var deleteConfirmText = page.dataset.deleteConfirm || 'Are you sure you want to delete this cookie?';
        var deleteBtnText = page.dataset.deleteBtn || 'Delete';
        var cancelBtnText = page.dataset.cancelBtn || 'Cancel';
        var loadingText = page.dataset.loadingText || 'Saving...';

        // 1. Initialize Category Counts Immediately
        updateCategoryCounts();

        // 2. General Policy Text Form AJAX Submission
        if (textForm) {
            textForm.addEventListener('submit', function (e) {
                e.preventDefault();

                var submitBtn = textForm.querySelector('button[type="submit"]');
                var originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';

                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="width: 14px; height: 14px; border-width: 2px; margin-right: 6px;"></span> ' + escapeHtml(loadingText);
                }

                var formData = new FormData(textForm);
                var siteUrl = (typeof SITE_URL !== 'undefined' && SITE_URL) ? SITE_URL : ((window.siteData && window.siteData.saddress) ? window.siteData.saddress : '');
                var actionUrl = siteUrl + 'mod_settings/ajax_save_cookie_policy_content';

                fetch(actionUrl, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnHtml;
                    }

                    if (data && data.status === 'success') {
                        if (navigator.vibrate) navigator.vibrate(12);
                        showNotification(data.message || 'Cookie policy text updated successfully.', 'success');
                    } else {
                        showNotification((data && data.message) ? data.message : 'Error updating cookie policy text.', 'error');
                    }
                })
                .catch(function (err) {
                    console.error('AJAX error saving cookie policy text:', err);
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnHtml;
                    }
                    showNotification('Network error saving cookie policy text.', 'error');
                });
            });
        }

        // 3. Category Filter Chips
        filterChips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                var category = chip.dataset.category || 'all';
                filterChips.forEach(function (c) { c.classList.remove('active'); });
                chip.classList.add('active');

                var cards = cookieList ? cookieList.querySelectorAll('.cookie-item-card') : [];
                var visibleCount = 0;

                cards.forEach(function (card) {
                    var cardCat = card.dataset.category || '';
                    if (category === 'all' || cardCat === category) {
                        card.style.display = 'block';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                if (emptyState) {
                    emptyState.style.display = (visibleCount === 0) ? 'block' : 'none';
                }
            });
        });

        // 4. Modal Handlers
        function openModal(itemData) {
            if (!itemModal || !itemForm) return;

            itemForm.reset();
            var modalTitle = document.getElementById('cookieModalTitle');

            if (itemData) {
                if (modalTitle) modalTitle.innerText = modalTitle.dataset.editTitle || 'Edit Cookie';
                itemForm.querySelector('#cookie_item_id').value = itemData.id || 0;
                itemForm.querySelector('#cookie_name').value = itemData.cookie_name || '';
                itemForm.querySelector('#cookie_category').value = itemData.category || 'necessary';
                itemForm.querySelector('#cookie_provider').value = itemData.provider || '';
                itemForm.querySelector('#cookie_duration').value = itemData.duration || '';
                itemForm.querySelector('#cookie_order').value = itemData.display_order || 0;
                itemForm.querySelector('#cookie_purpose').value = itemData.purpose || '';
                itemForm.querySelector('#cookie_is_active').checked = (itemData.is_active !== 'n');
            } else {
                if (modalTitle) modalTitle.innerText = modalTitle.dataset.addTitle || 'Add Cookie';
                itemForm.querySelector('#cookie_item_id').value = 0;
                itemForm.querySelector('#cookie_category').value = 'necessary';
                itemForm.querySelector('#cookie_order').value = 0;
                itemForm.querySelector('#cookie_is_active').checked = true;
            }

            itemModal.classList.add('open');
            document.body.style.overflow = 'hidden';

            var firstInput = itemForm.querySelector('#cookie_name');
            if (firstInput) {
                setTimeout(function () { firstInput.focus(); }, 100);
            }
        }

        function closeModal() {
            if (!itemModal) return;
            itemModal.classList.remove('open');
            document.body.style.overflow = '';
        }

        if (addBtn) {
            addBtn.addEventListener('click', function () {
                openModal(null);
            });
        }

        modalCloseBtns.forEach(function (btn) {
            btn.addEventListener('click', closeModal);
        });

        if (itemModal) {
            itemModal.addEventListener('click', function (e) {
                if (e.target === itemModal) {
                    closeModal();
                }
            });
        }

        // Global ESC key to close modal sheet
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && itemModal && itemModal.classList.contains('open')) {
                closeModal();
            }
        });

        // 5. Delegated Actions on Cookie List (Edit, Delete, Toggle)
        if (cookieList) {
            cookieList.addEventListener('click', function (e) {
                var editBtn = e.target.closest('[data-action="edit-cookie"]');
                var deleteBtn = e.target.closest('[data-action="delete-cookie"]');

                if (editBtn) {
                    var card = editBtn.closest('.cookie-item-card');
                    if (card && card.dataset.json) {
                        try {
                            var data = JSON.parse(card.dataset.json);
                            openModal(data);
                        } catch (ex) {
                            console.error('Error parsing cookie JSON:', ex);
                        }
                    }
                    return;
                }

                if (deleteBtn) {
                    var delCard = deleteBtn.closest('.cookie-item-card');
                    var cookieId = deleteBtn.dataset.id;
                    if (!cookieId) return;

                    var confirmTitle = deleteBtn.dataset.confirm || deleteConfirmText;

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: confirmTitle,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#ef4444',
                            cancelButtonColor: '#64748b',
                            confirmButtonText: deleteBtnText,
                            cancelButtonText: cancelBtnText
                        }).then(function (result) {
                            if (result.isConfirmed) {
                                executeDeleteCookie(cookieId, delCard);
                            }
                        });
                    } else if (confirm(confirmTitle)) {
                        executeDeleteCookie(cookieId, delCard);
                    }
                }
            });

            // Toggle switch change
            cookieList.addEventListener('change', function (e) {
                var toggle = e.target.closest('.cookie-toggle-input');
                if (toggle) {
                    var cookieId = toggle.dataset.id;
                    var card = toggle.closest('.cookie-item-card');
                    executeToggleCookie(cookieId, toggle, card);
                }
            });
        }

        // 6. AJAX Delete
        function executeDeleteCookie(cookieId, cardElement) {
            var siteUrl = (typeof SITE_URL !== 'undefined' && SITE_URL) ? SITE_URL : ((window.siteData && window.siteData.saddress) ? window.siteData.saddress : '');
            var actionUrl = siteUrl + 'mod_settings/ajax_delete_cookie_item';

            var formData = new FormData();
            formData.append('id', cookieId);

            fetch(actionUrl, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data && data.status === 'success') {
                    if (navigator.vibrate) navigator.vibrate(12);
                    showNotification(data.message || 'Cookie deleted successfully.', 'success');
                    if (cardElement) {
                        cardElement.style.transition = 'all 0.25s ease';
                        cardElement.style.opacity = '0';
                        cardElement.style.transform = 'scale(0.95)';
                        setTimeout(function () {
                            cardElement.remove();
                            updateCategoryCounts();
                        }, 250);
                    } else {
                        updateCategoryCounts();
                    }
                } else {
                    showNotification((data && data.message) ? data.message : 'Error deleting cookie.', 'error');
                }
            })
            .catch(function (err) {
                console.error('AJAX delete error:', err);
                showNotification('Network error deleting cookie.', 'error');
            });
        }

        // 7. AJAX Toggle Active
        function executeToggleCookie(cookieId, toggleInput, cardElement) {
            var siteUrl = (typeof SITE_URL !== 'undefined' && SITE_URL) ? SITE_URL : ((window.siteData && window.siteData.saddress) ? window.siteData.saddress : '');
            var actionUrl = siteUrl + 'mod_settings/ajax_toggle_cookie_item';

            var formData = new FormData();
            formData.append('id', cookieId);

            fetch(actionUrl, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data && data.status === 'success') {
                    if (cardElement) {
                        if (data.is_active === 'y') {
                            cardElement.classList.remove('inactive');
                        } else {
                            cardElement.classList.add('inactive');
                        }
                    }
                    if (navigator.vibrate) navigator.vibrate(8);
                    showNotification(data.message || 'Status updated.', 'success');
                } else {
                    toggleInput.checked = !toggleInput.checked;
                    showNotification((data && data.message) ? data.message : 'Error updating status.', 'error');
                }
            })
            .catch(function (err) {
                console.error('AJAX toggle error:', err);
                toggleInput.checked = !toggleInput.checked;
                showNotification('Network error updating status.', 'error');
            });
        }

        // 8. Save Cookie Item (Add / Edit) Form Submission
        if (itemForm) {
            itemForm.addEventListener('submit', function (e) {
                e.preventDefault();

                var saveBtn = itemForm.querySelector('button[type="submit"]');
                var originalBtnHtml = saveBtn ? saveBtn.innerHTML : '';

                if (saveBtn) {
                    saveBtn.disabled = true;
                    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="width: 14px; height: 14px; border-width: 2px; margin-right: 6px;"></span> ' + escapeHtml(loadingText);
                }

                var formData = new FormData(itemForm);
                if (!itemForm.querySelector('#cookie_is_active').checked) {
                    formData.set('is_active', 'n');
                } else {
                    formData.set('is_active', 'y');
                }

                var siteUrl = (typeof SITE_URL !== 'undefined' && SITE_URL) ? SITE_URL : ((window.siteData && window.siteData.saddress) ? window.siteData.saddress : '');
                var actionUrl = siteUrl + 'mod_settings/ajax_save_cookie_item';

                fetch(actionUrl, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (saveBtn) {
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = originalBtnHtml;
                    }

                    if (data && data.status === 'success' && data.item) {
                        if (navigator.vibrate) navigator.vibrate(12);
                        showNotification(data.message || 'Cookie saved successfully.', 'success');
                        closeModal();
                        renderOrUpdateCookieCard(data.item);
                        updateCategoryCounts();
                    } else {
                        showNotification((data && data.message) ? data.message : 'Error saving cookie.', 'error');
                    }
                })
                .catch(function (err) {
                    console.error('AJAX save error:', err);
                    if (saveBtn) {
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = originalBtnHtml;
                    }
                    showNotification('Network error saving cookie.', 'error');
                });
            });
        }

        // 9. Helper to render or update card in DOM
        function renderOrUpdateCookieCard(item) {
            if (!cookieList) return;
            var existingCard = cookieList.querySelector('.cookie-item-card[data-id="' + item.id + '"]');

            var cardHtml = `
                <div class="cookie-item-top">
                    <div class="cookie-item-name-wrap">
                        <span class="cookie-item-name">${escapeHtml(item.cookie_name)}</span>
                        <span class="cookie-item-provider">${escapeHtml(item.provider || 'Self-Hosted')}</span>
                    </div>
                    <span class="cookie-item-badge ${escapeHtml(item.category)}">${escapeHtml(item.category)}</span>
                </div>
                <div class="cookie-item-purpose">${escapeHtml(item.purpose || '')}</div>
                <div class="cookie-item-meta">
                    <div class="cookie-item-duration">
                        <i class="bi bi-clock-history"></i>
                        <span>${escapeHtml(item.duration || 'Session')}</span>
                    </div>
                    <div class="cookie-item-actions">
                        <label class="cookie-switch">
                            <input type="checkbox" class="cookie-toggle-input" data-id="${item.id}" ${item.is_active === 'y' ? 'checked' : ''}>
                            <span class="cookie-slider"></span>
                        </label>
                        <button type="button" class="cookie-action-btn" data-action="edit-cookie" title="${escapeHtml(editTitle)}">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <button type="button" class="cookie-action-btn delete" data-action="delete-cookie" data-id="${item.id}" data-confirm="${escapeHtml(deleteConfirmText)}" title="${escapeHtml(deleteTitle)}">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                </div>
            `;

            if (existingCard) {
                existingCard.dataset.category = item.category;
                existingCard.dataset.json = JSON.stringify(item);
                if (item.is_active === 'n') {
                    existingCard.classList.add('inactive');
                } else {
                    existingCard.classList.remove('inactive');
                }
                existingCard.innerHTML = cardHtml;
            } else {
                var newCard = document.createElement('div');
                newCard.className = 'cookie-item-card' + (item.is_active === 'n' ? ' inactive' : '');
                newCard.dataset.id = item.id;
                newCard.dataset.category = item.category;
                newCard.dataset.json = JSON.stringify(item);
                newCard.innerHTML = cardHtml;
                cookieList.prepend(newCard);
            }

            if (emptyState) emptyState.style.display = 'none';
        }

        // 10. Update Category Badges & Counts
        function updateCategoryCounts() {
            var cards = cookieList ? cookieList.querySelectorAll('.cookie-item-card') : [];
            var counts = { all: cards.length, necessary: 0, functional: 0, analytics: 0, marketing: 0 };

            cards.forEach(function (c) {
                var cat = c.dataset.category;
                if (counts[cat] !== undefined) counts[cat]++;
            });

            filterChips.forEach(function (chip) {
                var cat = chip.dataset.category || 'all';
                var countSpan = chip.querySelector('.chip-count');
                if (countSpan && counts[cat] !== undefined) {
                    countSpan.innerText = '(' + counts[cat] + ')';
                }
            });

            if (emptyState) {
                var activeChip = document.querySelector('.cookie-chip.active');
                var activeCat = activeChip ? (activeChip.dataset.category || 'all') : 'all';
                var visibleCount = 0;
                cards.forEach(function (card) {
                    var cardCat = card.dataset.category || '';
                    if (activeCat === 'all' || cardCat === activeCat) {
                        visibleCount++;
                    }
                });
                emptyState.style.display = (visibleCount === 0) ? 'block' : 'none';
            }
        }

        function escapeHtml(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.innerText = text;
            return div.innerHTML;
        }

        function showNotification(msg, type) {
            if (window.AFModalEngine && typeof window.AFModalEngine.showToast === 'function') {
                window.AFModalEngine.showToast(msg, type);
            } else if (typeof showAFToast === 'function') {
                showAFToast(msg, type);
            } else if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: (type === 'success') ? 'success' : 'error',
                    title: msg,
                    timer: 2500,
                    showConfirmButton: false
                });
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCookiePolicyAdmin);
    } else {
        initCookiePolicyAdmin();
    }
})();

