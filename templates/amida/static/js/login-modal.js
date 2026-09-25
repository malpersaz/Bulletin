document.addEventListener('DOMContentLoaded', function () {
    const loginModal = document.getElementById('loginModal');
    if (!loginModal) return;

    // Initialize messages from data attributes or global object
    const ds = loginModal.dataset || {};
    window.loginModalMessages = {
        signingIn: ds.i18nSigningIn || window.loginModalMessages?.signingIn || 'Signing in...',
        loginSuccess: ds.i18nLoginSuccess || window.loginModalMessages?.loginSuccess || 'Login successful!',
        loginFailed: ds.i18nLoginFailed || window.loginModalMessages?.loginFailed || 'Login failed.',
        connectionError: ds.i18nConnectionError || window.loginModalMessages?.connectionError || 'Connection error.',
        networkError: ds.i18nNetworkError || window.loginModalMessages?.networkError || 'Network error.',
        timeoutError: ds.i18nTimeoutError || window.loginModalMessages?.timeoutError || 'Timeout error.',
        serverError: ds.i18nServerError || window.loginModalMessages?.serverError || 'Server error.',
        pleaseFillAll: ds.i18nPleaseFillAll || window.loginModalMessages?.pleaseFillAll || 'Please fill in all fields.',
        redirecting: ds.i18nRedirecting || window.loginModalMessages?.redirecting || 'Redirecting...',
        alternativeOptions: ds.i18nAlternativeOptions || window.loginModalMessages?.alternativeOptions || '',
        showPassword: ds.i18nShowPassword || window.loginModalMessages?.showPassword || 'Show password',
        hidePassword: ds.i18nHidePassword || window.loginModalMessages?.hidePassword || 'Hide password',
        checkUserUrl: ds.urlCheckUser || window.loginModalMessages?.checkUserUrl || '/mod_user/checkUserExistence',
        userNotFound: ds.i18nUserNotFound || window.loginModalMessages?.userNotFound || 'User not found',
        usernameRequired: ds.i18nUsernameRequired || window.loginModalMessages?.usernameRequired || 'Username is required'
    };

    const loginForm = document.getElementById('loginForm');
    const step1 = document.getElementById('loginStep1');
    const step2 = document.getElementById('loginStep2');
    const btnNext = document.getElementById('btnNextStep');
    const btnBack = document.getElementById('btnBackToStep1');
    const btnTogglePassword = document.getElementById('togglePassword');
    const usernameInput = document.getElementById('loginUsername');
    const passwordInput = document.getElementById('loginPassword');
    const displayUsername = document.getElementById('displayUsername');
    const modalBody = loginModal.querySelector('.modal-body');

    if (!loginForm) return;

    // --- Step Transitions ---

    async function goToStep2() {
        const username = usernameInput.value.trim();
        if (!username) {
            showAlert(modalBody, window.loginModalMessages?.usernameRequired, 'warning');
            return;
        }

        setButtonLoading(btnNext, true, window.loginModalMessages?.signingIn);

        try {
            const formData = new FormData();
            formData.append('username', username);

            const response = await fetch(window.loginModalMessages?.checkUserUrl || '/mod_user/checkUserExistence', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.exists) {
                displayUsername.textContent = username;
                step1.classList.add('d-none');
                step2.classList.remove('d-none');
                step2.classList.add('animate__animated', 'animate__fadeInRight');
                passwordInput.focus();
                removeAlerts(modalBody);
            } else {
                showAlert(modalBody, data.message || window.loginModalMessages?.userNotFound, 'danger');
            }
        } catch (error) {
            showAlert(modalBody, window.loginModalMessages?.connectionError, 'danger');
        } finally {
            setButtonLoading(btnNext, false);
        }
    }

    function goToStep1() {
        step2.classList.add('d-none');
        step1.classList.remove('d-none');
        step1.classList.add('animate__animated', 'animate__fadeInLeft');
        usernameInput.focus();
    }

    const handleNext = function (e) {
        e.preventDefault();
        goToStep2();
    };

    const handleBack = function (e) {
        e.preventDefault();
        goToStep1();
    };

    // Support both click and touch for mobile responsiveness
    btnNext.addEventListener('click', handleNext);
    btnNext.addEventListener('touchstart', function (e) {
        if (!this.disabled) handleNext(e);
    }, { passive: false });

    btnBack.addEventListener('click', handleBack);
    btnBack.addEventListener('touchstart', handleBack, { passive: false });

    // Handle Enter key on username input (using keydown for better mobile support)
    usernameInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            goToStep2();
        }
    });

    // --- Password Toggle ---

    if (btnTogglePassword) {
        btnTogglePassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            const icon = this.querySelector('i');
            if (icon) {
                icon.classList.toggle('bi-eye');
                icon.classList.toggle('bi-eye-slash');
            }

            this.setAttribute('title', type === 'password' ?
                window.loginModalMessages?.showPassword :
                window.loginModalMessages?.hidePassword
            );
        });
    }

    // --- Form Submission ---

    loginForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const submitBtn = this.querySelector('[name="login"]');
        const formData = new FormData(this);
        formData.append('login', '');
        formData.append('ajax_request', 'true');

        setButtonLoading(submitBtn, true);

        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then(response => {
                const contentType = response.headers.get('content-type');
                if (response.ok && contentType && contentType.includes('application/json')) {
                    return response.json();
                } else {
                    return response.text().then(text => {
                        let errorMessage = window.loginModalMessages?.serverError;
                        if (text && text.trim().startsWith('{')) {
                            try {
                                const data = JSON.parse(text);
                                return data;
                            } catch (e) {
                                throw new Error(errorMessage);
                            }
                        }
                        throw new Error(errorMessage);
                    });
                }
            })
            .then(data => {
                if (data && data.success) {
                    handleLoginSuccess(data, loginModal, modalBody);
                } else {
                    setButtonLoading(submitBtn, false);
                    showAlert(modalBody, (data && data.message) || window.loginModalMessages?.loginFailed, 'danger');
                }
            })
            .catch(error => {
                setButtonLoading(submitBtn, false);
                showAlert(
                    modalBody,
                    window.loginModalMessages?.connectionError,
                    'danger'
                );
            });
    });

    // --- Helpers ---

    function setButtonLoading(button, isLoading, loadingText) {
        if (isLoading) {
            button.disabled = true;
            button.dataset.originalHtml = button.innerHTML;
            button.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                ${loadingText || window.loginModalMessages?.signingIn}
            `;
        } else {
            button.disabled = false;
            button.innerHTML = button.dataset.originalHtml || button.innerHTML;
        }
    }

    function handleLoginSuccess(data, modal, modalBody) {
        modalBody.innerHTML = `
            <div class="text-center py-4 animate__animated animate__zoomIn">
                <div class="mb-3">
                    <i class="bi bi-check-circle-fill text-success login-success-icon"></i>
                </div>
                <h4 class="text-success">${window.loginModalMessages?.loginSuccess}</h4>
                <p class="text-muted">${window.loginModalMessages?.redirecting}</p>
            </div>
        `;

        setTimeout(() => {
            window.location.href = data.redirect || '/';
        }, 1500);
    }

    function showAlert(container, message, type = 'danger') {
        removeAlerts(container);
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show mt-3 animate__animated animate__shakeX`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        if (container.firstChild) {
            container.insertBefore(alertDiv, container.firstChild);
        } else {
            container.appendChild(alertDiv);
        }
    }

    function removeAlerts(container) {
        const existingAlerts = container.querySelectorAll('.alert');
        existingAlerts.forEach(alert => alert.remove());
    }

    // --- Auto-open Modal from URL ---
    function checkAutoOpen() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('login') === 'modal') {
            if (typeof bootstrap !== 'undefined') {
                try {
                    const modal = new bootstrap.Modal(loginModal);
                    modal.show();

                    loginModal.addEventListener('shown.bs.modal', () => {
                        usernameInput.focus();
                    }, { once: true });

                    const url = new URL(window.location);
                    url.searchParams.delete('login');
                    window.history.replaceState({}, document.title, url.pathname + url.search);
                } catch (e) {
                }
            } else {
                setTimeout(checkAutoOpen, 200);
            }
        }
    }

    // Run after full page load to ensure Bootstrap etc. are ready
    window.addEventListener('load', checkAutoOpen);
});
