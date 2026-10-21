/**
 * mod_video_embed_edit.js
 * Externalized JavaScript controller for the video embed edit view.
 * Complies strictly with AGENTS.md Rule 2 (Strict Code Separation Mandate).
 */

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('videoEditForm') || document.getElementById('videoAddForm');
    if (!form) return;

    const siteAddress = form.dataset.siteAddress || (window.SITE_URL || '/');
    const videoId = form.dataset.id || '{id}';
    const embedInput = document.getElementById('embedInput');
    const embedPreview = document.getElementById('embedPreview');
    const nameInput = document.getElementById('nameInput');
    const originalInput = form.querySelector('[name="original"]');
    const durationInput = form.querySelector('[name="sure"]');
    const dateInput = document.getElementById('dateInput');
    const contentInput = document.getElementById('videoContent');
    const titleInput = document.getElementById('titleInput');
    const keywordsInput = document.getElementById('keywordsInput');
    const descriptionInput = document.getElementById('descriptionInput');
    const seoPreviewInput = document.getElementById('seoPreviewInput');
    const imageInput = document.getElementById('imageInput');
    const autofillImageInput = document.getElementById('autofillImageInput');
    const imageUploadArea = document.getElementById('imageUploadArea');
    const autofillBtn = document.getElementById('autofillButton');
    const autofillStatus = document.getElementById('autofillStatus');

    if (dateInput && !dateInput.value) {
        const now = new Date();
        const local = new Date(now.getTime() - (now.getTimezoneOffset() * 60000));
        dateInput.value = local.toISOString().slice(0, 16);
    }

    // Localized strings from form data attributes
    const i18n = {
        ok: form.dataset.i18nOk || 'OK',
        yes: form.dataset.i18nYes || 'Yes',
        no: form.dataset.i18nNo || 'No',
        cancel: form.dataset.i18nCancel || 'Cancel',
        save: form.dataset.i18nSave || 'Save',
        information: form.dataset.i18nInfo || 'Information!',
        error: form.dataset.i18nError || 'Error Occurred!',
        success: form.dataset.i18nSuccess || 'Operation Successful!',
        imageUpdateConfirm: form.dataset.i18nImageUpdateConfirm || 'If you change the image of this video, the old image will be deleted from the system!',
        removeImageTitle: form.dataset.i18nRemoveImageTitle || 'Are you sure you want to remove this image?',
        removeImageConfirm: form.dataset.i18nRemoveImageConfirm || 'Are you sure you want to delete this video image?',
        removeImageSuccess: form.dataset.i18nRemoveImageSuccess || 'Image Removed',
        unsupportedEmoji: form.dataset.i18nUnsupportedEmoji || 'Emoji or unsupported characters are not allowed in the Content field.',
        autofillMissing: form.dataset.i18nAutofillMissing || 'Paste a YouTube URL or embed code first.',
        autofillLoading: form.dataset.i18nAutofillLoading || 'Fetching video details from YouTube...',
        autofillSuccess: form.dataset.i18nAutofillSuccess || 'Video details imported successfully.',
        autofillError: form.dataset.i18nAutofillError || 'Video details could not be imported.',
        uploadPlaceholder: form.dataset.i18nUploadText || 'Tap or Drag to Upload Image',
        previewText: form.dataset.i18nPreviewText || 'Preview',
        fallbackSlug: form.dataset.i18nFallbackSlug || 'video'
    };

    /**
     * Clean meta text from forbidden characters
     */
    function cleanMetaText(value) {
        let cleaned = (value || '').toString();
        cleaned = cleaned.replace(/[^a-zA-Z\u0131\u011f\u00fc\u015f\u00f6\u00e7\u0130\u011e\u00dc\u015e\u00d6\u00c7\u00ee\u00ea\u00fb\u00ce\u00ca\u00db1234567890"' ]+/g, ' ');
        cleaned = cleaned.trim();
        for (let i = 0; i <= 10; i += 1) {
            cleaned = cleaned.replace(/  /g, ' ');
        }
        return cleaned;
    }

    /**
     * Slugify string for Wikipedia-style URL preview
     */
    function slugifyVideoName(value) {
        let slug = cleanMetaText(value);
        slug = slug.replace(/\s+/g, '_');
        slug = slug.replace(/\u0130/g, 'i');
        for (let i = 0; i <= 5; i += 1) {
            slug = slug.replace(/__/g, '_');
        }
        return slug || i18n.fallbackSlug;
    }

    /**
     * Update Live SEO URL preview
     */
    function updateSeoPreview() {
        if (!seoPreviewInput || !nameInput) return;
        const slug = slugifyVideoName(nameInput.value);
        seoPreviewInput.value = `${siteAddress}video/${videoId}-${slug}`;
    }

    /**
     * Update Live Embed Preview
     */
    function updateEmbedPreview() {
        if (!embedPreview || !embedInput) return;
        const raw = (embedInput.value || '').trim();
        if (raw) {
            embedPreview.innerHTML = raw;
            embedPreview.classList.add('has-content');
        } else {
            embedPreview.innerHTML = `
                <div class="embed-placeholder">
                    <i class="bi bi-play-circle embed-placeholder-icon"></i>
                    <span class="embed-placeholder-text">${i18n.previewText}</span>
                </div>
            `;
            embedPreview.classList.remove('has-content');
        }
    }

    // Embed input listeners
    if (embedInput) {
        embedInput.addEventListener('input', updateEmbedPreview);
    }

    // Name input auto-fills SEO fields
    if (nameInput) {
        nameInput.addEventListener('input', function() {
            const val = this.value;
            const cleanedVal = cleanMetaText(val);

            if (titleInput && (!titleInput.value || titleInput.dataset.autofilled !== 'false')) {
                titleInput.value = cleanedVal;
                titleInput.dataset.autofilled = 'true';
            }
            if (keywordsInput && (!keywordsInput.value || keywordsInput.dataset.autofilled !== 'false')) {
                keywordsInput.value = cleanMetaText(val).replace(/\s+/g, ',').toLowerCase().replace(/\u0130/g, 'i');
                keywordsInput.dataset.autofilled = 'true';
            }
            if (descriptionInput && (!descriptionInput.value || descriptionInput.dataset.autofilled !== 'false')) {
                descriptionInput.value = cleanedVal;
                descriptionInput.dataset.autofilled = 'true';
            }
            updateSeoPreview();
        });
    }

    // Mark SEO fields as manual if touched directly
    [titleInput, keywordsInput, descriptionInput].forEach(el => {
        if (el) {
            el.addEventListener('input', () => {
                el.dataset.autofilled = 'false';
            });
        }
    });

    /**
     * YouTube Metadata Autofill
     */
    if (autofillBtn) {
        autofillBtn.addEventListener('click', async () => {
            const embedVal = embedInput ? embedInput.value.trim() : '';
            const originalVal = originalInput ? originalInput.value.trim() : '';
            const source = embedVal || originalVal;

            if (!source) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        text: i18n.autofillMissing,
                        confirmButtonText: i18n.ok,
                        confirmButtonColor: '#ba1a1a'
                    });
                } else {
                    alert(i18n.autofillMissing);
                }
                return;
            }

            autofillBtn.classList.add('is-loading');
            const originalBtnHtml = autofillBtn.innerHTML;
            autofillBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> <span>${i18n.autofillLoading}</span>`;
            if (autofillStatus) autofillStatus.textContent = i18n.autofillLoading;

            try {
                const fd = new FormData();
                fd.append('embed', embedVal);
                fd.append('original', originalVal);

                const response = await fetch(`${siteAddress}mod_video_embed/autofill_metadata`, {
                    method: 'POST',
                    body: fd
                });
                const res = await response.json();

                if (res.status === 'success' && res.data) {
                    const data = res.data;
                    if (data.name && nameInput) nameInput.value = data.name;
                    if (data.original && originalInput) originalInput.value = data.original;
                    if (data.duration && durationInput) durationInput.value = data.duration;
                    if (data.embed && embedInput) embedInput.value = data.embed;
                    if (data.content && contentInput) contentInput.value = data.content;
                    if (data.title && titleInput) titleInput.value = data.title;
                    if (data.keywords && keywordsInput) keywordsInput.value = data.keywords;
                    if (data.description && descriptionInput) descriptionInput.value = data.description;

                    if (data.image && autofillImageInput) autofillImageInput.value = data.image;
                    if (data.image_url && imageUploadArea) {
                        imageUploadArea.innerHTML = `
                            <div class="current-image-wrapper">
                                <img src="${data.image_url}" class="current-image" alt="${data.name || 'Preview'}">
                                <button type="button" class="remove-img-btn" id="btnRemoveExistingImage" aria-label="${i18n.removeImageTitle}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        `;
                        bindRemoveImageButton();
                    }

                    updateEmbedPreview();
                    updateSeoPreview();

                    if (autofillStatus) autofillStatus.textContent = i18n.autofillSuccess;
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            text: res.message || i18n.autofillSuccess,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                } else {
                    const errMsg = res.message || i18n.autofillError;
                    if (autofillStatus) autofillStatus.textContent = errMsg;
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            text: errMsg,
                            confirmButtonText: i18n.ok,
                            confirmButtonColor: '#ba1a1a'
                        });
                    }
                }
            } catch (err) {
                console.error('Autofill error:', err);
                if (autofillStatus) autofillStatus.textContent = i18n.autofillError;
            } finally {
                autofillBtn.classList.remove('is-loading');
                autofillBtn.innerHTML = originalBtnHtml;
            }
        });
    }

    /**
     * Image Upload & Preview Handling
     */
    if (imageUploadArea && imageInput) {
        imageUploadArea.addEventListener('click', async (e) => {
            // Ignore if clicking remove button
            if (e.target.closest('.remove-img-btn')) return;

            const hasExisting = Boolean(imageUploadArea.querySelector('.current-image'));
            if (hasExisting && typeof Swal !== 'undefined') {
                const result = await Swal.fire({
                    title: i18n.information,
                    text: i18n.imageUpdateConfirm,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ba1a1a',
                    cancelButtonColor: '#6e7881',
                    confirmButtonText: i18n.ok,
                    cancelButtonText: i18n.cancel
                });
                if (result.isConfirmed) {
                    imageInput.click();
                }
            } else {
                imageInput.click();
            }
        });

        imageInput.addEventListener('change', () => {
            if (imageInput.files && imageInput.files[0]) {
                if (autofillImageInput) autofillImageInput.value = '';
                const reader = new FileReader();
                reader.onload = (e) => {
                    imageUploadArea.innerHTML = `
                        <div class="current-image-wrapper">
                            <img src="${e.target.result}" class="current-image" alt="Selected Image">
                            <button type="button" class="remove-img-btn" id="btnClearSelectedImage" aria-label="${i18n.cancel}">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    `;
                    const clearBtn = document.getElementById('btnClearSelectedImage');
                    if (clearBtn) {
                        clearBtn.addEventListener('click', (ev) => {
                            ev.preventDefault();
                            ev.stopPropagation();
                            resetImagePlaceholder();
                        });
                    }
                };
                reader.readAsDataURL(imageInput.files[0]);
            }
        });
    }

    function resetImagePlaceholder() {
        if (imageInput) imageInput.value = '';
        if (autofillImageInput) autofillImageInput.value = '';
        if (imageUploadArea) {
            imageUploadArea.innerHTML = `
                <i class="bi bi-cloud-arrow-up-fill upload-icon"></i>
                <p class="upload-text">${i18n.uploadPlaceholder}</p>
            `;
        }
    }

    function bindRemoveImageButton() {
        const removeBtn = document.getElementById('btnRemoveExistingImage');
        if (!removeBtn) return;

        removeBtn.addEventListener('click', (ev) => {
            ev.preventDefault();
            ev.stopPropagation();

            if (typeof Swal === 'undefined') {
                resetImagePlaceholder();
                return;
            }

            Swal.fire({
                title: i18n.removeImageTitle,
                text: i18n.removeImageConfirm,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ba1a1a',
                cancelButtonColor: '#6e7881',
                confirmButtonText: i18n.yes,
                cancelButtonText: i18n.no
            }).then(async (result) => {
                if (result.isConfirmed) {
                    const fd = new URLSearchParams();
                    fd.append('id', videoId);

                    try {
                        const r = await fetch(`${siteAddress}mod_video_embed/image_remove_image`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: fd
                        });
                        const text = await r.text();
                        let isSuccess = false;
                        let msg = i18n.removeImageSuccess;

                        try {
                            const parsed = JSON.parse(text);
                            if (parsed.status === 'success') {
                                isSuccess = true;
                                if (parsed.message) msg = parsed.message;
                            }
                        } catch (e) {
                            if (text.includes('Success') || text.includes('successful') || text.includes('serkeftî')) {
                                isSuccess = true;
                            }
                        }

                        if (isSuccess) {
                            Swal.fire(i18n.success, msg, 'success');
                            resetImagePlaceholder();
                        } else {
                            Swal.fire(i18n.error, text, 'error');
                        }
                    } catch (err) {
                        console.error('Image remove error:', err);
                        Swal.fire(i18n.error, i18n.autofillError, 'error');
                    }
                }
            });
        });
    }

    bindRemoveImageButton();

    /**
     * Bottom Toolbar Action Buttons
     */
    const btnCancel = document.getElementById('btnCancelEdit');
    if (btnCancel) {
        btnCancel.addEventListener('click', () => {
            const href = btnCancel.dataset.href;
            if (href) window.location.href = href;
        });
    }

    const triggerFormSubmit = () => {
        if (form) {
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        }
    };

    const fabSave = document.getElementById('fabSaveEdit');
    if (fabSave) {
        fabSave.addEventListener('click', triggerFormSubmit);
    }

    const btnSave = document.getElementById('btnSaveEdit');
    if (btnSave) {
        btnSave.addEventListener('click', triggerFormSubmit);
    }

    /**
     * Form Submit & Emoji Validation
     */
    form.addEventListener('submit', (e) => {
        const restrictedFields = [nameInput, titleInput, keywordsInput, descriptionInput, contentInput];
        const hasUnsupportedChar = restrictedFields.some(f => f && /[\u{10000}-\u{10FFFF}]/u.test(f.value || ''));

        if (hasUnsupportedChar) {
            e.preventDefault();
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    text: i18n.unsupportedEmoji,
                    confirmButtonText: i18n.ok,
                    confirmButtonColor: '#ba1a1a'
                });
            } else {
                alert(i18n.unsupportedEmoji);
            }
        }
    });

    // Initialize custom select picker sheets if supported
    if (typeof initSelectPickerSheets === 'function') {
        initSelectPickerSheets();
    }

    // Initial runs
    updateEmbedPreview();
    updateSeoPreview();
});
