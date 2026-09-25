(() => {
    const config = window.modSettingsConfig || null;
    const form = document.getElementById('settingsForm');
    const langSelect = document.getElementById('selectedLang');
    const generateButton = document.getElementById('generateAssetsButton');
    const assetsSection = document.getElementById('assetsSection');

    if (!config || !form) {
        return;
    }

    let isSubmitting = false;
    let userEditedForm = false;
    let baselineSnapshot = '';

    const serializeField = (field) => {
        if (!field || field.disabled || field.id === 'selectedLang') {
            return null;
        }

        const key = field.name || field.id;
        if (!key) {
            return null;
        }

        if (field.type === 'file') {
            const files = Array.from(field.files || []).map((file) => `${file.name}:${file.size}:${file.lastModified}`);
            return `${key}=${files.join('|')}`;
        }

        if (field.type === 'checkbox' || field.type === 'radio') {
            return `${key}=${field.checked ? '1' : '0'}`;
        }

        return `${key}=${field.value}`;
    };

    const serializeForm = () => {
        return Array.from(form.elements)
            .map(serializeField)
            .filter(Boolean)
            .join('&');
    };

    const captureBaseline = () => {
        baselineSnapshot = serializeForm();
    };

    const hasUnsavedChanges = () => userEditedForm && serializeForm() !== baselineSnapshot;

    const renderEmptyPreview = (preview) => {
        if (!preview) {
            return;
        }

        const wrapper = document.createElement('div');
        wrapper.className = 'asset-empty';

        const icon = document.createElement('i');
        icon.className = 'bi bi-image';

        const text = document.createElement('span');
        text.textContent = config.messages.notUploaded;

        wrapper.appendChild(icon);
        wrapper.appendChild(text);

        preview.innerHTML = '';
        preview.appendChild(wrapper);
    };

    captureBaseline();
    window.setTimeout(captureBaseline, 0);
    window.setTimeout(captureBaseline, 300);
    window.setTimeout(captureBaseline, 1000);
    window.setTimeout(captureBaseline, 1800);

    form.querySelectorAll('input, textarea, select').forEach((field) => {
        if (field.id === 'selectedLang') {
            return;
        }

        const markTouched = (event) => {
            if (!event.isTrusted) {
                return;
            }

            field.dataset.userTouched = '1';
        };

        const markEdited = (event) => {
            if (!event.isTrusted || field.dataset.userTouched !== '1') {
                return;
            }

            userEditedForm = true;
        };

        field.addEventListener('focusin', markTouched);
        field.addEventListener('pointerdown', markTouched);
        field.addEventListener('keydown', markTouched);
        field.addEventListener('input', markEdited);
        field.addEventListener('change', markEdited);
    });

    form.addEventListener('submit', () => {
        isSubmitting = true;
    });

    const beforeUnloadHandler = (event) => {
        if (isSubmitting || !hasUnsavedChanges()) {
            return;
        }

        event.preventDefault();
        event.returnValue = '';
    };

    window.addEventListener('beforeunload', beforeUnloadHandler);

    if (langSelect) {
        langSelect.addEventListener('change', async function handleLanguageChange() {
            const nextUrl = config.editBaseUrl + this.value;

            if (!hasUnsavedChanges()) {
                window.removeEventListener('beforeunload', beforeUnloadHandler);
                window.location.assign(nextUrl);
                return;
            }

            await Swal.fire({
                icon: 'warning',
                text: config.messages.unsavedChanges,
                confirmButtonText: config.messages.confirm
            });

            this.value = config.selectedLang;
        });
    }

    async function generateAssets() {
        if (!generateButton) {
            return;
        }

        const formData = new FormData(form);
        generateButton.disabled = true;

        try {
            const response = await fetch(config.generateUrl, {
                method: 'POST',
                body: formData
            });

            const payload = await response.json();
            if (!payload || payload.status !== 'success') {
                throw new Error(payload && payload.message ? payload.message : config.messages.genericError);
            }

            await Swal.fire({
                icon: 'success',
                text: payload.message,
                confirmButtonText: config.messages.confirm
            });

            window.location.hash = 'assetsSection';
            window.location.reload();
        } catch (error) {
            await Swal.fire({
                icon: 'error',
                text: String(error.message || error),
                confirmButtonText: config.messages.confirm
            });

            if (assetsSection) {
                assetsSection.open = true;
                assetsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        } finally {
            generateButton.disabled = false;
        }
    }

    const updateFileLabel = (field, input, previewType, label) => {
        const fileName = input.files && input.files[0] ? input.files[0].name : config.messages.noFileSelected;
        const target = document.getElementById('file-name-' + field);
        const metaTarget = document.getElementById('file-meta-' + field);

        if (target) target.textContent = fileName;
        if (metaTarget) metaTarget.textContent = '';

        if (!input.files || !input.files[0]) {
            return;
        }

        userEditedForm = true;

        const preview = document.getElementById('asset-preview-' + field);
        if (!preview) {
            return;
        }

        const file = input.files[0];

        if (previewType === 'image' && file.type.startsWith('image/')) {
            const objectUrl = URL.createObjectURL(file);
            const img = new Image();

            img.onload = () => {
                img.alt = String(label || '');
                preview.innerHTML = '';
                preview.appendChild(img);
                if (metaTarget) {
                    metaTarget.textContent = `${img.naturalWidth} x ${img.naturalHeight}px`;
                }
                URL.revokeObjectURL(objectUrl);
            };

            img.onerror = () => URL.revokeObjectURL(objectUrl);
            img.src = objectUrl;
            return;
        }

        const fileBox = document.createElement('div');
        fileBox.className = 'asset-file-box';

        const icon = document.createElement('i');
        icon.className = 'bi bi-file-earmark-code';

        const extension = document.createElement('strong');
        extension.textContent = (file.name.split('.').pop() || '').toUpperCase();

        fileBox.appendChild(icon);
        fileBox.appendChild(extension);

        preview.innerHTML = '';
        preview.appendChild(fileBox);
    };

    const imageRemoveImage = (lang, field) => {
        Swal.fire({
            icon: 'warning',
            text: config.messages.confirmDelete,
            showCancelButton: true,
            confirmButtonText: config.messages.delete,
            cancelButtonText: config.messages.cancel
        }).then((result) => {
            if (!result.isConfirmed) return;

            userEditedForm = true;

            const body = new URLSearchParams();
            body.set('lang', lang);
            body.set('field', field);

            fetch(config.removeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: body.toString()
            })
                .then((response) => response.text())
                .then((text) => {
                    if (text.trim() !== 'Success') {
                        throw new Error(text);
                    }

                    const input = document.getElementById('current-' + field);
                    const preview = document.getElementById('asset-preview-' + field);
                    const card = document.getElementById('asset-card-' + field);
                    const fileNameTarget = document.getElementById('file-name-' + field);
                    const metaTarget = document.getElementById('file-meta-' + field);

                    if (input) input.value = '';
                    if (fileNameTarget) fileNameTarget.textContent = config.messages.noFileSelected;
                    if (metaTarget) metaTarget.textContent = '';
                    renderEmptyPreview(preview);

                    if (card) {
                        const removeBtn = card.querySelector('.asset-remove-btn');
                        if (removeBtn) removeBtn.remove();
                    }

                    showSnackbar(config.messages.deleteSuccess, 'success');
                    window.setTimeout(() => window.location.reload(), 300);
                })
                .catch((error) => {
                    showSnackbar(`${config.messages.deleteError} ${String(error.message || error)}`, 'error');
                });
        });
    };

    form.querySelectorAll('[data-asset-input]').forEach((input) => {
        input.addEventListener('change', () => {
            updateFileLabel(
                input.dataset.field || '',
                input,
                input.dataset.previewType || '',
                input.dataset.label || ''
            );
        });
    });

    form.querySelectorAll('[data-remove-asset]').forEach((button) => {
        button.addEventListener('click', () => {
            imageRemoveImage(button.dataset.lang || config.selectedLang, button.dataset.field || '');
        });
    });

    document.querySelectorAll('[data-skip-unsaved-warning]').forEach((link) => {
        link.addEventListener('click', async (event) => {
            if (!hasUnsavedChanges()) {
                window.removeEventListener('beforeunload', beforeUnloadHandler);
                return;
            }

            event.preventDefault();

            const result = await Swal.fire({
                icon: 'warning',
                text: config.messages.unsavedChanges,
                showCancelButton: true,
                confirmButtonText: config.messages.leavePage,
                cancelButtonText: config.messages.stayOnPage
            });

            if (!result.isConfirmed) {
                return;
            }

            isSubmitting = true;
            window.removeEventListener('beforeunload', beforeUnloadHandler);
            window.location.href = link.href;
        });
    });

    if (generateButton) {
        generateButton.addEventListener('click', generateAssets);
    }

    const showLogoToggle = document.getElementById('show_logo');
    if (showLogoToggle && config.toggleLogoUrl) {
        showLogoToggle.addEventListener('change', async function() {
            const newValue = this.checked ? 'y' : 'n';
            const body = new URLSearchParams();
            body.set('show_logo', newValue);

            try {
                const response = await fetch(config.toggleLogoUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body.toString()
                });
                const payload = await response.json();
                
                if (payload && payload.status === 'success') {
                    if (typeof showSnackbar === 'function') {
                        showSnackbar(payload.message, 'success');
                    }
                    
                    // Update the baseline snapshot so unsaved changes warning does not appear 
                    // just because of this checkbox since it is already saved.
                     userEditedForm = false;
                     captureBaseline();
                } else {
                    if (typeof showSnackbar === 'function') {
                        showSnackbar(payload.message || 'Error occurred', 'error');
                    }
                    // Revert UI toggle if server request fails
                    this.checked = !this.checked;
                }
            } catch (error) {
                if (typeof showSnackbar === 'function') {
                    showSnackbar('Connection error', 'error');
                }
                this.checked = !this.checked;
            }
        });
    }

    if (window.location.hash === '#assetsSection' && assetsSection) {
        assetsSection.open = true;
        assetsSection.scrollIntoView({ behavior: 'auto', block: 'start' });
    }


    /**
     * Set header display style layout type.
     * @param {string} type
     */
    const setHeaderDisplayStyle = (type) => {
        const input = document.getElementById('logoTypeInput');
        if (input) {
            input.value = type;
        }

        const cards = document.querySelectorAll('.header-style-options .selection-card');
        cards.forEach((card) => {
            card.classList.remove('active');
        });

        const targetMap = {
            'square': 'opt-style-1',
            'rectangle': 'opt-style-2',
            'square_name': 'opt-style-3',
            'rectangle_name': 'opt-style-4',
            'name_only': 'opt-style-5'
        };

        const targetId = targetMap[type];
        if (targetId) {
            const targetCard = document.getElementById(targetId);
            if (targetCard) {
                targetCard.classList.add('active');
            }
        }

        userEditedForm = true;
    };

    window.setHeaderDisplayStyle = setHeaderDisplayStyle;

    /**
     * Remove asset wrapper for window calls.
     * @param {string} field
     */
    window.removeAsset = (field) => {
        const lang = config ? config.selectedLang : 'tr';
        imageRemoveImage(lang, field);
    };

    /**
     * Process selected image with Cropper modal.
     * @param {HTMLInputElement} input
     * @param {number} type
     */
    window.processSelectedImage = (input, type) => {
        if (!input || !input.files || !input.files[0]) {
            return;
        }

        const file = input.files[0];
        const reader = new FileReader();

        reader.onload = (event) => {
            const imageUrl = event.target.result;
            let title = (config && config.messages && config.messages.cropSquare) || 'Crop Image';
            let aspectRatio = 1;
            let targetHiddenId = 'croppedSquare';
            let targetAreaId = 'areaSquare';
            let targetWidth = 512;
            let targetHeight = 512;

            if (type === 2) {
                title = (config && config.messages && config.messages.cropRect) || 'Crop Rectangle Logo';
                aspectRatio = 3 / 1;
                targetHiddenId = 'croppedRect';
                targetAreaId = 'areaRect';
                targetWidth = 600;
                targetHeight = 200;
            } else if (type === 3) {
                title = (config && config.messages && config.messages.cropSymbol) || 'Crop Symbol Logo';
                aspectRatio = 1;
                targetHiddenId = 'croppedSymbol';
                targetAreaId = 'areaSymbol';
                targetWidth = 512;
                targetHeight = 512;
            }

            let cropperInstance = null;

            Swal.fire({
                title: title,
                html: `<div style="max-height: 400px; overflow: hidden;"><img id="cropperTargetImage" src="${imageUrl}" style="max-width: 100%; display: block;"></div>`,
                showCancelButton: true,
                confirmButtonText: (config && config.messages && config.messages.confirm) || 'OK',
                cancelButtonText: (config && config.messages && config.messages.cancel) || 'Cancel',
                didOpen: () => {
                    const img = document.getElementById('cropperTargetImage');
                    if (img && typeof Cropper !== 'undefined') {
                        cropperInstance = new Cropper(img, {
                            aspectRatio: aspectRatio,
                            viewMode: 1,
                            autoCropArea: 1
                        });
                    }
                },
                preConfirm: () => {
                    if (cropperInstance) {
                        const canvas = cropperInstance.getCroppedCanvas({
                            width: targetWidth,
                            height: targetHeight
                        });
                        if (canvas) {
                            return canvas.toDataURL('image/png');
                        }
                    }
                    return null;
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    const croppedData = result.value;
                    const hiddenInput = document.getElementById(targetHiddenId);
                    if (hiddenInput) {
                        hiddenInput.value = croppedData;
                    }

                    const areaContainer = document.getElementById(targetAreaId);
                    if (areaContainer) {
                        areaContainer.innerHTML = `<img src="${croppedData}" style="width:100%; height:100%; object-fit:contain; border-radius:8px;">`;
                    }

                    userEditedForm = true;
                }
                input.value = '';
            });
        };

        reader.readAsDataURL(file);
    };

})();
