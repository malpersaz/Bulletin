/**
 * Menu Module (mod_menu) Mobile Wizard
 * Material 3 Phone-First Interactive Logic
 * Compliant with Taste Skill, Zero Inline Scripts, and Phone-Only Contract
 */

(function () {
    'use strict';

    var totalSteps = 10;
    var currentStep = 1;
    var storageKey = 'modMenuWizardState';

    function getById(id) {
        return document.getElementById(id);
    }

    function getOverlay() {
        return getById('menuWizardOverlay');
    }

    function getForm() {
        return getById('menuWizardForm');
    }

    function getStepTitles() {
        var overlay = getOverlay();
        var titles = {};
        for (var i = 1; i <= totalSteps; i++) {
            titles[i] = overlay ? (overlay.getAttribute('data-step-title-' + i) || ('Gav ' + i)) : ('Gav ' + i);
        }
        return titles;
    }

    function saveState() {
        var nameInput = getById('menuWizardNameInput');
        var parentSelect = getById('menuWizardParentSelect');
        var urlInput = getById('menuWizardUrlInput');
        var fileInput = getById('menuWizardFileInput');
        var iconInput = getById('menuWizardIconInput');
        var htmlInput = getById('menuWizardHtmlInput');
        var targetInput = getById('menuWizardTargetHidden');
        var statusInput = getById('menuWizardStatusHidden');
        var aclSelect = getById('menuWizardAclSelect');

        var state = {
            name: nameInput ? nameInput.value : '',
            parent_id: parentSelect ? parentSelect.value : '',
            url: urlInput ? urlInput.value : '',
            file: fileInput ? fileInput.value : '',
            icon: iconInput ? iconInput.value : '',
            html: htmlInput ? htmlInput.value : '',
            target: targetInput ? targetInput.value : 'n',
            status: statusInput ? statusInput.value : 'y',
            acl_id: aclSelect ? aclSelect.value : '0'
        };

        try {
            sessionStorage.setItem(storageKey, JSON.stringify(state));
        } catch (e) {
            // Storage quota or disabled
        }
    }

    function restoreState() {
        try {
            var raw = sessionStorage.getItem(storageKey);
            if (!raw) {
                return;
            }
            var state = JSON.parse(raw);
            if (!state || typeof state !== 'object') {
                return;
            }

            var nameInput = getById('menuWizardNameInput');
            if (nameInput && !nameInput.value && state.name) {
                nameInput.value = state.name;
                updateCharCounter();
            }

            var urlInput = getById('menuWizardUrlInput');
            if (urlInput && !urlInput.value && state.url) {
                urlInput.value = state.url;
            }

            var fileInput = getById('menuWizardFileInput');
            if (fileInput && !fileInput.value && state.file) {
                fileInput.value = state.file;
            }

            var iconInput = getById('menuWizardIconInput');
            if (iconInput && !iconInput.value && state.icon) {
                iconInput.value = state.icon;
                updateIconPreview(state.icon);
            }

            var htmlInput = getById('menuWizardHtmlInput');
            if (htmlInput && !htmlInput.value && state.html) {
                htmlInput.value = state.html;
            }

            var targetInput = getById('menuWizardTargetHidden');
            if (targetInput && state.target) {
                setTargetValue(state.target);
            }

            var statusInput = getById('menuWizardStatusHidden');
            if (statusInput && state.status) {
                setStatusValue(state.status);
            }

            var aclSelect = getById('menuWizardAclSelect');
            if (aclSelect && state.acl_id !== undefined && state.acl_id !== null) {
                setAclValue(state.acl_id);
            }
        } catch (e) {
            // Error parsing session state
        }
    }

    function clearState() {
        try {
            sessionStorage.removeItem(storageKey);
        } catch (e) {
            // Storage quota or disabled
        }
    }

    function updateStepUI() {
        var titles = getStepTitles();
        var stepNameEl = getById('menuWizardStepName');
        var stepCounterEl = getById('menuWizardStepIndicator');
        var progressFill = getById('menuWizardProgressFill');
        var btnPrev = getById('menuWizardBtnPrev');
        var btnNext = getById('menuWizardBtnNext');
        var btnFinish = getById('menuWizardBtnFinish');

        if (stepNameEl) {
            stepNameEl.textContent = titles[currentStep] || '';
        }

        if (stepCounterEl) {
            stepCounterEl.textContent = currentStep + ' / ' + totalSteps;
        }

        if (progressFill) {
            var pct = Math.round((currentStep / totalSteps) * 100);
            progressFill.style.width = pct + '%';
        }

        // Show/hide step panels
        for (var i = 1; i <= totalSteps; i++) {
            var panel = getById('menuWizardStep' + i);
            if (panel) {
                if (i === currentStep) {
                    panel.classList.add('active');
                } else {
                    panel.classList.remove('active');
                }
            }
        }

        // Navigation buttons state
        if (btnPrev) {
            btnPrev.disabled = (currentStep === 1);
        }

        if (currentStep === 3) {
            var parentSelect = getById('menuWizardParentSelect');
            if (parentSelect && (!parentSelect.value || parentSelect.value === '')) {
                for (var pi = 0; pi < parentSelect.options.length; pi++) {
                    if (!parentSelect.options[pi].disabled && parentSelect.options[pi].value !== '') {
                        parentSelect.selectedIndex = pi;
                        break;
                    }
                }
            }
            updateHierarchyPreview();
        }

        if (currentStep === 2) {
            updateLiveNavPreview();
        }

        if (btnNext && btnFinish) {
            if (currentStep === totalSteps) {
                btnNext.style.display = 'none';
                btnFinish.style.display = 'inline-flex';
                updateSummary();
            } else {
                btnNext.style.display = 'inline-flex';
                btnFinish.style.display = 'none';
            }
        }

        // Scroll to top of content
        var content = document.querySelector('.menu-wizard-content');
        if (content) {
            content.scrollTop = 0;
        }
    }

    function validateCurrentStep() {
        if (currentStep === 1) {
            var langInput = getById('menuWizardLangInput');
            if (!langInput || !langInput.value.trim()) {
                return 'Zimanekî hilbijêre';
            }
            return null;
        }

        if (currentStep === 2) {
            var nameInput = getById('menuWizardNameInput');
            var nameValidation = getById('menuNameValidation');

            if (!nameInput || nameInput.value.trim().length < 2) {
                if (nameInput) {
                    nameInput.classList.add('has-error');
                    nameInput.focus();
                }
                if (nameValidation) {
                    nameValidation.classList.add('visible');
                }
                return 'Navê pêşekê pêwîst e';
            } else {
                if (nameInput) {
                    nameInput.classList.remove('has-error');
                }
                if (nameValidation) {
                    nameValidation.classList.remove('visible');
                }
            }
            return null;
        }

        if (currentStep === 3) {
            var parentSelect = getById('menuWizardParentSelect');
            var parentValidation = getById('menuParentValidation');

            if (parentSelect && (!parentSelect.value || parentSelect.value === '')) {
                for (var pi = 0; pi < parentSelect.options.length; pi++) {
                    if (!parentSelect.options[pi].disabled && parentSelect.options[pi].value !== '') {
                        parentSelect.selectedIndex = pi;
                        break;
                    }
                }
            }

            if (!parentSelect || parentSelect.value === '' || parentSelect.value === null) {
                if (parentValidation) {
                    parentValidation.classList.add('visible');
                }
                if (parentSelect) {
                    parentSelect.classList.add('has-error');
                    parentSelect.focus();
                }
                return 'Divê pêşeka dayîk were hilbijartin';
            } else {
                if (parentValidation) {
                    parentValidation.classList.remove('visible');
                }
                if (parentSelect) {
                    parentSelect.classList.remove('has-error');
                }
            }
            return null;
        }

        // Steps 4 through 10 (URL, Icon, File, HTML, Target, ACL, Status & Summary)
        return null;
    }

    function checkSessionAlive(callback) {
        var checkUrl = (window.SITE_URL || '/') + 'mod_user/session_check?ajax=1';
        fetch(checkUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            cache: 'no-store'
        })
        .then(function (res) {
            if (res.status === 401) {
                return res.json().then(function (data) {
                    callback(false, data);
                }).catch(function () {
                    callback(false, { redirect: (window.SITE_URL || '/') + '?login=modal&session_expired=1' });
                });
            }
            if (!res.ok) {
                callback(false, { redirect: (window.SITE_URL || '/') + '?login=modal&session_expired=1' });
                return;
            }
            return res.json().then(function (data) {
                if (data && data.authenticated === true) {
                    callback(true, data);
                } else {
                    callback(false, data);
                }
            }).catch(function () {
                callback(true, null);
            });
        })
        .catch(function () {
            callback(true, null);
        });
    }

    function showSessionExpiredModal(redirectUrl) {
        saveState();
        var targetLogin = redirectUrl || ((window.SITE_URL || '/') + '?login=modal&session_expired=1');
        var lang = window.SITE_LANG || 'tr';

        var title = 'Danişîn bi dawî bû!';
        var text = 'Dema danişîna we qediya ye. Hemû agahiyên we di gerokê de hatin parastin. Ji kerema xwe ji nû ve têkevin da ku tomarkirin winda nebe.';
        var btnLogin = 'Têkeve';
        var btnCancel = 'Betal bike';

        if (lang === 'tr') {
            title = 'Oturum Sona Erdi!';
            text = 'Güvenliğiniz için oturum süresi doldu. Girdiğiniz tüm bilgiler tarayıcınızda korundu. Kaydetmek için lütfen tekrar giriş yapın.';
            btnLogin = 'Giriş Yap';
            btnCancel = 'İptal';
        } else if (lang === 'en') {
            title = 'Session Expired!';
            text = 'Your session has timed out. All your input data is preserved. Please log in again to save.';
            btnLogin = 'Log In';
            btnCancel = 'Cancel';
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: title,
                text: text,
                showCancelButton: true,
                confirmButtonText: btnLogin,
                cancelButtonText: btnCancel,
                confirmButtonColor: '#1a73e8',
                allowOutsideClick: false
            }).then(function (result) {
                if (result.isConfirmed) {
                    window.open(targetLogin, '_blank');
                }
            });
        } else {
            alert(text);
            window.open(targetLogin, '_blank');
        }
    }

    function goToNextStep() {
        var error = validateCurrentStep();
        if (error) {
            return;
        }

        saveState();

        checkSessionAlive(function (isAlive, data) {
            if (!isAlive) {
                showSessionExpiredModal(data ? data.redirect : null);
                return;
            }

            if (currentStep < totalSteps) {
                currentStep++;
                updateStepUI();
            }
        });
    }

    function goToPrevStep() {
        if (currentStep > 1) {
            currentStep--;
            updateStepUI();
        }
    }

    function setTargetValue(val) {
        var hidden = getById('menuWizardTargetHidden');
        if (hidden) {
            hidden.value = val;
        }

        var cards = document.querySelectorAll('.menu-selection-card[data-target-value]');
        cards.forEach(function (card) {
            if (card.getAttribute('data-target-value') === val) {
                card.classList.add('active');
            } else {
                card.classList.remove('active');
            }
        });
        saveState();
    }

    function setStatusValue(val) {
        var hidden = getById('menuWizardStatusHidden');
        if (hidden) {
            hidden.value = val;
        }

        var cards = document.querySelectorAll('.menu-selection-card[data-status-value]');
        cards.forEach(function (card) {
            if (card.getAttribute('data-status-value') === val) {
                card.classList.add('active');
            } else {
                card.classList.remove('active');
            }
        });
        saveState();
        if (currentStep === totalSteps) {
            updateSummary();
        }
    }

    function setAclValue(val) {
        var aclSelect = getById('menuWizardAclSelect');
        if (aclSelect) {
            aclSelect.value = val;
        }

        var cards = document.querySelectorAll('.menu-acl-card[data-acl-value]');
        cards.forEach(function (card) {
            if (String(card.getAttribute('data-acl-value')) === String(val)) {
                card.classList.add('active');
            } else {
                card.classList.remove('active');
            }
        });
        saveState();
    }

    function handleLangSelection(card) {
        var selectedLang = card.getAttribute('data-lang-code');
        if (!selectedLang) {
            return;
        }

        var langInput = getById('menuWizardLangInput');
        var currentLang = langInput ? langInput.value : '';

        // If same language, simply select card and go next
        if (currentLang === selectedLang) {
            document.querySelectorAll('.menu-selection-card[data-lang-code]').forEach(function (c) {
                c.classList.remove('active');
            });
            card.classList.add('active');
            goToNextStep();
            return;
        }

        // Language changed: Save current state and reload with new language context
        if (langInput) {
            langInput.value = selectedLang;
        }
        saveState();

        var currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('lang', selectedLang);
        currentUrl.searchParams.set('step', '2');
        window.location.href = currentUrl.toString();
    }

    function updateIconPreview(iconClass) {
        var preview = getById('menuWizardIconPreview');
        if (!preview) {
            return;
        }

        var cleanIcon = (iconClass || '').trim();

        // Animate pop effect
        preview.style.transform = 'scale(0.8)';

        setTimeout(function () {
            if (cleanIcon) {
                // Ensure class has bi if not present
                var finalClass = cleanIcon;
                if (finalClass.indexOf('bi-') !== -1 && finalClass.indexOf('bi ') === -1) {
                    finalClass = 'bi ' + finalClass;
                }
                preview.innerHTML = '<i class="' + finalClass + '"></i>';
                preview.classList.add('has-icon');
            } else {
                preview.innerHTML = '<i class="bi bi-question"></i>';
                preview.classList.remove('has-icon');
            }
            preview.style.transform = 'scale(1.1)';

            setTimeout(function () {
                preview.style.transform = 'scale(1)';
            }, 150);
        }, 120);
    }

    function updateCharCounter() {
        var nameInput = getById('menuWizardNameInput');
        var counter = getById('menuCharCounter');
        if (nameInput && counter) {
            var len = nameInput.value.length;
            counter.textContent = len + ' / 255';
        }
    }

    function updateHierarchyPreview() {
        var parentSelect = getById('menuWizardParentSelect');
        var parentNameEl = getById('menuHierarchyParentName');
        var childNameEl = getById('menuHierarchyChildName');
        var nameInput = getById('menuWizardNameInput');

        if (parentNameEl && parentSelect) {
            var parentText = '-';
            if (parentSelect.selectedIndex >= 0 && parentSelect.options[parentSelect.selectedIndex]) {
                parentText = parentSelect.options[parentSelect.selectedIndex].text.replace(/\u00a0/g, ' ').trim();
            }
            parentNameEl.textContent = parentText || '-';
        }

        if (childNameEl) {
            var nameVal = nameInput ? nameInput.value.trim() : '';
            var fallback = (window.SITE_LANG === 'ku' ? 'Hêmana Nû' : (window.SITE_LANG === 'en' ? 'New Menu Item' : 'Yeni Menü Öğesi'));
            childNameEl.textContent = nameVal || fallback;
        }
    }

    function updateLiveNavPreview() {
        var nameInput = getById('menuWizardNameInput');
        var iconInput = getById('menuWizardIconInput');
        var liveNavText = getById('menuLiveNavText');
        var liveNavIcon = getById('menuLiveNavIcon');

        if (liveNavText) {
            var text = nameInput ? nameInput.value.trim() : '';
            var placeholder = (window.SITE_LANG === 'ku' ? 'Navê Menûyê' : (window.SITE_LANG === 'en' ? 'Menu Name' : 'Menü Adı'));
            liveNavText.textContent = text || placeholder;
        }

        if (liveNavIcon && iconInput) {
            var iconClass = (iconInput.value || '').trim();
            if (iconClass) {
                if (iconClass.indexOf('bi-') !== -1 && iconClass.indexOf('bi ') === -1) {
                    iconClass = 'bi ' + iconClass;
                }
                liveNavIcon.className = iconClass;
            } else {
                liveNavIcon.className = 'bi bi-list';
            }
        }
    }

    function updateSummary() {
        var langInput = getById('menuWizardLangInput');
        var nameInput = getById('menuWizardNameInput');
        var parentSelect = getById('menuWizardParentSelect');
        var urlInput = getById('menuWizardUrlInput');
        var iconInput = getById('menuWizardIconInput');
        var targetInput = getById('menuWizardTargetHidden');
        var statusInput = getById('menuWizardStatusHidden');
        var aclSelect = getById('menuWizardAclSelect');

        // Summary elements
        var sumLang = getById('menuSummaryLang');
        var sumName = getById('menuSummaryName');
        var sumParent = getById('menuSummaryParent');
        var sumUrl = getById('menuSummaryUrl');
        var sumTarget = getById('menuSummaryTarget');
        var sumStatus = getById('menuSummaryStatus');
        var sumAcl = getById('menuSummaryAcl');

        if (sumLang && langInput) {
            sumLang.textContent = (langInput.value || '').toUpperCase();
        }

        if (sumName && nameInput) {
            var iconPart = '';
            if (iconInput && iconInput.value.trim()) {
                var iconVal = iconInput.value.trim();
                if (iconVal.indexOf('bi-') !== -1 && iconVal.indexOf('bi ') === -1) {
                    iconVal = 'bi ' + iconVal;
                }
                iconPart = '<i class="' + iconVal + ' menu-summary-icon"></i> ';
            }
            sumName.innerHTML = iconPart + (nameInput.value.trim() || '-');
        }

        if (sumParent && parentSelect) {
            var parentText = '-';
            if (parentSelect.selectedIndex >= 0) {
                parentText = parentSelect.options[parentSelect.selectedIndex].text.trim();
            }
            sumParent.textContent = parentText;
        }

        if (sumUrl && urlInput) {
            sumUrl.textContent = urlInput.value.trim() || '#';
        }

        if (sumTarget && targetInput) {
            var overlay = getOverlay();
            var sameWinText = overlay ? (overlay.getAttribute('data-text-same-window') || 'Heman Pencere') : 'Heman Pencere';
            var newWinText = overlay ? (overlay.getAttribute('data-text-new-window') || 'Pencereya Nû') : 'Pencereya Nû';
            sumTarget.textContent = (targetInput.value === 'y') ? newWinText : sameWinText;
        }

        if (sumStatus && statusInput) {
            var isPub = (statusInput.value === 'y');
            var overlay = getOverlay();
            var pubText = overlay ? (overlay.getAttribute('data-text-published') || 'Weşandî') : 'Weşandî';
            var draftText = overlay ? (overlay.getAttribute('data-text-draft') || 'Pêşnivîs') : 'Pêşnivîs';
            var badgeClass = isPub ? 'badge-published' : 'badge-draft';
            var iconClass = isPub ? 'bi-eye-fill' : 'bi-eye-slash-fill';

            sumStatus.innerHTML = '<span class="menu-summary-badge ' + badgeClass + '"><i class="bi ' + iconClass + '"></i> ' + (isPub ? pubText : draftText) + '</span>';
        }

        if (sumAcl && aclSelect) {
            var aclText = '-';
            if (aclSelect.selectedIndex >= 0) {
                aclText = aclSelect.options[aclSelect.selectedIndex].text.trim();
            }
            sumAcl.textContent = aclText;
        }
    }

    function submitWizardForm() {
        var form = getForm();
        if (!form) {
            return;
        }

        // Run validation across step 1, 2, and 3
        var err1 = null;
        currentStep = 1;
        err1 = validateCurrentStep();
        if (err1) {
            updateStepUI();
            return;
        }

        currentStep = 2;
        var err2 = validateCurrentStep();
        if (err2) {
            updateStepUI();
            return;
        }

        currentStep = 3;
        var err3 = validateCurrentStep();
        if (err3) {
            updateStepUI();
            return;
        }

        currentStep = totalSteps;
        updateStepUI();
        saveState();

        // Check session liveness before submitting
        checkSessionAlive(function (isAlive, data) {
            if (!isAlive) {
                showSessionExpiredModal(data ? data.redirect : null);
                return;
            }

            // Ensure 'add' hidden input is present
            if (!form.querySelector('input[name="add"]')) {
                var addHidden = document.createElement('input');
                addHidden.type = 'hidden';
                addHidden.name = 'add';
                addHidden.value = '1';
                form.appendChild(addHidden);
            }

            // Submit form without prematurely wiping sessionStorage draft
            form.submit();
        });
    }

    function initServerErrors() {
        var overlay = getOverlay();
        if (!overlay) {
            return;
        }

        var hasErrors = overlay.getAttribute('data-has-errors') === 'true';
        if (!hasErrors) {
            return;
        }

        var rawErrors = overlay.getAttribute('data-error-messages');
        var errors = [];
        try {
            errors = JSON.parse(rawErrors || '[]');
        } catch (e) {
            errors = [];
        }

        if (errors.length > 0 && typeof Swal !== 'undefined') {
            var errorHtml = '<div class="menu-error-popup-wrap">';
            errors.forEach(function (err) {
                errorHtml += '<div class="menu-error-popup-item"><i class="bi bi-exclamation-triangle-fill menu-error-popup-icon"></i> <span>' + err + '</span></div>';
            });
            errorHtml += '</div>';

            Swal.fire({
                icon: 'warning',
                html: errorHtml,
                confirmButtonText: overlay.getAttribute('data-text-ok') || 'Temam',
                confirmButtonColor: '#ba1a1a'
            });

            // Target step 2 or 3 depending on error content
            var targetStep = 2;
            errors.forEach(function (err) {
                var lower = (err || '').toLowerCase();
                if (lower.indexOf('parent') !== -1 || lower.indexOf('üst') !== -1 || lower.indexOf('dayîk') !== -1) {
                    targetStep = 3;
                }
            });
            currentStep = targetStep;
            updateStepUI();
        }
    }

    function initEvents() {
        // Step buttons
        var btnNext = getById('menuWizardBtnNext');
        if (btnNext) {
            btnNext.addEventListener('click', function (e) {
                e.preventDefault();
                goToNextStep();
            });
        }

        var btnPrev = getById('menuWizardBtnPrev');
        if (btnPrev) {
            btnPrev.addEventListener('click', function (e) {
                e.preventDefault();
                goToPrevStep();
            });
        }

        var btnFinish = getById('menuWizardBtnFinish');
        if (btnFinish) {
            btnFinish.addEventListener('click', function (e) {
                e.preventDefault();
                submitWizardForm();
            });
        }

        // Close button
        var btnClose = getById('menuWizardCloseBtn');
        if (btnClose) {
            btnClose.addEventListener('click', function (e) {
                e.preventDefault();
                clearState();
                var redirectUrl = btnClose.getAttribute('data-redirect-url');
                if (redirectUrl) {
                    window.location.href = redirectUrl;
                } else {
                    window.history.back();
                }
            });
        }

        // Language selection cards
        var langCards = document.querySelectorAll('.menu-selection-card[data-lang-code]');
        langCards.forEach(function (card) {
            card.addEventListener('click', function () {
                handleLangSelection(card);
            });
        });

        // Target selection cards
        var targetCards = document.querySelectorAll('.menu-selection-card[data-target-value]');
        targetCards.forEach(function (card) {
            card.addEventListener('click', function () {
                var val = card.getAttribute('data-target-value');
                setTargetValue(val);
            });
        });

        // Status selection cards
        var statusCards = document.querySelectorAll('.menu-selection-card[data-status-value]');
        statusCards.forEach(function (card) {
            card.addEventListener('click', function () {
                var val = card.getAttribute('data-status-value');
                setStatusValue(val);
            });
        });

        // ACL selection cards
        var aclCards = document.querySelectorAll('.menu-acl-card[data-acl-value]');
        aclCards.forEach(function (card) {
            card.addEventListener('click', function () {
                var val = card.getAttribute('data-acl-value');
                setAclValue(val);
            });
        });

        // ACL select synchronization
        var aclSelect = getById('menuWizardAclSelect');
        if (aclSelect) {
            aclSelect.addEventListener('change', function () {
                setAclValue(this.value);
            });
        }

        // Summary jump-to-step buttons
        var jumpBtns = document.querySelectorAll('.menu-summary-jump-btn[data-jump-step]');
        jumpBtns.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var targetStep = parseInt(this.getAttribute('data-jump-step'), 10);
                if (targetStep >= 1 && targetStep <= totalSteps) {
                    currentStep = targetStep;
                    updateStepUI();
                }
            });
        });

        // Icon input and preview
        var iconInput = getById('menuWizardIconInput');
        if (iconInput) {
            iconInput.addEventListener('input', function () {
                updateIconPreview(this.value);
                updateLiveNavPreview();
                saveState();
            });
        }

        // Quick icon chips
        var iconChips = document.querySelectorAll('.menu-icon-chip[data-icon]');
        iconChips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                var iconVal = chip.getAttribute('data-icon');
                if (iconInput) {
                    iconInput.value = iconVal;
                    updateIconPreview(iconVal);
                    updateLiveNavPreview();
                    saveState();
                }
            });
        });

        // Quick link preset chips
        var presetChips = document.querySelectorAll('.menu-preset-chip[data-url]');
        presetChips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                var urlVal = chip.getAttribute('data-url');
                var urlInput = getById('menuWizardUrlInput');
                if (urlInput) {
                    urlInput.value = urlVal;
                    urlInput.focus();
                    saveState();
                }
            });
        });

        // Name input character counter, preview and validation reset
        var nameInput = getById('menuWizardNameInput');
        if (nameInput) {
            nameInput.addEventListener('input', function () {
                updateCharCounter();
                updateLiveNavPreview();
                updateHierarchyPreview();
                this.classList.remove('has-error');
                var valMsg = getById('menuNameValidation');
                if (valMsg) {
                    valMsg.classList.remove('visible');
                }
                saveState();
            });
        }

        // Parent select validation reset and hierarchy preview update
        var parentSelect = getById('menuWizardParentSelect');
        if (parentSelect) {
            parentSelect.addEventListener('change', function () {
                this.classList.remove('has-error');
                updateHierarchyPreview();
                var valMsg = getById('menuParentValidation');
                if (valMsg) {
                    valMsg.classList.remove('visible');
                }
                saveState();
            });
        }

        // Accordion toggle
        var accordionTrigger = getById('menuAccordionTrigger');
        var accordionCard = getById('menuAccordionCard');
        if (accordionTrigger && accordionCard) {
            accordionTrigger.addEventListener('click', function () {
                accordionCard.classList.toggle('open');
            });
        }

        // Form inputs auto-save
        var inputsToWatch = [
            'menuWizardUrlInput',
            'menuWizardFileInput',
            'menuWizardHtmlInput',
            'menuWizardAclSelect'
        ];
        inputsToWatch.forEach(function (id) {
            var el = getById(id);
            if (el) {
                el.addEventListener('change', saveState);
            }
        });
    }

    function init() {
        var overlay = getOverlay();
        if (!overlay) {
            return;
        }

        // Activate overlay
        overlay.classList.add('active');

        // Check if step requested via URL param (e.g. after lang switch)
        var urlParams = new URLSearchParams(window.location.search);
        var initialStepParam = parseInt(urlParams.get('step'), 10);
        if (initialStepParam && initialStepParam >= 1 && initialStepParam <= totalSteps) {
            currentStep = initialStepParam;
        }

        // Restore form data from session
        restoreState();

        // Initial preview and counters
        var iconInput = getById('menuWizardIconInput');
        if (iconInput && iconInput.value) {
            updateIconPreview(iconInput.value);
        }
        updateCharCounter();
        updateLiveNavPreview();
        updateHierarchyPreview();

        var aclSelect = getById('menuWizardAclSelect');
        if (aclSelect) {
            setAclValue(aclSelect.value || '0');
        }

        // Bind events
        initEvents();

        // Update UI
        updateStepUI();

        // Check server errors
        initServerErrors();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
