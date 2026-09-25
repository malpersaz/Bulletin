/**
 * User Menu & Navigation Sheet Modal Sub-Module
 */

function openUserMenuSheet(keepSubmenu = false) {
    const sheet = document.getElementById('user-menu-sheet');
    const overlay = document.getElementById('user-menu-overlay');
    if (sheet && overlay) {
        if (!keepSubmenu) {
            showUserMenuMain();
        }
        if (typeof window.AFModalEngine !== 'undefined' && window.AFModalEngine.pushModalElement) {
            window.AFModalEngine.pushModalElement('user-menu-sheet', null, { overlayId: 'user-menu-overlay' });
        } else {
            sheet.classList.add('active');
            overlay.classList.add('active');
            if (navigator.vibrate) navigator.vibrate(10);
        }
    }
}

function closeUserMenuSheet(preserveSubmenu = false) {
    const sheet = document.getElementById('user-menu-sheet');
    const overlay = document.getElementById('user-menu-overlay');
    if (sheet && overlay) {
        if (typeof window.AFModalEngine !== 'undefined' && window.AFModalEngine.closeAll) {
            window.AFModalEngine.closeAll();
        } else {
            sheet.classList.remove('active');
            overlay.classList.remove('active');
        }
        if (!preserveSubmenu) {
            setTimeout(showUserMenuMain, 300);
        }
    }
}

function showUserSubmenu(menuId) {
    const mainMenu = document.getElementById('user-menu-main');
    if (mainMenu) {
        mainMenu.style.display = 'none';
        mainMenu.classList.remove('mainmenu-enter');
    }

    const pms = document.getElementById('user-menu-pm');
    const admin = document.getElementById('user-menu-admin');
    if (pms) {
        pms.style.display = 'none';
        pms.classList.remove('submenu-enter');
        pms.classList.remove('is-hidden');
    }
    if (admin) {
        admin.style.display = 'none';
        admin.classList.remove('submenu-enter');
        admin.classList.remove('is-hidden');
    }

    const target = document.getElementById(menuId);
    if (target) {
        target.classList.remove('is-hidden');
        target.style.display = 'block';
        requestAnimationFrame(() => {
            target.classList.add('submenu-enter');
        });
    }

    const backBtn = document.getElementById('user-menu-back-btn');
    if (backBtn) {
        backBtn.style.display = 'inline-flex';
    }
}

function showUserMenuMain() {
    const pms = document.getElementById('user-menu-pm');
    const admin = document.getElementById('user-menu-admin');
    if (pms) {
        pms.style.display = 'none';
        pms.classList.remove('submenu-enter');
        pms.classList.remove('is-hidden');
    }
    if (admin) {
        admin.style.display = 'none';
        admin.classList.remove('submenu-enter');
        admin.classList.remove('is-hidden');
    }

    const backBtn = document.getElementById('user-menu-back-btn');
    if (backBtn) {
        backBtn.style.display = 'none';
    }

    const mainMenu = document.getElementById('user-menu-main');
    if (mainMenu) {
        mainMenu.classList.remove('is-hidden');
        mainMenu.style.display = 'block';
        requestAnimationFrame(() => {
            mainMenu.classList.add('mainmenu-enter');
        });
    }
}

// Global delegated event listeners for Header and Modal actions
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', function(e) {
        const target = e.target.closest('[data-action]');
        if (!target) return;

        const action = target.getAttribute('data-action');
        switch (action) {
            case 'open-user-menu':
                if (typeof openUserMenuSheet === 'function') openUserMenuSheet();
                break;
            case 'close-user-menu':
                if (typeof closeUserMenuSheet === 'function') closeUserMenuSheet();
                break;
            case 'user-submenu': {
                const subId = target.getAttribute('data-target');
                if (subId && typeof showUserSubmenu === 'function') showUserSubmenu(subId);
                break;
            }
            case 'user-menu-main':
                if (typeof showUserMenuMain === 'function') showUserMenuMain();
                break;
            case 'open-site-status':
                if (typeof openSiteStatusModal === 'function') openSiteStatusModal();
                break;
            case 'open-site-settings-menu':
                if (typeof openSiteSettingsMenuModal === 'function') openSiteSettingsMenuModal();
                break;
            case 'close-site-settings-menu':
                if (typeof closeSiteSettingsMenuModal === 'function') closeSiteSettingsMenuModal();
                break;
            case 'open-newsletter':
                if (typeof openNewsletterModal === 'function') openNewsletterModal();
                break;
            case 'close-newsletter':
                if (typeof closeNewsletterModal === 'function') closeNewsletterModal();
                break;
            case 'open-stats':
                if (typeof openStatsModal === 'function') openStatsModal();
                break;
            case 'close-stats':
                if (typeof closeStatsModal === 'function') closeStatsModal();
                break;
            case 'open-theme-management':
                if (typeof openThemeManagementModal === 'function') openThemeManagementModal();
                break;
            case 'open-logo-icon-management':
                if (typeof openLogoIconManagementModal === 'function') openLogoIconManagementModal();
                break;
            case 'open-redirect':
                if (typeof openRedirectModal === 'function') openRedirectModal();
                break;
            case 'close-redirect':
                if (typeof closeRedirectModal === 'function') closeRedirectModal();
                break;
            case 'modal-close-all':
                if (window.AFModalEngine && typeof window.AFModalEngine.closeAll === 'function') {
                    window.AFModalEngine.closeAll();
                }
                break;
            case 'modal-back-settings':
                if (typeof goBackFromSiteSettingsMenuModal === 'function') goBackFromSiteSettingsMenuModal();
                break;
            case 'modal-back-theme':
                if (typeof goBackFromThemeManagementModal === 'function') goBackFromThemeManagementModal();
                break;
            case 'modal-back-logo':
                if (typeof goBackFromLogoIconManagementModal === 'function') goBackFromLogoIconManagementModal();
                break;
            case 'modal-back-status':
                if (typeof goBackFromSiteStatusModal === 'function') goBackFromSiteStatusModal();
                break;
            case 'modal-back-stats':
                if (typeof goBackFromStatsModal === 'function') goBackFromStatsModal();
                break;
            case 'modal-back-newsletter':
                if (typeof goBackFromNewsletterModal === 'function') goBackFromNewsletterModal();
                break;
            case 'modal-back-redirect':
                if (typeof goBackFromRedirectModal === 'function') goBackFromRedirectModal();
                break;
            case 'modal-dynamic-back':
                if (window.AFModalEngine && typeof window.AFModalEngine.back === 'function') {
                    window.AFModalEngine.back();
                }
                break;
            case 'export-newsletter-csv':
                if (typeof exportSubscribersCSV === 'function') exportSubscribersCSV();
                break;
            case 'toggle-newsletter-add':
                if (typeof toggleAddSubscriberForm === 'function') toggleAddSubscriberForm();
                break;
            case 'toggle-redirect-add':
                if (typeof toggleRedirectAddForm === 'function') toggleRedirectAddForm();
                break;
            case 'hide-redirect-form':
                if (typeof hideRedirectForm === 'function') hideRedirectForm();
                break;
            case 'filter-newsletter': {
                const status = target.getAttribute('data-status');
                if (status && typeof filterNewsletterStatus === 'function') {
                    filterNewsletterStatus(status, target);
                }
                break;
            }
            case 'site-status-open':
                if (typeof setSiteStatusInstantAjax === 'function') setSiteStatusInstantAjax('open');
                break;
            case 'site-status-closed':
                if (typeof setSiteStatusInstantAjax === 'function') setSiteStatusInstantAjax('closed');
                break;
            case 'clear-search':
                if (typeof clearSearch === 'function') clearSearch();
                break;
            case 'open-filter-sheet':
                if (typeof openSheet === 'function') openSheet();
                break;
            case 'exit-selection':
                if (typeof exitSelectionMode === 'function') exitSelectionMode();
                break;
            case 'close-sheet':
                if (typeof closeSheet === 'function') closeSheet();
                break;
            case 'select-chip': {
                const chipType = target.getAttribute('data-chip-type');
                if (typeof selectChip === 'function') selectChip(target, chipType);
                break;
            }
            case 'open-lang-sheet':
                if (typeof openLangSheet === 'function') openLangSheet();
                break;
            case 'close-lang-sheet':
                if (typeof closeLangSheet === 'function') closeLangSheet();
                break;
            case 'confirm-lang-selection':
                if (typeof confirmLangSelection === 'function') confirmLangSelection();
                break;
            case 'clear-filters':
                if (typeof clearFilters === 'function') clearFilters();
                break;
            case 'apply-filters':
                if (typeof applyFilters === 'function') applyFilters();
                break;
            case 'close-context-menu':
                if (typeof closeContextMenu === 'function') closeContextMenu();
                break;
            case 'context-action': {
                const ctxType = target.getAttribute('data-context-type');
                if (ctxType && typeof handleContextAction === 'function') handleContextAction(ctxType);
                break;
            }
            case 'toggle-wizard-cat':
                if (typeof toggleWizardCategorySheet === 'function') toggleWizardCategorySheet();
                break;
            case 'select-wizard-cat': {
                const titleEl = target.querySelector('.category-item-title');
                const name = titleEl ? titleEl.textContent.trim() : (target.getAttribute('data-name') || '');
                const id = target.getAttribute('data-id');
                if (typeof selectWizardCategory === 'function') selectWizardCategory(name, id);
                break;
            }
            case 'select-video-cat': {
                const titleEl = target.querySelector('.category-item-title');
                const name = titleEl ? titleEl.textContent.trim() : (target.getAttribute('data-name') || '');
                const id = target.getAttribute('data-id');
                if (typeof selectVideoEmbedWizardCategory === 'function') selectVideoEmbedWizardCategory(name, id);
                break;
            }
            case 'close-select-picker':
                if (typeof closeSelectPickerSheet === 'function') closeSelectPickerSheet();
                break;
            case 'select-picker-add':
                if (typeof handleSelectPickerAdd === 'function') handleSelectPickerAdd();
                break;
            case 'modal-push': {
                const url = target.getAttribute('data-url');
                if (url && window.AFModalEngine && typeof window.AFModalEngine.push === 'function') {
                    e.preventDefault();
                    window.AFModalEngine.push(url);
                }
                break;
            }
        }
    });

    // Form Submissions
    const statsForm = document.getElementById('statsModalForm');
    if (statsForm) {
        statsForm.addEventListener('submit', function(e) {
            if (typeof saveStatsForm === 'function') saveStatsForm(e);
        });
    }

    const subscriberForm = document.getElementById('newsletter-add-admin-form');
    if (subscriberForm) {
        subscriberForm.addEventListener('submit', function(e) {
            if (typeof submitAddSubscriberAdmin === 'function') submitAddSubscriberAdmin(e);
        });
    }

    const redirectForm = document.getElementById('redirect-admin-form');
    if (redirectForm) {
        redirectForm.addEventListener('submit', function(e) {
            if (typeof submitRedirectFormAjax === 'function') submitRedirectFormAjax(e);
        });
    }

    // Input handlers
    const maintInput = document.getElementById('maintenance_message_input');
    if (maintInput) {
        maintInput.addEventListener('change', function() {
            if (typeof saveMaintenanceMessageInstantAjax === 'function') saveMaintenanceMessageInstantAjax();
        });
        maintInput.addEventListener('blur', function() {
            if (typeof saveMaintenanceMessageInstantAjax === 'function') saveMaintenanceMessageInstantAjax();
        });
    }

    const newsSearch = document.getElementById('newsletter-search-input');
    if (newsSearch) {
        newsSearch.addEventListener('input', function() {
            if (typeof onNewsletterSearchInput === 'function') onNewsletterSearchInput();
        });
    }

    const redirSearch = document.getElementById('redirect-search-input');
    if (redirSearch) {
        redirSearch.addEventListener('input', function() {
            if (typeof onRedirectSearchInput === 'function') onRedirectSearchInput();
        });
    }

    const catSearch = document.getElementById('wizardCategorySearchInput');
    if (catSearch) {
        catSearch.addEventListener('input', function() {
            if (typeof filterWizardCategorySheet === 'function') filterWizardCategorySheet();
        });
    }

    const pickerSearch = document.getElementById('selectPickerSearchInput');
    if (pickerSearch) {
        pickerSearch.addEventListener('input', function() {
            if (typeof filterSelectPickerSheet === 'function') filterSelectPickerSheet();
        });
    }

});

