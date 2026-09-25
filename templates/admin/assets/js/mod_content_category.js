/**
 * mod_content_category.js - Admin Category Interactions
 * Amed Framework
 *
 * Strict Code Separation: 100% External JS
 */

(function() {
    'use strict';

    let selectedCategoryId = null;
    let isReorderActive = false;

    document.addEventListener('DOMContentLoaded', function() {
        // --- 1. Selection & Actions ---
        const categoryCards = document.querySelectorAll('.category-node .m3-list-card');
        categoryCards.forEach(function(card) {
            card.addEventListener('click', function(e) {
                if (e.target.closest('.toggle-btn') || e.target.closest('.drag-handle')) {
                    return;
                }
                const parentNode = card.closest('.category-node');
                if (!parentNode) return;
                const id = parentNode.getAttribute('data-id');
                selectCategory(id, card);
            });
        });

        // --- 2. Tree Toggle ---
        const toggleBtns = document.querySelectorAll('.category-node .toggle-btn');
        toggleBtns.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const node = btn.closest('.category-node');
                if (!node) return;
                const children = node.querySelector('.list-children');
                if (children) {
                    const isHidden = children.style.display === 'none';
                    children.style.display = isHidden ? 'block' : 'none';
                    const icon = btn.querySelector('i');
                    if (icon) {
                        icon.className = isHidden ? 'bi bi-chevron-down' : 'bi bi-chevron-right';
                    }
                }
            });
        });

        // --- 3. Toolbar Actions ---
        const toolbarEdit = document.getElementById('toolbarEdit');
        if (toolbarEdit) {
            toolbarEdit.addEventListener('click', function() {
                if (selectedCategoryId) {
                    window.location.href = (window.siteAddress || '') + 'mod_content_category/edit/' + selectedCategoryId;
                }
            });
        }

        const toolbarDelete = document.getElementById('toolbarDelete');
        if (toolbarDelete) {
            toolbarDelete.addEventListener('click', function() {
                if (selectedCategoryId && typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Are you sure?',
                        text: 'This will delete the category and all its children.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, delete',
                        cancelButtonText: 'Cancel'
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            const formData = new FormData();
                            formData.append('id', selectedCategoryId);
                            fetch((window.siteAddress || '') + 'mod_content_category/delete', {
                                method: 'POST',
                                body: formData
                            }).then(function(r) { return r.text(); })
                              .then(function(res) {
                                  if (res.includes('Success')) {
                                      window.location.reload();
                                  } else {
                                      Swal.fire('Error', res, 'error');
                                  }
                              });
                        }
                    });
                }
            });
        }

        // --- 4. Reorder Toggle ---
        const reorderToggleBtn = document.getElementById('reorderToggleBtn');
        if (reorderToggleBtn) {
            reorderToggleBtn.addEventListener('click', function() {
                isReorderActive = !isReorderActive;
                const icon = document.getElementById('reorderIcon');
                const text = document.getElementById('reorderText');
                if (icon) {
                    icon.className = isReorderActive ? 'bi bi-unlock-fill' : 'bi bi-lock-fill';
                }
                if (text) {
                    text.textContent = isReorderActive ? 'Lock Reorder' : 'Unlock Reorder';
                }
            });
        }

        // --- 5. Image Preview & Upload in Forms ---
        const editImgInput = document.getElementById('editImgInput');
        if (editImgInput) {
            editImgInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(evt) {
                        const preview = document.getElementById('editImgPreview');
                        if (preview) {
                            preview.src = evt.target.result;
                        }
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        // --- 6. Advanced Section Toggle ---
        const advancedToggle = document.querySelector('.advanced-toggle');
        if (advancedToggle) {
            advancedToggle.addEventListener('click', function() {
                const content = document.querySelector('.advanced-content');
                if (content) {
                    content.classList.toggle('open');
                }
            });
        }

        // --- 7. Root Status Selection Cards ---
        const rootCards = document.querySelectorAll('.selection-card[data-root-val]');
        rootCards.forEach(function(card) {
            card.addEventListener('click', function() {
                rootCards.forEach(function(c) { c.classList.remove('active'); });
                card.classList.add('active');
                const val = card.getAttribute('data-root-val');
                const hiddenInput = document.getElementById('rootInput');
                if (hiddenInput) {
                    hiddenInput.value = val;
                }
            });
        });
    });

    function selectCategory(id, cardEl) {
        if (selectedCategoryId === id) {
            selectedCategoryId = null;
            cardEl.classList.remove('selected');
            updateToolbar(false);
        } else {
            document.querySelectorAll('.category-node .m3-list-card').forEach(function(c) {
                c.classList.remove('selected');
            });
            selectedCategoryId = id;
            cardEl.classList.add('selected');
            updateToolbar(true);
        }
    }

    function updateToolbar(active) {
        const toolbarEdit = document.getElementById('toolbarEdit');
        const toolbarDelete = document.getElementById('toolbarDelete');
        if (toolbarEdit) {
            if (active) {
                toolbarEdit.classList.remove('disabled');
            } else {
                toolbarEdit.classList.add('disabled');
            }
        }
        if (toolbarDelete) {
            if (active) {
                toolbarDelete.classList.remove('disabled');
            } else {
                toolbarDelete.classList.add('disabled');
            }
        }
    }
})();
