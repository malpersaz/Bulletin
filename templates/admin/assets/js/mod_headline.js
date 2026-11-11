/**
 * mod_headline.js - Phone-first admin behavior for the legacy
 * mod_headline controller surfaces (list, add, edit).
 * All configuration is read from data-* attributes; zero inline handlers.
 */
(function () {
    'use strict';

    function readMessages(element) {
        try {
            return JSON.parse((element && element.dataset.messages) || '{}');
        } catch (error) {
            return {};
        }
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

    function escapeHtml(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /* ------------------------------------------------------------------ */
    /* List surface                                                        */
    /* ------------------------------------------------------------------ */

    function initListPage() {
        const shell = document.getElementById('headlineShell');
        if (!shell) {
            return;
        }

        const cfg = {
            datatableUrl: shell.dataset.datatableUrl,
            editUrl: shell.dataset.editUrl,
            deleteUrl: shell.dataset.deleteUrl,
            addUrl: shell.dataset.addUrl,
            messages: readMessages(shell)
        };

        const listEl = document.getElementById('headlineList');
        const emptyEl = document.getElementById('headlineEmpty');
        const loaderEl = document.getElementById('headlineLoader');
        const toolbarEdit = document.getElementById('headlineToolbarEdit');
        const toolbarDelete = document.getElementById('headlineToolbarDelete');
        let selectedId = null;
        let searchValue = '';

        function setLoading(active) {
            if (loaderEl) {
                loaderEl.classList.toggle('is-hidden', !active);
            }
        }

        function cardHtml(row) {
            const id = row[1];
            const name = row[2];
            const imageHtml = row[3] || '';
            const lang = row[4] || '';
            const createdAt = row[5] || '';
            const statusHtml = row[6] || '';
            const srcMatch = imageHtml.match(/src="([^"]+)"/);
            const thumbSrc = srcMatch ? srcMatch[1] : '';
            const isPublished = statusHtml.indexOf('label-success') !== -1;
            const statusLabel = isPublished ? cfg.messages.status_published : cfg.messages.status_draft;
            const searchText = [id, name, lang, createdAt, statusLabel].join(' ').toLowerCase();

            return '' +
                '<article class="headline-card" data-id="' + escapeHtml(id) + '" data-search="' + escapeHtml(searchText) + '" tabindex="0" role="button">' +
                '  <div class="headline-thumb">' +
                (thumbSrc ? '<img src="' + escapeHtml(thumbSrc) + '" alt="' + escapeHtml(name) + '" loading="lazy">' : '<i class="bi bi-image"></i>') +
                '  </div>' +
                '  <div class="headline-card-body">' +
                '    <h3 class="headline-name">' + escapeHtml(name) + '</h3>' +
                '    <div class="headline-meta">' +
                '      <span>#' + escapeHtml(id) + '</span>' +
                '      <span>' + escapeHtml(lang.toUpperCase()) + '</span>' +
                '      <span>' + escapeHtml(createdAt) + '</span>' +
                '    </div>' +
                '  </div>' +
                '  <span class="headline-status-pill ' + (isPublished ? 'is-published' : 'is-draft') + '">' + escapeHtml(statusLabel) + '</span>' +
                '</article>';
        }

        function renderCards(rows) {
            if (!listEl) {
                return;
            }
            listEl.innerHTML = rows.map(cardHtml).join('');
            applyFilter(searchValue);
            updateEmptyState();
        }

        function updateEmptyState() {
            if (!emptyEl) {
                return;
            }
            const visible = listEl ? listEl.querySelectorAll('.headline-card:not(.is-filtered-out)').length : 0;
            emptyEl.classList.toggle('is-hidden', visible > 0);
        }

        function applyFilter(value) {
            const query = value.trim().toLowerCase();
            if (!listEl) {
                return;
            }
            listEl.querySelectorAll('.headline-card').forEach(function (card) {
                const match = query === '' || (card.dataset.search || '').indexOf(query) !== -1;
                card.classList.toggle('is-filtered-out', !match);
            });
            updateEmptyState();
        }

        function loadRecords() {
            setLoading(true);
            const body = new URLSearchParams();
            body.append('start', '0');
            body.append('length', '100');
            body.append('draw', '1');
            body.append('order[0][column]', '1');
            body.append('order[0][dir]', 'desc');
            if (searchValue) {
                body.append('search[name]', searchValue);
            }
            fetch(cfg.datatableUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (json) {
                    setLoading(false);
                    renderCards(json.data || []);
                })
                .catch(function () {
                    setLoading(false);
                    renderCards([]);
                });
        }

        function selectCard(card) {
            selectedId = card.dataset.id;
            listEl.querySelectorAll('.headline-card').forEach(function (item) {
                item.classList.toggle('is-selected', item === card);
            });
            if (toolbarEdit) {
                toolbarEdit.disabled = false;
            }
            if (toolbarDelete) {
                toolbarDelete.disabled = false;
            }
        }

        function clearSelection() {
            selectedId = null;
            listEl.querySelectorAll('.headline-card').forEach(function (item) {
                item.classList.remove('is-selected');
            });
            if (toolbarEdit) {
                toolbarEdit.disabled = true;
            }
            if (toolbarDelete) {
                toolbarDelete.disabled = true;
            }
        }

        function deleteSelected() {
            if (!selectedId) {
                return;
            }
            Swal.fire({
                icon: 'warning',
                title: cfg.messages.confirm_delete,
                showCancelButton: true,
                confirmButtonText: cfg.messages.confirm_yes,
                cancelButtonText: cfg.messages.confirm_cancel,
                confirmButtonColor: '#b3261e'
            }).then(function (result) {
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
                    .then(function (response) {
                        return response.text();
                    })
                    .then(function (text) {
                        if (text.indexOf('Success') === -1) {
                            notify(cfg.messages.delete_error, 'error');
                            return;
                        }
                        notify(cfg.messages.delete_success, 'success');
                        clearSelection();
                        loadRecords();
                    })
                    .catch(function () {
                        notify(cfg.messages.delete_error, 'error');
                    });
            });
        }

        if (listEl) {
            listEl.addEventListener('click', function (event) {
                const card = event.target.closest('.headline-card');
                if (card) {
                    selectCard(card);
                }
            });
        }

        if (toolbarEdit) {
            toolbarEdit.addEventListener('click', function () {
                if (selectedId) {
                    window.location.href = cfg.editUrl + selectedId;
                }
            });
        }

        if (toolbarDelete) {
            toolbarDelete.addEventListener('click', deleteSelected);
        }

        const fab = document.getElementById('mainFab');
        if (fab) {
            fab.addEventListener('click', function () {
                window.location.href = cfg.addUrl;
            });
        }

        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            let timer;
            searchInput.addEventListener('input', function () {
                clearTimeout(timer);
                const value = this.value;
                timer = setTimeout(function () {
                    searchValue = value;
                    applyFilter(value);
                }, 180);
            });
        }

        loadRecords();
    }

    /* ------------------------------------------------------------------ */
    /* Form surfaces (add / edit)                                          */
    /* ------------------------------------------------------------------ */

    function initFormPage() {
        const form = document.getElementById('headlineForm');
        if (!form) {
            return;
        }

        const cfg = readMessages(document.getElementById('headlineFormConfig'));

        const feedback = document.getElementById('headlineFormFeedback');
        if (feedback) {
            const hasError = feedback.dataset.hasError === '1';
            const hasSuccess = feedback.dataset.hasSuccess === '1';
            const message = feedback.dataset.message || '';
            let errors = [];
            try {
                errors = JSON.parse(feedback.dataset.errors || '[]');
            } catch (error) {
                errors = [];
            }

            if (hasError && typeof Swal !== 'undefined') {
                let html = '<div>' + escapeHtml(message) + '</div>';
                if (errors.length) {
                    html += '<ul>' + errors.map(function (item) { return '<li>' + escapeHtml(item) + '</li>'; }).join('') + '</ul>';
                }
                Swal.fire({
                    icon: 'warning',
                    title: cfg.form_error_title || '',
                    html: html,
                    confirmButtonColor: '#b3261e'
                });
            } else if (hasSuccess) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: message,
                        timer: 1600,
                        showConfirmButton: false
                    });
                }
                setTimeout(function () {
                    window.location.href = feedback.dataset.redirect;
                }, 1800);
            }
        }

        // Status selection cards drive the hidden status input.
        const statusInput = form.querySelector('input[name="status"]');
        form.querySelectorAll('.selection-card[data-status-value]').forEach(function (card) {
            card.addEventListener('click', function () {
                form.querySelectorAll('.selection-card[data-status-value]').forEach(function (item) {
                    item.classList.toggle('active', item === card);
                });
                if (statusInput) {
                    statusInput.value = card.dataset.statusValue;
                }
            });
        });

        const cancelButton = document.getElementById('headlineCancelBtn');
        if (cancelButton) {
            cancelButton.addEventListener('click', function () {
                window.location.href = (typeof SITE_URL !== 'undefined' ? SITE_URL : '/') + 'mod_headline/list';
            });
        }


        initImageField(form, cfg);
    }

    function initImageField(form, cfg) {
        const fileInput = form.querySelector('input[type="file"][name="image"]');
        const dropzone = document.getElementById('headlineUploadArea');
        const preview = document.getElementById('headlineImagePreview');
        const previewImg = preview ? preview.querySelector('img') : null;
        const removeButton = document.getElementById('headlineRemoveImageBtn');
        const recordIdInput = document.getElementById('headlineRecordId');
        const removeUrl = form.dataset.removeImageUrl || '';

        if (removeButton && recordIdInput && removeUrl) {
            removeButton.addEventListener('click', function () {
                Swal.fire({
                    icon: 'warning',
                    title: cfg.confirm_remove_image || '',
                    showCancelButton: true,
                    confirmButtonText: cfg.confirm_yes || '',
                    cancelButtonText: cfg.cancel || '',
                    confirmButtonColor: '#b3261e'
                }).then(function (result) {
                    if (!result.isConfirmed) {
                        return;
                    }
                    const body = new URLSearchParams();
                    body.append('id', recordIdInput.value);
                    fetch(removeUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: body.toString()
                    })
                        .then(function (response) {
                            return response.text();
                        })
                        .then(function (text) {
                            if (text.indexOf('Success') === -1) {
                                notify(cfg.image_remove_error || '', 'error');
                                return;
                            }
                            if (preview && dropzone) {
                                preview.classList.add('is-hidden');
                                dropzone.classList.remove('is-hidden');
                            }
                            notify(cfg.image_removed || '', 'success');
                        })
                        .catch(function () {
                            notify(cfg.image_remove_error || '', 'error');
                        });
                });
            });
        }

        if (!fileInput || !dropzone) {
            return;
        }

        fileInput.addEventListener('change', function () {
            if (!fileInput.files || !fileInput.files[0]) {
                return;
            }
            if (typeof Cropper === 'undefined') {
                notify(cfg.crop_error_plugin || '', 'error');
                fileInput.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = function (event) {
                Swal.fire({
                    title: cfg.crop_title || '',
                    html: '<div class="headline-crop-frame"><img id="headlineCropImage" src="' + event.target.result + '" alt=""></div>',
                    width: '86%',
                    allowOutsideClick: false,
                    showCancelButton: true,
                    confirmButtonText: cfg.crop_finish || '',
                    cancelButtonText: cfg.cancel || '',
                    didOpen: function () {
                        const image = document.getElementById('headlineCropImage');
                        new Cropper(image, { aspectRatio: 2000 / 750, viewMode: 1 });
                    },
                    preConfirm: function () {
                        const image = document.getElementById('headlineCropImage');
                        const cropper = image && image.cropper;
                        if (!cropper) {
                            return false;
                        }
                        return new Promise(function (resolve) {
                            const canvas = cropper.getCroppedCanvas({ width: 2000, height: 750 });
                            if (!canvas) {
                                Swal.showValidationMessage(cfg.crop_error_canvas || '');
                                resolve(false);
                                return;
                            }
                            canvas.toBlob(function (blob) {
                                resolve(blob);
                            }, 'image/jpeg', 0.9);
                        });
                    }
                }).then(function (result) {
                    if (result.isConfirmed && result.value) {
                        const file = new File([result.value], 'headline.jpg', { type: 'image/jpeg' });
                        const transfer = new DataTransfer();
                        transfer.items.add(file);
                        fileInput.files = transfer.files;
                        if (previewImg) {
                            previewImg.src = URL.createObjectURL(result.value);
                        }
                        if (preview) {
                            preview.classList.remove('is-hidden');
                            dropzone.classList.add('is-hidden');
                        }
                    } else {
                        fileInput.value = '';
                    }
                });
            };
            reader.readAsDataURL(fileInput.files[0]);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initListPage();
        initFormPage();
    });
})();
