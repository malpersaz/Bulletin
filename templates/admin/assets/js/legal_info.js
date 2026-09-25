/**
 * Amed Framework
 * Legal & Tax Information Admin JS
 * templates/admin/assets/js/legal_info.js
 */

(function () {
    'use strict';

    function initLegalInfoAdmin() {
        var form = document.getElementById('legalInfoForm');
        if (!form) return;

        // Prevent duplicate listener bindings if already initialized
        if (form.dataset.legalInit === 'true') return;
        form.dataset.legalInit = 'true';

        // Operation Mode Types Card Toggles
        var operationCheckboxes = form.querySelectorAll('.operation-type-checkbox');
        operationCheckboxes.forEach(function (chk) {
            chk.addEventListener('change', function () {
                var card = chk.closest('.operation-type-card');
                if (card) {
                    if (chk.checked) {
                        card.classList.add('active');
                    } else {
                        card.classList.remove('active');
                    }
                }
            });
        });

        // Company / Organization Type Select State Toggle
        var companyTypeSelect = document.getElementById('company_type');
        var companyTypeWrapper = document.getElementById('companyTypeWrapper');
        if (companyTypeSelect && companyTypeWrapper) {
            var updateCompanyTypeState = function () {
                var val = companyTypeSelect.value;
                if (val && val.trim() !== '') {
                    companyTypeWrapper.classList.add('has-value');
                    companyTypeSelect.classList.add('is-selected');
                } else {
                    companyTypeWrapper.classList.remove('has-value');
                    companyTypeSelect.classList.remove('is-selected');
                }
            };

            companyTypeSelect.addEventListener('change', updateCompanyTypeState);
            updateCompanyTypeState();
        }

        var isDirty = false;
        var formInputs = form.querySelectorAll('input, select, textarea');

        formInputs.forEach(function (input) {
            input.addEventListener('change', function () {
                isDirty = true;
            });
            input.addEventListener('input', function () {
                isDirty = true;
            });
        });

        // Handle AJAX submission
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var submitBtn = document.getElementById('legalInfoSubmitBtn') || form.querySelector('button[type="submit"]');
            var originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm btn-spinner" role="status" aria-hidden="true"></span>' + (submitBtn.innerText || 'Saving...');
            }

            var formData = new FormData(form);
            var actionUrl = form.getAttribute('action') || window.location.href;

            fetch(actionUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                }

                if (data && data.status === 'success') {
                    isDirty = false;
                    if (navigator.vibrate) {
                        navigator.vibrate(10);
                    }

                    var successMsg = data.message || 'Legal information updated successfully.';
                    if (window.AFModalEngine && typeof window.AFModalEngine.showToast === 'function') {
                        window.AFModalEngine.showToast(successMsg, 'success');
                    } else if (typeof showAFToast === 'function') {
                        showAFToast(successMsg, 'success');
                    } else if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: successMsg,
                            timer: 2500,
                            showConfirmButton: false
                        });
                    }
                } else {
                    var errorMsg = (data && data.message) ? data.message : 'An error occurred while saving legal information.';
                    if (window.AFModalEngine && typeof window.AFModalEngine.showToast === 'function') {
                        window.AFModalEngine.showToast(errorMsg, 'error');
                    } else if (typeof showAFToast === 'function') {
                        showAFToast(errorMsg, 'error');
                    } else if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'error',
                            title: errorMsg,
                            timer: 3000,
                            showConfirmButton: false
                        });
                    }
                }
            })
            .catch(function (err) {
                console.error('AJAX save error:', err);
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                }

                var netErrMsg = 'Network error or request failed.';
                if (window.AFModalEngine && typeof window.AFModalEngine.showToast === 'function') {
                    window.AFModalEngine.showToast(netErrMsg, 'error');
                } else if (typeof showAFToast === 'function') {
                    showAFToast(netErrMsg, 'error');
                }
            });
        });

        window.addEventListener('beforeunload', function (e) {
            if (isDirty) {
                var message = 'You have unsaved changes.';
                e.returnValue = message;
                return message;
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLegalInfoAdmin);
    } else {
        initLegalInfoAdmin();
    }
})();
