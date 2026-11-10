/**
 * mod_video_embed_edit.js
 * Externalized JavaScript controller for the video embed edit view.
 * Complies strictly with AGENTS.md Rule 2 (Strict Code Separation Mandate).
 */

function initVideoEmbedForm() {
    const form = document.getElementById('videoEditForm') || document.getElementById('videoAddForm');
    if (!form) return;
    if (form.dataset.initialized === 'true') return;
    form.dataset.initialized = 'true';

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
    const embedCard = document.getElementById('embedCard') || (embedInput ? embedInput.closest('.m3-card') : null);
    const alertDialog = document.getElementById('videoAutofillAlertDialog');
    const alertTitle = document.getElementById('videoAutofillAlertTitle');
    const alertMessage = document.getElementById('videoAutofillAlertMessage');
    const alertIcon = document.getElementById('videoAutofillAlertIcon');
    const alertDetails = document.getElementById('videoAutofillAlertDetails');
    const alertList = document.getElementById('videoAutofillAlertList');
    const alertBtnOk = document.getElementById('btnVideoAutofillAlertOk');

    let isFetchingAutofill = false;
    let autofillAbortController = null;
    let lastFetchedVideoId = '';
    let embedInputDebounceTimer = null;

    if (alertBtnOk && alertDialog) {
        alertBtnOk.addEventListener('click', () => {
            if (alertDialog.open) {
                alertDialog.close();
            }
        });
    }

    if (dateInput && !dateInput.value) {
        try {
            const formatted = (typeof getIstanbulDateTimeString === 'function')
                ? getIstanbulDateTimeString()
                : (new Intl.DateTimeFormat('sv-SE', {
                    timeZone: 'Europe/Istanbul',
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                }).format(new Date()).replace(' ', 'T'));
            dateInput.value = formatted;
        } catch (e) {
            console.warn('Failed to set default Istanbul datetime:', e);
        }
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
        autofillInvalid: form.dataset.i18nAutofillInvalid || 'A valid YouTube video could not be detected from the provided input.',
        autofillLoading: form.dataset.i18nAutofillLoading || 'Fetching video details from YouTube...',
        autofillSuccess: form.dataset.i18nAutofillSuccess || 'Video details imported successfully.',
        autofillPartial: form.dataset.i18nAutofillPartial || 'Cover image and basic details were imported. Fill the remaining fields if YouTube did not expose all metadata.',
        autofillError: form.dataset.i18nAutofillError || 'Video details could not be imported.',
        missingDuration: form.dataset.i18nMissingDuration || 'Duration',
        missingImage: form.dataset.i18nMissingImage || 'Image',
        missingContent: form.dataset.i18nMissingContent || 'Content',
        uploadPlaceholder: form.dataset.i18nUploadText || 'Tap or Drag to Upload Image',
        previewText: form.dataset.i18nPreviewText || 'Preview',
        fallbackSlug: form.dataset.i18nFallbackSlug || 'video'
    };

    /**
     * Remove 4-byte characters (emoji, supplemental pictographs outside BMP)
     */
    function stripUnsupportedEmoji(val) {
        return (val || '').toString().replace(/[\u{10000}-\u{10FFFF}]/gu, '').trim();
    }

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
     * Format slug string for Wikipedia-style URL preview
     */
    function formatVideoSlug(value) {
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
        const slug = formatVideoSlug(nameInput.value);
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

    /**
     * Parse video source (X, YouTube, Vimeo) from embed iframe or watch/short/share URL
     */
    function detectVideoSource(input) {
        let raw = (input || '').toString().trim();
        if (!raw) return null;

        // Decode encoded HTML entities if present
        if (raw.includes('&lt;') || raw.includes('&gt;') || raw.includes('&quot;') || raw.includes('&#')) {
            const txt = document.createElement('textarea');
            txt.innerHTML = raw;
            raw = txt.value;
        }

        // X (Twitter)
        const xMatch = raw.match(/(?:(?:x|twitter)\.com\/(?:([A-Za-z0-9_]{1,15})\/status(?:es)?|i\/(?:web\/)?status)\/(\d{5,25})|platform\.twitter\.com\/embed\/Tweet\.html\?(?:[^"'\s<>]*&)?id=(\d{5,25}))/i);
        if (xMatch) {
            const statusId = xMatch[2] || xMatch[3] || '';
            const handle = xMatch[1] && xMatch[1].toLowerCase() !== 'i' ? xMatch[1] : null;
            if (statusId) {
                return {
                    provider: 'x',
                    id: statusId,
                    cacheKey: 'x_' + statusId,
                    handle: handle,
                    canonicalUrl: 'https://x.com/' + (handle || 'i') + '/status/' + statusId
                };
            }
        }

        // YouTube
        const embedMatch = raw.match(/youtube(?:-nocookie)?\.com\/embed\/([A-Za-z0-9_-]{11})/i);
        if (embedMatch) return { provider: 'youtube', id: embedMatch[1], cacheKey: 'yt_' + embedMatch[1], canonicalUrl: 'https://www.youtube.com/watch?v=' + embedMatch[1] };

        const shortsMatch = raw.match(/youtube(?:-nocookie)?\.com\/shorts\/([A-Za-z0-9_-]{11})/i);
        if (shortsMatch) return { provider: 'youtube', id: shortsMatch[1], cacheKey: 'yt_' + shortsMatch[1], canonicalUrl: 'https://www.youtube.com/watch?v=' + shortsMatch[1] };

        const liveMatch = raw.match(/youtube(?:-nocookie)?\.com\/live\/([A-Za-z0-9_-]{11})/i);
        if (liveMatch) return { provider: 'youtube', id: liveMatch[1], cacheKey: 'yt_' + liveMatch[1], canonicalUrl: 'https://www.youtube.com/watch?v=' + liveMatch[1] };

        const shortUrlMatch = raw.match(/youtu\.be\/([A-Za-z0-9_-]{11})/i);
        if (shortUrlMatch) return { provider: 'youtube', id: shortUrlMatch[1], cacheKey: 'yt_' + shortUrlMatch[1], canonicalUrl: 'https://www.youtube.com/watch?v=' + shortUrlMatch[1] };

        const watchMatch = raw.match(/[?&]v=([A-Za-z0-9_-]{11})/i);
        if (watchMatch) return { provider: 'youtube', id: watchMatch[1], cacheKey: 'yt_' + watchMatch[1], canonicalUrl: 'https://www.youtube.com/watch?v=' + watchMatch[1] };

        if (/^[A-Za-z0-9_-]{11}$/.test(raw)) {
            return { provider: 'youtube', id: raw, cacheKey: 'yt_' + raw, canonicalUrl: 'https://www.youtube.com/watch?v=' + raw };
        }

        return null;
    }

    /**
     * Updates the live provider badge
     */
    function updateProviderBadge(sourceInfo) {
        const badge = document.getElementById('embedProviderBadge');
        if (!badge) return;
        badge.classList.remove('is-x', 'is-youtube');

        if (!sourceInfo) {
            badge.innerHTML = '<i class="bi bi-link-45deg"></i> <span class="badge-text">' + (i18n.providerCustom || 'Link / Embed') + '</span>';
            if (embedPreview) embedPreview.classList.remove('is-provider-x');
            return;
        }

        if (sourceInfo.provider === 'x') {
            badge.classList.add('is-x');
            badge.innerHTML = '<i class="bi bi-twitter-x"></i> <span class="badge-text">' + (i18n.providerX || 'X (Twitter)') + '</span>';
            if (embedPreview) embedPreview.classList.add('is-provider-x');
        } else if (sourceInfo.provider === 'youtube') {
            badge.classList.add('is-youtube');
            badge.innerHTML = '<i class="bi bi-youtube"></i> <span class="badge-text">' + (i18n.providerYoutube || 'YouTube') + '</span>';
            if (embedPreview) embedPreview.classList.remove('is-provider-x');
        }
    }

    /**
     * Show Top-Layer Safe In-Modal AJAX Alert Dialog
     */
    function showAutofillAjaxAlert(title, message, items = [], isWarning = false) {
        if (!alertDialog) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: isWarning ? 'warning' : 'error',
                    title: title || i18n.information,
                    text: message,
                    confirmButtonText: i18n.ok,
                    confirmButtonColor: '#ba1a1a'
                });
            } else {
                alert((title ? title + '\n' : '') + message);
            }
            return;
        }

        if (alertTitle) alertTitle.textContent = title || (isWarning ? i18n.information : i18n.error);
        if (alertMessage) alertMessage.textContent = message || '';

        if (alertIcon) {
            alertIcon.classList.toggle('is-warning', isWarning);
            alertIcon.innerHTML = isWarning ? '<i class="bi bi-exclamation-triangle-fill"></i>' : '<i class="bi bi-x-circle-fill"></i>';
        }

        if (alertDetails && alertList) {
            if (Array.isArray(items) && items.length > 0) {
                alertList.innerHTML = items.map(it => `<li>${it}</li>`).join('');
                alertDetails.classList.remove('is-hidden');
            } else {
                alertList.innerHTML = '';
                alertDetails.classList.add('is-hidden');
            }
        }

        if (typeof alertDialog.showModal === 'function') {
            if (!alertDialog.open) {
                alertDialog.showModal();
            }
        } else {
            alertDialog.setAttribute('open', '');
        }
    }

    /**
     * Toggle Textless Indeterminate Fetch State
     */
    function setFetchingState(loading) {
        isFetchingAutofill = Boolean(loading);
        if (embedCard) {
            embedCard.classList.toggle('is-fetching', isFetchingAutofill);
        }
        if (embedPreview) {
            embedPreview.classList.toggle('is-fetching', isFetchingAutofill);
        }
    }

    /**
     * Trigger visual flash highlight on auto-populated inputs
     */
    function flashInputFields(fields) {
        fields.forEach(el => {
            if (!el) return;
            el.classList.remove('is-autofilled-flash');
            void el.offsetWidth;
            el.classList.add('is-autofilled-flash');
            setTimeout(() => {
                el.classList.remove('is-autofilled-flash');
            }, 850);
        });
    }

    /**
     * Automatic YouTube Metadata Fetch Execution
     */
    async function triggerAutoFetchMetadata(videoId, sourceInputVal) {
        if (!videoId || isFetchingAutofill) return;
        if (videoId === lastFetchedVideoId) return;

        if (autofillAbortController) {
            autofillAbortController.abort();
        }
        autofillAbortController = new AbortController();

        setFetchingState(true);

        try {
            const embedVal = embedInput ? embedInput.value.trim() : '';
            const originalVal = originalInput ? originalInput.value.trim() : '';

            const fd = new FormData();
            fd.append('embed', embedVal || sourceInputVal || '');
            fd.append('original', originalVal || (videoId ? `https://www.youtube.com/watch?v=${videoId}` : ''));

            const response = await fetch(`${siteAddress}mod_video_embed/autofill_metadata`, {
                method: 'POST',
                body: fd,
                signal: autofillAbortController.signal
            });

            const res = await response.json();

            if (res.status === 'success' && res.data) {
                lastFetchedVideoId = videoId;
                const data = res.data;
                const populatedElements = [];

                if (data.name && nameInput) {
                    nameInput.value = stripUnsupportedEmoji(data.name);
                    populatedElements.push(nameInput);
                }
                if (data.original && originalInput) {
                    originalInput.value = stripUnsupportedEmoji(data.original);
                    populatedElements.push(originalInput);
                }
                if (data.duration && durationInput) {
                    durationInput.value = stripUnsupportedEmoji(data.duration);
                    populatedElements.push(durationInput);
                }
                if (data.embed && embedInput && embedInput.value.trim() !== data.embed.trim()) {
                    embedInput.value = data.embed;
                }
                if (data.content && contentInput) {
                    contentInput.value = stripUnsupportedEmoji(data.content);
                    populatedElements.push(contentInput);
                }
                if (data.title && titleInput) {
                    titleInput.value = stripUnsupportedEmoji(data.title);
                    populatedElements.push(titleInput);
                }
                if (data.keywords && keywordsInput) {
                    keywordsInput.value = stripUnsupportedEmoji(data.keywords);
                    populatedElements.push(keywordsInput);
                }
                if (data.description && descriptionInput) {
                    descriptionInput.value = stripUnsupportedEmoji(data.description);
                    populatedElements.push(descriptionInput);
                }

                if (data.image && autofillImageInput) {
                    autofillImageInput.value = data.image;
                }
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
                flashInputFields(populatedElements);

                // Check for missing fields / partial data
                if (res.is_partial || (Array.isArray(res.missing_fields) && res.missing_fields.length > 0)) {
                    const missingLabels = (res.missing_fields || []).map(f => {
                        if (f === 'duration') return i18n.missingDuration;
                        if (f === 'image') return i18n.missingImage;
                        if (f === 'description') return i18n.missingContent;
                        return f;
                    });
                    showAutofillAjaxAlert(i18n.information, res.message || i18n.autofillPartial, missingLabels, true);
                }
            } else {
                const errMsg = res.message || i18n.autofillError;
                showAutofillAjaxAlert(i18n.error, errMsg, res.warnings || [], false);
            }
        } catch (err) {
            if (err.name === 'AbortError') {
                return;
            }
            console.error('Autofill error:', err);
            showAutofillAjaxAlert(i18n.error, i18n.autofillError, [err.message || String(err)], false);
        } finally {
            setFetchingState(false);
        }
    }

    /**
     * Listeners for automatic detection on Embed & Original input
     */
    function handleEmbedInputChange(e) {
        updateEmbedPreview();

        if (embedInputDebounceTimer) {
            clearTimeout(embedInputDebounceTimer);
        }

        const rawVal = embedInput ? embedInput.value.trim() : '';
        if (!rawVal) {
            lastFetchedVideoId = '';
            updateProviderBadge(null);
            return;
        }

        const detected = detectVideoSource(rawVal);
        updateProviderBadge(detected);

        if (detected) {
            if (detected.cacheKey !== lastFetchedVideoId) {
                const delay = (e && e.type === 'paste') ? 80 : 350;
                embedInputDebounceTimer = setTimeout(() => {
                    triggerAutoFetchMetadata(detected.cacheKey, rawVal);
                }, delay);
            }
        } else {
            // If the user entered a substantial string (like an iframe or http) that cannot be parsed
            if (rawVal.length > 20 && (rawVal.includes('<iframe') || rawVal.startsWith('http'))) {
                embedInputDebounceTimer = setTimeout(() => {
                    showAutofillAjaxAlert(i18n.information, i18n.autofillInvalid, [], true);
                }, 800);
            }
        }
    }

    // Embed input listeners
    if (embedInput) {
        embedInput.addEventListener('input', handleEmbedInputChange);
        embedInput.addEventListener('paste', handleEmbedInputChange);
        embedInput.addEventListener('change', handleEmbedInputChange);
    }

    // Smart Toolbar buttons
    const btnPaste = document.getElementById('btnPasteClipboard');
    if (btnPaste && embedInput) {
        btnPaste.addEventListener('click', async () => {
            try {
                if (navigator.clipboard && typeof navigator.clipboard.readText === 'function') {
                    const text = await navigator.clipboard.readText();
                    if (text) {
                        embedInput.value = text.trim();
                        handleEmbedInputChange({ type: 'paste' });
                        embedInput.focus();
                    }
                } else {
                    embedInput.focus();
                    embedInput.select();
                }
            } catch (clipErr) {
                embedInput.focus();
                embedInput.select();
            }
        });
    }

    const btnClear = document.getElementById('btnClearEmbedSource');
    if (btnClear && embedInput) {
        btnClear.addEventListener('click', () => {
            embedInput.value = '';
            handleEmbedInputChange();
            embedInput.focus();
        });
    }

    const btnToggleRaw = document.getElementById('btnToggleRawEmbed');
    if (btnToggleRaw && embedInput) {
        btnToggleRaw.addEventListener('click', () => {
            const isCollapsed = embedInput.classList.toggle('is-collapsed-raw');
            btnToggleRaw.classList.toggle('is-active', !isCollapsed);
        });
    }

    if (originalInput) {
        originalInput.addEventListener('input', () => {
            const rawVal = originalInput.value.trim();
            const detected = detectVideoSource(rawVal);
            if (detected && detected.cacheKey !== lastFetchedVideoId && (!embedInput || !embedInput.value.trim())) {
                updateProviderBadge(detected);
                if (embedInputDebounceTimer) clearTimeout(embedInputDebounceTimer);
                embedInputDebounceTimer = setTimeout(() => {
                    triggerAutoFetchMetadata(detected.cacheKey, rawVal);
                }, 350);
            }
        });
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
     * Bottom Action Buttons & Cancel
     */
    const cancelBtns = [
        document.getElementById('btnCancelEdit'),
        ...document.querySelectorAll('a.m3-btn-half-neutral[href*="mod_video_embed/list"]'),
        ...document.querySelectorAll('[data-action="cancel-edit"]')
    ].filter(Boolean);

    cancelBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const isInsideModal = Boolean(form.closest('#modal-dynamic-body') || document.getElementById('modal-dynamic-dialog')?.open);
            if (isInsideModal && window.AFModalEngine && typeof window.AFModalEngine.back === 'function') {
                e.preventDefault();
                window.AFModalEngine.back();
                return;
            }
            const href = btn.dataset.href || btn.getAttribute('href');
            if (href && !btn.getAttribute('href')) {
                window.location.href = href;
            }
        });
    });

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
     * Form Submit: Handles emoji validation & modal AJAX submission
     */
    form.addEventListener('submit', async (e) => {
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
            return;
        }

        const isInsideModal = Boolean(form.closest('#modal-dynamic-body') || document.getElementById('modal-dynamic-dialog')?.open);
        if (!isInsideModal) {
            return;
        }

        e.preventDefault();
        const submitBtn = form.querySelector('button[type="submit"]') || document.querySelector('button[type="submit"][form="' + form.id + '"]');
        if (submitBtn) {
            submitBtn.disabled = true;
        }

        try {
            const formData = new FormData(form);
            formData.append('ajax_request', '1');

            // Ensure target module & content id are included if adding from a content media picker
            const targetContentId = window.currentMediaPickerRecordId || window.currentMediaPickerContentId;
            const targetModule = window.currentMediaPickerModule || 'mod_news';
            if (targetContentId && !formData.get('target_id')) {
                formData.append('target_id', String(targetContentId));
                formData.append('target_module', targetModule);
            }

            const targetUrl = form.getAttribute('action') || (siteAddress + (form.id === 'videoAddForm' ? 'mod_video_embed/add' : 'mod_video_embed/edit/' + videoId));

            const response = await fetch(targetUrl, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });

            const text = await response.text();
            let res;
            try {
                res = JSON.parse(text);
            } catch (jsonErr) {
                console.error('Non-JSON server response:', text);
                showAutofillAjaxAlert(i18n.error, i18n.error, [text.slice(0, 300)], false);
                return;
            }

            if (res.status === 'success') {
                if (window.AFModalEngine && typeof window.AFModalEngine.showToast === 'function') {
                    window.AFModalEngine.showToast(res.message || i18n.success, 'success');
                } else if (typeof showSnackbar === 'function') {
                    showSnackbar(res.message || i18n.success, 'success');
                }
                const newId = res.id || null;
                const prefVideoId = newId ? `vid:${newId}` : null;

                // Client-side fallback: If targetContentId exists and was not confirmed attached by backend
                if (targetContentId && newId && res.attached !== true) {
                    try {
                        const relFd = new FormData();
                        relFd.append('id', String(targetContentId));
                        relFd.append('type', 'video');
                        relFd.append('value', prefVideoId);
                        fetch(`${siteAddress}${targetModule}/ajax_update_relation`, {
                            method: 'POST',
                            body: relFd
                        }).catch(() => {});
                    } catch (relErr) {
                        console.error('Relation fallback update error:', relErr);
                    }
                }

                if (typeof window.refreshActiveMediaPicker === 'function') {
                    window.refreshActiveMediaPicker(prefVideoId);
                }
                if (typeof closeSelectPickerSheet === 'function') {
                    closeSelectPickerSheet();
                }
                const mgr = window.newsManager || window.contentManager;
                if (mgr && typeof mgr.loadData === 'function') {
                    mgr.loadData(true);
                }
                if (window.AFModalEngine && typeof window.AFModalEngine.back === 'function') {
                    window.AFModalEngine.back();
                }
            } else {
                const errMsg = res.message || (res.errors ? res.errors.join(', ') : i18n.error);
                const details = Array.isArray(res.errors) ? res.errors : [];
                showAutofillAjaxAlert(i18n.error, errMsg, details, false);
            }
        } catch (err) {
            console.error('Video form submit error:', err);
            showAutofillAjaxAlert(i18n.error, i18n.error, [err.message || String(err)], false);
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
            }
        }
    });

    // Initialize custom select picker sheets if supported
    if (typeof initSelectPickerSheets === 'function') {
        initSelectPickerSheets();
    }

    // Initial runs
    if (embedInput && embedInput.value.trim()) {
        const initialDetected = detectVideoSource(embedInput.value.trim());
        updateProviderBadge(initialDetected);
    }
    updateEmbedPreview();
    updateSeoPreview();
}

window.initVideoEmbedForm = initVideoEmbedForm;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initVideoEmbedForm);
} else {
    initVideoEmbedForm();
}
