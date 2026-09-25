(function () {
    function getCurrentLocale() {
        var langInput = document.getElementById('lang');
        return langInput && langInput.value ? langInput.value : undefined;
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            }[char];
        });
    }

    function sanitizeText(value) {
        return String(value || '')
            .normalize('NFKC')
            .replace(/[^\p{L}\p{N}\s_-]/gu, ' ')
            .replace(/[_-]{2,}/g, ' ')
            .replace(/^\s+|\s+$/g, '')
            .replace(/\s+/g, ' ');
    }

    function isKurdishLocale(locale) {
        if (!locale) return false;
        var l = String(locale).toLowerCase().trim();
        return ['ku', 'ckb', 'zza'].indexOf(l) !== -1;
    }

    function normalizeKurdishTypos(text, locale) {
        if (!text) return "";
        if (isKurdishLocale(locale)) {
            text = text.replace(/İ/g, 'Î');
            text = text.replace(/ı/g, 'i');
            text = text.replace(/Ü/g, 'U');
            text = text.replace(/ü/g, 'u');
            text = text.replace(/Ö/g, 'O');
            text = text.replace(/ö/g, 'o');
            text = text.replace(/Ğ/g, 'X');
            text = text.replace(/ğ/g, 'x');
        }
        return text;
    }

    function generatedMetaValues(value) {
        var locale = getCurrentLocale();
        if (isKurdishLocale(locale)) {
            value = normalizeKurdishTypos(value, locale);
        }
        var cleanValue = String(value || '').replace(/['’"”„“]/g, '');
        var sanitizedValue = sanitizeText(cleanValue);
        var seoValue = sanitizedValue.replace(/[\s-]+/g, '_');
        var keywordsValue = sanitizedValue.replace(/[\s-]+/g, ',');

        if (seoValue && seoValue.toLocaleLowerCase) {
            try {
                seoValue = locale ? seoValue.toLocaleLowerCase(locale) : seoValue.toLowerCase();
            } catch (e) {
                seoValue = seoValue.toLowerCase();
            }
        }
        if (seoValue && seoValue.normalize) {
            seoValue = seoValue.normalize('NFC');
        }
        if (seoValue) {
            seoValue = seoValue.replace(/\u0307/g, '');
        }

        if (keywordsValue && keywordsValue.toLocaleLowerCase) {
            try {
                keywordsValue = locale ? keywordsValue.toLocaleLowerCase(locale) : keywordsValue.toLowerCase();
            } catch (e) {
                keywordsValue = keywordsValue.toLowerCase();
            }
        }
        if (keywordsValue && keywordsValue.normalize) {
            keywordsValue = keywordsValue.normalize('NFC');
        }
        if (keywordsValue) {
            keywordsValue = keywordsValue.replace(/\u0307/g, '');
        }

        if (isKurdishLocale(locale)) {
            if (seoValue) {
                seoValue = seoValue.replace(/ı/g, 'i').replace(/İ/g, 'i');
            }
            if (keywordsValue) {
                keywordsValue = keywordsValue.replace(/ı/g, 'i').replace(/İ/g, 'i');
            }
        }

        if (seoValue) {
            seoValue = seoValue.replace(/_+/g, '_').replace(/^_+|_+$/g, '');
        }
        if (keywordsValue) {
            keywordsValue = keywordsValue.replace(/,+/g, ',').replace(/^,+|,+$/g, '');
        }

        return {
            seo: seoValue,
            title: value,
            keywords: keywordsValue
        };
    }

    function initTomSelect(ids) {
        if (typeof TomSelect === 'undefined') {
            return;
        }

        ids.forEach(function (id) {
            var element = document.getElementById(id);
            if (element) {
                new TomSelect('#' + id, { create: false });
            }
        });
    }

    function initTinyMce(selector) {
        if (typeof tinymce === 'undefined' || !selector) {
            return;
        }

        tinymce.init({
            license_key: 'gpl',
            selector: selector,
            height: 400,
            menubar: false,
            statusbar: false,
            branding: false,
            promotion: false,
            elementpath: false,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | blocks | ' +
                'bold italic backcolor | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'removeformat | link image | code fullscreen | help',
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:16px }',
            setup: function (editor) {
                editor.on('change', function () {
                    editor.save();
                });
            }
        });
    }

    function bindManualTracking(input, key, initialValues, mode) {
        if (!input) {
            return;
        }

        input.dataset.metaKey = key;
        if (mode === 'edit') {
            input.dataset.manuallyEdited = 'false';
        } else {
            input.dataset.manuallyEdited = initialValues
                ? ((input.value !== '' && input.value !== initialValues[key]) ? 'true' : 'false')
                : (input.value !== '' ? 'true' : 'false');
        }

        input.addEventListener('input', function () {
            input.dataset.manuallyEdited = 'true';
        });
    }

    function syncPageDeck() {
        var summaryInput = document.getElementById('summary');
        var descriptionInput = document.getElementById('description');
        if (summaryInput) {
            updateCharCounter(summaryInput, 'introCounter', 120);
        }
        if (summaryInput && descriptionInput && (!descriptionInput.value || descriptionInput.dataset.manuallyEdited !== 'true')) {
            var val = summaryInput.value || '';
            var sanitized = typeof sanitizeText === 'function' ? sanitizeText(val) : val;
            descriptionInput.value = sanitized.substring(0, 120);
        }
    }

    function initMetaAutofill() {
        var config = window.MOD_PAGE_FORM || {};
        var nameInput = document.getElementById('name');
        var summaryInput = document.getElementById('summary');
        var seoInput = document.getElementById('seo');
        var titleInput = document.getElementById('title');
        var keywordsInput = document.getElementById('keywords');
        var descriptionInput = document.getElementById('description');
        var initialMetaValues = generatedMetaValues(nameInput ? nameInput.value : '');

        bindManualTracking(seoInput, 'seo', initialMetaValues, config.mode);
        bindManualTracking(titleInput, 'title', initialMetaValues, config.mode);
        bindManualTracking(keywordsInput, 'keywords', initialMetaValues, config.mode);
        bindManualTracking(descriptionInput, 'description', null, config.mode);

        if (nameInput) {
            nameInput.addEventListener('input', function () {
                var originalVal = nameInput.value;
                var locale = getCurrentLocale();
                var normalizedVal = normalizeKurdishTypos(originalVal, locale);
                
                if (originalVal !== normalizedVal) {
                    var start = nameInput.selectionStart;
                    var end = nameInput.selectionEnd;
                    nameInput.value = normalizedVal;
                    nameInput.setSelectionRange(start, end);
                }
                
                var metaValues = generatedMetaValues(nameInput.value);
                [seoInput, titleInput, keywordsInput].forEach(function (input) {
                    if (!input || input.dataset.manuallyEdited === 'true') {
                        return;
                    }
                    input.value = metaValues[input.dataset.metaKey];
                });
            });
        }

        if (summaryInput) {
            summaryInput.addEventListener('input', syncPageDeck);
        }
    }

    function resetSelectsOnLangChange(ids) {
        var langInput = document.getElementById('lang');
        if (!langInput) {
            return;
        }

        langInput.addEventListener('change', function () {
            ids.forEach(function (id) {
                var element = document.getElementById(id);
                if (!element) {
                    return;
                }
                element.value = '0';
                if (element.tomselect) {
                    element.tomselect.setValue('0', true);
                }
                element.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
    }

    function reloadOnLangChange(config) {
        var langInput = document.getElementById('lang');
        if (!langInput || !config || !config.enabled) {
            return;
        }

        langInput.addEventListener('change', function () {
            var nextLang = langInput.value || '';
            var url = new URL(window.location.href);

            if (nextLang) {
                url.searchParams.set('lang', nextLang);
            } else {
                url.searchParams.delete('lang');
            }

            window.location.href = url.toString();
        });
    }

    function showInitialAlerts(config) {
        if (typeof Swal === 'undefined' || !config) {
            return;
        }

        if (config.errorMessage) {
            Swal.fire({
                icon: 'warning',
                html: '<b><i class="bi bi-exclamation-triangle-fill"></i> ' + escapeHtml(config.errorMessage) + '</b>',
                confirmButtonText: config.okText || 'OK',
                confirmButtonColor: '#ba1a1a'
            });
            return;
        }

        if (config.errors && config.errors.length) {
            var errorHtml = '<div style="text-align: left;">' + config.errors.map(function (error) {
                return '<div style="margin-bottom: 8px;"><i class="bi bi-exclamation-triangle-fill" style="color: #ba1a1a;"></i> <b>' + escapeHtml(error) + '</b></div>';
            }).join('') + '</div>';

            Swal.fire({
                icon: 'warning',
                html: errorHtml,
                confirmButtonText: config.okText || 'OK',
                confirmButtonColor: '#ba1a1a'
            });
        }
    }

    function handleSuccessRedirect(config) {
        if (!config || !config.url || !config.delayMs) {
            return;
        }

        window.setTimeout(function () {
            window.location.href = config.url;
        }, config.delayMs);
    }

    function toggleCardSelection(hiddenId, element, value) {
        var hidden = document.getElementById(hiddenId);
        if (!hidden || !element) {
            return;
        }

        var grid = hidden.parentElement ? hidden.parentElement.querySelector('.selection-grid') : null;
        if (grid) {
            grid.querySelectorAll('.selection-card').forEach(function (card) {
                card.classList.remove('active');
            });
        }

        element.classList.add('active');
        hidden.value = value;
    }

    function updateCharCounter(input, counterId, max) {
        var counter = document.getElementById(counterId);
        if (!counter || !input) {
            return;
        }
        var len = input.value.length;
        counter.innerText = len + ' / ' + max;
        if (counterId === 'introCounter') {
            counter.classList.remove('is-valid', 'is-warning', 'is-error');
            if (len >= 90 && len <= 120) {
                counter.classList.add('is-valid');
            } else if (len > 120) {
                counter.classList.add('is-error');
            } else if (len > 0) {
                counter.classList.add('is-warning');
            }
        }
    }

    function toggleAdvanced() {
        var content = document.getElementById('advancedContent');
        var icon = document.getElementById('advancedIcon');
        if (!content || !icon) {
            return;
        }
        content.classList.toggle('active');
        icon.classList.toggle('bi-chevron-up');
        icon.classList.toggle('bi-chevron-down');
    }

    function getFormConfig() {
        var form = document.getElementById('editForm');
        var baseConfig = window.MOD_PAGE_FORM || {};
        if (!form) {
            return baseConfig;
        }

        var ds = form.dataset || {};
        var errors = [];
        if (ds.errors) {
            try {
                errors = JSON.parse(ds.errors);
            } catch (e) {
                errors = [];
            }
        } else if (baseConfig.alerts && baseConfig.alerts.errors) {
            errors = baseConfig.alerts.errors;
        }

        return {
            mode: ds.mode || baseConfig.mode || 'edit',
            tinyMceSelector: baseConfig.tinyMceSelector || '#contentTextarea',
            tomSelectIds: baseConfig.tomSelectIds || ['parent_id', 'menu', 'acl_id', 'gallery_id', 'file_id'],
            resetSelectIds: baseConfig.resetSelectIds || ['parent_id', 'category_id', 'menu', 'gallery_id', 'file_id'],
            alerts: {
                errorMessage: ds.errorMessage || (baseConfig.alerts && baseConfig.alerts.errorMessage) || '',
                errors: errors,
                okText: ds.okText || (baseConfig.alerts && baseConfig.alerts.okText) || 'OK',
                deckLengthError: ds.deckLengthError || (baseConfig.alerts && baseConfig.alerts.deckLengthError) || 'Please enter an introductory text between 90 and 120 characters.'
            },
            removeImage: {
                url: ds.removeUrl || (baseConfig.removeImage && baseConfig.removeImage.url) || '',
                pageId: ds.removeId || (baseConfig.removeImage && baseConfig.removeImage.pageId) || '',
                title: ds.removeTitle || (baseConfig.removeImage && baseConfig.removeImage.title) || '',
                confirmText: ds.removeConfirmText || (baseConfig.removeImage && baseConfig.removeImage.confirmText) || 'Yes',
                cancelText: ds.removeCancelText || (baseConfig.removeImage && baseConfig.removeImage.cancelText) || 'No',
                successTitle: ds.successTitle || (baseConfig.removeImage && baseConfig.removeImage.successTitle) || 'Success',
                successMessage: ds.successMessage || (baseConfig.removeImage && baseConfig.removeImage.successMessage) || '',
                errorTitle: ds.errorTitle || (baseConfig.removeImage && baseConfig.removeImage.errorTitle) || 'Error',
                uploadText: ds.uploadText || (baseConfig.removeImage && baseConfig.removeImage.uploadText) || '',
                inputId: 'editImgInput'
            },
            successRedirect: ds.successUrl ? {
                url: ds.successUrl,
                delayMs: parseInt(ds.redirectDelay, 10) || 2000
            } : (baseConfig.successRedirect || null)
        };
    }

    function initContextReload() {
        var langSelect = document.getElementById('lang');
        var slotSelect = document.getElementById('menu_slot');

        function reloadFormContext() {
            if (!langSelect) {
                return;
            }

            var url = new URL(window.location.href);
            url.searchParams.set('lang', langSelect.value);
            if (slotSelect && slotSelect.value) {
                url.searchParams.set('menu_slot', slotSelect.value);
            }
            window.location.href = url.toString();
        }

        if (langSelect) {
            langSelect.addEventListener('change', reloadFormContext);
        }

        if (slotSelect) {
            slotSelect.addEventListener('change', reloadFormContext);
        }
    }

    function initActionDelegation() {
        document.addEventListener('click', function (e) {
            var target = e.target.closest('[data-action]');
            if (!target) {
                return;
            }

            var action = target.getAttribute('data-action');
            if (action === 'select-deck') {
                e.preventDefault();
                toggleCardSelection('deckHidden', target, target.getAttribute('data-value'));
            } else if (action === 'select-root') {
                e.preventDefault();
                toggleCardSelection('rootHidden', target, target.getAttribute('data-value'));
            } else if (action === 'select-status') {
                e.preventDefault();
                toggleCardSelection('publishHidden', target, target.getAttribute('data-value'));
            } else if (action === 'toggle-advanced') {
                e.preventDefault();
                toggleAdvanced();
            } else if (action === 'cancel-edit') {
                e.preventDefault();
                var href = target.getAttribute('data-href');
                if (href) {
                    window.location.href = href;
                }
            } else if (action === 'submit-edit-form') {
                e.preventDefault();
                var formId = target.getAttribute('data-form') || 'editForm';
                submitEditForm(formId);
            } else if (action === 'remove-preview-image') {
                e.preventDefault();
                removeImage();
            }
        });
    }

    function previewImage(input) {
        if (!(input && input.files && input.files[0])) {
            return;
        }

        var reader = new FileReader();
        reader.onload = function (e) {
            var area = document.querySelector('.image-upload-area');
            if (!area) {
                return;
            }
            area.innerHTML = '' +
                '<img id="editImgPreview" src="' + e.target.result + '" style="width:100%; height:auto; border-radius:8px;">' +
                '<button type="button" class="remove-img-btn" data-action="remove-preview-image"><i class="bi bi-trash"></i></button>';
        };
        reader.readAsDataURL(input.files[0]);
    }

    function removeImage() {
        var config = getFormConfig();
        var removeConfig = config.removeImage || null;
        if (typeof Swal === 'undefined' || !removeConfig || !removeConfig.url) {
            return;
        }

        Swal.fire({
            title: removeConfig.title || 'Are you sure?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ba1a1a',
            cancelButtonColor: '#6e7881',
            confirmButtonText: removeConfig.confirmText || 'Yes',
            cancelButtonText: removeConfig.cancelText || 'No'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            var formData = new URLSearchParams();
            formData.append('id', removeConfig.pageId);

            fetch(removeConfig.url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            })
                .then(function (response) { return response.text(); })
                .then(function (data) {
                    if (data.trim() === 'Success') {
                        Swal.fire(removeConfig.successTitle || 'Success', removeConfig.successMessage || '', 'success');
                        var area = document.querySelector('.image-upload-area');
                        if (area) {
                            area.innerHTML = '<i class="bi bi-cloud-arrow-up-fill upload-icon"></i><p>' + escapeHtml(removeConfig.uploadText) + '</p>';
                        }
                        var input = document.getElementById(removeConfig.inputId || 'editImgInput');
                        if (input) {
                            input.value = '';
                        }
                    } else {
                        Swal.fire(removeConfig.errorTitle || 'Error', data, 'error');
                    }
                });
        });
    }

    function submitEditForm(formId) {
        var form = document.getElementById(formId);
        if (!form) {
            return;
        }

        if (typeof tinymce !== 'undefined') {
            tinymce.triggerSave();
        }

        var summaryInput = document.getElementById('summary');
        if (summaryInput) {
            var sVal = summaryInput.value.trim();
            if (sVal.length < 90 || sVal.length > 120) {
                var config = getFormConfig();
                var errMsg = (config.alerts && config.alerts.deckLengthError) || 'Please enter an introductory text between 90 and 120 characters.';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        html: '<b>' + escapeHtml(errMsg) + '</b>',
                        confirmButtonText: (config.alerts && config.alerts.okText) || 'OK',
                        confirmButtonColor: '#ba1a1a'
                    });
                }
                summaryInput.focus();
                summaryInput.reportValidity();
                return;
            }
        }

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        form.submit();
    }

    document.addEventListener('DOMContentLoaded', function () {
        var config = getFormConfig();
        initTomSelect(config.tomSelectIds || []);
        if (typeof initSelectPickerSheets === 'function') {
            initSelectPickerSheets();
        }
        initTinyMce(config.tinyMceSelector);
        initMetaAutofill();
        syncPageDeck();
        initActionDelegation();
        if (config.mode === 'edit') {
            initContextReload();
        }
        var summaryInput = document.getElementById('summary');
        if (summaryInput) {
            summaryInput.addEventListener('input', function () {
                updateCharCounter(summaryInput, 'introCounter', 120);
            });
            updateCharCounter(summaryInput, 'introCounter', 120);
        }
        var form = document.getElementById('editForm');
        if (form) {
            form.addEventListener('submit', function (e) {
                var summaryInput = document.getElementById('summary');
                if (summaryInput) {
                    var sVal = summaryInput.value.trim();
                    if (sVal.length < 90 || sVal.length > 120) {
                        e.preventDefault();
                        var cfg = getFormConfig();
                        var errMsg = (cfg.alerts && cfg.alerts.deckLengthError) || 'Please enter an introductory text between 90 and 120 characters.';
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'warning',
                                html: '<b>' + escapeHtml(errMsg) + '</b>',
                                confirmButtonText: (cfg.alerts && cfg.alerts.okText) || 'OK',
                                confirmButtonColor: '#ba1a1a'
                            });
                        }
                        summaryInput.focus();
                        summaryInput.reportValidity();
                    }
                }
            });
        }
        if (config.reloadOnLangChange && config.mode === 'add') {
            reloadOnLangChange(config.reloadOnLangChange);
        } else if (config.mode !== 'edit') {
            resetSelectsOnLangChange(config.resetSelectIds || []);
        }
        if (typeof initializeEditImageInteractions === 'function') {
            initializeEditImageInteractions();
        }
        showInitialAlerts(config.alerts || {});
        handleSuccessRedirect(config.successRedirect || null);
    });

    window.escapeHtml = escapeHtml;
    window.toggleAdvanced = toggleAdvanced;
    window.selectPublishStatus = function (el, val) { toggleCardSelection('publishHidden', el, val); };
    window.selectRootStatus = function (el, val) { toggleCardSelection('rootHidden', el, val); };
    window.selectDeckStatus = function (el, val) { toggleCardSelection('deckHidden', el, val); };
    window.updateCharCounter = updateCharCounter;
    window.previewImage = previewImage;
    window.removeImage = removeImage;
    window.submitEditForm = submitEditForm;
})();
