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
                    if (text.trim() !== 'Oke') {
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
                    
                    // Update the baseline snapshot so "unsaved changes" warning doesn't appear 
                    // just because of this checkbox since it's already saved.
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
    // --- Social Media Manager ---
    class SocialManager {
        constructor() {
            this.listContainer = document.getElementById('socialMediaList');
            if (this.listContainer && config.socialListUrl) {
                this.loadList();
            }
        }

        async loadList() {
            try {
                const response = await fetch(config.socialListUrl);
                const payload = await response.json();
                if (payload.status === 'success') {
                    this.renderList(payload.data || []);
                } else {
                    this.listContainer.innerHTML = `<div class="settings-banner error"><span>${payload.message}</span></div>`;
                }
            } catch (error) {
                this.listContainer.innerHTML = `<div class="settings-banner error"><span>Data could not be loaded</span></div>`;
            }
        }

        renderList(items) {
            if (!items.length) {
                this.listContainer.innerHTML = `<div style="padding: 40px 20px; text-align: center; color: #64748b; background: white; border-radius: 16px; border: 1px dashed #cbd5e1;">${config.messages.noRecordsFound}</div>`;
                return;
            }

            this.listContainer.innerHTML = '';
            items.forEach((item) => {
                const row = document.createElement('div');
                row.className = 'social-card-item';
                row.style.display = 'flex';
                row.style.alignItems = 'center';
                row.style.justifyContent = 'space-between';
                row.style.padding = '16px 20px';
                row.style.background = '#ffffff';
                row.style.borderRadius = '16px';
                row.style.marginBottom = '12px';
                row.style.border = '1px solid #e2e8f0';
                row.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
                row.style.boxShadow = '0 2px 4px rgba(0,0,0,0.02)';
                
                row.onmouseenter = () => { 
                    row.style.boxShadow = '0 10px 15px -3px rgba(0,0,0,0.05), 0 4px 6px -4px rgba(0,0,0,0.05)'; 
                    row.style.transform = 'translateY(-2px)'; 
                    row.style.border = '1px solid #cbd5e1'; 
                };
                row.onmouseleave = () => { 
                    row.style.boxShadow = '0 2px 4px rgba(0,0,0,0.02)'; 
                    row.style.transform = 'translateY(0)'; 
                    row.style.border = '1px solid #e2e8f0'; 
                };

                const isChecked = item.is_active === 'y' ? 'checked' : '';
                
                const brandColors = {
                    'facebook': '#1877F2',
                    'twitter': '#0f1419',
                    'x': '#0f1419',
                    'instagram': '#E4405F',
                    'linkedin': '#0A66C2',
                    'youtube': '#FF0000',
                    'tiktok': '#000000',
                    'whatsapp': '#25D366',
                    'spotify': '#1DB954'
                };
                const platName = item.platform.toLowerCase();
                const iconColor = brandColors[platName] || 'var(--bs-primary, #4f46e5)';
                const iconBg = brandColors[platName] ? iconColor + '15' : 'rgba(79, 70, 229, 0.1)';

                row.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 20px; flex: 1; overflow: hidden;">
                        <div style="width: 48px; height: 48px; border-radius: 50%; background-color: ${iconBg}; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="${item.icon_class || 'bi bi-link'}" style="font-size: 1.4rem; color: ${iconColor};"></i>
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <strong style="display: block; font-size: 1.1rem; color: #1e293b; text-transform: capitalize; margin-bottom: 2px; font-weight: 600; letter-spacing: -0.01em;">${item.platform}</strong>
                            <a href="${item.url}" target="_blank" style="font-size: 0.9rem; color: #64748b; text-decoration: none; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; transition: color 0.2s;" onmouseover="this.style.color='${iconColor}'" onmouseout="this.style.color='#64748b'">${item.url || `<span style="opacity:0.5; font-style:italic;">${config.messages.noUrlConfigured}</span>`}</a>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 16px; margin-left: 16px; flex-shrink: 0;">
                        <label class="m3-modern-switch" title="Active / Inactive" style="margin: 0;">
                            <input type="checkbox" onchange="window.socialManager.toggleStatus(${item.id}, this.checked)" ${isChecked}>
                            <span class="m3-modern-slider"></span>
                        </label>
                        <button type="button" style="width: 40px; height: 40px; border-radius: 50%; border: none; background: #f8fafc; color: #475569; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#e2e8f0'; this.style.color='#1e293b';" onmouseout="this.style.background='#f8fafc'; this.style.color='#475569';" onclick="window.socialManager.openForm(${item.id}, '${item.platform}', '${item.url}')" title="${config.messages.edit}">
                            <i class="bi bi-pencil-square" style="font-size: 1.1rem;"></i>
                        </button>
                    </div>
                `;
                this.listContainer.appendChild(row);
            });
        }

        async openForm(id, platform, url) {
            const title = config.messages.edit;
            const platformDisplay = platform.charAt(0).toUpperCase() + platform.slice(1);
            
            const html = `
                <div style="text-align: left; margin-top: 15px;">
                    <p style="margin-bottom: 20px; font-weight: 500; font-size: 1.1rem; color: var(--m3-on-surface, #1e293b);"><i class="bi bi-link"></i> ${platformDisplay}</p>
                    <div class="m3-input-wrapper" style="margin-bottom: 20px;">
                        <input type="url" id="swal-url" class="m3-input-lg" placeholder=" " value="${url}">
                        <label class="m3-floating-label">URL / Link</label>
                    </div>
                </div>
            `;

            const result = await Swal.fire({
                title: title,
                html: html,
                showCancelButton: true,
                confirmButtonText: config.messages.save,
                cancelButtonText: config.messages.cancel,
                customClass: {
                    container: 'social-swal-container',
                    popup: 'social-swal-popup'
                },
                preConfirm: () => {
                    const u = document.getElementById('swal-url').value.trim();
                    return { url: u };
                }
            });

            if (result.isConfirmed) {
                this.saveItem(id, result.value.url);
            }
        }

        async saveItem(id, url) {
            const body = new URLSearchParams();
            body.set('id', id);
            body.set('url', url);

            try {
                const response = await fetch(config.socialSaveUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body.toString()
                });
                const payload = await response.json();
                if (payload.status === 'success') {
                    if (typeof showSnackbar === 'function') showSnackbar(payload.message, 'success');
                    this.loadList();
                } else {
                    Swal.fire('Error', payload.message, 'error');
                }
            } catch (error) {
                Swal.fire('Error', 'Connection error', 'error');
            }
        }



        async toggleStatus(id, isActive) {
            const body = new URLSearchParams();
            body.set('id', id);
            body.set('is_active', isActive ? 'y' : 'n');

            try {
                const response = await fetch(config.socialToggleUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body.toString()
                });
                const payload = await response.json();
                if (payload.status === 'success') {
                    if (typeof showSnackbar === 'function') showSnackbar(payload.message, 'success');
                } else {
                    if (typeof showSnackbar === 'function') showSnackbar(payload.message, 'error');
                    this.loadList();
                }
            } catch (error) {
                if (typeof showSnackbar === 'function') showSnackbar('Connection error', 'error');
                this.loadList();
            }
        }
    }

    if (config && config.socialListUrl) {
        window.socialManager = new SocialManager();
    }

})();
