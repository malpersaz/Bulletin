document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('login-form');
    const loginButton = document.getElementById('manual-submit-button');
    const alertContainer = document.getElementById('login-alerts');

    if (!loginForm) return;

    const loginUrl = loginForm.getAttribute('action') || (loginForm.dataset && loginForm.dataset.loginUrl) || '/login';
    const defaultRedirect = (loginForm.dataset && loginForm.dataset.redirectUrl) || '/';
    const connectionErrorMsg = (loginForm.dataset && loginForm.dataset.connectionError) || 'Connection error. Please try again.';

    if (loginButton) {
        loginButton.addEventListener('click', function(e) {
            e.preventDefault();
            submitFormAjax();
        });
    }

    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        submitFormAjax();
    });

    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');

    function handleEnterKey(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            submitFormAjax();
        }
    }

    if (usernameInput) {
        usernameInput.addEventListener('keypress', handleEnterKey);
    }
    if (passwordInput) {
        passwordInput.addEventListener('keypress', handleEnterKey);
    }

    function showAlert(message, type) {
        if (alertContainer) {
            alertContainer.innerHTML = `<div class="alert alert-${type}" role="alert">${message}</div>`;
        }
    }

    function submitFormAjax() {
        const form = document.getElementById('login-form');
        if (!form) return;

        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        const button = document.getElementById('manual-submit-button');
        const originalText = button ? button.textContent : '';

        // Loading state
        if (button) {
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>...';
        }

        const formData = new FormData();
        formData.append('username', username);
        formData.append('password', password);
        formData.append('login', 'login');
        formData.append('ajax_request', 'true');

        fetch(loginUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return response.json();
            }
            throw new Error('server_response_error');
        })
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                setTimeout(() => {
                    window.location.href = data.redirect || defaultRedirect;
                }, 500);
            } else {
                let errorHtml = '<ul class="mb-0">';
                if (data.errors && data.errors.length > 0) {
                    data.errors.forEach(err => {
                        errorHtml += `<li>${err}</li>`;
                    });
                } else {
                    errorHtml += `<li>${data.message}</li>`;
                }
                errorHtml += '</ul>';
                showAlert(errorHtml, 'danger');
                if (button) {
                    button.disabled = false;
                    button.textContent = originalText;
                }
            }
        })
        .catch(error => {
            console.error('Login request failed:', error);
            showAlert(connectionErrorMsg, 'danger');
            if (button) {
                button.disabled = false;
                button.textContent = originalText;
            }
        });
    }
});
