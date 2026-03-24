// Avatar Upload JavaScript - Amed Framework
// Handles avatar upload and deletion functionality

document.addEventListener('DOMContentLoaded', function() {
    // Avatar Upload Handler
    const avatarModal = document.getElementById('avatarModal');
    if (avatarModal) {
        const uploadForm = avatarModal.querySelector('#avatarUploadForm');
        const fileInput = avatarModal.querySelector('input[type="file"]');
        const deleteBtn = avatarModal.querySelector('#deleteAvatarBtn');

        // File input preview
        if (fileInput) {
            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Validate file type
                    if (!file.type.startsWith('image/')) {
                        alert('Lütfen sadece resim dosyası seçin.');
                        e.target.value = '';
                        return;
                    }

                    // Validate file size (max 5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        alert('Dosya boyutu maksimum 5MB olmalıdır.');
                        e.target.value = '';
                        return;
                    }

                    // Show preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const preview = avatarModal.querySelector('#avatarPreview');
                        if (preview) {
                            preview.src = e.target.result;
                            preview.style.display = 'block';
                        }
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        // Upload form submission
        if (uploadForm) {
            uploadForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;

                // Loading state
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Yükleniyor...';

                // Determine credentials mode based on cookie availability
                let credentialsMode = 'same-origin';
                try {
                    if (!navigator.cookieEnabled) {
                        credentialsMode = 'omit';
                    }
                } catch (e) {
                    console.warn('Cookie access check failed:', e);
                    credentialsMode = 'omit';
                }

                fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    credentials: credentialsMode,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update avatar images
                        const avatarImages = document.querySelectorAll('.user-avatar, .dropdown-avatar');
                        avatarImages.forEach(img => {
                            img.src = data.avatar_url + '?t=' + new Date().getTime();
                        });

                        // Close modal
                        const modal = bootstrap.Modal.getInstance(avatarModal);
                        modal.hide();

                        // Reset form
                        this.reset();
                        const preview = avatarModal.querySelector('#avatarPreview');
                        if (preview) {
                            preview.style.display = 'none';
                        }

                        // Show success message
                        showToast('Profil fotoğrafınız başarıyla güncellendi.', 'success');
                    } else {
                        alert(data.message || 'Bir hata oluştu.');
                    }
                })
                .catch(error => {
                    console.error('Avatar upload error:', error);
                    alert('Bağlantı hatası. Lütfen tekrar deneyin.');
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
            });
        }

        // Delete avatar
        if (deleteBtn) {
            deleteBtn.addEventListener('click', function() {
                if (!confirm('Profil fotoğrafınızı silmek istediğinizden emin misiniz?')) {
                    return;
                }

                const userId = this.dataset.userId;
                if (!userId) return;

                const originalText = this.innerHTML;
                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Siliniyor...';

                // Determine credentials mode based on cookie availability
                let credentialsMode = 'same-origin';
                try {
                    if (!navigator.cookieEnabled) {
                        credentialsMode = 'omit';
                    }
                } catch (e) {
                    console.warn('Cookie access check failed:', e);
                    credentialsMode = 'omit';
                }

                fetch('/mod_user/deleteAvatar', {
                    method: 'POST',
                    credentials: credentialsMode,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ user_id: userId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update avatar images to default
                        const avatarImages = document.querySelectorAll('.user-avatar, .dropdown-avatar');
                        avatarImages.forEach(img => {
                            img.src = data.default_avatar + '?t=' + new Date().getTime();
                        });

                        // Close modal
                        const modal = bootstrap.Modal.getInstance(avatarModal);
                        modal.hide();

                        showToast('Profil fotoğrafınız silindi.', 'info');
                    } else {
                        alert(data.message || 'Bir hata oluştu.');
                    }
                })
                .catch(error => {
                    console.error('Avatar delete error:', error);
                    alert('Bağlantı hatası. Lütfen tekrar deneyin.');
                })
                .finally(() => {
                    this.disabled = false;
                    this.innerHTML = originalText;
                });
            });
        }
    }

    // Toast notification helper
    function showToast(message, type = 'info') {
        const toastContainer = document.getElementById('toastContainer') || createToastContainer();

        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        toastContainer.appendChild(toast);

        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();

        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }

    function createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
        return container;
    }
});