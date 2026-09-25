/**
 * Amed Framework - Installer Client Script
 * Handles language preview/saving, database engine toggling, and international phone input formatting.
 */

(function () {
    'use strict';

    // 1. Language selector handling
    function initLanguageForm() {
        var languageForm = document.getElementById('language-form');
        if (!languageForm) {
            return;
        }

        var languageAction = document.getElementById('language-action');
        var languageInputs = languageForm.querySelectorAll('input[name="installer_language"]');
        var saveButton = document.getElementById('language-save-button');

        for (var i = 0; i < languageInputs.length; i += 1) {
            languageInputs[i].addEventListener('change', function () {
                if (languageAction) {
                    languageAction.value = 'preview_language';
                }
                languageForm.submit();
            });
        }

        if (saveButton && languageAction) {
            saveButton.addEventListener('click', function () {
                languageAction.value = 'save_language';
            });
        }
    }

    // 2. Database adapter switching
    function initDatabaseAdapter() {
        var dbForm = document.getElementById('db_form');
        if (!dbForm) {
            return;
        }

        var adapterRadios = dbForm.querySelectorAll('input[name="database_adapter"]');
        function updateAdapter() {
            var selected = dbForm.querySelector('input[name="database_adapter"]:checked');
            if (selected) {
                dbForm.dataset.adapter = selected.value;
            }
        }

        for (var i = 0; i < adapterRadios.length; i += 1) {
            adapterRadios[i].addEventListener('change', updateAdapter);
        }

        updateAdapter();
    }

    // 3. International telephone input handling
    function initPhoneInputs() {
        if (typeof window.intlTelInput !== 'function') {
            return;
        }

        function initSinglePhoneInput(inputId) {
            var input = document.getElementById(inputId);
            if (!input || input.dataset.intlReady === '1') {
                return;
            }

            var targetId = input.dataset.countryTargetId;
            var hiddenInput = targetId ? document.getElementById(targetId) : null;
            var form = input.closest('form');

            var getInitialCountry = function (callback) {
                if (typeof fetch === 'function') {
                    fetch('https://ipapi.co/json')
                        .then(function (res) { return res.json(); })
                        .then(function (data) { callback(data && data.country_code ? data.country_code.toLowerCase() : ''); })
                        .catch(function () { callback(''); });
                } else {
                    callback('');
                }
            };

            var iti = window.intlTelInput(input, {
                initialCountry: 'auto',
                geoIpLookup: getInitialCountry,
                separateDialCode: true,
                strictMode: true,
                autoPlaceholder: 'aggressive'
            });

            function syncCountryCode() {
                if (!hiddenInput) {
                    return;
                }
                var countryData = iti.getSelectedCountryData();
                if (countryData && countryData.dialCode) {
                    hiddenInput.value = '+' + countryData.dialCode;
                }
            }

            if (input.value && input.value.trim() !== '') {
                iti.setNumber(input.value.trim());
            }

            syncCountryCode();

            input.addEventListener('input', function () {
                var val = input.value.trim();
                if (val.indexOf('0') === 0) {
                    input.value = val.replace(/^0+/, '');
                }
            });

            input.addEventListener('countrychange', syncCountryCode);

            if (form && form.dataset.installerPhoneBound !== '1') {
                form.addEventListener('submit', function () {
                    var phoneInputs = form.querySelectorAll('input[data-phone-store-mode="full"]');
                    for (var j = 0; j < phoneInputs.length; j += 1) {
                        var pInput = phoneInputs[j];
                        var pIti = window.intlTelInput.getInstance(pInput);
                        var cTarget = document.getElementById(pInput.dataset.countryTargetId);

                        if (pIti) {
                            var cData = pIti.getSelectedCountryData();
                            if (cTarget && cData && cData.dialCode) {
                                cTarget.value = '+' + cData.dialCode;
                            }

                            var digits = pInput.value.replace(/\D/g, '');
                            if (cData && cData.dialCode) {
                                digits = digits.replace(/^0+/, '');
                                pInput.value = '+' + cData.dialCode + digits;
                            } else {
                                pInput.value = digits;
                            }
                        }
                    }
                });

                form.dataset.installerPhoneBound = '1';
            }

            input.dataset.intlReady = '1';
        }

        initSinglePhoneInput('project_phone');
        initSinglePhoneInput('project_whatsapp');
    }

    // Initialize all components on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initLanguageForm();
            initDatabaseAdapter();
            initPhoneInputs();
        });
    } else {
        initLanguageForm();
        initDatabaseAdapter();
        initPhoneInputs();
    }
}());
