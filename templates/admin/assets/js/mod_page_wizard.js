(function () {
    var currentStep = 1;
    var totalSteps = 8;
    var config = window.MOD_PAGE_WIZARD_CONFIG || {};
    var storageKey = 'pageWizardState';
    var stateMaxAge = 24 * 60 * 60 * 1000;
    var seoFieldTouched = {
        seo: false,
        title: false,
        keywords: false,
        description: false
    };

    function wizardElement() {
        return document.getElementById('pageWizard');
    }

    function formElement() {
        return document.getElementById('pageWizardForm');
    }

    function getById(id) {
        return document.getElementById(id);
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

    function selectedText(id, fallback) {
        var element = getById(id);
        if (!element || element.selectedIndex < 0) {
            return fallback || '';
        }
        return element.options[element.selectedIndex].text || fallback || '';
    }

    function currentState() {
        return {
            step: currentStep,
            data: {
                lang: getById('pageWizardHiddenLang') ? getById('pageWizardHiddenLang').value : '',
                name: getById('pageWizardNameInput') ? getById('pageWizardNameInput').value : '',
                summary: getById('pageWizardSummaryInput') ? getById('pageWizardSummaryInput').value : '',
                parent_id: getById('pageWizardParentId') ? getById('pageWizardParentId').value : '0',
                category_id: getById('pageWizardCategoryId') ? getById('pageWizardCategoryId').value : '0',
                menu: getById('pageWizardMenuId') ? getById('pageWizardMenuId').value : '0',
                status: getById('pageWizardStatusHidden') ? getById('pageWizardStatusHidden').value : 'y',
                root: getById('pageWizardRootSelect') ? getById('pageWizardRootSelect').value : 'n',
                deck: getById('pageWizardDeckSelect') ? getById('pageWizardDeckSelect').value : 'n',
                gallery_id: getById('pageWizardGalleryId') ? getById('pageWizardGalleryId').value : '0',
                file_id: getById('pageWizardFileId') ? getById('pageWizardFileId').value : '0',
                acl_id: getById('pageWizardAclId') ? getById('pageWizardAclId').value : '0',
                seo: getById('pageWizardSeoInput') ? getById('pageWizardSeoInput').value : '',
                title: getById('pageWizardTitleInput') ? getById('pageWizardTitleInput').value : '',
                keywords: getById('pageWizardKeywordsInput') ? getById('pageWizardKeywordsInput').value : '',
                description: getById('pageWizardDescriptionInput') ? getById('pageWizardDescriptionInput').value : '',
                seo_touched: seoFieldTouched
            },
            timestamp: Date.now()
        };
    }

    function saveState() {
        try {
            localStorage.setItem(storageKey, JSON.stringify(currentState()));
        } catch (error) {
            console.warn('Page wizard state save error:', error);
        }
    }

    function loadState() {
        try {
            var saved = localStorage.getItem(storageKey);
            if (!saved) {
                return false;
            }

            var state = JSON.parse(saved);
            if (!state || !state.data) {
                return false;
            }
            if (state.timestamp && Date.now() - state.timestamp > stateMaxAge) {
                clearState(false);
                return false;
            }

            currentStep = state.step || 1;

            if (getById('pageWizardHiddenLang') && state.data.lang) {
                getById('pageWizardHiddenLang').value = state.data.lang;
            }
            if (getById('pageWizardNameInput')) {
                getById('pageWizardNameInput').value = state.data.name || '';
            }
            if (getById('pageWizardSummaryInput')) {
                getById('pageWizardSummaryInput').value = state.data.summary || '';
            }
            if (getById('pageWizardParentId')) {
                getById('pageWizardParentId').value = state.data.parent_id || '0';
            }
            if (getById('pageWizardCategoryId')) {
                getById('pageWizardCategoryId').value = state.data.category_id || '0';
            }
            if (getById('pageWizardMenuId')) {
                getById('pageWizardMenuId').value = state.data.menu || '0';
            }
            if (getById('pageWizardStatusHidden')) {
                getById('pageWizardStatusHidden').value = state.data.status || 'y';
            }
            if (getById('pageWizardRootSelect')) {
                getById('pageWizardRootSelect').value = state.data.root || 'n';
            }
            if (getById('pageWizardDeckSelect')) {
                getById('pageWizardDeckSelect').value = state.data.deck || 'n';
            }
            if (getById('pageWizardGalleryId')) {
                getById('pageWizardGalleryId').value = state.data.gallery_id || '0';
            }
            if (getById('pageWizardFileId')) {
                getById('pageWizardFileId').value = state.data.file_id || '0';
            }
            if (getById('pageWizardAclId')) {
                getById('pageWizardAclId').value = state.data.acl_id || '0';
            }
            if (getById('pageWizardSeoInput')) {
                getById('pageWizardSeoInput').value = state.data.seo || '';
            }
            if (getById('pageWizardTitleInput')) {
                getById('pageWizardTitleInput').value = state.data.title || '';
            }
            if (getById('pageWizardKeywordsInput')) {
                getById('pageWizardKeywordsInput').value = state.data.keywords || '';
            }
            if (getById('pageWizardDescriptionInput')) {
                getById('pageWizardDescriptionInput').value = state.data.description || '';
            }
            if (state.data.seo_touched) {
                seoFieldTouched = {
                    seo: !!state.data.seo_touched.seo,
                    title: !!state.data.seo_touched.title,
                    keywords: !!state.data.seo_touched.keywords,
                    description: !!state.data.seo_touched.description
                };
            }

            return true;
        } catch (error) {
            console.warn('Page wizard state load error:', error);
            return false;
        }
    }

    function clearState(markSubmitted) {
        try {
            localStorage.removeItem(storageKey);
            if (markSubmitted) {
                localStorage.setItem('pageWizardJustSubmitted', Date.now().toString());
            }
        } catch (error) {
            console.warn('Page wizard state clear error:', error);
        }
    }

    function syncSelectionCards(selector, value) {
        document.querySelectorAll(selector).forEach(function (card) {
            var cardValue = (card.getAttribute('data-value') || '').toLowerCase();
            card.classList.toggle('active', cardValue === String(value || '').toLowerCase());
        });
    }

    function syncStatusCards() {
        var statusInput = getById('pageWizardStatusHidden');
        syncSelectionCards('#pageWizardStatusGrid .selection-card', statusInput ? statusInput.value : 'y');
    }

    function syncRootCards() {
        var rootInput = getById('pageWizardRootSelect');
        syncSelectionCards('#pageWizardRootGrid .selection-card', rootInput ? rootInput.value : 'n');
    }

    function syncDeckCards() {
        var deckInput = getById('pageWizardDeckSelect');
        syncSelectionCards('#pageWizardDeckGrid .selection-card', deckInput ? deckInput.value : 'n');
    }

    function syncLanguageCards() {
        var hiddenLang = getById('pageWizardHiddenLang');
        syncSelectionCards('#pageWizardStep1 .selection-card', hiddenLang ? hiddenLang.value : config.initialLang);
    }

    function updateCounters() {
        var nameInput = getById('pageWizardNameInput');
        var summaryInput = getById('pageWizardSummaryInput');
        var nameCounter = getById('pageWizardNameCounter');
        var summaryCounter = getById('pageWizardSummaryCounter');

        if (nameInput && nameCounter) {
            nameCounter.innerText = nameInput.value.length + ' / 255';
        }
        if (summaryInput && summaryCounter) {
            summaryCounter.innerText = summaryInput.value.length + ' / 280';
        }
    }

    function updateSummary() {
        var hiddenLang = getById('pageWizardHiddenLang');
        var summaryLang = getById('pageWizardSummaryLang');
        var summaryName = getById('pageWizardSummaryName');
        var summaryCategory = getById('pageWizardSummaryCategory');
        var summaryMenu = getById('pageWizardSummaryMenu');
        var nameInput = getById('pageWizardNameInput');

        if (summaryLang && hiddenLang) {
            summaryLang.innerText = (hiddenLang.value || config.initialLang || '').toUpperCase();
        }

        if (summaryName) {
            summaryName.innerText = (nameInput && nameInput.value.trim()) || (config.texts && config.texts.selectedNone) || '';
        }

        if (summaryCategory) {
            summaryCategory.innerText = selectedText('pageWizardCategoryId', config.texts && config.texts.selectedNone);
        }

        if (summaryMenu) {
            summaryMenu.innerText = selectedText('pageWizardMenuId', config.texts && config.texts.selectedNone);
        }
    }

    function updateStepUI() {
        var stepTitle = getById('pageWizardStepTitle');
        var stepCounter = getById('pageWizardStepCounter');
        var progressFill = getById('pageWizardProgressFill');
        var btnPrev = getById('btnPageWizardPrev');
        var btnNext = getById('btnPageWizardNext');
        var btnFinish = getById('btnPageWizardFinish');
        var stepInput = getById('pageWizardStepInput');
        var titles = [
            'Language',
            'Structure',
            'Title',
            'Summary',
            'Content',
            'Display',
            'Relations',
            'SEO & Finish'
        ];

        document.querySelectorAll('#pageWizard .wizard-step').forEach(function (step) {
            step.classList.remove('active');
        });

        var current = getById('pageWizardStep' + currentStep);
        if (current) {
            current.classList.add('active');
        }

        if (stepTitle) {
            stepTitle.innerText = titles[currentStep - 1];
        }
        if (stepCounter) {
            stepCounter.innerText = currentStep + '/' + totalSteps;
        }
        if (progressFill) {
            progressFill.style.width = (currentStep / totalSteps) * 100 + '%';
        }
        if (stepInput) {
            stepInput.value = currentStep;
        }
        if (btnPrev) {
            btnPrev.disabled = currentStep === 1;
        }
        if (btnNext) {
            btnNext.style.display = currentStep === totalSteps ? 'none' : 'inline-flex';
        }
        if (btnFinish) {
            btnFinish.style.display = currentStep === totalSteps ? 'inline-flex' : 'none';
        }

        updateCounters();
        updateSummary();
        syncLanguageCards();
        syncStatusCards();
        syncRootCards();
        syncDeckCards();
        saveState();
    }

    function validateCurrentStep() {
        var nameInput = getById('pageWizardNameInput');
        var contentInput = getById('pageWizardContentTextarea');

        if (currentStep === 3 && (!nameInput || nameInput.value.trim().length < 1)) {
            return (config.texts && config.texts.validationName) || 'Name is required.';
        }

        if (currentStep === 5) {
            if (typeof tinymce !== 'undefined' && tinymce.get('pageWizardContentTextarea')) {
                var editorContent = tinymce.get('pageWizardContentTextarea').getContent({ format: 'text' }).trim();
                if (!editorContent) {
                    return (config.texts && config.texts.validationContent) || 'Content is required.';
                }
            } else if (!contentInput || contentInput.value.trim().length < 1) {
                return (config.texts && config.texts.validationContent) || 'Content is required.';
            }
        }

        return '';
    }

    function generateSeoValues() {
        var nameInput = getById('pageWizardNameInput');
        var summaryInput = getById('pageWizardSummaryInput');
        var seoInput = getById('pageWizardSeoInput');
        var titleInput = getById('pageWizardTitleInput');
        var keywordsInput = getById('pageWizardKeywordsInput');
        var descriptionInput = getById('pageWizardDescriptionInput');
        var source = nameInput ? nameInput.value : '';

        if (!source) {
            return;
        }

        var normalized = source
            .normalize('NFKC')
            .replace(/[^\p{L}\p{N}\s_-]/gu, ' ')
            .replace(/\s+/g, ' ')
            .trim();

        if (seoInput && !seoFieldTouched.seo) {
            seoInput.value = normalized.replace(/\s+/g, '_').toLowerCase();
        }
        if (titleInput && !seoFieldTouched.title) {
            titleInput.value = source;
        }
        if (keywordsInput && !seoFieldTouched.keywords) {
            keywordsInput.value = normalized.replace(/\s+/g, ',').toLowerCase();
        }
        if (descriptionInput && !seoFieldTouched.description) {
            descriptionInput.value = (summaryInput && summaryInput.value.trim()) || source;
        }
    }

    function setupSeoFieldTracking() {
        var mapping = {
            pageWizardSeoInput: 'seo',
            pageWizardTitleInput: 'title',
            pageWizardKeywordsInput: 'keywords',
            pageWizardDescriptionInput: 'description'
        };

        Object.keys(mapping).forEach(function (id) {
            var element = getById(id);
            if (!element) {
                return;
            }

            element.addEventListener('input', function () {
                seoFieldTouched[mapping[id]] = true;
                saveState();
            });
        });
    }

    function showValidation(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                html: '<b>' + escapeHtml(message) + '</b>',
                confirmButtonColor: '#ba1a1a'
            });
            return;
        }

        window.alert(message);
    }

    function showValidationList(messages) {
        if (!messages || !messages.length) {
            return;
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                html: '<div style="text-align:left;"><b>' + messages.map(escapeHtml).join('<br>') + '</b></div>',
                confirmButtonColor: '#ba1a1a'
            });
            return;
        }

        window.alert(messages.join('\n'));
    }

    function initEditor() {
        if (typeof tinymce === 'undefined') {
            return;
        }

        tinymce.init({
            selector: '#pageWizardContentTextarea',
            height: 320,
            menubar: false,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | bullist numlist | removeformat | help',
            setup: function (editor) {
                editor.on('change keyup', function () {
                    editor.save();
                    saveState();
                });
            }
        });
    }

    function bindInputs() {
        ['pageWizardNameInput', 'pageWizardSummaryInput', 'pageWizardSeoInput', 'pageWizardTitleInput', 'pageWizardKeywordsInput', 'pageWizardDescriptionInput', 'pageWizardParentId', 'pageWizardCategoryId', 'pageWizardMenuId', 'pageWizardRootSelect', 'pageWizardDeckSelect', 'pageWizardGalleryId', 'pageWizardFileId', 'pageWizardAclId'].forEach(function (id) {
            var element = getById(id);
            if (!element) {
                return;
            }
            element.addEventListener('input', function () {
                updateCounters();
                updateSummary();
                saveState();
            });
            element.addEventListener('change', function () {
                updateSummary();
                saveState();
            });
        });

        var nameInput = getById('pageWizardNameInput');
        if (nameInput) {
            nameInput.addEventListener('input', function () {
                generateSeoValues();
                updateSummary();
                saveState();
            });
        }

        var summaryInput = getById('pageWizardSummaryInput');
        if (summaryInput) {
            summaryInput.addEventListener('input', function () {
                generateSeoValues();
                saveState();
            });
        }
    }

    function openPageWizard() {
        var wizard = wizardElement();
        if (!wizard) {
            return;
        }
        wizard.classList.add('active');
        wizard.style.visibility = 'visible';
        updateStepUI();
    }

    function closePageWizard() {
        if (window.location.pathname.indexOf('/mod_page/add') !== -1 && config.texts && config.texts.closeRedirect) {
            clearState(false);
            window.location.href = config.texts.closeRedirect;
            return;
        }

        var wizard = wizardElement();
        if (!wizard) {
            return;
        }
        wizard.classList.remove('active');
        clearState(false);
    }

    function nextPageWizardStep() {
        var validation = validateCurrentStep();
        if (validation) {
            showValidation(validation);
            return;
        }

        if (currentStep < totalSteps) {
            currentStep += 1;
            if (currentStep === totalSteps) {
                generateSeoValues();
            }
            updateStepUI();
        }
    }

    function prevPageWizardStep() {
        if (currentStep > 1) {
            currentStep -= 1;
            updateStepUI();
        }
    }

    function selectPageWizardLang(element, lang) {
        document.querySelectorAll('#pageWizardStep1 .selection-card').forEach(function (card) {
            card.classList.remove('active');
        });
        element.classList.add('active');
        if (getById('pageWizardHiddenLang')) {
            getById('pageWizardHiddenLang').value = String(lang || '').toLowerCase();
            getById('pageWizardHiddenLang').dispatchEvent(new Event('change', { bubbles: true }));
        }
        updateSummary();
        saveState();

        var currentLang = String(config.initialLang || '').toLowerCase();
        var nextLang = String(lang || '').toLowerCase();
        if (nextLang && nextLang !== currentLang) {
            var url = new URL(window.location.href);
            url.searchParams.set('lang', nextLang);
            window.location.href = url.toString();
        }
    }

    function selectPageWizardStatus(element, value) {
        document.querySelectorAll('#pageWizardStatusGrid .selection-card').forEach(function (card) {
            card.classList.remove('active');
        });
        element.classList.add('active');
        if (getById('pageWizardStatusHidden')) {
            getById('pageWizardStatusHidden').value = value;
        }
        saveState();
    }

    function selectPageWizardRoot(element, value) {
        document.querySelectorAll('#pageWizardRootGrid .selection-card').forEach(function (card) {
            card.classList.remove('active');
        });
        element.classList.add('active');
        if (getById('pageWizardRootSelect')) {
            getById('pageWizardRootSelect').value = value;
        }
        saveState();
    }

    function selectPageWizardDeck(element, value) {
        document.querySelectorAll('#pageWizardDeckGrid .selection-card').forEach(function (card) {
            card.classList.remove('active');
        });
        element.classList.add('active');
        if (getById('pageWizardDeckSelect')) {
            getById('pageWizardDeckSelect').value = value;
        }
        saveState();
    }

    function previewPageWizardImage(input) {
        var preview = getById('pageWizardImgPreview');
        if (!(input && input.files && input.files[0] && preview)) {
            return;
        }

        var reader = new FileReader();
        reader.onload = function (event) {
            preview.src = event.target.result;
            preview.style.display = 'block';
            preview.classList.remove('mod-page-wizard-preview-hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }

    function submitPageWizard() {
        var validation = validateCurrentStep();
        if (validation) {
            showValidation(validation);
            return;
        }

        var form = formElement();
        if (!form) {
            return;
        }

        if (typeof tinymce !== 'undefined' && tinymce.get('pageWizardContentTextarea')) {
            tinymce.get('pageWizardContentTextarea').save();
        }

        try {
            sessionStorage.setItem('pageWizardSubmittedAt', Date.now().toString());
        } catch (error) {
            console.warn('Page wizard submit flag error:', error);
        }
        form.submit();
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (!wizardElement()) {
            return;
        }

        if (config.hasSuccess) {
            return;
        }

        try {
            var submittedAt = sessionStorage.getItem('pageWizardSubmittedAt');
            if (submittedAt) {
                if (config.hasErrors) {
                    sessionStorage.removeItem('pageWizardSubmittedAt');
                } else {
                    clearState(false);
                    sessionStorage.removeItem('pageWizardSubmittedAt');
                }
            }
        } catch (error) {
            console.warn('Page wizard submission flag error:', error);
        }

        var hasState = loadState();
        var initialStep = parseInt(config.initialStep || 1, 10);
        if (config.hasErrors && !Number.isNaN(initialStep) && initialStep >= 1 && initialStep <= totalSteps) {
            currentStep = initialStep;
        } else if (hasState && !config.hasErrors) {
            currentStep = currentStep >= 1 && currentStep <= totalSteps ? currentStep : 1;
        } else if (!hasState && !Number.isNaN(initialStep) && initialStep >= 1 && initialStep <= totalSteps) {
            currentStep = initialStep;
        }
        initEditor();
        bindInputs();
        if (typeof initSelectPickerSheets === 'function') {
            initSelectPickerSheets();
        }
        setupSeoFieldTracking();
        generateSeoValues();
        updateCounters();
        updateSummary();
        updateStepUI();
        openPageWizard();

        if (config.hasErrors && Array.isArray(config.errorMessages) && config.errorMessages.length > 0) {
            showValidationList(config.errorMessages);
        }
    });

    window.openPageWizard = openPageWizard;
    window.closePageWizard = closePageWizard;
    window.nextPageWizardStep = nextPageWizardStep;
    window.prevPageWizardStep = prevPageWizardStep;
    window.selectPageWizardLang = selectPageWizardLang;
    window.selectPageWizardStatus = selectPageWizardStatus;
    window.selectPageWizardRoot = selectPageWizardRoot;
    window.selectPageWizardDeck = selectPageWizardDeck;
    window.previewPageWizardImage = previewPageWizardImage;
    window.submitPageWizard = submitPageWizard;
})();
