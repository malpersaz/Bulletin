/**
 * Page Management Module - List & Tree Hierarchy Controller
 * Modern Material Design 3 Phone-First Controller (Super Polished)
 */
(function () {
    'use strict';

    var selectedPageId = null;
    var sortableInstances = [];
    var isReorderActive = false;
    var areAllExpanded = false;
    var currentStatusFilter = 'all';
    var currentSearchTerm = '';
    var lastTapTime = 0;
    var lastTapId = null;

    // Cache original titles for search highlighting restore
    var originalTitles = {};

    function getLangString(keyPath, fallback) {
        var al = (typeof window.adminLang !== 'undefined' && window.adminLang)
            ? window.adminLang
            : (typeof adminLang !== 'undefined' ? adminLang : {});
        var parts = keyPath.split('.');

        // Helper to traverse an object with array of parts
        function lookup(obj, pathArr) {
            var curr = obj;
            for (var i = 0; i < pathArr.length; i++) {
                if (curr && typeof curr === 'object' && pathArr[i] in curr) {
                    curr = curr[pathArr[i]];
                } else {
                    return null;
                }
            }
            return (typeof curr === 'string' && curr.length > 0) ? curr : null;
        }

        // 1. Direct path in window.adminLang
        var res = lookup(al, parts);
        if (res !== null) return res;

        // 2. If keyPath starts with 'mod_page.', search inside al.mod_page
        if (parts[0] === 'mod_page') {
            var subParts = parts.slice(1);
            if (al.mod_page) {
                res = lookup(al.mod_page, subParts);
                if (res !== null) return res;
            }
            if (al.active_module) {
                res = lookup(al.active_module, subParts);
                if (res !== null) return res;
            }
        } else {
            // Check in al.mod_page or al.active_module with full parts
            if (al.mod_page) {
                res = lookup(al.mod_page, parts);
                if (res !== null) return res;
            }
            if (al.active_module) {
                res = lookup(al.active_module, parts);
                if (res !== null) return res;
            }
        }

        // 3. Check in al.context_menu or al.general for standard action keys
        if (al.context_menu) {
            var lastPart = parts[parts.length - 1];
            if (typeof al.context_menu[lastPart] === 'string' && al.context_menu[lastPart].length > 0) {
                return al.context_menu[lastPart];
            }
        }

        return fallback || '';
    }

    function initOriginalTitles() {
        document.querySelectorAll('.page-tree-node').forEach(function (node) {
            var id = node.getAttribute('data-id');
            var titleEl = node.querySelector('.card-title');
            if (id && titleEl && !(id in originalTitles)) {
                originalTitles[id] = titleEl.textContent.trim();
            }
        });
    }

    function initSortableTree() {
        if (typeof Sortable === 'undefined') {
            return;
        }

        var root = document.getElementById('pageRootList');
        if (root) {
            registerSortable(root);
        }

        document.querySelectorAll('.list-children').forEach(function (el) {
            registerSortable(el);
        });
    }

    function registerSortable(el) {
        var s = new Sortable(el, {
            group: 'page_nested',
            animation: 250,
            handle: '.drag-handle',
            disabled: true,
            fallbackOnBody: true,
            swapThreshold: 0.65,
            ghostClass: 'sortable-ghost',
            onEnd: function (evt) {
                var reorderConfirmTitle = getLangString('mod_page.messages.warning.reorder', 'Change page order?');
                var reorderConfirmText = getLangString('mod_page.confirmations.reorder_single', 'Do you want to change the order of this page?');
                var yesText = getLangString('mod_page.options.yes', 'Yes');
                var noText = getLangString('mod_page.options.no', 'No');

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: reorderConfirmTitle,
                        text: reorderConfirmText,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: yesText,
                        cancelButtonText: noText,
                        confirmButtonColor: '#6750a4',
                        customClass: { popup: 'm3-swal-popup' }
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            applySortOrder(evt);
                        } else {
                            window.location.reload();
                        }
                    });
                } else {
                    applySortOrder(evt);
                }
            }
        });
        sortableInstances.push(s);
    }

    function applySortOrder(evt) {
        var itemEl = evt.item;
        var newParentId = 0;
        var container = evt.to;

        if (container.classList.contains('list-children')) {
            var parentNode = container.closest('.page-tree-node');
            if (parentNode) {
                newParentId = parentNode.getAttribute('data-id');
            }
        }

        var activeIds = [];
        var siblings = container.querySelectorAll(':scope > .page-tree-node');
        siblings.forEach(function (node) {
            activeIds.push(node.getAttribute('data-id'));
        });

        updatePageOrder(itemEl.getAttribute('data-id'), newParentId, activeIds);
    }

    function updatePageOrder(id, parentId, orderIds) {
        var siteAddress = typeof SITE_URL !== 'undefined' ? SITE_URL : (window.location.origin + '/');
        var formData = new URLSearchParams();
        formData.append('id', id);
        formData.append('parent_id', parentId);
        orderIds.forEach(function (sid) {
            formData.append('order[]', sid);
        });

        fetch(siteAddress + 'mod_page/reorder', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData
        })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (data.trim() === 'Success') {
                    if (typeof showSnackbar === 'function') {
                        showSnackbar(getLangString('mod_page.success', 'Operation Successful'), 'success');
                    } else if (typeof Swal !== 'undefined') {
                        var Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000
                        });
                        Toast.fire({
                            icon: 'success',
                            title: getLangString('mod_page.success', 'Operation Successful')
                        });
                    }
                } else {
                    if (typeof showSnackbar === 'function') {
                        showSnackbar(data || getLangString('mod_page.error', 'Error Occurred'), 'error');
                    } else if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: getLangString('mod_page.error', 'Error Occurred'),
                            text: data
                        });
                    }
                }
            })
            .catch(function (err) {
                console.error('Reorder network error:', err);
            });
    }

    function toggleReorderMode() {
        isReorderActive = !isReorderActive;
        var btn = document.getElementById('reorderToggleBtn');
        var icon = document.getElementById('reorderIcon');
        var text = document.getElementById('reorderText');
        var banner = document.getElementById('reorderBanner');

        var lockText = getLangString('mod_page.actions.lock_reorder', 'Lock');
        var unlockText = getLangString('mod_page.actions.unlock_reorder', 'Reorder');

        if (isReorderActive) {
            document.body.classList.add('reorder-active');
            if (btn) btn.classList.add('active');
            if (icon) icon.className = 'bi bi-unlock-fill';
            if (text) text.innerText = lockText;
            if (banner) banner.classList.remove('is-hidden');

            sortableInstances.forEach(function (s) { s.option('disabled', false); });

            if (typeof showSnackbar === 'function') {
                showSnackbar(unlockText, 'info');
            }
        } else {
            document.body.classList.remove('reorder-active');
            if (btn) btn.classList.remove('active');
            if (icon) icon.className = 'bi bi-lock-fill';
            if (text) text.innerText = unlockText;
            if (banner) banner.classList.add('is-hidden');

            sortableInstances.forEach(function (s) { s.option('disabled', true); });

            if (typeof showSnackbar === 'function') {
                showSnackbar(lockText, 'info');
            }
        }
    }

    function toggleExpandCollapseAll() {
        areAllExpanded = !areAllExpanded;
        var btn = document.getElementById('expandCollapseAllBtn');
        var icon = document.getElementById('expandCollapseIcon');
        var text = document.getElementById('expandCollapseText');

        var expandText = getLangString('mod_page.expand_all', 'Expand All');
        var collapseText = getLangString('mod_page.collapse_all', 'Collapse All');

        var childrenLists = document.querySelectorAll('.list-children');
        var toggleBtns = document.querySelectorAll('.page-tree-node .toggle-btn');

        if (areAllExpanded) {
            childrenLists.forEach(function (list) { list.classList.remove('collapsed'); });
            toggleBtns.forEach(function (b) { b.classList.remove('collapsed'); });
            if (btn) btn.classList.add('active');
            if (icon) icon.className = 'bi bi-arrows-collapse';
            if (text) text.innerText = collapseText;
        } else {
            childrenLists.forEach(function (list) { list.classList.add('collapsed'); });
            toggleBtns.forEach(function (b) { b.classList.add('collapsed'); });
            if (btn) btn.classList.remove('active');
            if (icon) icon.className = 'bi bi-arrows-expand';
            if (text) text.innerText = expandText;
        }
    }

    function toggleTreeNode(btn, e) {
        if (e) {
            e.stopPropagation();
        }
        var node = btn.closest('.page-tree-node');
        var children = node ? node.querySelector('.list-children') : null;
        if (children) {
            children.classList.toggle('collapsed');
            btn.classList.toggle('collapsed');
        }
    }

    function selectPageNode(id, card) {
        var isAlreadySelected = (selectedPageId === id);

        document.querySelectorAll('.page-tree-node .m3-list-card.selected').forEach(function (el) {
            el.classList.remove('selected');
        });

        if (isAlreadySelected) {
            selectedPageId = null;
            updateToolbar(false);
        } else {
            card.classList.add('selected');
            selectedPageId = id;
            var status = card.getAttribute('data-status');
            updateToolbar(true, status);
            if (window.navigator && window.navigator.vibrate) {
                window.navigator.vibrate(12);
            }
        }
    }

    function updateToolbar(active, status) {
        var buttons = [
            document.getElementById('toolbarGallery'),
            document.getElementById('toolbarFaq'),
            document.getElementById('toolbarFiles'),
            document.getElementById('toolbarApprove'),
            document.getElementById('toolbarEdit'),
            document.getElementById('toolbarDelete')
        ];

        var approveIcon = document.getElementById('approveIcon');
        var approveText = document.getElementById('approveText');
        var approveBtn = document.getElementById('toolbarApprove');

        var publishYesText = getLangString('mod_page.admin.radio.publish_yes', 'Publish');
        var publishNoText = getLangString('mod_page.admin.radio.publish_no', 'Save as Draft');

        buttons.forEach(function (btn) {
            if (!btn) return;
            if (active) {
                btn.classList.remove('is-disabled');
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
                btn.classList.add('is-disabled');
            }
        });

        if (approveBtn) {
            if (active) {
                if (status === 'y') {
                    if (approveIcon) approveIcon.className = 'bi bi-eye-slash-fill';
                    approveBtn.setAttribute('title', publishNoText);
                    approveBtn.setAttribute('aria-label', publishNoText);
                    if (approveText) approveText.innerText = publishNoText;
                    approveBtn.classList.add('is-unpublished');
                } else {
                    if (approveIcon) approveIcon.className = 'bi bi-eye-fill';
                    approveBtn.setAttribute('title', publishYesText);
                    approveBtn.setAttribute('aria-label', publishYesText);
                    if (approveText) approveText.innerText = publishYesText;
                    approveBtn.classList.remove('is-unpublished');
                }
            } else {
                approveBtn.classList.remove('is-unpublished');
                if (approveIcon) approveIcon.className = 'bi bi-eye-fill';
                approveBtn.setAttribute('title', publishYesText);
                approveBtn.setAttribute('aria-label', publishYesText);
                if (approveText) approveText.innerText = publishYesText;
            }
        }
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function applyFilters() {
        var term = currentSearchTerm.toLowerCase().trim();
        var statusFilter = currentStatusFilter;
        var allNodes = document.querySelectorAll('.page-tree-node');
        var rootList = document.getElementById('pageRootList');
        var searchEmptyState = document.getElementById('searchEmptyState');
        var searchEmptyDesc = document.getElementById('searchEmptyDesc');

        var visibleCount = 0;

        // Reset all displays and titles first
        allNodes.forEach(function (n) {
            n.style.display = 'none';
            var id = n.getAttribute('data-id');
            var titleEl = n.querySelector('.card-title');
            if (titleEl && id in originalTitles) {
                titleEl.textContent = originalTitles[id];
            }
        });

        // Find matches
        allNodes.forEach(function (n) {
            var name = (n.getAttribute('data-name') || '').toLowerCase();
            var id = n.getAttribute('data-id') || '';
            var status = n.getAttribute('data-status') || '';

            // Status filter check
            var passesStatus = true;
            if (statusFilter === 'published') {
                passesStatus = (status === 'y');
            } else if (statusFilter === 'draft') {
                passesStatus = (status !== 'y');
            }

            // Search query check
            var passesSearch = true;
            if (term.length > 0) {
                passesSearch = (name.indexOf(term) !== -1) || (id === term);
            }

            if (passesStatus && passesSearch) {
                visibleCount++;
                showNodeBranch(n);

                // Highlight term in title if search is active
                if (term.length > 0 && name.indexOf(term) !== -1) {
                    var titleEl = n.querySelector('.card-title');
                    var orig = originalTitles[id] || '';
                    if (titleEl && orig) {
                        var regex = new RegExp('(' + term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
                        titleEl.innerHTML = escapeHtml(orig).replace(regex, '<mark class="search-matched-text">$1</mark>');
                    }
                }
            }
        });

        // Show/hide empty state
        if (visibleCount === 0 && (term.length > 0 || statusFilter !== 'all')) {
            if (rootList) rootList.style.display = 'none';
            if (searchEmptyState) {
                searchEmptyState.classList.remove('is-hidden');
                if (searchEmptyDesc) {
                    searchEmptyDesc.textContent = term.length > 0
                        ? '"' + term + '"'
                        : getLangString('mod_page.empty.no_pages', 'No pages found');
                }
            }
        } else {
            if (rootList) rootList.style.display = '';
            if (searchEmptyState) searchEmptyState.classList.add('is-hidden');
        }
    }

    function showNodeBranch(node) {
        node.style.display = '';
        var parent = node.parentElement ? node.parentElement.closest('.page-tree-node') : null;
        if (parent) {
            showNodeBranch(parent);
            var children = parent.querySelector('.list-children');
            if (children) {
                children.classList.remove('collapsed');
            }
            var btn = parent.querySelector('.toggle-btn');
            if (btn) {
                btn.classList.remove('collapsed');
            }
        }
    }

    function editSelectedPage() {
        if (selectedPageId) {
            var siteAddress = typeof SITE_URL !== 'undefined' ? SITE_URL : (window.location.origin + '/');
            window.location.href = siteAddress + 'mod_page/edit/' + selectedPageId;
        }
    }

    function approveSelectedPage() {
        if (!selectedPageId) return;

        var selectedCard = document.querySelector('.page-tree-node .m3-list-card.selected');
        if (!selectedCard) return;

        var currentStatus = selectedCard.getAttribute('data-status');
        var isPublishing = currentStatus !== 'y';

        var titleMsg = isPublishing
            ? getLangString('mod_page.quick_publish_confirm', 'Are you sure you want to publish this page?')
            : getLangString('mod_page.quick_unpublish_confirm', 'Are you sure you want to unpublish this page?');

        var successMsg = isPublishing
            ? getLangString('mod_page.published_msg', 'Page Published Successfully')
            : getLangString('mod_page.unpublished_msg', 'Page Unpublished Successfully');

        var yesText = getLangString('mod_page.options.yes', 'Yes');
        var noText = getLangString('mod_page.options.no', 'No');

        if (typeof Swal === 'undefined') {
            return;
        }

        Swal.fire({
            title: titleMsg,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: yesText,
            cancelButtonText: noText,
            confirmButtonColor: isPublishing ? '#2e7d32' : '#d97706',
            cancelButtonColor: '#6750a4',
            customClass: { popup: 'm3-swal-popup' }
        }).then(function (result) {
            if (result.isConfirmed) {
                var siteAddress = typeof SITE_URL !== 'undefined' ? SITE_URL : (window.location.origin + '/');
                var formData = new URLSearchParams();
                formData.append('query[]', selectedPageId);

                var endpoint = isPublishing ? 'approve' : 'unapprove';

                fetch(siteAddress + 'mod_page/' + endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData
                })
                    .then(function (r) { return r.text(); })
                    .then(function (data) {
                        if (data.trim() === 'Success') {
                            Swal.fire({
                                title: getLangString('mod_page.success', 'Operation Successful'),
                                text: successMsg,
                                icon: 'success',
                                timer: 1800,
                                showConfirmButton: false
                            }).then(function () {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire(getLangString('mod_page.error', 'Error Occurred'), data, 'error');
                        }
                    });
            }
        });
    }

    function gallerySelectedPage() {
        if (selectedPageId) {
            var siteAddress = typeof SITE_URL !== 'undefined' ? SITE_URL : (window.location.origin + '/');
            var url = siteAddress + 'mod_gallery/list/mod_page/' + selectedPageId;
            if (window.AFModalEngine) {
                AFModalEngine.push(url, { skipHistory: true });
            } else {
                window.location.href = url;
            }
        }
    }

    function faqSelectedPage() {
        if (selectedPageId) {
            var siteAddress = typeof SITE_URL !== 'undefined' ? SITE_URL : (window.location.origin + '/');
            var url = siteAddress + 'mod_faq/list/mod_page/' + selectedPageId;
            if (window.AFModalEngine) {
                AFModalEngine.push(url, { skipHistory: true });
            } else {
                window.location.href = url;
            }
        }
    }

    function filesSelectedPage() {
        if (!selectedPageId) return;

        var siteAddress = typeof SITE_URL !== 'undefined' ? SITE_URL : (window.location.origin + '/');
        window.currentMediaPickerAddUrl = siteAddress + 'mod_attachment/list/mod_page/' + selectedPageId;

        var addBtn = document.getElementById('selectPickerAddBtn');
        if (addBtn) addBtn.style.display = 'block';

        var pickerData = new FormData();
        pickerData.append('q', '');

        var currentData = new FormData();
        currentData.append('id', selectedPageId);

        Promise.all([
            fetch(siteAddress + 'mod_page/ajax_get_files', { method: 'POST', body: pickerData }).then(function (r) { return r.json(); }),
            fetch(siteAddress + 'mod_page/ajax_get_current_media', { method: 'POST', body: currentData }).then(function (r) { return r.json(); })
        ]).then(function (res) {
            var files = res[0] || [];
            var currentMedia = res[1] || {};

            var select = document.getElementById('dynamicPageFileSelect');
            if (!select) {
                select = document.createElement('select');
                select.id = 'dynamicPageFileSelect';
                select.style.display = 'none';
                document.body.appendChild(select);

                select.addEventListener('change', function () {
                    var formData = new FormData();
                    formData.append('id', window.currentMediaPickerContentId);
                    formData.append('type', 'files');
                    formData.append('value', select.value || '0');

                    fetch(siteAddress + 'mod_page/ajax_update_relation', {
                        method: 'POST',
                        body: formData
                    }).then(function (r) { return r.json(); }).then(function (result) {
                        if (result.status !== 'success') {
                            if (typeof showSnackbar === 'function') {
                                showSnackbar(result.message || getLangString('mod_page.error', 'Error'), 'error');
                            } else if (typeof Swal !== 'undefined') {
                                Swal.fire(getLangString('mod_page.error', 'Error'), result.message, 'error');
                            }
                            return;
                        }
                        if (typeof showSnackbar === 'function') {
                            showSnackbar(result.message || getLangString('mod_page.success', 'Success'), 'success');
                        } else if (typeof Swal !== 'undefined') {
                            var Toast = Swal.mixin({
                                toast: true, position: 'top-end', showConfirmButton: false, timer: 2000
                            });
                            Toast.fire({ icon: 'success', title: result.message || getLangString('mod_page.success', 'Success') });
                        }
                        if (typeof closeSelectPickerSheet === 'function') {
                            closeSelectPickerSheet();
                        }
                    }).catch(function (err) {
                        console.error('Relation error:', err);
                    });
                });
            }

            var noneOptionText = getLangString('mod_page.wizard.summary.selected_none', getLangString('mod_page.menu_options.none', 'None'));
            select.innerHTML = '<option value="0">' + escapeHtml(noneOptionText) + '</option>';
            files.forEach(function (item) {
                var option = document.createElement('option');
                option.value = String(item.id || item.value || 0);
                option.textContent = item.text || item.name || String(item.id);
                select.appendChild(option);
            });

            select.value = String(currentMedia.file_id || 0);
            select.dataset.pickerTitle = getLangString('mod_page.button.file', 'File Manager');
            window.currentMediaPickerContentId = selectedPageId;
            window.currentMediaPickerType = 'files';

            var card = document.querySelector('.page-tree-node[data-id="' + selectedPageId + '"]');
            var itemTitle = card ? (card.querySelector('.card-title') ? card.querySelector('.card-title').textContent.trim() : '') : '';

            var pickerTitle = document.getElementById('selectPickerTitle');
            var pickerSubtitle = document.getElementById('selectPickerSubtitle');

            if (pickerTitle) {
                pickerTitle.textContent = getLangString('mod_page.button.file', 'File Manager');
            }
            if (pickerSubtitle) {
                pickerSubtitle.textContent = itemTitle;
                pickerSubtitle.style.display = itemTitle ? 'block' : 'none';
            }

            if (typeof openSelectPickerSheet === 'function') {
                openSelectPickerSheet(select.id);
            }
        }).catch(function (err) {
            console.error('File picker error:', err);
        });
    }

    function deleteSelectedPage() {
        if (!selectedPageId) return;

        var warningTitle = getLangString('mod_page.messages.warning.delete', 'Are you sure you want to delete this page?');
        var warningText = getLangString('mod_page.confirmations.delete_single', 'Are you sure you want to delete this page?');
        var yesText = getLangString('mod_page.options.yes', 'Yes');
        var noText = getLangString('mod_page.options.no', 'No');

        if (typeof Swal === 'undefined') {
            return;
        }

        Swal.fire({
            title: warningTitle,
            text: warningText,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ba1a1a',
            cancelButtonColor: '#6750a4',
            confirmButtonText: yesText,
            cancelButtonText: noText,
            customClass: { popup: 'm3-swal-popup' }
        }).then(function (result) {
            if (result.isConfirmed) {
                var siteAddress = typeof SITE_URL !== 'undefined' ? SITE_URL : (window.location.origin + '/');
                var formData = new URLSearchParams();
                formData.append('delete', selectedPageId);

                fetch(siteAddress + 'mod_page/dragdrop_delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData
                })
                    .then(function (r) { return r.text(); })
                    .then(function (data) {
                        if (data.trim() === 'Success') {
                            Swal.fire({
                                icon: 'success',
                                title: getLangString('mod_page.success', 'Operation Successful'),
                                text: getLangString('mod_page.messages.success.delete', 'Page deleted successfully'),
                                timer: 1800,
                                showConfirmButton: false
                            }).then(function () {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire(getLangString('mod_page.error', 'Error Occurred'), data, 'error');
                        }
                    });
            }
        });
    }

    // Initialize on DOM Ready
    document.addEventListener('DOMContentLoaded', function () {
        initOriginalTitles();
        initSortableTree();

        // Bind Page Thumb Image Fallback Listeners
        document.querySelectorAll('.page-thumb-img').forEach(function (img) {
            img.addEventListener('error', function () {
                this.style.display = 'none';
                if (this.nextElementSibling) {
                    this.nextElementSibling.classList.remove('is-hidden');
                }
            });
        });

        // Bind Search Input
        var searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function (e) {
                currentSearchTerm = e.target.value;
                applyFilters();
            });
        }

        var clearSearchBtn = document.getElementById('clearSearchBtn');
        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', function () {
                if (searchInput) {
                    searchInput.value = '';
                }
                currentSearchTerm = '';
                applyFilters();
            });
        }

        var resetSearchBtn = document.getElementById('resetSearchBtn');
        if (resetSearchBtn) {
            resetSearchBtn.addEventListener('click', function () {
                if (searchInput) {
                    searchInput.value = '';
                }
                currentSearchTerm = '';
                currentStatusFilter = 'all';
                document.querySelectorAll('.page-stat-card').forEach(function (card) {
                    if (card.getAttribute('data-status-filter') === 'all') {
                        card.classList.add('active');
                    } else {
                        card.classList.remove('active');
                    }
                });
                applyFilters();
            });
        }

        // Stat Card Filters
        document.querySelectorAll('.page-stat-card').forEach(function (card) {
            card.addEventListener('click', function () {
                var filter = card.getAttribute('data-status-filter');
                if (!filter) return;

                document.querySelectorAll('.page-stat-card').forEach(function (c) {
                    c.classList.remove('active');
                });
                card.classList.add('active');

                currentStatusFilter = filter;
                applyFilters();
            });
        });

        // Global Event Delegation
        document.addEventListener('click', function (e) {
            // A. Tree Node Toggle Button
            var toggleBtn = e.target.closest('.toggle-btn');
            if (toggleBtn) {
                e.preventDefault();
                e.stopPropagation();
                toggleTreeNode(toggleBtn, e);
                return;
            }

            // B. Tree Card Selection & Double Tap Handling
            var card = e.target.closest('.page-tree-node .m3-list-card');
            if (card && !e.target.closest('.drag-handle') && !e.target.closest('.toggle-btn')) {
                var node = card.closest('.page-tree-node');
                var id = node ? node.getAttribute('data-id') : card.getAttribute('data-id');
                if (id) {
                    var now = Date.now();
                    // Mobile Double-Tap check (within 320ms on same card)
                    if (lastTapId === id && (now - lastTapTime < 320)) {
                        lastTapTime = 0;
                        lastTapId = null;
                        var siteAddress = typeof SITE_URL !== 'undefined' ? SITE_URL : (window.location.origin + '/');
                        window.location.href = siteAddress + 'mod_page/edit/' + id;
                        return;
                    }
                    lastTapTime = now;
                    lastTapId = id;
                    selectPageNode(id, card);
                }
                return;
            }

            // C. Actions via [data-action]
            var actionEl = e.target.closest('[data-action]');
            if (actionEl) {
                var action = actionEl.getAttribute('data-action');
                if (action === 'toggle-reorder') {
                    e.preventDefault();
                    toggleReorderMode();
                } else if (action === 'toggle-expand-all') {
                    e.preventDefault();
                    toggleExpandCollapseAll();
                } else if (action === 'gallery') {
                    e.preventDefault();
                    gallerySelectedPage();
                } else if (action === 'faq') {
                    e.preventDefault();
                    faqSelectedPage();
                } else if (action === 'files') {
                    e.preventDefault();
                    filesSelectedPage();
                } else if (action === 'publish') {
                    e.preventDefault();
                    approveSelectedPage();
                } else if (action === 'edit') {
                    e.preventDefault();
                    editSelectedPage();
                } else if (action === 'delete') {
                    e.preventDefault();
                    deleteSelectedPage();
                } else if (action === 'clear-search') {
                    e.preventDefault();
                    if (searchInput) searchInput.value = '';
                    currentSearchTerm = '';
                    applyFilters();
                } else if (action === 'navigate') {
                    var href = actionEl.getAttribute('data-href');
                    if (href) {
                        e.preventDefault();
                        window.location.href = href;
                    }
                }
            }
        });

        // Double-tap/Double-click on desktop to edit directly
        document.addEventListener('dblclick', function (e) {
            var card = e.target.closest('.page-tree-node .m3-list-card');
            if (card && !e.target.closest('.drag-handle') && !e.target.closest('.toggle-btn')) {
                var node = card.closest('.page-tree-node');
                var id = node ? node.getAttribute('data-id') : card.getAttribute('data-id');
                if (id) {
                    var siteAddress = typeof SITE_URL !== 'undefined' ? SITE_URL : (window.location.origin + '/');
                    window.location.href = siteAddress + 'mod_page/edit/' + id;
                }
            }
        });
    });

    // Expose globals for backward compatibility if required
    window.toggleReorderMode = toggleReorderMode;
    window.toggleExpandCollapseAll = toggleExpandCollapseAll;
    window.editSelected = editSelectedPage;
    window.approveSelected = approveSelectedPage;
    window.gallerySelected = gallerySelectedPage;
    window.faqSelected = faqSelectedPage;
    window.filesSelected = filesSelectedPage;
    window.deleteSelected = deleteSelectedPage;
    window.applyPageFilters = applyFilters;
})();

