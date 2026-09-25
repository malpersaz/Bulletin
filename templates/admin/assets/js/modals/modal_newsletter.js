/**
 * Newsletter Subscriptions Modal Sub-Module
 */

window.activeNewsletterStatusFilter = window.activeNewsletterStatusFilter || 'all';

function openNewsletterModal() {
    if (typeof closeUserMenuSheet === 'function') closeUserMenuSheet(true);
    const sheet = document.getElementById('newsletter-sheet');
    const overlay = document.getElementById('newsletter-sheet-overlay');
    if (sheet && overlay) {
        if (typeof window.AFModalEngine !== 'undefined' && window.AFModalEngine.pushModalElement) {
            window.AFModalEngine.pushModalElement(sheet, null, { overlayId: 'newsletter-sheet-overlay' });
        } else {
            sheet.classList.add('active');
            overlay.classList.add('active');
            if (navigator.vibrate) navigator.vibrate(10);
        }
        fetchNewsletterSubscribers();
    }
}

function closeNewsletterModal() {
    const sheet = document.getElementById('newsletter-sheet');
    const overlay = document.getElementById('newsletter-sheet-overlay');
    if (sheet && overlay) {
        if (typeof window.AFModalEngine !== 'undefined' && window.AFModalEngine.closeSingle) {
            window.AFModalEngine.closeSingle('newsletter-sheet');
        } else {
            sheet.classList.remove('active');
            overlay.classList.remove('active');
        }
    }
}

function goBackFromNewsletterModal() {
    closeNewsletterModal();
    if (typeof openUserMenuSheet === 'function') {
        openUserMenuSheet(true);
        if (typeof showUserSubmenu === 'function') showUserSubmenu('user-menu-admin');
    }
}

function onNewsletterSearchInput() {
    fetchNewsletterSubscribers();
}

function filterNewsletterStatus(status, btn) {
    window.activeNewsletterStatusFilter = status;
    document.querySelectorAll('.newsletter-status-filter-btn').forEach(b => {
        b.style.background = 'var(--md-sys-color-surface-container, #f3edf7)';
        b.style.color = 'var(--md-sys-color-on-surface-variant, #49454f)';
    });
    if (btn) {
        btn.style.background = 'var(--md-sys-color-primary, #6750a4)';
        btn.style.color = '#ffffff';
    }
    fetchNewsletterSubscribers();
}

function fetchNewsletterSubscribers() {
    const container = document.getElementById('newsletter-list-container');
    if (!container) return;
    const siteUrl = typeof SITE_URL !== 'undefined' ? SITE_URL : '';

    const searchInput = document.getElementById('newsletter-search-input');
    const q = searchInput ? searchInput.value : '';

    fetch(siteUrl + 'mod_newsletter/ajax_list?q=' + encodeURIComponent(q) + '&status=' + window.activeNewsletterStatusFilter)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' && data.subscribers) {
                if (data.subscribers.length === 0) {
                    container.innerHTML = '<div style="text-align: center; padding: 24px; color: var(--md-sys-color-on-surface-variant);"><i class="bi bi-envelope-x" style="font-size: 28px; opacity: 0.5;"></i><p style="margin-top: 8px;">No subscribers found.</p></div>';
                    return;
                }
                let html = '';
                data.subscribers.forEach(sub => {
                    const isSub = sub.is_active == 1;
                    const statusBadge = isSub
                        ? '<span style="background: #e6f4ea; color: #137333; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">Subscribed</span>'
                        : '<span style="background: #fce8e6; color: #c62828; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">Unsubscribed</span>';

                    html += `
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border-bottom: 1px solid var(--md-sys-color-outline-variant); background: var(--md-sys-color-surface);">
                            <div style="min-width: 0; flex: 1; margin-right: 12px;">
                                <div style="font-weight: 600; font-size: 14px; color: var(--md-sys-color-on-surface); word-break: break-all;">${sub.email}</div>
                                <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px;">
                                    ${statusBadge}
                                    <span style="font-size: 11px; opacity: 0.7;">${sub.created_at || ''}</span>
                                </div>
                            </div>
                            <div style="display: flex; gap: 4px; flex-shrink: 0;">
                                <button type="button" onclick="copySubscriberEmail('${sub.email}')" class="icon-btn" title="Copy Email" style="color: var(--md-sys-color-primary);"><i class="bi bi-clipboard"></i></button>
                                <button type="button" onclick="deleteSubscriberAjax(${sub.id}, '${sub.email}')" class="icon-btn" title="Delete" style="color: var(--md-sys-color-error);"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                    `;
                });
                container.innerHTML = html;
            } else {
                container.innerHTML = '<div style="text-align: center; padding: 20px; color: red;">Failed to load subscribers.</div>';
            }
        })
        .catch(err => {
            console.error(err);
            container.innerHTML = '<div style="text-align: center; padding: 20px; color: red;">An error occurred.</div>';
        });
}

function copySubscriberEmail(email) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(email).then(() => {
            if (navigator.vibrate) navigator.vibrate(10);
            if (typeof Swal !== 'undefined') {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 1500
                });
                Toast.fire({ icon: 'success', title: 'Email copied!' });
            }
        });
    }
}

function toggleAddSubscriberForm() {
    const form = document.getElementById('newsletter-add-form');
    if (form) {
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }
}

function submitAddSubscriberAdmin(e) {
    if (e) e.preventDefault();
    const siteUrl = typeof SITE_URL !== 'undefined' ? SITE_URL : '';

    const emailInput = document.getElementById('newsletter-email-input');
    const email = emailInput ? emailInput.value : '';
    if (!email) return;

    const formData = new FormData();
    formData.append('email', email);

    fetch(siteUrl + 'mod_newsletter/ajax_add_subscriber', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            if (navigator.vibrate) navigator.vibrate(10);
            if (emailInput) emailInput.value = '';
            toggleAddSubscriberForm();
            fetchNewsletterSubscribers();
            if (typeof Swal !== 'undefined') {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 1500
                });
                Toast.fire({ icon: 'success', title: data.message });
            }
        } else {
            alert(data.message || 'Error adding subscriber');
        }
    })
    .catch(err => {
        console.error(err);
        alert('An error occurred');
    });
}

function deleteSubscriberAjax(id, email) {
    const confirmMsg = (window.adminLang && window.adminLang.general && window.adminLang.general.delete_confirm) || 'Are you sure you want to delete this subscriber?';
    if (!confirm(confirmMsg)) return;
    const siteUrl = typeof SITE_URL !== 'undefined' ? SITE_URL : '';

    const formData = new FormData();
    formData.append('id', id);

    fetch(siteUrl + 'mod_newsletter/ajax_delete_subscriber', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            if (navigator.vibrate) navigator.vibrate(15);
            fetchNewsletterSubscribers();
            if (typeof Swal !== 'undefined') {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 1500
                });
                Toast.fire({ icon: 'success', title: data.message });
            }
        } else {
            alert(data.message || 'Error deleting');
        }
    });
}

function exportSubscribersCSV() {
    const siteUrl = typeof SITE_URL !== 'undefined' ? SITE_URL : '';
    window.location.href = siteUrl + 'mod_newsletter/ajax_export_csv';
}
