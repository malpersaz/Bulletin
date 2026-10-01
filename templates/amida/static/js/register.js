/**
 * Amida Kurd Registration Module Script
 * Super Polish: Live validation, password security checker, captcha refresh, country format
 */

document.addEventListener('DOMContentLoaded', function () {
    const appEl = document.getElementById('registerApp');
    const form = document.getElementById('registerForm');

    // Clean URL query params if success=1 was passed
    if (window.location.search && window.location.search.includes('success=1')) {
        const cleanUrl = window.location.pathname;
        window.history.replaceState({}, document.title, cleanUrl);
    }

    if (!form || !appEl) {
        return;
    }

    // Parse language data and endpoints safely from data attributes
    let LANG = {};
    try {
        const rawLang = appEl.getAttribute('data-lang');
        if (rawLang) {
            LANG = JSON.parse(rawLang);
        }
    } catch (e) {
        console.warn('Failed to parse registration language payload:', e);
    }

    const checkUsernameUrl = appEl.getAttribute('data-check-username-url') || '';
    const checkEmailUrl = appEl.getAttribute('data-check-email-url') || '';
    const captchaUrl = appEl.getAttribute('data-captcha-url') || '';

    // Elements
    const nameInput = document.getElementById('name');
    const lastNameInput = document.getElementById('last_name');
    const usernameInput = document.getElementById('username');
    const usernameFeedback = document.getElementById('usernameFeedback');
    const emailInput = document.getElementById('email');
    const emailFeedback = document.getElementById('emailFeedback');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('password2');
    const phoneInput = document.getElementById('phone');
    const countryCodeSelect = document.getElementById('country_code');
    const captchaInput = document.getElementById('captcha');
    const captchaImg = document.getElementById('registerCaptchaImg');
    const reloadCaptchaBtn = document.getElementById('reloadCaptchaBtn');
    const submitBtn = document.getElementById('registerSubmitBtn');

    // Password UI Elements
    const strengthBox = document.getElementById('passwordStrengthBox');
    const strengthBar = document.getElementById('passwordStrengthBar');
    const strengthText = document.getElementById('passwordStrengthText');
    const matchIndicator = document.getElementById('passwordMatchIndicator');
    const matchText = document.getElementById('passwordMatchText');
    const matchIcon = document.getElementById('passwordMatchIcon');

    let debounceTimer;

    // --- 1. Password Visibility Toggle ---
    const toggleButtons = form.querySelectorAll('.register-toggle-password');
    toggleButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const targetInput = document.getElementById(targetId);
            const icon = this.querySelector('i');
            if (targetInput && icon) {
                if (targetInput.type === 'password') {
                    targetInput.type = 'text';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    targetInput.type = 'password';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            }
        });
    });

    // --- 2. Live Username Availability Check ---
    if (usernameInput && usernameFeedback && checkUsernameUrl) {
        usernameInput.addEventListener('input', function () {
            const username = this.value.trim();
            clearTimeout(debounceTimer);

            if (username.length === 0) {
                usernameInput.classList.remove('is-valid', 'is-invalid');
                usernameFeedback.innerHTML = '';
                return;
            }

            if (username.length < 2) {
                usernameInput.classList.remove('is-valid');
                usernameInput.classList.add('is-invalid');
                usernameFeedback.innerHTML = `<span class="text-danger"><i class="bi bi-x-circle me-1"></i>${LANG.username_short || 'Username too short'}</span>`;
                return;
            }

            if (username.length > 12) {
                usernameInput.classList.remove('is-valid');
                usernameInput.classList.add('is-invalid');
                usernameFeedback.innerHTML = `<span class="text-danger"><i class="bi bi-x-circle me-1"></i>${LANG.username_long || 'Username too long'}</span>`;
                return;
            }

            usernameFeedback.innerHTML = `<span class="text-muted"><i class="bi bi-hourglass-split me-1"></i>${LANG.checking || 'Checking...'}</span>`;

            debounceTimer = setTimeout(() => {
                let credentialsMode = 'same-origin';
                try {
                    if (!navigator.cookieEnabled) credentialsMode = 'omit';
                } catch (e) {
                    credentialsMode = 'omit';
                }

                fetch(checkUsernameUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: credentialsMode,
                    body: 'username=' + encodeURIComponent(username)
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.available) {
                            usernameInput.classList.remove('is-invalid');
                            usernameInput.classList.add('is-valid');
                            usernameFeedback.innerHTML = `<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>${LANG.username_available || 'Available'}</span>`;
                        } else {
                            usernameInput.classList.remove('is-valid');
                            usernameInput.classList.add('is-invalid');
                            usernameFeedback.innerHTML = `<span class="text-danger"><i class="bi bi-exclamation-circle-fill me-1"></i>${data.message || LANG.username_exists || 'Already exists'}</span>`;
                        }
                    })
                    .catch(error => {
                        console.error('Username check error:', error);
                        usernameFeedback.innerHTML = `<span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>${LANG.check_failed || 'Check failed'}</span>`;
                    });
            }, 450);
        });
    }

    // --- 3. Live Email Availability Check ---
    if (emailInput && emailFeedback && checkEmailUrl) {
        emailInput.addEventListener('input', function () {
            const email = this.value.trim();
            clearTimeout(debounceTimer);

            if (email.length === 0) {
                emailInput.classList.remove('is-valid', 'is-invalid');
                emailFeedback.innerHTML = '';
                return;
            }

            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                emailInput.classList.remove('is-valid');
                emailInput.classList.add('is-invalid');
                emailFeedback.innerHTML = `<span class="text-danger"><i class="bi bi-x-circle me-1"></i>${LANG.validation_invalid_email || 'Invalid email format'}</span>`;
                return;
            }

            emailFeedback.innerHTML = `<span class="text-muted"><i class="bi bi-hourglass-split me-1"></i>${LANG.checking || 'Checking...'}</span>`;

            debounceTimer = setTimeout(() => {
                let credentialsMode = 'same-origin';
                try {
                    if (!navigator.cookieEnabled) credentialsMode = 'omit';
                } catch (e) {
                    credentialsMode = 'omit';
                }

                const postBody = 'validateValue=' + encodeURIComponent(email) +
                    '&validateId=email&validateError=' + encodeURIComponent(LANG.email_exists || 'Email exists');

                fetch(checkEmailUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: credentialsMode,
                    body: postBody
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.jsonValidateReturn && data.jsonValidateReturn[2] === 'true') {
                            emailInput.classList.remove('is-invalid');
                            emailInput.classList.add('is-valid');
                            emailFeedback.innerHTML = `<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>${LANG.email_available || 'Available'}</span>`;
                        } else {
                            emailInput.classList.remove('is-valid');
                            emailInput.classList.add('is-invalid');
                            emailFeedback.innerHTML = `<span class="text-danger"><i class="bi bi-exclamation-circle-fill me-1"></i>${LANG.email_exists || 'Already registered'}</span>`;
                        }
                    })
                    .catch(error => {
                        console.error('Email check error:', error);
                        emailFeedback.innerHTML = `<span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>${LANG.check_failed || 'Check failed'}</span>`;
                    });
            }, 500);
        });
    }

    // --- 4. Password Strength & Rule Checklist ---
    const passwordRules = {
        length: val => val.length >= 8,
        uppercase: val => /[A-Z]/.test(val),
        lowercase: val => /[a-z]/.test(val),
        number: val => /[0-9]/.test(val),
        special: val => /[^A-Za-z0-9]/.test(val)
    };

    function updatePasswordStrength() {
        if (!passwordInput || !strengthBox || !strengthBar || !strengthText) return;
        const val = passwordInput.value;

        if (val.length === 0) {
            strengthBox.classList.add('d-none');
            return;
        }

        strengthBox.classList.remove('d-none');

        let passedCount = 0;
        Object.keys(passwordRules).forEach(rule => {
            const passed = passwordRules[rule](val);
            const item = form.querySelector(`.password-criteria-item[data-rule="${rule}"]`);
            if (item) {
                const icon = item.querySelector('.criteria-icon');
                if (passed) {
                    item.classList.remove('is-invalid');
                    item.classList.add('is-valid');
                    if (icon) icon.className = 'bi bi-check-circle-fill criteria-icon text-success';
                    passedCount++;
                } else {
                    item.classList.remove('is-valid');
                    item.classList.add('is-invalid');
                    if (icon) icon.className = 'bi bi-circle criteria-icon';
                }
            }
        });

        const percent = Math.min(100, Math.round((passedCount / 5) * 100));
        strengthBar.style.width = percent + '%';

        if (passedCount <= 2) {
            strengthBar.className = 'password-meter-bar bg-danger';
            strengthText.textContent = LANG.strength_weak || 'Weak';
            strengthText.className = 'small fw-bold text-danger';
        } else if (passedCount <= 4) {
            strengthBar.className = 'password-meter-bar bg-warning';
            strengthText.textContent = LANG.strength_fair || 'Good';
            strengthText.className = 'small fw-bold text-warning';
        } else {
            strengthBar.className = 'password-meter-bar bg-success';
            strengthText.textContent = LANG.strength_strong || 'Strong';
            strengthText.className = 'small fw-bold text-success';
        }

        checkPasswordMatch();
    }

    function checkPasswordMatch() {
        if (!confirmPasswordInput || !passwordInput || !matchIndicator || !matchText || !matchIcon) return;
        const pass1 = passwordInput.value;
        const pass2 = confirmPasswordInput.value;

        if (pass2.length === 0) {
            matchIndicator.classList.add('d-none');
            return;
        }

        matchIndicator.classList.remove('d-none');
        if (pass1 === pass2) {
            matchIndicator.className = 'password-match-indicator match-success';
            matchIcon.className = 'bi bi-check-circle-fill';
            matchText.textContent = LANG.password_match || 'Passwords match';
            confirmPasswordInput.classList.remove('is-invalid');
            confirmPasswordInput.classList.add('is-valid');
        } else {
            matchIndicator.className = 'password-match-indicator match-fail';
            matchIcon.className = 'bi bi-exclamation-circle-fill';
            matchText.textContent = LANG.password_mismatch || 'Passwords do not match';
            confirmPasswordInput.classList.remove('is-valid');
            confirmPasswordInput.classList.add('is-invalid');
        }
    }

    if (passwordInput) {
        passwordInput.addEventListener('input', updatePasswordStrength);
    }

    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);
    }

    // --- 5. Country Phone Handling ---
    const phoneFormats = {
        '+90': { placeholder: '555 123 4567', maxLength: 10 },
        '+1': { placeholder: '555 123 4567', maxLength: 10 },
        '+44': { placeholder: '20 7946 0958', maxLength: 10 },
        '+49': { placeholder: '30 12345678', maxLength: 11 },
        '+33': { placeholder: '6 12 34 56 78', maxLength: 9 },
        '+34': { placeholder: '612 345 678', maxLength: 9 },
        '+39': { placeholder: '333 1234567', maxLength: 10 },
        '+31': { placeholder: '6 12345678', maxLength: 9 },
        '+41': { placeholder: '79 123 45 67', maxLength: 9 },
        '+43': { placeholder: '699 1234567', maxLength: 10 },
        '+46': { placeholder: '70 123 45 67', maxLength: 9 },
        '+47': { placeholder: '123 45678', maxLength: 8 },
        '+45': { placeholder: '12 34 56 78', maxLength: 8 },
        '+358': { placeholder: '50 123 4567', maxLength: 10 }
    };

    if (countryCodeSelect && phoneInput) {
        countryCodeSelect.addEventListener('change', function () {
            const format = phoneFormats[this.value];
            if (format) {
                phoneInput.placeholder = format.placeholder;
                phoneInput.maxLength = format.maxLength;
                if (this.value !== '') {
                    phoneInput.focus();
                }
            }
        });

        phoneInput.addEventListener('input', function (e) {
            const format = phoneFormats[countryCodeSelect.value];
            let val = e.target.value.replace(/\D/g, '');
            if (format && val.length > format.maxLength) {
                val = val.slice(0, format.maxLength);
            }
            e.target.value = val;
        });
    }

    // --- 6. Captcha Reload ---
    function refreshCaptcha() {
        if (!captchaImg) return;
        const baseSrc = captchaUrl || captchaImg.getAttribute('src').split('?')[0];
        captchaImg.src = baseSrc + '?' + Date.now();

        if (reloadCaptchaBtn) {
            reloadCaptchaBtn.classList.add('is-spinning');
            setTimeout(() => {
                reloadCaptchaBtn.classList.remove('is-spinning');
            }, 550);
        }

        if (captchaInput) {
            captchaInput.value = '';
            captchaInput.focus();
        }
    }

    if (captchaImg) {
        captchaImg.addEventListener('click', refreshCaptcha);
    }
    if (reloadCaptchaBtn) {
        reloadCaptchaBtn.addEventListener('click', refreshCaptcha);
    }

    // --- 7. Form Submission & Client-side Guard ---
    form.addEventListener('submit', function (e) {
        let isFormValid = true;
        const requiredInputs = form.querySelectorAll('input[required]');

        // Reset visual validation states
        requiredInputs.forEach(input => {
            const feedback = input.parentNode.querySelector('.invalid-feedback');
            if (feedback) feedback.textContent = '';
        });

        function setFieldError(input, errorMsg) {
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
            const feedback = input.parentNode.querySelector('.invalid-feedback') ||
                input.closest('.mb-3')?.querySelector('.invalid-feedback') ||
                input.closest('.row')?.querySelector('.invalid-feedback');
            if (feedback) {
                feedback.textContent = errorMsg;
            }
            isFormValid = false;
        }

        // Validate Name
        if (nameInput && (!nameInput.value.trim() || nameInput.value.trim().length < 2)) {
            setFieldError(nameInput, LANG.validation_required_name || 'Please enter your name');
        }

        // Validate Last Name
        if (lastNameInput && (!lastNameInput.value.trim() || lastNameInput.value.trim().length < 2)) {
            setFieldError(lastNameInput, LANG.validation_required_surname || 'Please enter your surname');
        }

        // Validate Username
        if (usernameInput) {
            const uVal = usernameInput.value.trim();
            if (!uVal) {
                setFieldError(usernameInput, LANG.validation_required_username || 'Please enter a username');
            } else if (uVal.length < 2) {
                setFieldError(usernameInput, LANG.validation_username_short || 'Username too short');
            } else if (uVal.length > 12) {
                setFieldError(usernameInput, LANG.validation_username_long || 'Username too long');
            }
        }

        // Validate Email
        if (emailInput) {
            const eVal = emailInput.value.trim();
            const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!eVal) {
                setFieldError(emailInput, LANG.validation_required_email || 'Please enter your email');
            } else if (!pattern.test(eVal)) {
                setFieldError(emailInput, LANG.validation_invalid_email || 'Please enter a valid email');
            }
        }

        // Validate Password
        if (passwordInput) {
            const pVal = passwordInput.value;
            if (!pVal) {
                setFieldError(passwordInput, LANG.validation_required_password || 'Please enter a password');
            } else if (pVal.length < 8) {
                setFieldError(passwordInput, LANG.validation_password_short || 'Password must be at least 8 characters');
            } else if (pVal.length > 64) {
                setFieldError(passwordInput, LANG.validation_password_long || 'Password must not exceed 64 characters');
            } else if (!/[A-Z]/.test(pVal) || !/[a-z]/.test(pVal) || !/[0-9]/.test(pVal) || !/[^A-Za-z0-9]/.test(pVal)) {
                setFieldError(passwordInput, LANG.password_complexity_error || 'Password must meet complexity requirements');
            }
        }

        // Validate Confirm Password
        if (confirmPasswordInput && passwordInput) {
            if (!confirmPasswordInput.value) {
                setFieldError(confirmPasswordInput, LANG.validation_confirm_password || 'Please confirm password');
            } else if (confirmPasswordInput.value !== passwordInput.value) {
                setFieldError(confirmPasswordInput, LANG.password_mismatch || 'Passwords do not match');
            }
        }

        // Validate Phone
        if (phoneInput && !phoneInput.value.trim()) {
            setFieldError(phoneInput, LANG.validation_required_phone || 'Please enter phone number');
        }

        // Validate Captcha
        if (captchaInput && (!captchaInput.value.trim() || captchaInput.value.trim().length !== 5)) {
            setFieldError(captchaInput, LANG.validation_required_captcha || 'Please enter security code');
        }

        if (!isFormValid) {
            e.preventDefault();
            e.stopPropagation();
            const firstInvalid = form.querySelector('.is-invalid');
            if (firstInvalid) {
                firstInvalid.focus();
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }

        // Show loading spinner on button and prevent double submit
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>${LANG.checking || 'Tê şandin...'}`;
        }
    });

    // Clear validity state on user input
    requiredInputs = form.querySelectorAll('input');
    requiredInputs.forEach(input => {
        input.addEventListener('input', function () {
            this.classList.remove('is-invalid');
            const feedback = this.parentNode.querySelector('.invalid-feedback') ||
                this.closest('.mb-3')?.querySelector('.invalid-feedback');
            if (feedback) feedback.textContent = '';
        });
    });
});
