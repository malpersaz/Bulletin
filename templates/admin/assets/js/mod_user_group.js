/**
 * User Group Management Module - List & Hierarchy Controller
 */
(function () {
    'use strict';

    let selectedUserGroupId = null;
    let sortableInstances = [];
    let isReorderActive = false;

    function getSiteAddress() {
        if (typeof window.SITE_URL !== 'undefined' && window.SITE_URL) {
            return window.SITE_URL;
        }
        if (typeof window.SITE_ADDRESS !== 'undefined' && window.SITE_ADDRESS) {
            return window.SITE_ADDRESS;
        }
        return '/';
    }

    function getLang(key, fallback) {
        if (typeof window.adminLang !== 'undefined' && window.adminLang && window.adminLang.active_module) {
            const mod = window.adminLang.active_module;
            if (key in mod && typeof mod[key] === 'string') {
                return mod[key];
            }
            if (mod.general && key in mod.general && typeof mod.general[key] === 'string') {
                return mod.general[key];
            }
            if (mod.ajax && key in mod.ajax && typeof mod.ajax[key] === 'string') {
                return mod.ajax[key];
            }
        }
        return fallback || '';
    }

    document.addEventListener('DOMContentLoaded', function () {
        const wrapper = document.querySelector('.mobile-wrapper');
        if (wrapper) {
            wrapper.classList.add('mesh-bg');
        }

        const globalSearchInput = document.getElementById('searchInput');
        if (globalSearchInput) {
            globalSearchInput.addEventListener('input', function (e) {
                filterUserGroups(e.target.value);
            });
        }

        window.clearSearch = function () {
            if (globalSearchInput) {
                globalSearchInput.value = '';
                filterUserGroups('');
            }
        };

        function initSortable(el) {
            if (!el || typeof Sortable === 'undefined') return;
            const s = new Sortable(el, {
                group: 'nested_groups',
                animation: 300,
                fallbackOnBody: true,
                swapThreshold: 0.65,
                handle: '.drag-handle',
                disabled: true,
                ghostClass: 'sortable-ghost',
                dragClass: 'sortable-drag',
                onEnd: function (evt) {
                    Swal.fire({
                        title: getLang('reorder_active', 'Reorder Mode Active'),
                        text: getLang('drag_drop_instruction', ''),
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: getLang('yes', 'Yes'),
                        cancelButtonText: getLang('cancel', 'Cancel'),
                        customClass: { popup: 'm3-swal-popup' }
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            updateOrder(evt);
                        } else {
                            location.reload();
                        }
                    });
                }
            });
            sortableInstances.push(s);
        }

        const rootList = document.getElementById('userGroupRootList');
        if (rootList) initSortable(rootList);
        document.querySelectorAll('.list-children').forEach(function (el) {
            initSortable(el);
        });

        function updateOrder(evt) {
            const itemEl = evt.item;
            const groupId = itemEl.dataset.id;
            const container = evt.to;

            let parentId = 0;
            if (container.classList.contains('list-children')) {
                const wrapperNode = container.closest('.category-node');
                if (wrapperNode) parentId = wrapperNode.dataset.id;
            }

            const allIds = [];
            document.querySelectorAll('.category-node').forEach(function (node) {
                if (node.dataset.id) {
                    allIds.push(node.dataset.id);
                }
            });

            const card = itemEl.querySelector('.m3-list-card');
            if (card) card.style.opacity = '0.5';

            const formData = new URLSearchParams();
            formData.append('group_id', groupId);
            formData.append('parent_id', parentId);

            const siteAddr = getSiteAddress();

            fetch(siteAddr + 'mod_user_group/move', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            })
                .then(function (response) {
                    return response.text();
                })
                .then(function (resp) {
                    if (resp.trim().toUpperCase() === 'OK') {
                        const orderData = new URLSearchParams();
                        allIds.forEach(function (sid) {
                            orderData.append('order[]', sid);
                        });

                        return fetch(siteAddr + 'mod_user_group/order', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: orderData
                        });
                    } else {
                        throw new Error(resp);
                    }
                })
                .then(function (response) {
                    return response.text();
                })
                .then(function (orderResp) {
                    if (card) card.style.opacity = '1';
                    if (orderResp.trim().toUpperCase() === 'OK') {
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true
                        });
                        Toast.fire({ icon: 'success', title: getLang('order_changed', 'Order Changed!') });
                    } else {
                        throw new Error(orderResp);
                    }
                })
                .catch(function (err) {
                    if (card) card.style.opacity = '1';
                    console.error('Order update error:', err);
                    Swal.fire({ icon: 'error', title: getLang('error', 'Error'), text: String(err) });
                    setTimeout(function () {
                        location.reload();
                    }, 1000);
                });
        }
    });

    function toggleReorderMode() {
        isReorderActive = !isReorderActive;
        const btn = document.getElementById('reorderToggleBtn');
        const icon = document.getElementById('reorderIcon');
        const text = document.getElementById('reorderText');

        if (isReorderActive) {
            document.body.classList.add('reorder-active');
            if (btn) btn.classList.add('active');
            if (icon) icon.className = 'bi bi-unlock-fill';
            if (text) text.innerText = getLang('reorder_active', 'Reorder Mode Active');

            sortableInstances.forEach(function (s) {
                s.option('disabled', false);
            });

            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 1500
            });
            Toast.fire({ icon: 'info', title: getLang('reorder_active', 'Reorder Mode Active') });
        } else {
            document.body.classList.remove('reorder-active');
            if (btn) btn.classList.remove('active');
            if (icon) icon.className = 'bi bi-lock-fill';
            if (text) text.innerText = getLang('reorder_active', 'Reorder Mode Active');

            sortableInstances.forEach(function (s) {
                s.option('disabled', true);
            });
        }
    }

    function filterUserGroups(val) {
        const term = val.toLowerCase().trim();
        const nodes = document.querySelectorAll('.category-node');

        if (!term) {
            nodes.forEach(function (n) {
                n.style.display = '';
                const children = n.querySelector('.list-children');
                if (children) children.classList.remove('collapsed');
                const toggleBtn = n.querySelector('.toggle-btn');
                if (toggleBtn) toggleBtn.classList.remove('collapsed');
            });
            return;
        }

        nodes.forEach(function (n) {
            n.style.display = 'none';
        });
        nodes.forEach(function (n) {
            const name = n.dataset.name || '';
            if (name.includes(term)) {
                showRecursively(n);
            }
        });

        function showRecursively(node) {
            node.style.display = 'block';
            let parent = node.parentElement ? node.parentElement.closest('.category-node') : null;
            while (parent) {
                parent.style.display = 'block';
                const pChildren = parent.querySelector('.list-children');
                if (pChildren) pChildren.classList.remove('collapsed');
                const pToggle = parent.querySelector('.toggle-btn');
                if (pToggle) pToggle.classList.remove('collapsed');
                parent = parent.parentElement ? parent.parentElement.closest('.category-node') : null;
            }
        }
    }

    function toggleUserGroup(btn) {
        const node = btn.closest('.category-node');
        if (!node) return;
        const children = node.querySelector(':scope > .list-children');

        if (children) {
            children.classList.toggle('collapsed');
            btn.classList.toggle('collapsed');
        }
    }

    function selectUserGroup(id, element) {
        const isCurrent = element.classList.contains('selected');
        document.querySelectorAll('.m3-list-card.selected').forEach(function (el) {
            el.classList.remove('selected');
        });

        if (!isCurrent) {
            element.classList.add('selected');
            selectedUserGroupId = id;
            toggleToolbar(true);
            if (window.navigator && window.navigator.vibrate) {
                window.navigator.vibrate(10);
            }
        } else {
            selectedUserGroupId = null;
            toggleToolbar(false);
        }
    }

    function toggleToolbar(active) {
        const edit = document.getElementById('toolbarEdit');
        const del = document.getElementById('toolbarDelete');
        if (edit && del) {
            if (active) {
                edit.classList.remove('is-disabled');
                del.classList.remove('is-disabled');
                edit.classList.add('active');
                del.classList.add('active');
            } else {
                edit.classList.remove('active');
                del.classList.remove('active');
                edit.classList.add('is-disabled');
                del.classList.add('is-disabled');
            }
        }
    }

    function editSelected() {
        if (selectedUserGroupId) {
            window.location.href = getSiteAddress() + 'mod_user_group/edit/' + selectedUserGroupId;
        }
    }

    function deleteSelected() {
        if (!selectedUserGroupId) return;

        Swal.fire({
            title: getLang('confirm_delete', 'Are you sure?'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff4444',
            cancelButtonColor: '#999',
            confirmButtonText: getLang('yes', 'Yes'),
            cancelButtonText: getLang('cancel', 'Cancel'),
            customClass: { popup: 'premium-swal-popup' }
        }).then(function (result) {
            if (result.isConfirmed) {
                fetch(getSiteAddress() + 'mod_user_group/delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ group_id: selectedUserGroupId }).toString()
                })
                    .then(function (response) {
                        return response.text();
                    })
                    .then(function (resp) {
                        if (resp.trim().toUpperCase() === 'OK') {
                            Swal.fire({
                                icon: 'success',
                                title: getLang('group_deleted', 'Deleted!'),
                                timer: 1000,
                                showConfirmButton: false
                            });
                            setTimeout(function () {
                                window.location.reload();
                            }, 1000);
                        } else {
                            Swal.fire(getLang('error', 'Error'), resp, 'error');
                        }
                    })
                    .catch(function (err) {
                        console.error('Delete error:', err);
                        Swal.fire(getLang('error', 'Error'), String(err), 'error');
                    });
            }
        });
    }

    document.addEventListener('click', function (e) {
        if (e.target.closest('.drag-handle')) {
            e.stopPropagation();
            return;
        }

        const actionEl = e.target.closest('[data-action]');
        if (!actionEl) return;

        const action = actionEl.getAttribute('data-action');
        switch (action) {
            case 'toggle-user-group':
                e.preventDefault();
                e.stopPropagation();
                toggleUserGroup(actionEl);
                break;

            case 'select-user-group':
                e.preventDefault();
                selectUserGroup(actionEl.getAttribute('data-id'), actionEl);
                break;

            case 'toggle-reorder-mode':
                e.preventDefault();
                toggleReorderMode();
                break;

            case 'edit-selected':
                e.preventDefault();
                editSelected();
                break;

            case 'delete-selected':
                e.preventDefault();
                deleteSelected();
                break;
        }
    });
})();
