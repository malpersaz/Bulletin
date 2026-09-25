/**
 * Redirect Management Modal Sub-Module
 */

function openRedirectModal() {
    closeSiteSettingsMenuModal();
    const sheet = document.getElementById('redirect-sheet');
    const overlay = document.getElementById('redirect-sheet-overlay');
    if (sheet && overlay) {
        if (typeof window.AFModalEngine !== 'undefined' && window.AFModalEngine.pushModalElement) {
            window.AFModalEngine.pushModalElement(sheet, null, { overlayId: 'redirect-sheet-overlay' });
        } else {
            sheet.classList.add('active');
            overlay.classList.add('active');
            if (navigator.vibrate) navigator.vibrate(10);
        }
        fetchRedirects();
    }
}

function closeRedirectModal() {
    const sheet = document.getElementById('redirect-sheet');
    const overlay = document.getElementById('redirect-sheet-overlay');
    if (sheet && overlay) {
        if (typeof window.AFModalEngine !== 'undefined' && window.AFModalEngine.closeSingle) {
            window.AFModalEngine.closeSingle('redirect-sheet');
        } else {
            sheet.classList.remove('active');
            overlay.classList.remove('active');
        }
    }
}

function goBackFromRedirectModal() {
    closeRedirectModal();
    openSiteSettingsMenuModal();
}

function onRedirectSearchInput() {
    fetchRedirects();
}

function fetchRedirects() {
    const container = document.getElementById('redirects-list-container');
    if (!container) return;
    const siteUrl = typeof SITE_URL !== 'undefined' ? SITE_URL : '';

    const searchInput = document.getElementById('redirect-search-input');
    const searchVal = searchInput ? searchInput.value : '';

    fetch(siteUrl + 'mod_redirect/ajax_list?q=' + encodeURIComponent(searchVal))
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' && data.items) {
                if (data.items.length === 0) {
                    container.innerHTML = '<div style="text-align: center; padding: 24px; color: var(--md-sys-color-on-surface-variant);"><i class="bi bi-arrow-right-left" style="font-size: 28px; opacity: 0.5;"></i><p style="margin-top: 8px;">No redirects found.</p></div>';
                    return;
                }
                let html = '';
                data.items.forEach(item => {
                    html += `
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border-bottom: 1px solid var(--md-sys-color-outline-variant); background: var(--md-sys-color-surface);">
                            <div style="min-width: 0; flex: 1; margin-right: 12px;">
                                <div style="font-weight: 600; font-size: 14px; color: var(--md-sys-color-on-surface); word-break: break-all;">${item.old_url}</div>
                                <div style="font-size: 12px; color: var(--md-sys-color-primary); word-break: break-all; margin-top: 2px;">&rarr; ${item.new_url}</div>
                                <div style="font-size: 11px; opacity: 0.7; margin-top: 2px;">Code: <strong>${item.status_code || 301}</strong></div>
                            </div>
                            <div style="display: flex; gap: 4px; flex-shrink: 0;">
                                <button type="button" onclick="editRedirectInline(${item.id}, '${item.old_url.replace(/'/g, "\\'")}', '${item.new_url.replace(/'/g, "\\'")}', ${item.status_code || 301})" class="icon-btn" style="color: var(--md-sys-color-primary);"><i class="bi bi-pencil"></i></button>
                                <button type="button" onclick="deleteRedirectAjax(${item.id})" class="icon-btn" style="color: var(--md-sys-color-error);"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                    `;
                });
                container.innerHTML = html;
            } else {
                container.innerHTML = '<div style="text-align: center; padding: 20px; color: red;">Failed to load redirects.</div>';
            }
        })
        .catch(err => {
            console.error(err);
            container.innerHTML = '<div style="text-align: center; padding: 20px; color: red;">An error occurred.</div>';
        });
}

function toggleRedirectAddForm() {
    const form = document.getElementById('redirect-add-form');
    if (form) {
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
        if (form.style.display === 'block') {
            document.getElementById('redirect-id-input').value = '';
            document.getElementById('redirect-old-input').value = '';
            document.getElementById('redirect-new-input').value = '';
            document.getElementById('redirect-code-input').value = '301';
        }
    }
}

function editRedirectInline(id, oldUrl, newUrl, statusCode) {
    const form = document.getElementById('redirect-add-form');
    if (form) {
        form.style.display = 'block';
        document.getElementById('redirect-id-input').value = id;
        document.getElementById('redirect-old-input').value = oldUrl;
        document.getElementById('redirect-new-input').value = newUrl;
        document.getElementById('redirect-code-input').value = statusCode || 301;
    }
}

function hideRedirectForm() {
    const form = document.getElementById('redirect-add-form');
    if (form) form.style.display = 'none';
}

function submitRedirectFormAjax(event) {
    if (event) event.preventDefault();
    const siteUrl = typeof SITE_URL !== 'undefined' ? SITE_URL : '';

    const id = document.getElementById('redirect-id-input').value;
    const oldUrl = document.getElementById('redirect-old-input').value;
    const newUrl = document.getElementById('redirect-new-input').value;
    const statusCode = document.getElementById('redirect-code-input').value;

    if (!oldUrl || !newUrl) {
        alert('Please fill in both old and new URL fields.');
        return;
    }

    const formData = new FormData();
    if (id) formData.append('id', id);
    formData.append('old_url', oldUrl);
    formData.append('new_url', newUrl);
    formData.append('status_code', statusCode);

    fetch(siteUrl + 'mod_redirect/ajax_save', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            if (navigator.vibrate) navigator.vibrate(10);
            hideRedirectForm();
            fetchRedirects();
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: data.message || 'Redirect saved',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        } else {
            alert(data.message || 'Error saving redirect');
        }
    })
    .catch(err => {
        console.error(err);
        alert('An error occurred');
    });
}

function deleteRedirectAjax(id) {
    if (!confirm('Are you sure you want to delete this redirect?')) return;
    const siteUrl = typeof SITE_URL !== 'undefined' ? SITE_URL : '';

    const formData = new FormData();
    formData.append('id', id);

    fetch(siteUrl + 'mod_redirect/ajax_delete', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            if (navigator.vibrate) navigator.vibrate(10);
            fetchRedirects();
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: data.message || 'Redirect deleted',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        } else {
            alert(data.message || 'Error deleting redirect');
        }
    })
    .catch(err => {
        console.error(err);
        alert('An error occurred');
    });
}
