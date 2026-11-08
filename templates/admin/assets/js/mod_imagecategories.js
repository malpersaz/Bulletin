/**
 * Image Categories Management Module - List & Form Controller
 * Material Design 3 Phone-First Surface
 */
(function () {
    'use strict';

    let selectedImageCategoryId = null;
    let imageCategorySortables = [];
    let imageCategoryReorderActive = false;

    function getListConfig() {
        const root = document.getElementById('categoryRootList');
        if (!root) {
            return {};
        }
        return {
            siteAddress: root.dataset.siteAddress || '/',
            moveUrl: root.dataset.moveUrl || (root.dataset.siteAddress + 'mod_imagecategories/move'),
            editUrl: root.dataset.editUrl || (root.dataset.siteAddress + 'mod_imagecategories/edit/'),
            deleteUrl: root.dataset.deleteUrl || (root.dataset.siteAddress + 'mod_imagecategories/delete'),
            addUrl: root.dataset.addUrl || (root.dataset.siteAddress + 'mod_imagecategories/add'),
            i18n: {
                reorderTitle: root.dataset.i18nReorderTitle || 'Reorder Categories',
                reorderConfirm: root.dataset.i18nReorderConfirm || 'Save new category order?',
                save: root.dataset.i18nSave || 'Save',
                cancel: root.dataset.i18nCancel || 'Cancel',
                selectRecord: root.dataset.i18nSelectRecord || 'Please select a category first.',
                lockReorder: root.dataset.i18nLockReorder || 'Lock Reorder',
                unlockReorder: root.dataset.i18nUnlockReorder || 'Unlock Reorder',
                modeUnlocked: root.dataset.i18nModeUnlocked || 'Reorder mode active.',
                successReordered: root.dataset.i18nSuccessReordered || 'Categories reordered successfully.',
                networkError: root.dataset.i18nNetworkError || 'A network error occurred.',
                emptyState: root.dataset.i18nEmptyState || 'No image categories found.',
                emptyCta: root.dataset.i18nEmptyCta || 'Add Category',
                deleteTitle: root.dataset.i18nDeleteTitle || 'Delete Category',
                deleteConfirmText: root.dataset.i18nDeleteConfirmText || 'Are you sure you want to delete this category?',
                deleteConfirmButton: root.dataset.i18nDeleteConfirmButton || 'Delete',
                deleteCancelButton: root.dataset.i18nDeleteCancelButton || 'Cancel',
                successDelete: root.dataset.i18nSuccessDelete || 'Category deleted successfully.'
            }
        };
    }

    function initSortable(element) {
        if (!element || typeof Sortable === 'undefined') {
            return;
        }

        const config = getListConfig();
        const sortable = new Sortable(element, {
            group: 'image-category-tree',
            animation: 220,
            fallbackOnBody: true,
            swapThreshold: 0.65,
            handle: '.drag-handle',
            draggable: '.category-node',
            disabled: true,
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',
            onEnd: function (event) {
                if (typeof Swal === 'undefined') {
                    updateCategoryOrder(event);
                    return;
                }
                Swal.fire({
                    title: config.i18n.reorderTitle,
                    text: config.i18n.reorderConfirm,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: config.i18n.save,
                    cancelButtonText: config.i18n.cancel
                }).then(function (result) {
                    if (result.isConfirmed) {
                        updateCategoryOrder(event);
                        return;
                    }
                    window.location.reload();
                });
            }
        });
        imageCategorySortables.push(sortable);
    }

    function selectedCategoryNode() {
        if (!selectedImageCategoryId) {
            return null;
        }
        return document.querySelector('.image-category-node[data-id="' + selectedImageCategoryId + '"]');
    }

    function clearSelectedCategory() {
        selectedImageCategoryId = null;
        document.querySelectorAll('.image-category-card').forEach(function (card) {
            card.classList.remove('active', 'selected');
        });
    }

    function selectCategory(id, cardElement) {
        document.querySelectorAll('.image-category-card').forEach(function (card) {
            card.classList.remove('active', 'selected');
        });
        if (cardElement) {
            cardElement.classList.add('active', 'selected');
        }
        selectedImageCategoryId = id;
    }

    function editCategory(id) {
        const config = getListConfig();
        window.location.href = config.editUrl + id;
    }

    function editSelectedCategory() {
        const config = getListConfig();
        if (!selectedImageCategoryId) {
            if (typeof showSnackbar === 'function') {
                showSnackbar(config.i18n.selectRecord, 'warning');
            }
            return;
        }
        editCategory(selectedImageCategoryId);
    }

    function toggleCategory(button) {
        const node = button.closest('.category-node');
        const children = node ? node.querySelector('.list-children') : null;
        if (!children) {
            return;
        }
        children.classList.toggle('collapsed');
        button.classList.toggle('collapsed');
    }

    function toggleReorderMode() {
        imageCategoryReorderActive = !imageCategoryReorderActive;
        const config = getListConfig();
        const button = document.getElementById('reorderToggleBtn');
        const icon = document.getElementById('reorderIcon');
        const text = document.getElementById('reorderText');

        document.body.classList.toggle('reorder-active', imageCategoryReorderActive);
        imageCategorySortables.forEach(function (sortable) {
            sortable.option('disabled', !imageCategoryReorderActive);
        });

        if (imageCategoryReorderActive) {
            if (icon) icon.className = 'bi bi-unlock-fill';
            if (text) text.innerText = config.i18n.lockReorder;
            if (button) button.classList.add('is-active');
            if (typeof showSnackbar === 'function') {
                showSnackbar(config.i18n.modeUnlocked, 'info');
            }
            return;
        }

        if (icon) icon.className = 'bi bi-lock-fill';
        if (text) text.innerText = config.i18n.unlockReorder;
        if (button) button.classList.remove('is-active');
    }

    function updateCategoryOrder(event) {
        const config = getListConfig();
        const item = event.item;
        const container = event.to;
        const id = item.dataset.id;
        let parentId = 0;

        if (container.classList.contains('list-children')) {
            const parentNode = container.closest('.category-node');
            if (parentNode) {
                parentId = parentNode.dataset.id;
            }
        }

        const body = new URLSearchParams();
        body.append('id', id);
        body.append('parent_id', parentId);
        container.querySelectorAll(':scope > .category-node').forEach(function (node) {
            body.append('order[]', node.dataset.id);
        });

        fetch(config.moveUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString()
        }).then(function (response) {
            return response.text();
        }).then(function (text) {
            if (text.trim() === 'Success') {
                if (typeof showSnackbar === 'function') {
                    showSnackbar(config.i18n.successReordered, 'success');
                }
                return;
            }
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', text: text }).then(function () {
                    window.location.reload();
                });
            } else {
                window.location.reload();
            }
        }).catch(function () {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', text: config.i18n.networkError }).then(function () {
                    window.location.reload();
                });
            } else {
                window.location.reload();
            }
        });
    }

    function restoreEmptyStateIfNeeded() {
        if (document.querySelector('.category-node') || document.querySelector('.empty-state')) {
            return;
        }

        const rootList = document.getElementById('categoryRootList');
        if (!rootList) {
            return;
        }

        const config = getListConfig();
        const emptyStateHtml = [
            '<div class="empty-state">',
            '<i class="bi bi-images"></i>',
            '<strong>' + config.i18n.emptyState + '</strong>',
            '<a href="' + config.addUrl + '" class="primary-btn empty-state-cta">' + config.i18n.emptyCta + '</a>',
            '</div>'
        ].join('');

        rootList.insertAdjacentHTML('beforeend', emptyStateHtml);
    }

    async function deleteSelectedCategory() {
        const config = getListConfig();
        if (!selectedImageCategoryId) {
            if (typeof showSnackbar === 'function') {
                showSnackbar(config.i18n.selectRecord, 'warning');
            }
            return;
        }

        const selectedCard = selectedCategoryNode();
        if (!selectedCard) {
            clearSelectedCategory();
            if (typeof showSnackbar === 'function') {
                showSnackbar(config.i18n.selectRecord, 'warning');
            }
            return;
        }

        if (typeof Swal !== 'undefined') {
            const confirmation = await Swal.fire({
                icon: 'warning',
                title: config.i18n.deleteTitle,
                text: config.i18n.deleteConfirmText,
                showCancelButton: true,
                confirmButtonText: config.i18n.deleteConfirmButton,
                cancelButtonText: config.i18n.deleteCancelButton,
                reverseButtons: true,
                focusCancel: true
            });

            if (!confirmation.isConfirmed) {
                return;
            }
        }

        const body = new URLSearchParams();
        body.append('query[]', selectedImageCategoryId);

        try {
            const response = await fetch(config.deleteUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: body.toString()
            });

            const text = await response.text();
            if (text.trim() === 'Success') {
                selectedCard.remove();
                clearSelectedCategory();
                restoreEmptyStateIfNeeded();
                if (typeof showSnackbar === 'function') {
                    showSnackbar(config.i18n.successDelete, 'success');
                }
                return;
            }

            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', text: text });
            }
        } catch (error) {
            if (typeof showSnackbar === 'function') {
                showSnackbar(config.i18n.networkError, 'error');
            }
        }
    }

    function initList() {
        const rootList = document.getElementById('categoryRootList');
        if (!rootList) {
            return;
        }

        // Initialize Sortable trees
        initSortable(rootList);
        document.querySelectorAll('.list-children').forEach(initSortable);

        // Click delegation on the root container
        rootList.addEventListener('click', function (event) {
            // Drag handle click - stop propagation
            if (event.target.closest('.drag-handle')) {
                event.stopPropagation();
                return;
            }

            // Toggle subcategories
            const toggleBtn = event.target.closest('[data-action="toggle-category"]');
            if (toggleBtn) {
                event.stopPropagation();
                toggleCategory(toggleBtn);
                return;
            }

            // Edit category button
            const editBtn = event.target.closest('[data-action="edit-category"]');
            if (editBtn) {
                event.stopPropagation();
                const card = editBtn.closest('.image-category-card');
                const id = editBtn.dataset.id || (card ? card.dataset.id : null);
                if (id) {
                    editCategory(id);
                }
                return;
            }

            // Select category card
            const card = event.target.closest('.image-category-card');
            if (card && card.dataset.id) {
                selectCategory(card.dataset.id, card);
                return;
            }
        });

        // Reorder toggle button
        const reorderBtn = document.getElementById('reorderToggleBtn');
        if (reorderBtn) {
            reorderBtn.addEventListener('click', function (e) {
                e.preventDefault();
                toggleReorderMode();
            });
        }

        // Toolbar buttons
        const deleteBtn = document.querySelector('[data-action="delete-selected"]');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', function (e) {
                e.preventDefault();
                deleteSelectedCategory();
            });
        }

        const editSelectedBtn = document.querySelector('[data-action="edit-selected"]');
        if (editSelectedBtn) {
            editSelectedBtn.addEventListener('click', function (e) {
                e.preventDefault();
                editSelectedCategory();
            });
        }
    }

    function initForm() {
        const form = document.querySelector('form.image-category-shell');
        if (!form) {
            return;
        }

        const nameInput = document.getElementById('image-category-name');
        const seoInput = document.getElementById('image-category-seo');
        const titleInput = document.getElementById('image-category-title');
        const keywordsInput = document.getElementById('image-category-keywords');
        const descriptionInput = document.getElementById('image-category-description');
        const langSelect = document.getElementById('image-category-lang');

        function syncImageCategoryMeta() {
            if (!nameInput || !seoInput || !titleInput || !keywordsInput || !descriptionInput) {
                return;
            }
            const locale = (langSelect && langSelect.value) ? String(langSelect.value).toLowerCase().trim() : 'tr';
            if (!seoInput.dataset.touched) {
                seoInput.value = typeof makeSeoValue === 'function' ? makeSeoValue(nameInput.value, locale) : nameInput.value;
            }
            if (!titleInput.dataset.touched) {
                titleInput.value = typeof sanitizeMetaText === 'function' ? sanitizeMetaText(nameInput.value) : (typeof sanitizeText === 'function' ? sanitizeText(nameInput.value) : nameInput.value);
            }
            if (!keywordsInput.dataset.touched) {
                keywordsInput.value = typeof makeKeywordsValue === 'function' ? makeKeywordsValue(nameInput.value, locale) : nameInput.value;
            }
            if (!descriptionInput.dataset.touched) {
                const cleanedValue = typeof sanitizeMetaText === 'function' ? sanitizeMetaText(nameInput.value) : (typeof sanitizeText === 'function' ? sanitizeText(nameInput.value) : nameInput.value);
                descriptionInput.value = cleanedValue.substring(0, 255);
            }
        }

        if (nameInput) {
            nameInput.addEventListener('input', syncImageCategoryMeta);
        }

        if (langSelect) {
            langSelect.addEventListener('change', function () {
                const url = new URL(window.location.href);
                url.searchParams.set('lang', langSelect.value);
                window.location.href = url.toString();
            });
        }

        [seoInput, titleInput, keywordsInput, descriptionInput].forEach(function (input) {
            if (!input) return;
            input.addEventListener('input', function () {
                input.dataset.touched = '1';
            });
        });

        // Sync initial state if title is empty
        if (nameInput && nameInput.value && titleInput && !titleInput.value) {
            syncImageCategoryMeta();
        }

        document.querySelectorAll('[data-selection-group]').forEach(function (group) {
            group.addEventListener('change', function (event) {
                if (event.target.type !== 'radio') return;
                group.querySelectorAll('.selection-card').forEach(function (card) {
                    card.classList.toggle('active', card.contains(event.target));
                });
            });
        });

        // Remove image button delegation
        const removeImageBtn = form.querySelector('[data-action="remove-image"]');
        if (removeImageBtn) {
            removeImageBtn.addEventListener('click', async function (e) {
                e.preventDefault();
                const id = removeImageBtn.dataset.id;
                const removeUrl = form.dataset.removeImageUrl || (form.dataset.siteAddress ? form.dataset.siteAddress + 'mod_imagecategories/image_remove_image' : '/mod_imagecategories/image_remove_image');
                const confirmText = form.dataset.i18nRemoveImageConfirm || 'Are you sure you want to remove this image?';
                const removeBtnText = form.dataset.i18nRemoveButton || 'Remove';
                const cancelBtnText = form.dataset.i18nCancelButton || 'Cancel';
                const successText = form.dataset.i18nImageRemovedSuccess || 'Image removed successfully.';
                const networkError = form.dataset.i18nNetworkError || 'A network error occurred.';

                if (typeof Swal !== 'undefined') {
                    const confirmation = await Swal.fire({
                        icon: 'warning',
                        text: confirmText,
                        showCancelButton: true,
                        confirmButtonText: removeBtnText,
                        cancelButtonText: cancelBtnText,
                        reverseButtons: true,
                        focusCancel: true
                    });
                    if (!confirmation.isConfirmed) return;
                }

                const body = new URLSearchParams();
                body.append('id', id);

                try {
                    const response = await fetch(removeUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                        body: body.toString()
                    });
                    const text = await response.text();
                    if (text.trim() === 'Success') {
                        const currentImage = document.getElementById('image-category-current-image');
                        if (currentImage) {
                            currentImage.remove();
                        }
                        if (typeof showSnackbar === 'function') {
                            showSnackbar(successText, 'success');
                        }
                        return;
                    }
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', text: text });
                    }
                } catch (err) {
                    if (typeof showSnackbar === 'function') {
                        showSnackbar(networkError, 'error');
                    }
                }
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        initList();
        initForm();
    });
})();
