document.addEventListener('DOMContentLoaded', function () {
    const loginModal = document.getElementById('loginModal');
    if (!loginModal) return;

    const loginForm = loginModal.querySelector('form');
    if (!loginForm) return;

    // --- Added Step Transition Elements ---
    const step1 = document.getElementById('loginStep1');
    const step2 = document.getElementById('loginStep2');
    const btnNext = document.getElementById('btnNextStep');
    const btnBack = document.getElementById('btnBackToStep1');
    const btnTogglePassword = document.getElementById('togglePassword');
    const usernameInput = document.getElementById('loginUsername');
    const passwordInput = document.getElementById('loginPassword');
    const displayUsername = document.getElementById('displayUsername');
    const modalBody = loginModal.querySelector('.modal-body');

    // --- Step Transitions ---
    async function goToStep2() {
        const username = usernameInput ? usernameInput.value.trim() : '';
        if (!username) {
            showAlert(modalBody, window.loginModalMessages?.pleaseFillAll || 'Bikirhêner pêwîst e', 'warning');
            return;
        }

        if (btnNext) setButtonLoading(btnNext, true);

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
                if (displayUsername) displayUsername.textContent = username;
                if (step1) step1.classList.add('d-none');
                if (step2) {
                    step2.classList.remove('d-none');
                    step2.classList.add('animate__animated', 'animate__fadeInRight');
                }
                if (passwordInput) passwordInput.focus();
                removeAlerts(modalBody);
            } else {
                showAlert(modalBody, data.message || 'Bikirhêner nehat dîtin', 'danger');
            }
        } catch (error) {
            console.error('Check user error:', error);
            showAlert(modalBody, window.loginModalMessages?.connectionError || 'Çewtiya têkiliyê', 'danger');
        } finally {
            if (btnNext) setButtonLoading(btnNext, false);
        }
    }

    function goToStep1() {
        if (step2) step2.classList.add('d-none');
        if (step1) {
            step1.classList.remove('d-none');
            step1.classList.add('animate__animated', 'animate__fadeInLeft');
        }
        if (usernameInput) usernameInput.focus();
    }

    const handleNext = function (e) {
        e.preventDefault();
        goToStep2();
    };

    const handleBack = function (e) {
        e.preventDefault();
        goToStep1();
    };

    if (btnNext) {
        btnNext.addEventListener('click', handleNext);
        btnNext.addEventListener('touchstart', function (e) {
            if (!this.disabled) handleNext(e);
        }, { passive: false });
    }

    if (btnBack) {
        btnBack.addEventListener('click', handleBack);
        btnBack.addEventListener('touchstart', handleBack, { passive: false });
    }

    if (usernameInput) {
        usernameInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                goToStep2();
            }
        });
    }

    // --- Password Toggle ---
    if (btnTogglePassword && passwordInput) {
        btnTogglePassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            const icon = this.querySelector('i');
            if (icon) {
                icon.classList.toggle('bi-eye');
                icon.classList.toggle('bi-eye-slash');
            }
        });
    }

    // --- Login Form Handler ---
    let retryCount = 0;
    const maxRetries = 2;

            loginForm.addEventListener('submit', function (e) {
                e.preventDefault();

                const formData = new FormData(this);
                const submitBtn = this.querySelector('[name="login"]');
                const modalBody = loginModal.querySelector('.modal-body');

                // Enhanced AJAX detection - add ajax_request flag
                formData.append('login', '');
                formData.append('ajax_request', 'true');

                const originalText = submitBtn.innerHTML;
                const username = this.querySelector('input[name="username"]').value.trim();
                const password = this.querySelector('input[name="password"]').value;

                // Validation before sending
                if (!username || !password) {
                    showAlert(modalBody, window.loginModalMessages?.pleaseFillAll || 'Please fill in all fields', 'warning');
                    return;
                }

                // Enhanced loading state
                setButtonLoading(submitBtn, true);

                // Perform login request with retry mechanism
                performLogin(this.action, formData, { submitBtn, originalText, modalBody, loginForm }, retryCount);
            });

            function performLogin(url, formData, elements, retryCount) {
                const { submitBtn, originalText, modalBody, loginForm } = elements;

                // Enhanced request with timeout
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 15000); // 15 second timeout

                // Determine credentials mode based on cookie availability
                let credentialsMode = 'same-origin';
                try {
                    // Test if we can access cookies
                    if (!navigator.cookieEnabled) {
                        credentialsMode = 'omit';
                    }
                } catch (e) {
                    console.warn('Cookie access check failed:', e);
                    credentialsMode = 'omit';
                }

                fetch(url, {
                    method: 'POST',
                    body: formData,
                    credentials: credentialsMode,
                    signal: controller.signal,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Cache-Control': 'no-cache'
                    }
                })
                    .then(response => {
                        clearTimeout(timeoutId);

                        // Enhanced response debugging
                        console.log('=== Login Response Debug ===');
                        console.log('Status:', response.status, response.statusText);
                        console.log('Headers:', [...response.headers.entries()]);
                        console.log('URL:', response.url);

                        const contentType = response.headers.get('content-type');
                        console.log('Content-Type:', contentType);

                        // Log raw response for debugging
                        const responseClone = response.clone();

                        if (contentType && contentType.includes('application/json')) {
                            return response.json();
                        } else {
                            return response.text().then(text => {
                                console.log('HTML Response (first 500 chars):', text.substring(0, 500));
                                return text;
                            });
                        }
                    })
                    .then(data => {
                        console.log('=== Processed Data ===');
                        console.log('Type:', typeof data);
                        console.log('Data:', data);

                        // Handle JSON response (AJAX)
                        if (typeof data === 'object' && data !== null) {
                            if (data.success) {
                                handleLoginSuccess(data, loginModal, modalBody);
                            } else {
                                handleLoginError(data, submitBtn, originalText, modalBody, loginForm);
                            }
                        } else {
                            // Handle HTML response (fallback for non-AJAX)
                            handleHtmlResponse(data, loginModal, modalBody, submitBtn, originalText, loginForm);
                        }
                    })
                    .catch(error => {
                        clearTimeout(timeoutId);

                        console.error('=== Login Error ===');
                        console.error('Error:', error);
                        console.error('Message:', error.message);
                        console.error('Retry count:', retryCount);

                        // Enhanced error classification
                        let errorMessage = window.loginModalMessages?.connectionError || 'Connection error';
                        let shouldRetry = false;

                        if (error.name === 'AbortError') {
                            errorMessage = window.loginModalMessages?.timeoutError || 'Request timeout';
                            shouldRetry = retryCount < maxRetries;
                        } else if (error.name === 'TypeError' && error.message.includes('fetch')) {
                            errorMessage = window.loginModalMessages?.networkError || 'Network error - please check your connection';
                            shouldRetry = retryCount < maxRetries;
                        } else if (error.name === 'TypeError' && error.message.includes('JSON')) {
                            errorMessage = window.loginModalMessages?.serverError || 'Server response error';
                            shouldRetry = false;
                        }

                        if (shouldRetry) {
                            // Retry mechanism
                            const retryBtn = createRetryButton(() => {
                                performLogin(url, formData, elements, retryCount + 1);
                            }, retryCount + 1, maxRetries);

                            setButtonLoading(submitBtn, false);

                            const alertDiv = document.createElement('div');
                            alertDiv.className = 'alert alert-warning alert-dismissible fade show mt-3';
                            alertDiv.innerHTML = `
                            <strong>${errorMessage}</strong><br>
                            <small>Would you like to retry? (${retryCount + 1}/${maxRetries})</small>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        `;

                            insertAlert(modalBody, alertDiv);
                            loginForm.parentNode.insertBefore(retryBtn, loginForm.nextSibling);
                        } else {
                            // Final error - no more retries
                            setButtonLoading(submitBtn, false);
                            showAlert(modalBody, errorMessage, 'danger');

                            if (retryCount >= maxRetries) {
                                showFallbackOptions(modalBody);
                            }
                        }
                    });
            }

            function setButtonLoading(button, isLoading) {
                if (isLoading) {
                    button.disabled = true;
                    button.classList.add('disabled');
                    button.innerHTML = `
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        ${window.loginModalMessages?.signingIn || 'Signing in...'}
                    `;
                } else {
                    button.disabled = false;
                    button.classList.remove('disabled');
                    button.innerHTML = button.dataset.originalText || 'Login';
                }
            }

            function handleLoginSuccess(data, modal, modalBody) {
                // Success handling
                const modalInstance = bootstrap.Modal.getInstance(modal);

                // Show success message briefly
                modalBody.innerHTML = `
                    <div class="text-center py-3">
                        <div class="spinner-border text-success mb-3" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <div class="alert alert-success">
                            <strong>${window.loginModalMessages?.loginSuccess || 'Login successful!'}</strong><br>
                            <small>${window.loginModalMessages?.redirecting || 'Redirecting...'}</small>
                        </div>
                    </div>
                `;

                // Redirect after short delay
                setTimeout(() => {
                    window.location.href = data.redirect || '/mod_user/panel';
                }, 1500);
            }

            function handleLoginError(data, submitBtn, originalText, modalBody, loginForm) {
                // Error handling for JSON responses
                setButtonLoading(submitBtn, false);

                // Show validation errors if available
                let errorMessage = data.message || window.loginModalMessages?.loginFailed || 'Login failed';

                if (data.errors && Array.isArray(data.errors)) {
                    errorMessage = data.errors.join('<br>');
                }

                showAlert(modalBody, errorMessage, 'danger');

                // Auto-focus username field for retry
                const usernameField = loginForm.querySelector('input[name="username"]');
                if (usernameField) {
                    setTimeout(() => usernameField.focus(), 100);
                }
            }

            function handleHtmlResponse(html, modal, modalBody, submitBtn, originalText, loginForm) {
                // Fallback handling for HTML responses
                const successPatterns = [
                    /location\.href.*mod_user\/panel/i,
                    /mod_user\/panel/i,
                    /login.*successful/i
                ];

                const isSuccess = successPatterns.some(pattern => pattern.test(html));

                if (isSuccess) {
                    handleLoginSuccess({ redirect: '/mod_user/panel' }, modal, modalBody);
                } else {
                    setButtonLoading(submitBtn, false);

                    // Try to extract error from HTML
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const alertElements = doc.querySelectorAll('.alert-danger, .error, .text-danger');

                    let errorMessage = window.loginModalMessages?.loginFailed || 'Login failed';
                    if (alertElements.length > 0) {
                        errorMessage = Array.from(alertElements)
                            .map(el => el.textContent.trim())
                            .filter(text => text)
                            .join('<br>');
                    }

                    showAlert(modalBody, errorMessage, 'danger');
                }
            }

            function showAlert(container, message, type = 'danger') {
                removeAlerts(container);

                const alertDiv = document.createElement('div');
                alertDiv.className = `alert alert-${type} alert-dismissible fade show mt-3`;
                alertDiv.innerHTML = `
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;

                insertAlert(container, alertDiv);
            }

            function insertAlert(container, alertDiv) {
                // Insert alert at the beginning of the modal body
                if (container.firstChild) {
                    container.insertBefore(alertDiv, container.firstChild);
                } else {
                    container.appendChild(alertDiv);
                }

                // Auto-remove after 8 seconds for success/info messages
                if (!alertDiv.classList.contains('alert-danger')) {
                    setTimeout(() => {
                        if (alertDiv.parentNode) {
                            alertDiv.remove();
                        }
                    }, 8000);
                }
            }

            function removeAlerts(container) {
                const existingAlerts = container.querySelectorAll('.alert');
                existingAlerts.forEach(alert => alert.remove());
            }

            function createRetryButton(onClick, currentRetry, maxRetries) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn btn-warning w-100 mt-2';
                button.innerHTML = `
                    <i class="fas fa-redo me-2"></i>
                    Retry (${currentRetry}/${maxRetries})
                `;
                button.onclick = onClick;
                return button;
            }

            function showFallbackOptions(container) {
                const fallbackDiv = document.createElement('div');
                fallbackDiv.className = 'mt-3 p-3 border rounded';
                fallbackDiv.innerHTML = `
                    <strong>${window.loginModalMessages?.alternativeOptions || 'Alternative Options'}</strong>
                    <div class="mt-2">
                        <a href="/mod_user/login" class="btn btn-outline-primary btn-sm me-2">
                            <i class="fas fa-external-link-alt me-1"></i>
                            Login Page
                        </a>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="location.reload()">
                            <i class="fas fa-sync me-1"></i>
                            Refresh Page
                        </button>
                    </div>
                `;
                container.appendChild(fallbackDiv);
            }

            // Store original button text
            const submitBtn = loginForm.querySelector('[name="login"]');
            if (submitBtn) {
                submitBtn.dataset.originalText = submitBtn.innerHTML;
            }
});