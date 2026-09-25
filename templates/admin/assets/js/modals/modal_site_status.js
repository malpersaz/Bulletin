/**
 * Site Online / Maintenance Status Modal Sub-Module
 */

function openSiteStatusModal() {
    if (typeof closeUserMenuSheet === 'function') closeUserMenuSheet(true);
    const modal = document.getElementById('site-status-modal');
    const overlay = document.getElementById('site-status-modal-overlay');
    if (modal && overlay) {
        if (typeof window.AFModalEngine !== 'undefined' && window.AFModalEngine.pushModalElement) {
            window.AFModalEngine.pushModalElement(modal, null, { overlayId: 'site-status-modal-overlay' });
        } else {
            modal.classList.add('active');
            overlay.classList.add('active');
            if (navigator.vibrate) navigator.vibrate(10);
        }
    }
}

function closeSiteStatusModal() {
    const modal = document.getElementById('site-status-modal');
    const overlay = document.getElementById('site-status-modal-overlay');
    if (modal && overlay) {
        if (typeof window.AFModalEngine !== 'undefined' && window.AFModalEngine.closeSingle) {
            window.AFModalEngine.closeSingle('site-status-modal');
        } else {
            modal.classList.remove('active');
            overlay.classList.remove('active');
        }
    }
}

function goBackFromSiteStatusModal() {
    closeSiteStatusModal();
    if (typeof openUserMenuSheet === 'function') {
        openUserMenuSheet(true);
    }
}

function setSiteStatusInstantAjax(newStatus) {
    const siteUrl = typeof SITE_URL !== 'undefined' ? SITE_URL : '';
    const msg = getMaintenanceMsgInputVal();

    const formData = new FormData();
    formData.append('site_status', newStatus);
    formData.append('maintenance_msg', msg);

    fetch(siteUrl + 'mod_settings/ajax_save_site_status', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            if (navigator.vibrate) navigator.vibrate(10);
            updateSiteStatusUI(newStatus, msg);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: data.message || 'Site status updated',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        } else {
            alert(data.message || 'Error updating status');
        }
    })
    .catch(err => {
        console.error(err);
        alert('An error occurred');
    });
}

function saveMaintenanceMessageInstantAjax() {
    const siteUrl = typeof SITE_URL !== 'undefined' ? SITE_URL : '';
    const msg = getMaintenanceMsgInputVal();

    const activeRadio = document.querySelector('input[name="site_status_modal_radio"]:checked');
    const currentStatus = activeRadio ? activeRadio.value : '1';

    const formData = new FormData();
    formData.append('site_status', currentStatus);
    formData.append('maintenance_msg', msg);

    fetch(siteUrl + 'mod_settings/ajax_save_site_status', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            if (navigator.vibrate) navigator.vibrate(10);
            updateSiteStatusUI(currentStatus, msg);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: data.message || 'Message saved',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        }
    })
    .catch(err => console.error(err));
}

function getMaintenanceMsgInputVal() {
    const el = document.getElementById('modal_maintenance_msg_input');
    return el ? el.value : '';
}

function updateSiteStatusUI(status, message) {
    const activeRadio = document.querySelector(`input[name="site_status_modal_radio"][value="${status}"]`);
    if (activeRadio) activeRadio.checked = true;

    const maintenanceContainer = document.getElementById('modal_maintenance_msg_container');
    if (maintenanceContainer) {
        maintenanceContainer.style.display = (status === '0') ? 'block' : 'none';
    }
}
