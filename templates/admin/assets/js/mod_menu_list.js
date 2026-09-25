/**
 * Menu Management Module - List & Hierarchy Controller
 * Modern Material Design 3 Phone-First Controller (Super Polished)
 */
(function () {
    'use strict';

    var selectedMenuId = null;
    var sortableInstances = [];
    var isReorderActive = false;

    // Clear any previous wizard draft upon reaching list page
    try {
        sessionStorage.removeItem('modMenuWizardState');
    } catch (e) {}
    var areAllExpanded = false;
    var currentStatusFilter = 'all';
    var currentSearchTerm = '';
    var lastTapTime = 0;
    var lastTapId = null;

    // Cache original titles for search highlighting restore
    var originalTitles = {};

    function getSiteAddress() {
        if (typeof window.SITE_URL !== 'undefined' && window.SITE_URL) {
            return window.SITE_URL;
        }
        if (typeof SITE_URL !== 'undefined' && SITE_URL) {
            return SITE_URL;
        }
        if (typeof window.SITE_ADDRESS !== 'undefined' && window.SITE_ADDRESS) {
            return window.SITE_ADDRESS;
        }
        if (typeof SITE_ADDRESS !== 'undefined' && SITE_ADDRESS) {
            return SITE_ADDRESS;
        }
        return '/';
    }

    function getLangString(keyPath, fallback) {
        var al = (typeof window.adminLang !== 'undefined' && window.adminLang)
            ? window.adminLang
            : (typeof adminLang !== 'undefined' ? adminLang : {});
        var parts = keyPath.split('.');

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

        var res = lookup(al, parts);
        if (res !== null) return res;

        if (al.mod_menu) {
            res = lookup(al.mod_menu, parts);
            if (res !== null) return res;
            if (parts[0] === 'mod_menu') {
                res = lookup(al.mod_menu, parts.slice(1));
                if (res !== null) return res;
            }
        }

        if (al.active_module) {
            res = lookup(al.active_module, parts);
            if (res !== null) return res;
            if (parts[0] === 'mod_menu') {
                res = lookup(al.active_module, parts.slice(1));
                if (res !== null) return res;
            }
        }

        return fallback || '';
    }

    function initOriginalTitles() {
        document.querySelectorAll('.menu-node').forEach(function (node) {
            var id = node.getAttribute('data-id');
            var titleEl = node.querySelector('.card-title');
            if (id && titleEl && !(id in originalTitles)) {
                originalTitles[id] = titleEl.textContent.trim();
            }
        });
    }

    function getExpandedNodeIds() {
        try {
            var stored = localStorage.getItem('mod_menu_expanded_nodes');
            return stored ? JSON.parse(stored) : [];
        } catch (e) {
            return [];
        }
    }

    function saveExpandedNodeIds(ids) {
        try {
            localStorage.setItem('mod_menu_expanded_nodes', JSON.stringify(ids));
        } catch (e) {}
    }

    function updateExpandedState() {
        var expandedIds = [];
        document.querySelectorAll('.menu-node').forEach(function (node) {
            var children = node.querySelector(':scope > .list-children');
            if (children && !children.classList.contains('collapsed')) {
                var id = node.getAttribute('data-id');
                if (id) expandedIds.push(id);
            }
        });
        saveExpandedNodeIds(expandedIds);
    }

    function restoreExpandedState() {
        var expandedIds = getExpandedNodeIds();
        if (expandedIds && expandedIds.length > 0) {
            expandedIds.forEach(function (id) {
                var node = document.querySelector('.menu-node[data-id="' + id + '"]');
                if (node) {
                    var children = node.querySelector(':scope > .list-children');
                    var btn = node.querySelector(':scope > .m3-list-card .toggle-btn');
                    if (children) children.classList.remove('collapsed');
                    if (btn) btn.classList.remove('collapsed');
                }
            });
        }
    }

    function registerSortable(el) {
        if (typeof Sortable === 'undefined' || !el) return;

        var instance = new Sortable(el, {
            group: 'menu_nested',
            animation: 250,
            handle: '.drag-handle',
            disabled: !isReorderActive,
            fallbackOnBody: true,
            swapThreshold: 0.65,
            ghostClass: 'sortable-ghost',
            onEnd: function (evt) {
                var warningTitle = getLangString('mod_menu.messages.warning.reorder', 'Reorder Menus');
                var confirmText = getLangString('mod_menu.confirmations.reorder_single', 'Do you want to move this menu to its new location?');
                var yesText = getLangString('mod_menu.options.yes', 'Yes');
                var noText = getLangString('mod_menu.options.no', 'No');

                Swal.fire({
                    title: warningTitle,
                    text: confirmText,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#6750a4',
                    cancelButtonColor: '#79747e',
                    confirmButtonText: yesText,
                    cancelButtonText: noText,
                    background: 'rgba(255,255,255,0.98)',
                    backdrop: 'rgba(0,0,10,0.5)'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        var itemEl = evt.item;
                        var newParentId = 0;
                        var container = evt.to;

                        if (container.classList.contains('list-children')) {
                            var parentNode = container.closest('.menu-node');
                            if (parentNode) {
                                newParentId = parentNode.getAttribute('data-id') || 0;
                            }
                        }

                        var activeIds = [];
                        var siblings = container.querySelectorAll(':scope > .menu-node');
                        siblings.forEach(function (node) {
                            var nid = node.getAttribute('data-id');
                            if (nid) activeIds.push(nid);
                        });

                        if (newParentId > 0) {
                            var expanded = getExpandedNodeIds();
                            var pStr = newParentId.toString();
                            if (expanded.indexOf(pStr) === -1) {
                                expanded.push(pStr);
                                saveExpandedNodeIds(expanded);
                            }
                        }

                        updateMenuOrder(itemEl.getAttribute('data-id'), newParentId, activeIds);
                    } else {
                        location.reload();
                    }
                });
            }
        });
        sortableInstances.push(instance);
    }

    function initSortables() {
        var root = document.getElementById('menuRootList');
        if (root) registerSortable(root);

        document.querySelectorAll('.list-children').forEach(function (el) {
            registerSortable(el);
        });
    }

    function updateMenuOrder(id, parent_id, orderList) {
        var formData = new URLSearchParams();
        formData.append('id', id);
        formData.append('parent_id', parent_id);
        orderList.forEach(function (sid) {
            formData.append('order[]', sid);
        });

        var siteAddr = getSiteAddress();
        if (!siteAddr.endsWith('/')) {
            siteAddr += '/';
        }
        fetch(siteAddr + 'mod_menu/reorder', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
            .then(function (response) { return response.text(); })
            .then(function (data) {
                if (data.trim() === 'Success') {
                    var successMsg = getLangString('mod_menu.messages.success.reordered', 'Menu order updated!');
                    var toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 1500
                    });
                    toast.fire({ icon: 'success', title: successMsg });
                    setTimeout(function () {
                        location.reload();
                    }, 800);
                } else {
                    var errorTitle = getLangString('mod_menu.error_title', 'Error!');
                    Swal.fire({ icon: 'error', title: errorTitle, text: data });
                }
            })
            .catch(function (err) {
                console.error('Reorder error:', err);
                location.reload();
            });
    }

    function toggleReorderMode() {
        isReorderActive = !isReorderActive;

        var btn = document.getElementById('reorderToggleBtn');
        var icon = document.getElementById('reorderIcon');
        var text = document.getElementById('reorderText');
        var banner = document.getElementById('reorderBanner');

        if (isReorderActive) {
            document.body.classList.add('reorder-active');
            if (btn) btn.classList.add('active');
            if (icon) icon.className = 'bi bi-unlock-fill';
            if (text) text.textContent = getLangString('mod_menu.actions.lock_reorder', 'Lock Reorder Mode');
            if (banner) banner.classList.remove('is-hidden');

            sortableInstances.forEach(function (s) {
                if (s && typeof s.option === 'function') s.option('disabled', false);
            });

            var toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 1500
            });
            toast.fire({ icon: 'info', title: getLangString('mod_menu.messages.info.reorder_mode', 'Mode: Reorder (Unlocked)') });
        } else {
            document.body.classList.remove('reorder-active');
            if (btn) btn.classList.remove('active');
            if (icon) icon.className = 'bi bi-lock-fill';
            if (text) text.textContent = getLangString('mod_menu.actions.unlock_reorder', 'Unlock Reorder Mode');
            if (banner) banner.classList.add('is-hidden');

            sortableInstances.forEach(function (s) {
                if (s && typeof s.option === 'function') s.option('disabled', true);
            });
        }
    }

    function toggleExpandAll() {
        areAllExpanded = !areAllExpanded;

        var icon = document.getElementById('expandCollapseIcon');
        var text = document.getElementById('expandCollapseText');

        document.querySelectorAll('.menu-node').forEach(function (node) {
            var children = node.querySelector(':scope > .list-children');
            var btn = node.querySelector(':scope > .m3-list-card .toggle-btn');
            if (children && btn) {
                if (areAllExpanded) {
                    children.classList.remove('collapsed');
                    btn.classList.remove('collapsed');
                } else {
                    children.classList.add('collapsed');
                    btn.classList.add('collapsed');
                }
            }
        });

        if (areAllExpanded) {
            if (icon) icon.className = 'bi bi-arrows-collapse';
            if (text) text.textContent = getLangString('mod_menu.collapse_all', 'Collapse All');
        } else {
            if (icon) icon.className = 'bi bi-arrows-expand';
            if (text) text.textContent = getLangString('mod_menu.expand_all', 'Expand All');
        }

        updateExpandedState();
    }

    function toggleNode(node) {
        if (!node) return;
        var children = node.querySelector(':scope > .list-children');
        var btn = node.querySelector(':scope > .m3-list-card .toggle-btn');

        if (children) {
            children.classList.toggle('collapsed');
            if (btn) btn.classList.toggle('collapsed');
            updateExpandedState();
        }
    }

    function selectNode(id, card) {
        var isCurrent = card.classList.contains('selected');

        document.querySelectorAll('.m3-list-card.selected').forEach(function (el) {
            el.classList.remove('selected');
        });

        if (isCurrent) {
            selectedMenuId = null;
            updateToolbar(false);
        } else {
            card.classList.add('selected');
            selectedMenuId = id;
            var nodeEl = card.closest('.menu-node');
            var isContainer = (nodeEl && nodeEl.getAttribute('data-is-container') === '1') || card.getAttribute('data-is-container') === '1';
            var status = (nodeEl ? nodeEl.getAttribute('data-status') : null) || card.getAttribute('data-status') || 'y';
            updateToolbar(true, status, isContainer);
            if (window.navigator && window.navigator.vibrate) {
                window.navigator.vibrate(10);
            }
        }
    }

    function updateToolbar(active, status, isContainer) {
        var editBtn = document.getElementById('toolbarEdit');
        var delBtn = document.getElementById('toolbarDelete');
        var eyeBtn = document.getElementById('toolbarApprove');
        var eyeIcon = document.getElementById('approveIcon');

        if (active) {
            if (editBtn) {
                editBtn.classList.remove('is-disabled');
                editBtn.classList.add('active');
            }
            if (delBtn) {
                if (isContainer) {
                    delBtn.classList.add('is-disabled');
                    delBtn.classList.remove('active');
                } else {
                    delBtn.classList.remove('is-disabled');
                    delBtn.classList.add('active');
                }
            }
            if (eyeBtn) {
                eyeBtn.classList.remove('is-disabled');
                eyeBtn.classList.add('active');
                var isPublished = (status === 'y');
                if (eyeIcon) {
                    eyeIcon.className = isPublished ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill';
                }
                var toggleTitle = isPublished
                    ? getLangString('mod_menu.actions.unpublish', 'Unpublish')
                    : getLangString('mod_menu.actions.publish', 'Publish');
                eyeBtn.setAttribute('title', toggleTitle);
                eyeBtn.setAttribute('aria-label', toggleTitle);
            }
        } else {
            if (editBtn) {
                editBtn.classList.add('is-disabled');
                editBtn.classList.remove('active');
            }
            if (delBtn) {
                delBtn.classList.add('is-disabled');
                delBtn.classList.remove('active');
            }
            if (eyeBtn) {
                eyeBtn.classList.add('is-disabled');
                eyeBtn.classList.remove('active');
                if (eyeIcon) {
                    eyeIcon.className = 'bi bi-eye-fill';
                }
                var defaultToggleTitle = getLangString('mod_menu.actions.publish_toggle', 'Publish Status');
                eyeBtn.setAttribute('title', defaultToggleTitle);
                eyeBtn.setAttribute('aria-label', defaultToggleTitle);
            }
        }
    }

    function editSelected() {
        if (!selectedMenuId) return;
        var siteAddr = getSiteAddress();
        if (!siteAddr.endsWith('/')) {
            siteAddr += '/';
        }
        window.location.href = siteAddr + 'mod_menu/edit/' + selectedMenuId;
    }

    function toggleStatusSelected() {
        if (!selectedMenuId) return;

        var selectedCard = document.querySelector('.m3-list-card.selected');
        var selectedNode = selectedCard ? selectedCard.closest('.menu-node') : document.querySelector('.menu-node[data-id="' + selectedMenuId + '"]');
        var currentStatus = (selectedNode ? selectedNode.getAttribute('data-status') : null)
            || (selectedCard ? selectedCard.getAttribute('data-status') : null)
            || 'y';
        var isPublishing = currentStatus !== 'y';
        var endpoint = isPublishing ? 'approve' : 'unapprove';

        var confirmMsg = isPublishing
            ? getLangString('mod_menu.confirmations.publish', 'Are you sure you want to publish this menu?')
            : getLangString('mod_menu.confirmations.unpublish', 'Are you sure you want to unpublish this menu?');

        var successMsg = isPublishing
            ? getLangString('mod_menu.messages.success.published', 'Menu successfully published!')
            : getLangString('mod_menu.messages.success.unpublished', 'Menu successfully unpublished!');

        var yesText = getLangString('mod_menu.options.yes', 'Yes');
        var noText = getLangString('mod_menu.options.no', 'No');
        var successTitle = getLangString('mod_menu.success_title', 'Success!');
        var errorTitle = getLangString('mod_menu.error_title', 'Error!');

        Swal.fire({
            title: confirmMsg,
            icon: isPublishing ? 'question' : 'info',
            iconColor: isPublishing ? '#2e7d32' : '#e65100',
            showCancelButton: true,
            confirmButtonColor: isPublishing ? '#2e7d32' : '#e65100',
            cancelButtonColor: '#6750a4',
            confirmButtonText: yesText,
            cancelButtonText: noText,
            background: 'rgba(255,255,255,0.98)',
            backdrop: 'rgba(0,0,10,0.5)'
        }).then(function (result) {
            if (result.isConfirmed) {
                var formData = new URLSearchParams();
                formData.append('query[]', selectedMenuId);

                var siteAddr = getSiteAddress();
                if (!siteAddr.endsWith('/')) {
                    siteAddr += '/';
                }

                fetch(siteAddr + 'mod_menu/' + endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                    .then(function (r) { return r.text(); })
                    .then(function (msg) {
                        if (msg.trim() === 'Success') {
                            Swal.fire({
                                icon: 'success',
                                title: successTitle,
                                text: successMsg,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(function () {
                                location.reload();
                            });
                        } else {
                            Swal.fire(errorTitle, msg, 'error');
                        }
                    })
                    .catch(function (err) {
                        console.error('Status toggle error:', err);
                        Swal.fire(errorTitle, getLangString('mod_menu.errors.general', 'An error occurred.'), 'error');
                    });
            }
        });
    }

    function deleteSelected() {
        if (!selectedMenuId) return;

        var selectedNode = document.querySelector('.menu-node[data-id="' + selectedMenuId + '"]');
        if (selectedNode && selectedNode.getAttribute('data-is-container') === '1') {
            return;
        }

        var warningTitle = getLangString('mod_menu.messages.warning.delete', 'Are you sure you want to delete this menu?');
        var confirmText = getLangString('mod_menu.confirmations.delete_single', 'This action cannot be undone.');
        var yesText = getLangString('mod_menu.options.yes', 'Yes');
        var noText = getLangString('mod_menu.options.no', 'No');
        var successTitle = getLangString('mod_menu.success_title', 'Success!');
        var errorTitle = getLangString('mod_menu.error_title', 'Error!');
        var successMsg = getLangString('mod_menu.messages.success.deleted', 'Menu successfully deleted!');

        Swal.fire({
            title: warningTitle,
            text: confirmText,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#b3261e',
            cancelButtonColor: '#6750a4',
            confirmButtonText: yesText,
            cancelButtonText: noText,
            background: 'rgba(255,255,255,0.98)',
            backdrop: 'rgba(0,0,10,0.5)'
        }).then(function (result) {
            if (result.isConfirmed) {
                var formData = new URLSearchParams();
                formData.append('delete[]', selectedMenuId);

                var siteAddr = getSiteAddress();
                if (!siteAddr.endsWith('/')) {
                    siteAddr += '/';
                }

                fetch(siteAddr + 'mod_menu/dragdrop_delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                    .then(function (r) { return r.text(); })
                    .then(function (data) {
                        if (data.trim() === 'Success') {
                            Swal.fire({
                                icon: 'success',
                                title: successTitle,
                                text: successMsg,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(function () {
                                location.reload();
                            });
                        } else {
                            Swal.fire(errorTitle, data, 'error');
                        }
                    })
                    .catch(function (err) {
                        console.error('Delete error:', err);
                        Swal.fire(errorTitle, getLangString('mod_menu.errors.general', 'An error occurred.'), 'error');
                    });
            }
        });
    }

    function applyCombinedFilter() {
        var allNodes = document.querySelectorAll('.menu-node');
        var visibleCount = 0;
        var term = currentSearchTerm.toLowerCase().trim();

        // 1. Reset all nodes
        allNodes.forEach(function (node) {
            node.classList.remove('is-hidden');
            var id = node.getAttribute('data-id');
            var titleEl = node.querySelector('.card-title');
            if (id && titleEl && (id in originalTitles)) {
                titleEl.textContent = originalTitles[id];
            }
        });

        // 2. Status filter
        if (currentStatusFilter !== 'all') {
            allNodes.forEach(function (node) {
                var status = node.getAttribute('data-status') || 'y';
                var matchesStatus = (currentStatusFilter === 'published' && status === 'y') ||
                                    (currentStatusFilter === 'draft' && status !== 'y');
                if (!matchesStatus) {
                    node.classList.add('is-hidden');
                }
            });

            // Ensure parent nodes remain visible if any child matches
            allNodes.forEach(function (node) {
                if (!node.classList.contains('is-hidden')) {
                    var curr = node.parentElement ? node.parentElement.closest('.menu-node') : null;
                    while (curr) {
                        curr.classList.remove('is-hidden');
                        curr = curr.parentElement ? curr.parentElement.closest('.menu-node') : null;
                    }
                }
            });
        }

        // 3. Search filter
        if (term.length > 0) {
            allNodes.forEach(function (node) {
                node.classList.add('is-hidden');
            });

            allNodes.forEach(function (node) {
                var name = (node.getAttribute('data-name') || '').toLowerCase();
                var id = node.getAttribute('data-id') || '';

                if (name.indexOf(term) !== -1 || ('#' + id).indexOf(term) !== -1) {
                    node.classList.remove('is-hidden');
                    visibleCount++;

                    // Highlight matched text
                    var titleEl = node.querySelector('.card-title');
                    if (titleEl && (id in originalTitles)) {
                        var orig = originalTitles[id];
                        var idx = orig.toLowerCase().indexOf(term);
                        if (idx !== -1) {
                            var before = orig.substring(0, idx);
                            var match = orig.substring(idx, idx + term.length);
                            var after = orig.substring(idx + term.length);
                            titleEl.innerHTML = escapeHtml(before) + '<mark class="search-highlight">' + escapeHtml(match) + '</mark>' + escapeHtml(after);
                        }
                    }

                    // Show all ancestor nodes and expand them
                    var parentNode = node.parentElement ? node.parentElement.closest('.menu-node') : null;
                    while (parentNode) {
                        parentNode.classList.remove('is-hidden');
                        var pChildren = parentNode.querySelector(':scope > .list-children');
                        var pBtn = parentNode.querySelector(':scope > .m3-list-card .toggle-btn');
                        if (pChildren) pChildren.classList.remove('collapsed');
                        if (pBtn) pBtn.classList.remove('collapsed');
                        parentNode = parentNode.parentElement ? parentNode.parentElement.closest('.menu-node') : null;
                    }
                }
            });

            var searchEmpty = document.getElementById('searchEmptyState');
            if (searchEmpty) {
                if (visibleCount === 0) {
                    searchEmpty.classList.remove('is-hidden');
                } else {
                    searchEmpty.classList.add('is-hidden');
                }
            }
        } else {
            var sEmpty = document.getElementById('searchEmptyState');
            if (sEmpty) sEmpty.classList.add('is-hidden');
        }
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // --- Global Event Delegation ---
    document.addEventListener('click', function (e) {
        var actionEl = e.target.closest('[data-action]');
        if (!actionEl) return;

        var action = actionEl.getAttribute('data-action');

        switch (action) {
            case 'toggle-reorder':
                e.preventDefault();
                toggleReorderMode();
                break;

            case 'toggle-expand-all':
                e.preventDefault();
                toggleExpandAll();
                break;

            case 'toggle-node':
                e.preventDefault();
                e.stopPropagation();
                var node = actionEl.closest('.menu-node');
                toggleNode(node);
                break;

            case 'select-card':
                e.preventDefault();
                var nodeEl = actionEl.closest('.menu-node');
                if (!nodeEl) return;
                var id = nodeEl.getAttribute('data-id');

                // Double tap handling
                var now = Date.now();
                if (lastTapId === id && (now - lastTapTime) < 300) {
                    editSelected();
                    lastTapTime = 0;
                    lastTapId = null;
                    return;
                }
                lastTapTime = now;
                lastTapId = id;

                selectNode(id, actionEl);
                break;

            case 'edit':
                e.preventDefault();
                editSelected();
                break;

            case 'publish':
                e.preventDefault();
                toggleStatusSelected();
                break;

            case 'delete':
                e.preventDefault();
                deleteSelected();
                break;

            case 'clear-search':
                e.preventDefault();
                var searchInputs = [
                    document.getElementById('topSearchInput'),
                    document.getElementById('searchInput')
                ];
                searchInputs.forEach(function (inp) {
                    if (inp) inp.value = '';
                });
                currentSearchTerm = '';
                applyCombinedFilter();
                break;

            case 'select-status-pill':
                e.preventDefault();
                var filterType = actionEl.getAttribute('data-status-filter') || 'all';
                currentStatusFilter = filterType;

                document.querySelectorAll('.menu-stat-card').forEach(function (card) {
                    card.classList.remove('active');
                });
                actionEl.classList.add('active');
                applyCombinedFilter();
                break;
        }
    });

    // --- DOM Ready Initialization ---
    document.addEventListener('DOMContentLoaded', function () {
        initOriginalTitles();
        initSortables();
        restoreExpandedState();

        // Connect Header Search Bar
        var searchInput = document.getElementById('topSearchInput') || document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function (e) {
                currentSearchTerm = e.target.value || '';
                applyCombinedFilter();
            });
        }

        // Handle Slot Hash Navigation (#header, #footer, #action)
        function handleSlotHash() {
            var hash = (window.location.hash || '').replace(/^#/, '').toLowerCase();
            if (!hash) return;
            if (hash.indexOf('slot-') === 0) {
                hash = hash.substring(5);
            }
            var targetNode = document.querySelector('.menu-node[data-slot="' + hash + '"]') ||
                             document.getElementById('slot-' + hash) ||
                             document.getElementById(hash);
            if (targetNode) {
                var curr = targetNode;
                while (curr && curr !== document.body) {
                    if (curr.classList.contains('menu-node')) {
                        var children = curr.querySelector(':scope > .list-children');
                        var btn = curr.querySelector(':scope > .m3-list-card .toggle-btn');
                        if (children) children.classList.remove('collapsed');
                        if (btn) btn.classList.remove('collapsed');
                    }
                    curr = curr.parentElement;
                }
                setTimeout(function () {
                    targetNode.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    var card = targetNode.querySelector(':scope > .m3-list-card');
                    if (card) {
                        card.classList.add('slot-highlight');
                        setTimeout(function () {
                            card.classList.remove('slot-highlight');
                        }, 2000);
                    }
                }, 150);
            }
        }

        handleSlotHash();
        window.addEventListener('hashchange', handleSlotHash);

        // Add mesh background to mobile wrapper
        var wrapper = document.querySelector('.mobile-wrapper');
        if (wrapper) {
            wrapper.classList.add('mesh-bg');
        }
    });

})();
