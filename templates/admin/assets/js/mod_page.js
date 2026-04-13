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

    function temizle(veri) {
        return String(veri || '')
            .normalize('NFKC')
            .replace(/[^\p{L}\p{N}\s_-]/gu, ' ')
            .replace(/[_-]{2,}/g, ' ')
            .replace(/^\s+|\s+$/g, '')
            .replace(/\s+/g, ' ');
    }

    function generatedMetaValues(veri) {
        var temizVeri = temizle(veri);
        var locale = getCurrentLocale();
        var seoValue = temizVeri.replace(/\s+/g, '_');
        var keywordsValue = temizVeri.replace(/\s+/g, ',');

        if (seoValue && seoValue.toLocaleLowerCase) {
            seoValue = locale ? seoValue.toLocaleLowerCase(locale) : seoValue.toLowerCase();
        }

        if (keywordsValue && keywordsValue.toLocaleLowerCase) {
            keywordsValue = locale ? keywordsValue.toLocaleLowerCase(locale) : keywordsValue.toLowerCase();
        }

        return {
            seo: seoValue,
            title: veri,
            keywords: keywordsValue,
            description: veri
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
            smart_paste: false,
            paste_webkit_styles: 'none',
            paste_merge_formats: true,
            paste_data_images: false,
            invalid_styles: 'color font-family background-color font-size',
            selector: selector,
            height: 400,
            menubar: false,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | blocks | ' +
                'bold italic backcolor | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'removeformat | help',
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:16px }',
            setup: function (editor) {
                editor.on('change', function () {
                    editor.save();
                });
            }
        });
    }

    function bindManualTracking(input, key, initialValues) {
        if (!input) {
            return;
        }

        input.dataset.metaKey = key;
        input.dataset.manuallyEdited = initialValues
            ? ((input.value !== '' && input.value !== initialValues[key]) ? 'true' : 'false')
            : (input.value !== '' ? 'true' : 'false');

        input.addEventListener('input', function () {
            input.dataset.manuallyEdited = 'true';
        });
    }

    function initMetaAutofill() {
        var nameInput = document.getElementById('name');
        var seoInput = document.getElementById('seo');
        var titleInput = document.getElementById('title');
        var keywordsInput = document.getElementById('keywords');
        var descriptionInput = document.getElementById('description');
        var initialMetaValues = generatedMetaValues(nameInput ? nameInput.value : '');

        bindManualTracking(seoInput, 'seo', initialMetaValues);
        bindManualTracking(titleInput, 'title', initialMetaValues);
        bindManualTracking(keywordsInput, 'keywords', initialMetaValues);
        bindManualTracking(descriptionInput, 'description', initialMetaValues);

        if (!nameInput) {
            return;
        }

        nameInput.addEventListener('input', function () {
            var metaValues = generatedMetaValues(nameInput.value);
            [seoInput, titleInput, keywordsInput, descriptionInput].forEach(function (input) {
                if (!input || input.dataset.manuallyEdited === 'true') {
                    return;
                }
                input.value = metaValues[input.dataset.metaKey];
            });
        });
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
                confirmButtonText: config.okText || 'Tamam',
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
                confirmButtonText: config.okText || 'Tamam',
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
        counter.innerText = input.value.length + ' / ' + max;
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
                '<button type="button" class="remove-img-btn" onclick="event.preventDefault(); removeImage()"><i class="bi bi-trash"></i></button>';
        };
        reader.readAsDataURL(input.files[0]);
    }

    function removeImage() {
        var config = window.MOD_PAGE_FORM || {};
        var removeConfig = config.removeImage || null;
        if (typeof Swal === 'undefined' || !removeConfig) {
            return;
        }

        Swal.fire({
            title: removeConfig.title,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ba1a1a',
            cancelButtonColor: '#6e7881',
            confirmButtonText: removeConfig.confirmText,
            cancelButtonText: removeConfig.cancelText
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
                    if (data.trim() === 'Oke') {
                        Swal.fire(removeConfig.successTitle, removeConfig.successMessage, 'success');
                        var area = document.querySelector('.image-upload-area');
                        if (area) {
                            area.innerHTML = '<i class="bi bi-cloud-arrow-up-fill upload-icon"></i><p>' + escapeHtml(removeConfig.uploadText) + '</p>';
                        }
                        var input = document.getElementById(removeConfig.inputId || 'editImgInput');
                        if (input) {
                            input.value = '';
                        }
                    } else {
                        Swal.fire(removeConfig.errorTitle, data, 'error');
                    }
                });
        });
    }

    function submitEditForm(formId) {
        var form = document.getElementById(formId);
        if (!form) {
            return;
        }

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        form.submit();
    }

    document.addEventListener('DOMContentLoaded', function () {
        var config = window.MOD_PAGE_FORM || {};
        initTomSelect(config.tomSelectIds || []);
        if (typeof initSelectPickerSheets === 'function') {
            initSelectPickerSheets();
        }
        initTinyMce(config.tinyMceSelector);
        initMetaAutofill();
        if (config.reloadOnLangChange && config.mode === 'add') {
            reloadOnLangChange(config.reloadOnLangChange);
        } else {
            resetSelectsOnLangChange(config.resetSelectIds || []);
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
