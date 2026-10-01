/**
 * User Profile & Password Modal Management Sub-Module
 */

if (typeof window.AFModalEngine !== 'undefined' && window.AFModalEngine.loadedModules) {
    window.AFModalEngine.loadedModules['modal_user_profile'] = true;
}

function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();
        reader.onload = function (e) {
            const container = document.getElementById('avatarPreviewContainer');
            if (container && !container.dataset.uploadFinished) {
                container.innerHTML = `
                    <img src="${e.target.result}" id="avatarPreview" class="m3-hero-avatar" alt="Avatar">
                    <button type="button" class="m3-hero-avatar-btn" disabled>
                        <i class="bi bi-arrow-repeat spin"></i>
                    </button>`;
            }
        };
        reader.readAsDataURL(file);

        const container = document.getElementById('avatarPreviewContainer');
        if (container) delete container.dataset.uploadFinished;

        // Upload and persist avatar immediately
        uploadAvatarFile(file);
    }
}

function uploadAvatarFile(file) {
    if (!file) return;

    const siteUrl = (typeof SITE_URL !== 'undefined' && SITE_URL) ? SITE_URL : ((window.siteData && window.siteData.saddress) ? window.siteData.saddress : '/');
    const container = document.getElementById('avatarPreviewContainer');
    const initialId = container?.dataset?.initialId || '';
    const deleteTitle = (window.adminLang && window.adminLang.mod_user && window.adminLang.mod_user.button && window.adminLang.mod_user.button.delete) || 'Delete';
    const successMsg = (window.adminLang && window.adminLang.mod_user && window.adminLang.mod_user.message && window.adminLang.mod_user.message.success && window.adminLang.mod_user.message.success.edit) || 'Avatar updated successfully';

    const formData = new FormData();
    formData.append('image', file);

    fetch(siteUrl + 'mod_user/ajax_upload_avatar', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === 'success' && res.image) {
            if (navigator.vibrate) navigator.vibrate(10);
            if (typeof showAFToast === 'function') {
                showAFToast(res.message || successMsg, 'success', 2000);
            } else if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: res.message || successMsg,
                    timer: 2000,
                    showConfirmButton: false
                });
            }

            const iAddress = (window.siteData && window.siteData.iaddress) ? window.siteData.iaddress : ((typeof SITE_ADDRESS !== 'undefined' && SITE_ADDRESS) ? SITE_ADDRESS + 'static/' : '/static/');
            const newImgUrl = iAddress + 'mod_user/' + res.image;

            if (container) {
                container.dataset.uploadFinished = 'true';
                container.dataset.initialSrc = newImgUrl;
                container.innerHTML = `
                    <img src="${newImgUrl}" id="avatarPreview" class="m3-hero-avatar" alt="Avatar">
                    <button type="button" class="m3-hero-avatar-btn remove-btn" data-action="delete-avatar" data-id="${initialId}" title="${deleteTitle}">
                        <i class="bi bi-trash"></i>
                    </button>`;
            }

            // Sync user avatar across open page surfaces
            document.querySelectorAll('.user-avatar, .user-avatar-sm, .avatar-img').forEach(img => {
                if (img.tagName === 'IMG') {
                    img.src = newImgUrl;
                }
            });

            document.querySelectorAll('.user-avatar-placeholder, .user-avatar-sm-placeholder').forEach(placeholder => {
                const isSm = placeholder.classList.contains('user-avatar-sm-placeholder');
                const size = isSm ? 28 : 32;
                const cls = isSm ? 'user-avatar-sm rounded-circle me-2' : 'user-avatar rounded-circle';
                const newImg = document.createElement('img');
                newImg.src = newImgUrl;
                newImg.alt = 'Avatar';
                newImg.className = cls;
                newImg.width = size;
                newImg.height = size;
                placeholder.replaceWith(newImg);
            });

            document.querySelectorAll('.user-menu-avatar-large').forEach(parent => {
                parent.innerHTML = `<img src="${newImgUrl}" alt="Avatar">`;
            });
        } else {
            const err = res.message || 'Failed to upload avatar';
            if (typeof showAFToast === 'function') {
                showAFToast(err, 'error', 2500);
            } else if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'Error', text: err });
            }
            cancelAvatarSelection();
        }
    })
    .catch(err => {
        console.error('Avatar upload error:', err);
        if (typeof showAFToast === 'function') {
            showAFToast('Failed to upload avatar', 'error', 2500);
        }
        cancelAvatarSelection();
    });
}

function cancelAvatarSelection() {
    const fileInput = document.getElementById('image');
    if (fileInput) fileInput.value = '';

    const container = document.getElementById('avatarPreviewContainer');
    if (container) {
        delete container.dataset.uploadFinished;
        const initialSrc = container.dataset.initialSrc;
        const initialId = container.dataset.initialId;
        const deleteTitle = (window.adminLang && window.adminLang.mod_user && window.adminLang.mod_user.button && window.adminLang.mod_user.button.delete) || 'Delete';
        const selectTitle = (window.adminLang && window.adminLang.mod_user && window.adminLang.mod_user.button && window.adminLang.mod_user.button.select_image) || 'Select Image';

        if (initialSrc) {
            container.innerHTML = `
                <img src="${initialSrc}" id="avatarPreview" class="m3-hero-avatar" alt="Avatar">
                <button type="button" class="m3-hero-avatar-btn remove-btn" data-action="delete-avatar" data-id="${initialId || ''}" title="${deleteTitle}">
                    <i class="bi bi-trash"></i>
                </button>`;
        } else {
            container.innerHTML = `
                <div class="m3-hero-avatar-placeholder" data-action="select-avatar">
                    <i class="bi bi-person-fill"></i>
                </div>
                <button type="button" class="m3-hero-avatar-btn upload-btn" data-action="select-avatar" title="${selectTitle}">
                    <i class="bi bi-camera-fill"></i>
                </button>`;
        }
    }
}

function switchProfileTab(btn) {
    if (!btn) return;
    const targetId = btn.dataset.tabTarget;
    if (!targetId) return;

    const tabsContainer = btn.closest('.m3-profile-tabs') || document;
    tabsContainer.querySelectorAll('.m3-tab-btn').forEach(b => {
        b.classList.remove('active');
        b.setAttribute('aria-selected', 'false');
    });
    btn.classList.add('active');
    btn.setAttribute('aria-selected', 'true');

    const form = btn.closest('form') || document;
    form.querySelectorAll('.m3-tab-content').forEach(pane => {
        pane.classList.remove('active');
    });

    const targetPane = document.getElementById(targetId);
    if (targetPane) {
        targetPane.classList.add('active');
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
    const siteUrl = (typeof SITE_URL !== 'undefined' && SITE_URL) ? SITE_URL : ((window.siteData && window.siteData.saddress) ? window.siteData.saddress : '/');
    const lang = (window.adminLang && window.adminLang.mod_user) || {};
    const confirmTitle = lang.remove_image || lang.confirm_remove_image || 'Are you sure you want to remove this image?';
    const confirmYes = (lang.button && lang.button.yes) || 'Yes';
    const confirmNo = (lang.button && lang.button.no) || 'No';
    const successTitle = lang.remove_image_success || 'Image removed successfully';
    const selectTitle = (lang.button && lang.button.select_image) || 'Select Image';

    const executeDelete = () => {
        const formData = new FormData();
        const deleteId = id || (document.getElementById('avatarPreviewContainer')?.dataset?.initialId) || '';
        if (deleteId) {
            formData.append('id', deleteId);
        }

        fetch(siteUrl + "mod_user/delete_image", {
            method: "POST",
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(r => {
            const contentType = r.headers.get('content-type') || '';
            if (contentType.includes('application/json')) {
                return r.json();
            }
            return r.text().then(text => {
                if (text.trim() === 'Success') {
                    return { status: 'success', message: successTitle };
                }
                return { status: 'error', message: text };
            });
        })
        .then(res => {
            if (res.status === 'success') {
                if (navigator.vibrate) navigator.vibrate(10);
                if (typeof showAFToast === 'function') {
                    showAFToast(res.message || successTitle, 'success', 2000);
                } else if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: res.message || successTitle,
                        timer: 2000,
                        showConfirmButton: false
                    });
                }

                const fileInput = document.getElementById('image');
                if (fileInput) fileInput.value = '';

                const container = document.getElementById('avatarPreviewContainer');
                if (container) {
                    container.dataset.initialSrc = '';
                    container.innerHTML = `
                        <div class="m3-hero-avatar-placeholder" data-action="select-avatar">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <button type="button" class="m3-hero-avatar-btn upload-btn" data-action="select-avatar" title="${selectTitle}">
                            <i class="bi bi-camera-fill"></i>
                        </button>`;
                }

                document.querySelectorAll('.avatar-img').forEach(img => {
                    const parent = img.parentElement;
                    if (parent && parent.classList.contains('avatar-small')) {
                        const userName = (window.siteData && window.siteData.username) || '';
                        const initial = userName ? userName.charAt(0).toUpperCase() : 'U';
                        parent.innerHTML = `<span class="avatar-initials">${initial}</span>`;
                    } else {
                        img.remove();
                    }
                });
                document.querySelectorAll('.user-menu-avatar-large img').forEach(img => {
                    const parent = img.parentElement;
                    if (parent) {
                        const userName = (window.siteData && window.siteData.username) || '';
                        const initial = userName ? userName.charAt(0).toUpperCase() : 'U';
                        parent.innerHTML = `<span>${initial}</span>`;
                    }
                });
            } else {
                const errMsg = res.message || 'Error removing image';
                if (typeof showAFToast === 'function') {
                    showAFToast(errMsg, 'error', 2500);
                } else if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errMsg
                    });
                }
            }
        })
        .catch(err => {
            console.error('Delete image error:', err);
            const errNetwork = 'An error occurred while removing the image.';
            if (typeof showAFToast === 'function') {
                showAFToast(errNetwork, 'error', 2500);
            } else if (typeof Swal !== 'undefined') {
                Swal.fire('Error', errNetwork, 'error');
            }
        });
    };

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: confirmTitle,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ba1a1a',
            cancelButtonColor: '#6e7881',
            confirmButtonText: confirmYes,
            cancelButtonText: confirmNo
        }).then((result) => {
            if (result.isConfirmed) {
                executeDelete();
            }
        });
    } else if (confirm(confirmTitle)) {
        executeDelete();
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
            if (data.image) {
                const siteUrl = (typeof SITE_URL !== 'undefined' && SITE_URL) ? SITE_URL : ((window.siteData && window.siteData.saddress) ? window.siteData.saddress : '/');
                const iAddress = (window.siteData && window.siteData.iaddress) ? window.siteData.iaddress : ((typeof SITE_ADDRESS !== 'undefined' && SITE_ADDRESS) ? SITE_ADDRESS + 'static/' : '/static/');
                const newImgUrl = iAddress + 'mod_user/' + data.image;
                const container = document.getElementById('avatarPreviewContainer');
                if (container) {
                    const deleteTitle = (window.adminLang && window.adminLang.mod_user && window.adminLang.mod_user.button && window.adminLang.mod_user.button.delete) || 'Delete';
                    container.dataset.initialSrc = newImgUrl;
                    container.innerHTML = `
                        <img src="${newImgUrl}" id="avatarPreview" class="m3-hero-avatar" alt="Avatar">
                        <button type="button" class="m3-hero-avatar-btn remove-btn" data-action="delete-avatar" data-id="${container.dataset.initialId || ''}" title="${deleteTitle}">
                            <i class="bi bi-trash"></i>
                        </button>`;
                }
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

document.addEventListener('click', function(e) {
    // 1. Delete Avatar
    const deleteBtn = e.target.closest('[data-action="delete-avatar"]');
    if (deleteBtn) {
        e.preventDefault();
        const id = deleteBtn.dataset.id || (document.getElementById('avatarPreviewContainer')?.dataset?.initialId) || '';
        deleteImage(id);
        return;
    }

    // 2. Cancel Avatar Selection
    const cancelBtn = e.target.closest('[data-action="cancel-avatar"]');
    if (cancelBtn) {
        e.preventDefault();
        cancelAvatarSelection();
        return;
    }

    // 3. Select Avatar (trigger file input)
    const selectBtn = e.target.closest('[data-action="select-avatar"]');
    if (selectBtn) {
        e.preventDefault();
        const fileInput = document.getElementById('image');
        if (fileInput) {
            fileInput.click();
        }
        return;
    }

    // 4. Switch Tab
    const tabBtn = e.target.closest('[data-action="switch-tab"]');
    if (tabBtn) {
        e.preventDefault();
        switchProfileTab(tabBtn);
        return;
    }

    // 5. Toggle Password
    const toggleBtn = e.target.closest('[data-action="toggle-password"]');
    if (toggleBtn) {
        e.preventDefault();
        const targetId = toggleBtn.dataset.target;
        if (targetId) {
            togglePassword(targetId, toggleBtn);
        }
        return;
    }
});

// Change event for file input
document.addEventListener('change', function(e) {
    if (e.target && (e.target.id === 'image' || (e.target.dataset && e.target.dataset.action === 'preview-avatar'))) {
        previewAvatar(e.target);
    }
});

// Input events for char counter, password strength, and password match
document.addEventListener('input', function(e) {
    if (!e.target) return;

    if (e.target.dataset && e.target.dataset.counterId) {
        const max = parseInt(e.target.dataset.counterMax, 10) || 255;
        updateCharCounter(e.target, e.target.dataset.counterId, max);
    }

    if (e.target.dataset && e.target.dataset.action === 'check-strength') {
        checkStrength(e.target.value);
    }

    if ((e.target.dataset && e.target.dataset.action === 'check-match') || e.target.id === 'new_password') {
        checkMatch();
    }
});

