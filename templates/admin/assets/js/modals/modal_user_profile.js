/**
 * User Profile & Password Modal Management Sub-Module
 */

function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const container = document.getElementById('avatarPreviewContainer');
            if (container) {
                container.innerHTML = `
                    <div id="image-container" style="display: flex; flex-direction: column; align-items: center;">
                        <img src="${e.target.result}" id="avatarPreview" alt="Avatar">
                        <div style="text-align: center;">
                            <button type="button" class="m3-btn-outlined" onclick="location.reload()" style="color: var(--md-sys-color-outline); border-color: var(--md-sys-color-outline); font-size: 12px; padding: 4px 16px; border-radius: 20px;">
                                <i class="bi bi-x-circle"></i> ${ (window.adminLang && window.adminLang.general && window.adminLang.general.cancel) || 'Cancel' }
                            </button>
                        </div>
                    </div>`;
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function updateCharCounter(input, counterId, max) {
    if (!input) return;
    const currentLength = input.value ? input.value.length : 0;
    const counter = document.getElementById(counterId);
    if (counter) {
        counter.innerText = `${currentLength} / ${max}`;
        if (currentLength > max) {
            counter.style.color = 'var(--md-sys-color-error)';
        } else {
            counter.style.color = 'var(--md-sys-color-on-surface-variant)';
        }
    }
}

function deleteImage(id) {
    const siteUrl = typeof SITE_URL !== 'undefined' ? SITE_URL : '';
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Are you sure you want to remove this image?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ba1a1a',
            cancelButtonColor: '#6e7881',
            confirmButtonText: 'Yes',
            cancelButtonText: 'No'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('id', id);

                fetch(siteUrl + "mod_user/delete_image", {
                    method: "POST",
                    body: formData
                })
                .then(r => r.text())
                .then(msg => {
                    if (msg === 'Success') {
                        if (navigator.vibrate) navigator.vibrate(10);
                        Swal.fire('Image removed successfully', '', 'success');
                        const container = document.getElementById('avatarPreviewContainer');
                        if (container) {
                            container.innerHTML = `
                                <div class="upload-btn-wrapper">
                                    <button type="button" class="m3-upload-button" onclick="document.getElementById('image').click()">
                                        <i class="bi bi-cloud-arrow-up"></i>
                                        Select Image
                                    </button>
                                    <div style="color: var(--md-sys-color-on-surface-variant); font-size: 12px;">Select Image</div>
                                </div>`;
                        }
                    } else {
                        Swal.fire('Error', msg, 'error');
                    }
                });
            }
        });
    }
}

function togglePassword(inputId, iconElement) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const icon = iconElement.querySelector ? iconElement.querySelector('i') : iconElement;
    if (input.type === 'password') {
        input.type = 'text';
        if (icon && icon.classList) icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        if (icon && icon.classList) icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}

function checkStrength(password) {
    const progress = document.getElementById('strength-progress');
    const text = document.getElementById('strength-text');
    const reqLen = document.getElementById('req-len');
    const reqComp = document.getElementById('req-complex');
    
    if (!progress || !text) return;
    let strength = 0;
    
    if (password.length >= 6) {
        strength++;
        if (reqLen) {
            reqLen.style.color = '#34A853';
            const icon = reqLen.querySelector('i');
            if (icon) icon.className = 'bi bi-check-circle-fill';
        }
    } else {
        if (reqLen) {
            reqLen.style.color = 'inherit';
            const icon = reqLen.querySelector('i');
            if (icon) icon.className = 'bi bi-circle';
        }
    }

    const hasLower = /[a-z]/.test(password);
    const hasUpper = /[A-Z]/.test(password);
    const hasNumber = /\d/.test(password);
    const hasSpecial = /[^a-zA-Z\d]/.test(password);

    if (hasLower && hasUpper && hasNumber) {
        strength++;
        if (hasSpecial) strength++;
        if (reqComp) {
            reqComp.style.color = '#34A853';
            const icon = reqComp.querySelector('i');
            if (icon) icon.className = 'bi bi-check-circle-fill';
        }
    } else {
        if (reqComp) {
            reqComp.style.color = 'inherit';
            const icon = reqComp.querySelector('i');
            if (icon) icon.className = 'bi bi-circle';
        }
    }

    if (password.length > 10) strength++;

    const colors = [
        'var(--md-sys-color-error)',
        'var(--md-sys-color-error)',
        '#FBBC04',
        '#34A853',
        'var(--md-sys-color-primary)'
    ];
    
    const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];

    const displayStrength = Math.min(strength, 4);
    const width = (displayStrength / 4) * 100;
    progress.style.width = width + '%';
    progress.style.backgroundColor = colors[displayStrength];
    text.innerText = labels[displayStrength];
    text.style.color = colors[displayStrength];

    checkMatch();
}

function checkMatch() {
    const p1El = document.getElementById('new_password');
    const p2El = document.getElementById('confirm_password');
    const feedback = document.getElementById('match-feedback');
    
    if (!p1El || !p2El || !feedback) return;
    const p1 = p1El.value;
    const p2 = p2El.value;

    if (p1 && p2) {
        feedback.style.display = 'flex';
        const icon = feedback.querySelector('i');
        const span = feedback.querySelector('span');
        if (p1 === p2) {
            feedback.style.color = '#34A853';
            if (icon) icon.className = 'bi bi-check-circle-fill';
            if (span) span.innerText = 'Passwords match';
        } else {
            feedback.style.color = 'var(--md-sys-color-error)';
            if (icon) icon.className = 'bi bi-x-circle-fill';
            if (span) span.innerText = 'Passwords do not match';
        }
    } else {
        feedback.style.display = 'none';
    }
}

function showFormValidationToast(msg, targetInput) {
    if (navigator.vibrate) navigator.vibrate([10, 50, 10]);
    if (targetInput && typeof targetInput.focus === 'function') {
        targetInput.focus();
    }
    if (typeof showAFToast === 'function') {
        showAFToast(msg, 'error', 2500);
    } else if (typeof Swal !== 'undefined') {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: msg,
            timer: 2500,
            showConfirmButton: false
        });
    }
}

function submitEditProfileAjax(event) {
    if (event) event.preventDefault();
    const form = document.getElementById('profileInfoForm') || (event ? event.target : null);
    if (!form) return;

    const currentPasswordInput = form.querySelector('[name="current_password"]');
    const emailInput = form.querySelector('[name="email"]');

    const currentPassword = currentPasswordInput ? currentPasswordInput.value.trim() : '';
    const email = emailInput ? emailInput.value.trim() : '';

    if (!currentPassword) {
        showFormValidationToast(form.dataset.msgCurrentPasswordRequired || 'Current password is required', currentPasswordInput);
        return;
    }

    if (!email) {
        showFormValidationToast(form.dataset.msgEmailRequired || 'Email address is required', emailInput);
        return;
    }

    if (form.dataset.submitting === 'true') return;
    form.dataset.submitting = 'true';

    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) submitBtn.disabled = true;

    const siteUrl = typeof SITE_URL !== 'undefined' ? SITE_URL : (typeof site !== 'undefined' ? site.saddress : '');

    const formData = new FormData(form);
    fetch(siteUrl + 'mod_user/ajax_save_info', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            if (navigator.vibrate) navigator.vibrate(10);
            if (typeof showAFToast === 'function') {
                showAFToast(data.message || 'Profile updated successfully', 'success', 2000);
            } else if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: data.message || 'Profile updated successfully',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
            if (data.full_name) {
                document.querySelectorAll('.user-full-name').forEach(el => el.textContent = data.full_name);
            }
        } else {
            if (typeof showAFToast === 'function') {
                showAFToast(data.message || 'Error updating profile', 'error', 2500);
            } else if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: data.message || 'Error updating profile',
                    timer: 2500,
                    showConfirmButton: false
                });
            }
        }
    })
    .catch(err => {
        console.error(err);
        if (typeof showAFToast === 'function') {
            showAFToast('An error occurred while updating profile.', 'error', 2500);
        } else if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: 'An error occurred while updating profile.',
                timer: 2500,
                showConfirmButton: false
            });
        }
    })
    .finally(() => {
        delete form.dataset.submitting;
        if (submitBtn) submitBtn.disabled = false;
    });
}

function submitChangePasswordAjax(event) {
    if (event) event.preventDefault();
    const form = document.getElementById('changePasswordForm') || (event ? event.target : null);
    if (!form) return;

    const currentPasswordInput = form.querySelector('[name="current_password"]');
    const newPasswordInput = form.querySelector('[name="new_password"]');
    const confirmPasswordInput = form.querySelector('[name="confirm_password"]');

    const currentPassword = currentPasswordInput ? currentPasswordInput.value.trim() : '';
    const newPassword = newPasswordInput ? newPasswordInput.value : '';
    const confirmPassword = confirmPasswordInput ? confirmPasswordInput.value : '';

    if (!currentPassword) {
        showFormValidationToast(form.dataset.msgCurrentPasswordRequired || 'Current password is required', currentPasswordInput);
        return;
    }

    if (!newPassword) {
        showFormValidationToast(form.dataset.msgNewPasswordRequired || 'New password is required', newPasswordInput);
        return;
    }

    if (newPassword.length < 6) {
        showFormValidationToast(form.dataset.msgPasswordMinLength || 'New password must be at least 6 characters long', newPasswordInput);
        return;
    }

    if (!confirmPassword) {
        showFormValidationToast(form.dataset.msgConfirmPasswordRequired || 'Password confirmation is required', confirmPasswordInput);
        return;
    }

    if (newPassword !== confirmPassword) {
        showFormValidationToast(form.dataset.msgPasswordMismatch || 'Passwords do not match', confirmPasswordInput);
        return;
    }

    if (form.dataset.submitting === 'true') return;
    form.dataset.submitting = 'true';

    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) submitBtn.disabled = true;

    const siteUrl = typeof SITE_URL !== 'undefined' ? SITE_URL : (typeof site !== 'undefined' ? site.saddress : '');

    const formData = new FormData(form);
    fetch(siteUrl + 'mod_user/ajax_save_change_password', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            if (navigator.vibrate) navigator.vibrate(10);
            if (typeof showAFToast === 'function') {
                showAFToast(data.message || 'Password changed successfully', 'success', 2000);
            } else if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: data.message || 'Password changed successfully',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
            form.reset();
            setTimeout(() => {
                if (window.AFModalEngine && typeof window.AFModalEngine.back === 'function') {
                    window.AFModalEngine.back();
                }
            }, 1200);
        } else {
            if (typeof showAFToast === 'function') {
                showAFToast(data.message || 'Error changing password', 'error', 2500);
            } else if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: data.message || 'Error changing password',
                    timer: 2500,
                    showConfirmButton: false
                });
            }
        }
    })
    .catch(err => {
        console.error(err);
        if (typeof showAFToast === 'function') {
            showAFToast('An error occurred while changing password.', 'error', 2500);
        } else if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: 'An error occurred while changing password.',
                timer: 2500,
                showConfirmButton: false
            });
        }
    })
    .finally(() => {
        delete form.dataset.submitting;
        if (submitBtn) submitBtn.disabled = false;
    });
}

document.addEventListener('submit', function(e) {
    if (e.target && e.target.id === 'profileInfoForm') {
        submitEditProfileAjax(e);
    } else if (e.target && e.target.id === 'changePasswordForm') {
        submitChangePasswordAjax(e);
    }
});
