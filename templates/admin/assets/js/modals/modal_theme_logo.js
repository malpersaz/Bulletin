/**
 * Site Settings, Theme, Logo & Stats Modal Sub-Module
 */

function openSiteSettingsMenuModal() {
    if (typeof closeUserMenuSheet === 'function') closeUserMenuSheet(true);
    const modal = document.getElementById('site-settings-menu-modal');
    if (modal) {
        if (typeof window.AFModalEngine !== 'undefined' && window.AFModalEngine.pushModalElement) {
            window.AFModalEngine.pushModalElement(modal);
        } else {
            if (modal.showModal && !modal.open) modal.showModal();
            modal.classList.add('active');
            if (navigator.vibrate) navigator.vibrate(10);
        }
    }
}

function closeSiteSettingsMenuModal() {
    const modal = document.getElementById('site-settings-menu-modal');
    if (modal) {
        if (typeof window.AFModalEngine !== 'undefined' && window.AFModalEngine.closeSingle) {
            window.AFModalEngine.closeSingle('site-settings-menu-modal');
        } else {
            if (modal.close && modal.open) modal.close();
            modal.classList.remove('active');
        }
    }
}

function goBackFromSiteSettingsMenuModal() {
    closeSiteSettingsMenuModal();
    if (typeof openUserMenuSheet === 'function') {
        openUserMenuSheet(true);
        if (typeof showUserSubmenu === 'function') showUserSubmenu('user-menu-admin');
    }
}

function openThemeManagementModal() {
    const modal = document.getElementById('theme-management-modal');
    if (modal) {
        if (typeof window.AFModalEngine !== 'undefined' && window.AFModalEngine.pushModalElement) {
            window.AFModalEngine.pushModalElement('theme-management-modal');
        } else {
            if (modal.showModal && !modal.open) modal.showModal();
            modal.classList.add('active');
            if (navigator.vibrate) navigator.vibrate(10);
        }
        loadThemesAjax();
    }
}

function closeThemeManagementModal() {
    const modal = document.getElementById('theme-management-modal');
    if (modal) {
        if (typeof window.AFModalEngine !== 'undefined' && window.AFModalEngine.closeSingle) {
            window.AFModalEngine.closeSingle('theme-management-modal');
        } else {
            if (modal.close && modal.open) modal.close();
            modal.classList.remove('active');
        }
    }
}

function goBackFromThemeManagementModal() {
    if (typeof window.AFModalEngine !== 'undefined' && window.AFModalEngine.back) {
        window.AFModalEngine.back();
    } else {
        closeThemeManagementModal();
        openSiteSettingsMenuModal();
    }
}

function loadThemesAjax() {
    const container = document.getElementById('themeModalBody');
    if (!container) return;
    const siteUrl = typeof SITE_URL !== 'undefined' ? SITE_URL : '';

    container.innerHTML = '<div style="text-align: center; padding: 30px 20px; color: var(--md-sys-color-on-surface-variant, #49454F);"><i class="bi bi-arrow-repeat" style="font-size: 28px; display: inline-block; animation: spin 1s infinite linear;"></i><p class="theme-loading-text" style="margin-top: 12px; font-weight: 600;">' + (((typeof adminLang !== "undefined" ? adminLang : {}) && (typeof adminLang !== "undefined" ? adminLang : {}).mod_settings && (typeof adminLang !== "undefined" ? adminLang : {}).mod_settings.loading_themes) || 'Loading themes...') + '</p></div>';

    fetch(siteUrl + 'mod_settings/ajax_get_themes', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
    })
    .then(res => res.json())
    .then(data => renderThemesList(data, container))
    .catch(err => {
        console.error('Failed to load themes:', err);
        const errMsg = ((typeof adminLang !== "undefined" ? adminLang : {}) && (typeof adminLang !== "undefined" ? adminLang : {}).mod_settings && (typeof adminLang !== "undefined" ? adminLang : {}).mod_settings.generic_error) || 'An error occurred while loading themes.';
        container.innerHTML = '<div style="text-align: center; padding: 20px; color: var(--md-sys-color-error, #ba1a1a);"><i class="bi bi-exclamation-circle" style="font-size: 28px;"></i><p style="margin-top: 8px;">' + errMsg + '</p></div>';
    });
}

function renderThemesList(data, container) {
    if (data && data.status === 'success' && Array.isArray(data.themes)) {
        const filteredThemes = data.themes.filter(t => t.id !== 'partials' && t.id !== 'admin' && t.id !== 'base_static');
        let html = '<div class="edit-container"><div class="edit-card" style="display: flex; flex-direction: column; gap: 12px;">';
        filteredThemes.forEach(theme => {
            const isChecked = theme.is_active ? 'checked' : '';
            const activeBadge = theme.is_active 
                ? '<span class="theme-active-badge" style="background: var(--md-sys-color-primary, #6750A4); color: #ffffff; width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 2px 6px rgba(103, 80, 164, 0.3);"><i class="bi bi-check-lg" style="font-size: 16px; font-weight: 800;"></i></span>' 
                : '';
            const borderStyle = theme.is_active 
                ? 'border: 2px solid var(--md-sys-color-primary, #6750A4); box-shadow: 0 4px 12px rgba(103, 80, 164, 0.16);' 
                : 'border: 1px solid var(--md-sys-color-outline-variant, #E7E0EC);';
            const bgStyle = theme.is_active 
                ? 'background: var(--md-sys-color-primary-container, #EADDFF);' 
                : 'background: var(--md-sys-color-surface, #FAF9FD);';
            const titleColor = theme.is_active 
                ? 'color: var(--md-sys-color-on-primary-container, #21005D);' 
                : 'color: var(--md-sys-color-on-surface, #1C1B1F);';

            html += `
                <label style="display: flex; align-items: center; justify-content: space-between; padding: 16px; border-radius: 14px; ${borderStyle} ${bgStyle} cursor: pointer; margin: 0; transition: all 0.2s ease-in-out;">
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <input type="radio" name="app_template" value="${theme.id}" ${isChecked} onchange="selectThemeAjax('${theme.id}')" style="width: 20px; height: 20px; accent-color: var(--md-sys-color-primary, #6750A4); cursor: pointer;">
                        <div>
                            <strong style="font-size: 15px; font-weight: 700; ${titleColor}">${theme.name}</strong>
                            <span style="display: block; font-size: 12px; opacity: 0.75; color: var(--md-sys-color-on-surface-variant, #49454F);">${theme.id}</span>
                        </div>
                    </div>
                    ${activeBadge}
                </label>
            `;
        });
        html += '</div></div>';
        container.innerHTML = html;
    } else {
        const fallbackMsg = ((typeof adminLang !== "undefined" ? adminLang : {}) && (typeof adminLang !== "undefined" ? adminLang : {}).mod_settings && (typeof adminLang !== "undefined" ? adminLang : {}).mod_settings.generic_error) || 'Failed to load themes.';
        container.innerHTML = '<p style="color: var(--md-sys-color-error, #ba1a1a); text-align: center; padding: 20px;">' + ((data && data.message) || fallbackMsg) + '</p>';
    }
}

function updateActiveThemeCard(themeId) {
    const container = document.getElementById('themeModalBody');
    if (!container) return;
    const labels = container.querySelectorAll('label');
    labels.forEach(label => {
        const input = label.querySelector('input[name="app_template"]');
        if (!input) return;
        const isActive = input.value === themeId;
        input.checked = isActive;

        if (isActive) {
            label.style.border = '2px solid var(--md-sys-color-primary, #6750A4)';
            label.style.boxShadow = '0 4px 12px rgba(103, 80, 164, 0.16)';
            label.style.background = 'var(--md-sys-color-primary-container, #EADDFF)';
            
            let badge = label.querySelector('.theme-active-badge');
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'theme-active-badge';
                badge.style.cssText = 'background: var(--md-sys-color-primary, #6750A4); color: #ffffff; width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 2px 6px rgba(103, 80, 164, 0.3);';
                badge.innerHTML = '<i class="bi bi-check-lg" style="font-size: 16px; font-weight: 800;"></i>';
                label.appendChild(badge);
            }
            const titleEl = label.querySelector('strong');
            if (titleEl) titleEl.style.color = 'var(--md-sys-color-on-primary-container, #21005D)';
        } else {
            label.style.border = '1px solid var(--md-sys-color-outline-variant, #E7E0EC)';
            label.style.boxShadow = 'none';
            label.style.background = 'var(--md-sys-color-surface, #FAF9FD)';
            
            const badge = label.querySelector('.theme-active-badge');
            if (badge) badge.remove();

            const titleEl = label.querySelector('strong');
            if (titleEl) titleEl.style.color = 'var(--md-sys-color-on-surface, #1C1B1F)';
        }
    });
}

function selectThemeAjax(themeId) {
    if (!themeId) return;
    updateActiveThemeCard(themeId);

    const siteUrl = typeof SITE_URL !== 'undefined' ? SITE_URL : '';
    const formData = new FormData();
    formData.append('app_template', themeId);

    fetch(siteUrl + 'mod_settings/ajax_save_theme', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data && data.status === 'success') {
            if (navigator.vibrate) navigator.vibrate(12);
            const toastMsg = ((typeof adminLang !== "undefined" ? adminLang : {}) && (typeof adminLang !== "undefined" ? adminLang : {}).mod_settings && (typeof adminLang !== "undefined" ? adminLang : {}).mod_settings.theme_save_success) || data.message || 'Theme updated successfully.';
            if (typeof window.showAFToast === 'function') {
                window.showAFToast(toastMsg, 'success', 1500);
            }
        } else {
            console.error(data ? data.message : 'Error updating theme');
            const errorMsg = (data && data.message) ? data.message : 'Failed to update theme.';
            if (typeof window.showAFToast === 'function') {
                window.showAFToast(errorMsg, 'error', 2000);
            }
            loadThemesAjax();
        }
    })
    .catch(err => {
        console.error('Theme selection save error:', err);
        if (typeof window.showAFToast === 'function') {
            const errThemeMsg = ((typeof adminLang !== "undefined" ? adminLang : {}) && (typeof adminLang !== "undefined" ? adminLang : {}).mod_settings && (typeof adminLang !== "undefined" ? adminLang : {}).mod_settings.generic_error) || 'An error occurred while saving theme.';
            window.showAFToast(errThemeMsg, 'error', 2000);
        }
        loadThemesAjax();
    });
}

function openStatsModal() {
    if (typeof closeUserMenuSheet === 'function') closeUserMenuSheet(true);
    const modal = document.getElementById('stats-modal');
    if (modal) {
        if (typeof window.AFModalEngine !== 'undefined' && window.AFModalEngine.pushModalElement) {
            window.AFModalEngine.pushModalElement(modal);
        } else {
            if (modal.showModal && !modal.open) modal.showModal();
            modal.classList.add('active');
            if (navigator.vibrate) navigator.vibrate(10);
        }
    }
}

function closeStatsModal() {
    const modal = document.getElementById('stats-modal');
    if (modal) {
        if (typeof window.AFModalEngine !== 'undefined' && window.AFModalEngine.closeSingle) {
            window.AFModalEngine.closeSingle('stats-modal');
        } else {
            if (modal.close && modal.open) modal.close();
            modal.classList.remove('active');
        }
    }
}

function goBackFromStatsModal() {
    closeStatsModal();
    if (typeof openUserMenuSheet === 'function') {
        openUserMenuSheet(true);
        if (typeof showUserSubmenu === 'function') showUserSubmenu('user-menu-admin');
    }
}

function saveStatsForm(e) {
    if (e) e.preventDefault();
    const form = document.getElementById('statsForm');
    if (!form) return;
    const siteUrl = typeof SITE_URL !== 'undefined' ? SITE_URL : '';

    const formData = new FormData(form);
    fetch(siteUrl + 'mod_settings/ajax_save_stats', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            closeStatsModal();
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: data.message || 'Stats settings saved',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        } else {
            const errStats = ((typeof adminLang !== "undefined" ? adminLang : {}) && (typeof adminLang !== "undefined" ? adminLang : {}).mod_settings && (typeof adminLang !== "undefined" ? adminLang : {}).mod_settings.generic_error) || 'Error saving stats';
            alert(data.message || errStats);
        }
    })
    .catch(err => {
        console.error(err);
        const errStatsMsg = ((typeof adminLang !== "undefined" ? adminLang : {}) && (typeof adminLang !== "undefined" ? adminLang : {}).mod_settings && (typeof adminLang !== "undefined" ? adminLang : {}).mod_settings.generic_error) || 'An error occurred while saving stats';
        alert(errStatsMsg);
    });
}

function openLogoIconManagementModal() {
    closeSiteSettingsMenuModal();
    const modal = document.getElementById('logo-icon-management-modal');
    if (modal) {
        if (typeof window.AFModalEngine !== 'undefined' && window.AFModalEngine.pushModalElement) {
            window.AFModalEngine.pushModalElement(modal);
        } else {
            if (modal.showModal && !modal.open) modal.showModal();
            modal.classList.add('active');
            if (navigator.vibrate) navigator.vibrate(10);
        }
        loadLogoIconAjax();
    }
}

function closeLogoIconManagementModal() {
    const modal = document.getElementById('logo-icon-management-modal');
    if (modal) {
        if (typeof window.AFModalEngine !== 'undefined' && window.AFModalEngine.closeSingle) {
            window.AFModalEngine.closeSingle('logo-icon-management-modal');
        } else {
            if (modal.close && modal.open) modal.close();
            modal.classList.remove('active');
        }
    }
}

function goBackFromLogoIconManagementModal() {
    closeLogoIconManagementModal();
    openSiteSettingsMenuModal();
}

function loadLogoIconAjax() {
    const container = document.getElementById('logoIconModalBody');
    if (!container) return;
    const siteUrl = typeof SITE_URL !== 'undefined' ? SITE_URL : '';
    const lang = ((typeof adminLang !== "undefined" ? adminLang : {}) && (typeof adminLang !== "undefined" ? adminLang : {}).mod_settings) || {};
    const loadingMsg = lang.loading_message || 'Loading...';

    container.innerHTML = '<div style="text-align: center; padding: 20px; color: var(--md-sys-color-on-surface-variant, #49454F);"><i class="bi bi-arrow-repeat" style="font-size: 24px; display: inline-block; animation: spin 1s infinite linear;"></i><p style="margin-top: 8px;">' + loadingMsg + '</p></div>';

    fetch(siteUrl + 'mod_settings/ajax_get_logo_icon_data')
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' && data.data) {
                renderLogoIconModalBody(data.data);
            } else {
                container.innerHTML = '<p style="color: red; text-align: center;">' + (data.message || (lang.generic_error || 'Failed to load logo & icon data.')) + '</p>';
            }
        })
        .catch(err => {
            console.error(err);
            container.innerHTML = '<p style="color: red; text-align: center;">' + (lang.generic_error || 'An error occurred while loading logo & icon settings.') + '</p>';
        });
}

function getSiteAssetUrl() {
    if (typeof SITE_ADDRESS !== 'undefined' && SITE_ADDRESS) {
        return SITE_ADDRESS;
    }
    if (typeof SITE_URL !== 'undefined' && SITE_URL) {
        return SITE_URL.replace(/\/[a-z]{2,3}\/$/i, '/');
    }
    return '/';
}

function renderLogoIconModalBody(d) {
    const container = document.getElementById('logoIconModalBody');
    if (!container || !d) return;

    const logoType = d.logo_type || 'rectangle';
    const siteUrl = typeof SITE_URL !== 'undefined' ? SITE_URL : '';
    const siteAssetUrl = getSiteAssetUrl();
    const timestamp = Date.now();

    const lang = ((typeof adminLang !== "undefined" ? adminLang : {}) && (typeof adminLang !== "undefined" ? adminLang : {}).mod_settings) || {};
    const headerTitle = lang.header_style_title || 'Header Display Style';
    const headerDesc = lang.header_style_desc || 'Choose how the logo and site name will appear in the header.';
    const styleSquare = lang.header_style_square || 'Only Square Logo';
    const styleRect = lang.header_style_rect || 'Only Rectangle Logo';
    const styleSquareName = lang.header_style_square_name || 'Square Logo & Site Name';
    const styleRectName = lang.header_style_rect_name || 'Rectangle Logo & Site Name';
    const styleNameOnly = lang.header_style_name_only || 'Site Name Only';
    const assetsTitle = (lang.sections && lang.sections.assets_title) || 'Assets';
    const logoHelpText = lang.logo_help_text || 'Upload a transparent PNG. Minimum 512x512 px, recommended 1024x1024 px or larger. The system will generate favicon, icon, and manifest files automatically.';
    const logoRectTitle = lang.logo_rect_title || 'Rectangle Logo (3:1)';
    const logoSquareTitle = lang.logo_square_title || 'Square Logo (1:1)';
    const logoSymbolTitle = lang.logo_symbol_title || 'Favicon (1:1)';
    const deleteText = lang.delete || 'Delete';

    const animSectionTitle = lang.animated_logo_section_title || 'Animated Logo';
    const animSectionDesc = lang.animated_logo_section_desc || 'Upload a WebM, MP4, or Lottie JSON file to replace the static header logo.';
    const enableAnimLogo = lang.enable_animated_logo || 'Enable Animated Logo';
    const animHeightLabel = lang.animated_logo_height_label || 'MAX DISPLAY HEIGHT (PX)';
    const animHeight48Std = lang.animated_logo_height_48_std || '48 px (Standard)';
    const animAspectLabel = lang.animated_logo_aspect_label || 'ASPECT RATIO';
    const animAspectVideo = lang.animated_logo_aspect_video || '16:9 Video';
    const animAspectHorizontal = lang.animated_logo_aspect_horizontal || '3:1 Horizontal';
    const animAspectSquare = lang.animated_logo_aspect_square || '1:1 Square';
    const animAspectAuto = lang.animated_logo_aspect_auto || 'Auto Detect';
    const animModeLabel = lang.animated_logo_mode_label || 'PLAY MODE';
    const animModeLoop = lang.animated_logo_mode_loop || 'Continuous Loop';
    const animModeOnce = lang.animated_logo_mode_once || 'Play Once & Pause';
    const animModeHover = lang.animated_logo_mode_hover || 'Play on Hover';
    const animBlendLabel = lang.animated_logo_blend_label || 'BACKGROUND BLEND MODE';
    const animBlendMultiply = lang.animated_logo_blend_multiply || 'Remove Light Background';
    const animBlendScreen = lang.animated_logo_blend_screen || 'Screen';
    const animBlendNone = lang.animated_logo_blend_none || 'None (Original)';

    let html = `
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <!-- Header Display Style Section -->
            <div style="background: var(--md-sys-color-surface-variant, #F4EFF4); padding: 14px; border-radius: 14px;">
                <h4 style="margin: 0 0 4px 0; font-size: 13px; font-weight: 700; color: var(--md-sys-color-on-surface, #1C1B1F);">${headerTitle}</h4>
                <p style="margin: 0 0 10px 0; font-size: 11px; color: var(--md-sys-color-on-surface-variant, #49454F);">${headerDesc}</p>
                
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <div onclick="setHeaderStyleAjax('square')" class="modal-style-option ${logoType === 'square' ? 'active' : ''}" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; border-radius: 10px; border: ${logoType === 'square' ? '2px solid var(--md-sys-color-primary, #6750A4)' : '1px solid var(--md-sys-color-outline-variant, #E7E0EC)'}; background: ${logoType === 'square' ? 'var(--md-sys-color-primary-container, #EADDFF)' : '#ffffff'}; cursor: pointer;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i class="bi bi-person-square" style="font-size: 18px; color: var(--md-sys-color-primary, #6750A4);"></i>
                            <span style="font-size: 12px; font-weight: 600;">${styleSquare}</span>
                        </div>
                        ${logoType === 'square' ? '<i class="bi bi-check-circle-fill" style="color: var(--md-sys-color-primary, #6750A4);"></i>' : ''}
                    </div>

                    <div onclick="setHeaderStyleAjax('rectangle')" class="modal-style-option ${logoType === 'rectangle' ? 'active' : ''}" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; border-radius: 10px; border: ${logoType === 'rectangle' ? '2px solid var(--md-sys-color-primary, #6750A4)' : '1px solid var(--md-sys-color-outline-variant, #E7E0EC)'}; background: ${logoType === 'rectangle' ? 'var(--md-sys-color-primary-container, #EADDFF)' : '#ffffff'}; cursor: pointer;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i class="bi bi-image" style="font-size: 18px; color: var(--md-sys-color-primary, #6750A4);"></i>
                            <span style="font-size: 12px; font-weight: 600;">${styleRect}</span>
                        </div>
                        ${logoType === 'rectangle' ? '<i class="bi bi-check-circle-fill" style="color: var(--md-sys-color-primary, #6750A4);"></i>' : ''}
                    </div>

                    <div onclick="setHeaderStyleAjax('square_name')" class="modal-style-option ${logoType === 'square_name' ? 'active' : ''}" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; border-radius: 10px; border: ${logoType === 'square_name' ? '2px solid var(--md-sys-color-primary, #6750A4)' : '1px solid var(--md-sys-color-outline-variant, #E7E0EC)'}; background: ${logoType === 'square_name' ? 'var(--md-sys-color-primary-container, #EADDFF)' : '#ffffff'}; cursor: pointer;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i class="bi bi-layout-text-sidebar" style="font-size: 18px; color: var(--md-sys-color-primary, #6750A4);"></i>
                            <span style="font-size: 12px; font-weight: 600;">${styleSquareName}</span>
                        </div>
                        ${logoType === 'square_name' ? '<i class="bi bi-check-circle-fill" style="color: var(--md-sys-color-primary, #6750A4);"></i>' : ''}
                    </div>

                    <div onclick="setHeaderStyleAjax('rectangle_name')" class="modal-style-option ${logoType === 'rectangle_name' ? 'active' : ''}" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; border-radius: 10px; border: ${logoType === 'rectangle_name' ? '2px solid var(--md-sys-color-primary, #6750A4)' : '1px solid var(--md-sys-color-outline-variant, #E7E0EC)'}; background: ${logoType === 'rectangle_name' ? 'var(--md-sys-color-primary-container, #EADDFF)' : '#ffffff'}; cursor: pointer;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i class="bi bi-layout-text-window" style="font-size: 18px; color: var(--md-sys-color-primary, #6750A4);"></i>
                            <span style="font-size: 12px; font-weight: 600;">${styleRectName}</span>
                        </div>
                        ${logoType === 'rectangle_name' ? '<i class="bi bi-check-circle-fill" style="color: var(--md-sys-color-primary, #6750A4);"></i>' : ''}
                    </div>

                    <div onclick="setHeaderStyleAjax('name_only')" class="modal-style-option ${logoType === 'name_only' ? 'active' : ''}" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; border-radius: 10px; border: ${logoType === 'name_only' ? '2px solid var(--md-sys-color-primary, #6750A4)' : '1px solid var(--md-sys-color-outline-variant, #E7E0EC)'}; background: ${logoType === 'name_only' ? 'var(--md-sys-color-primary-container, #EADDFF)' : '#ffffff'}; cursor: pointer;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i class="bi bi-fonts" style="font-size: 18px; color: var(--md-sys-color-primary, #6750A4);"></i>
                            <span style="font-size: 12px; font-weight: 600;">${styleNameOnly}</span>
                        </div>
                        ${logoType === 'name_only' ? '<i class="bi bi-check-circle-fill" style="color: var(--md-sys-color-primary, #6750A4);"></i>' : ''}
                    </div>
                </div>
            </div>

            <!-- Assets Side-by-Side Upload Cards -->
            <div style="background: #ffffff; padding: 16px; border-radius: 16px; border: 1px solid var(--md-sys-color-outline-variant, #E7E0EC); box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                    <h4 style="margin: 0; font-size: 13.5px; font-weight: 700; color: var(--md-sys-color-on-surface, #1C1B1F);">${assetsTitle}</h4>
                </div>
                <p style="margin: 0 0 14px 0; font-size: 11px; line-height: 1.45; color: var(--md-sys-color-on-surface-variant, #49454F);">${logoHelpText}</p>

                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <!-- Rectangle Logo (3:1) Card -->
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <span style="font-size: 12px; font-weight: 700; color: #0f172a;">${logoRectTitle.replace(/\s*\(\d+:\d+\)/, '')}</span>
                                <span style="font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 6px; background: #e0e7ff; color: #3730a3;">3:1</span>
                            </div>
                            ${d.logo_path ? `
                                <button type="button" onclick="event.stopPropagation(); removeLogoAssetModalAjax('logo_path')" style="width: 24px; height: 24px; border-radius: 6px; background: #fee2e2; color: #dc2626; border: none; display: inline-flex; align-items: center; justify-content: center; cursor: pointer;" title="${deleteText}">
                                    <i class="bi bi-trash" style="font-size: 11px;"></i>
                                </button>
                            ` : ''}
                        </div>

                        <div style="position: relative; width: 100%; height: 80px; border: 2px dashed #cbd5e1; border-radius: 10px; display: flex; align-items: center; justify-content: center; overflow: hidden; background-color: #ffffff; background-image: linear-gradient(45deg, #f1f5f9 25%, transparent 25%), linear-gradient(-45deg, #f1f5f9 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #f1f5f9 75%), linear-gradient(-45deg, transparent 75%, #f1f5f9 75%); background-size: 16px 16px; background-position: 0 0, 0 8px, 8px -8px, -8px 0px; cursor: pointer; transition: border-color 0.2s;" onclick="document.getElementById('modalInputRect').click()" onmouseover="this.style.borderColor='#6366f1';" onmouseout="this.style.borderColor='#cbd5e1';">
                            ${d.logo_path ? `
                                <img src="${siteAssetUrl}static/mod_settings/${d.logo_path}?t=${timestamp}" style="max-width: 90%; max-height: 90%; object-fit: contain; padding: 4px;" onerror="this.onerror=null; this.src='${siteAssetUrl}static/mod_settings/${d.logo_path}';">
                            ` : `
                                <div style="text-align: center; color: #6366f1;">
                                    <i class="bi bi-cloud-arrow-up" style="font-size: 24px; display: block; margin-bottom: 2px;"></i>
                                    <span style="display: block; font-size: 10px; font-weight: 600;">${logoRectTitle}</span>
                                </div>
                            `}
                        </div>
                        <input type="file" id="modalInputRect" style="display:none;" accept="image/*" onchange="processSelectedLogoModalImage(this, 2)">
                    </div>

                    <!-- Square Logo & Favicon Side-by-Side Row -->
                    <div style="display: flex; gap: 12px; align-items: stretch;">
                        <!-- Square Logo (1:1) -->
                        <div style="flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                                    <div style="display: flex; align-items: center; gap: 4px;">
                                        <span style="font-size: 11.5px; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${logoSquareTitle.replace(/\s*\(\d+:\d+\)/, '')}</span>
                                        <span style="font-size: 9.5px; font-weight: 700; padding: 1px 5px; border-radius: 5px; background: #e0e7ff; color: #3730a3;">1:1</span>
                                    </div>
                                    ${d.site_icon ? `
                                        <button type="button" onclick="event.stopPropagation(); removeLogoAssetModalAjax('site_icon')" style="width: 24px; height: 24px; border-radius: 6px; background: #fee2e2; color: #dc2626; border: none; display: inline-flex; align-items: center; justify-content: center; cursor: pointer;" title="${deleteText}">
                                            <i class="bi bi-trash" style="font-size: 11px;"></i>
                                        </button>
                                    ` : ''}
                                </div>

                                <div style="position: relative; width: 100%; height: 75px; border: 2px dashed #cbd5e1; border-radius: 10px; display: flex; align-items: center; justify-content: center; overflow: hidden; background-color: #ffffff; background-image: linear-gradient(45deg, #f1f5f9 25%, transparent 25%), linear-gradient(-45deg, #f1f5f9 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #f1f5f9 75%), linear-gradient(-45deg, transparent 75%, #f1f5f9 75%); background-size: 16px 16px; background-position: 0 0, 0 8px, 8px -8px, -8px 0px; cursor: pointer; transition: border-color 0.2s;" onclick="document.getElementById('modalInputSquare').click()" onmouseover="this.style.borderColor='#6366f1';" onmouseout="this.style.borderColor='#cbd5e1';">
                                    ${d.site_icon ? `
                                        <img src="${siteAssetUrl}static/mod_settings/${d.site_icon}?t=${timestamp}" style="max-width: 88%; max-height: 88%; object-fit: contain; padding: 2px;" onerror="this.onerror=null; this.src='${siteAssetUrl}static/mod_settings/${d.site_icon}';">
                                    ` : `
                                        <div style="text-align: center; color: #6366f1;">
                                            <i class="bi bi-cloud-arrow-up" style="font-size: 20px; display: block;"></i>
                                            <span style="display: block; font-size: 9px; font-weight: 600;">${logoSquareTitle}</span>
                                        </div>
                                    `}
                                </div>
                            </div>
                            <input type="file" id="modalInputSquare" style="display:none;" accept="image/*" onchange="processSelectedLogoModalImage(this, 1)">
                        </div>

                        <!-- Favicon (1:1) -->
                        <div style="flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                                    <div style="display: flex; align-items: center; gap: 4px;">
                                        <span style="font-size: 11.5px; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${logoSymbolTitle.replace(/\s*\(\d+:\d+\)/, '')}</span>
                                        <span style="font-size: 9.5px; font-weight: 700; padding: 1px 5px; border-radius: 5px; background: #e0e7ff; color: #3730a3;">1:1</span>
                                    </div>
                                    ${d.logo_symbol ? `
                                        <button type="button" onclick="event.stopPropagation(); removeLogoAssetModalAjax('logo_symbol')" style="width: 24px; height: 24px; border-radius: 6px; background: #fee2e2; color: #dc2626; border: none; display: inline-flex; align-items: center; justify-content: center; cursor: pointer;" title="${deleteText}">
                                            <i class="bi bi-trash" style="font-size: 11px;"></i>
                                        </button>
                                    ` : ''}
                                </div>

                                <div style="position: relative; width: 100%; height: 75px; border: 2px dashed #cbd5e1; border-radius: 10px; display: flex; align-items: center; justify-content: center; overflow: hidden; background-color: #ffffff; background-image: linear-gradient(45deg, #f1f5f9 25%, transparent 25%), linear-gradient(-45deg, #f1f5f9 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #f1f5f9 75%), linear-gradient(-45deg, transparent 75%, #f1f5f9 75%); background-size: 16px 16px; background-position: 0 0, 0 8px, 8px -8px, -8px 0px; cursor: pointer; transition: border-color 0.2s;" onclick="document.getElementById('modalInputSymbol').click()" onmouseover="this.style.borderColor='#6366f1';" onmouseout="this.style.borderColor='#cbd5e1';">
                                    ${d.logo_symbol ? `
                                        <img src="${siteAssetUrl}static/mod_settings/${d.logo_symbol}?t=${timestamp}" style="max-width: 88%; max-height: 88%; object-fit: contain; padding: 2px;" onerror="this.onerror=null; this.src='${siteAssetUrl}static/mod_settings/${d.logo_symbol}';">
                                    ` : `
                                        <div style="text-align: center; color: #6366f1;">
                                            <i class="bi bi-circle-square" style="font-size: 20px; display: block;"></i>
                                            <span style="display: block; font-size: 9px; font-weight: 600;">${logoSymbolTitle}</span>
                                        </div>
                                    `}
                                </div>
                            </div>
                            <input type="file" id="modalInputSymbol" style="display:none;" accept="image/*" onchange="processSelectedLogoModalImage(this, 3)">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Animated Logo System (Google Doodle Media Card) -->
            <div style="background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%); padding: 16px; border-radius: 16px; border: 1px solid rgba(226, 232, 240, 0.9); box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.03);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 32px; height: 32px; border-radius: 10px; background: rgba(99, 102, 241, 0.1); color: #6366f1; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                            <i class="bi bi-play-btn-fill"></i>
                        </div>
                        <h4 style="margin: 0; font-size: 13.5px; font-weight: 700; color: #0f172a; letter-spacing: -0.2px;">${animSectionTitle}</h4>
                    </div>
                    <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; padding: 4px 10px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; font-size: 11px; font-weight: 600; color: #334155; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                        <input type="checkbox" id="animatedLogoUseToggle" ${d.use_animated_logo === 'y' ? 'checked' : ''} onchange="saveAnimatedLogoSettingsAjax()" style="accent-color: #6366f1; width: 15px; height: 15px;">
                        <span>${enableAnimLogo}</span>
                    </label>
                </div>
                <p style="margin: 0 0 14px 0; font-size: 11px; line-height: 1.5; color: #64748b;">${animSectionDesc}</p>

                <svg width="0" height="0" style="position: absolute; width: 0; height: 0; overflow: hidden; pointer-events: none;" aria-hidden="true">
                    <defs>
                        <filter id="af-admin-remove-white-bg" color-interpolation-filters="sRGB">
                            <feColorMatrix type="matrix" values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  -1.1 -1.1 -1.1 3.3 0" />
                        </filter>
                    </defs>
                </svg>
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <div style="position: relative; width: 100%; min-height: 96px; border: 2px dashed #cbd5e1; border-radius: 14px; display: flex; align-items: center; justify-content: center; overflow: hidden; background: #ffffff; cursor: pointer; padding: 12px; transition: all 0.2s ease;" onclick="document.getElementById('modalInputAnimatedLogo').click()" onmouseover="this.style.borderColor='#6366f1'; this.style.background='rgba(99,102,241,0.02)';" onmouseout="this.style.borderColor='#cbd5e1'; this.style.background='#ffffff';">
                        ${d.animated_logo_path ? `
                            <div style="width: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative;">
                                ${ (d.animated_logo_type === 'webm' || d.animated_logo_type === 'mp4') ? `
                                    <video src="${siteAssetUrl}static/mod_settings/${d.animated_logo_path}?t=${timestamp}" autoplay loop muted playsinline style="max-height: 64px; max-width: 100%; object-fit: contain; filter: url(#af-admin-remove-white-bg); mix-blend-mode: multiply;"></video>
                                ` : (d.animated_logo_type === 'json') ? `
                                    <span style="font-size: 12px; font-weight: 600; color: #1e293b;"><i class="bi bi-file-code-fill" style="margin-right:4px; color: #6366f1;"></i>${d.animated_logo_path}</span>
                                ` : `
                                    <img src="${siteAssetUrl}static/mod_settings/${d.animated_logo_path}?t=${timestamp}" style="max-height: 64px; max-width: 100%; object-fit: contain; filter: url(#af-admin-remove-white-bg); mix-blend-mode: multiply;">
                                ` }
                                <button type="button" onclick="event.stopPropagation(); removeAnimatedLogoModalAjax()" style="position:absolute; top:2px; right:2px; width:28px; height:28px; border-radius:50%; background:rgba(239,68,68,0.95); color:#fff; border:none; display:flex; align-items:center; justify-content:center; cursor:pointer; box-shadow: 0 2px 8px rgba(239,68,68,0.3);" title="${deleteText}">
                                    <i class="bi bi-trash" style="font-size:13px;"></i>
                                </button>
                            </div>
                        ` : `
                            <div style="text-align:center; color: #6366f1;">
                                <div style="width: 38px; height: 38px; border-radius: 50%; background: rgba(99, 102, 241, 0.08); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 6px;">
                                    <i class="bi bi-cloud-arrow-up-fill" style="font-size: 20px;"></i>
                                </div>
                                <div style="display: flex; gap: 4px; justify-content: center; align-items: center; flex-wrap: wrap;">
                                    <span style="font-size: 10px; font-weight: 700; background: #e0e7ff; color: #3730a3; padding: 2px 6px; border-radius: 6px;">WebM</span>
                                    <span style="font-size: 10px; font-weight: 700; background: #e0e7ff; color: #3730a3; padding: 2px 6px; border-radius: 6px;">MP4</span>
                                    <span style="font-size: 10px; font-weight: 700; background: #e0e7ff; color: #3730a3; padding: 2px 6px; border-radius: 6px;">Lottie JSON</span>
                                    <span style="font-size: 10px; font-weight: 700; background: #e0e7ff; color: #3730a3; padding: 2px 6px; border-radius: 6px;">WebP</span>
                                </div>
                            </div>
                        `}
                    </div>
                    <input type="file" id="modalInputAnimatedLogo" style="display:none;" accept=".webm,.mp4,.json,.gif,.apng,.svg,.webp" onchange="uploadAnimatedLogoFileModalAjax(this)">

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px;">
                        <div style="display: flex; flex-direction: column;">
                            <label style="display:block; margin-bottom:5px; font-size:10px; font-weight:700; color:#475569; text-transform: uppercase; letter-spacing: 0.3px;">${animHeightLabel}</label>
                            <select id="animatedLogoHeightSelect" onchange="saveAnimatedLogoSettingsAjax()" style="width:100%; height: 40px; padding: 0 24px 0 10px; border-radius: 10px; border: 1px solid #cbd5e1; font-size: 11.5px; background: #ffffff; color: #0f172a; font-weight: 500; outline: none; text-overflow: ellipsis; white-space: nowrap; overflow: hidden;">
                                <option value="32" ${d.animated_logo_height == 32 ? 'selected' : ''}>32 px</option>
                                <option value="40" ${d.animated_logo_height == 40 ? 'selected' : ''}>40 px</option>
                                <option value="48" ${d.animated_logo_height == 48 ? 'selected' : ''}>${animHeight48Std}</option>
                                <option value="56" ${d.animated_logo_height == 56 ? 'selected' : ''}>56 px</option>
                                <option value="64" ${d.animated_logo_height == 64 ? 'selected' : ''}>64 px</option>
                                <option value="80" ${d.animated_logo_height == 80 ? 'selected' : ''}>80 px</option>
                            </select>
                        </div>
                        <div style="display: flex; flex-direction: column;">
                            <label style="display:block; margin-bottom:5px; font-size:10px; font-weight:700; color:#475569; text-transform: uppercase; letter-spacing: 0.3px;">${animAspectLabel}</label>
                            <select id="animatedLogoAspectSelect" onchange="saveAnimatedLogoSettingsAjax()" style="width:100%; height: 40px; padding: 0 24px 0 10px; border-radius: 10px; border: 1px solid #cbd5e1; font-size: 11.5px; background: #ffffff; color: #0f172a; font-weight: 500; outline: none; text-overflow: ellipsis; white-space: nowrap; overflow: hidden;">
                                <option value="16/9" ${d.animated_logo_aspect === '16/9' ? 'selected' : ''}>${animAspectVideo}</option>
                                <option value="3/1" ${d.animated_logo_aspect === '3/1' ? 'selected' : ''}>${animAspectHorizontal}</option>
                                <option value="1/1" ${d.animated_logo_aspect === '1/1' ? 'selected' : ''}>${animAspectSquare}</option>
                                <option value="auto" ${d.animated_logo_aspect === 'auto' ? 'selected' : ''}>${animAspectAuto}</option>
                            </select>
                        </div>
                        <div style="display: flex; flex-direction: column;">
                            <label style="display:block; margin-bottom:5px; font-size:10px; font-weight:700; color:#475569; text-transform: uppercase; letter-spacing: 0.3px;">${animModeLabel}</label>
                            <select id="animatedLogoModeSelect" onchange="saveAnimatedLogoSettingsAjax()" style="width:100%; height: 40px; padding: 0 24px 0 10px; border-radius: 10px; border: 1px solid #cbd5e1; font-size: 11.5px; background: #ffffff; color: #0f172a; font-weight: 500; outline: none; text-overflow: ellipsis; white-space: nowrap; overflow: hidden;">
                                <option value="loop" ${d.animated_logo_mode === 'loop' ? 'selected' : ''}>${animModeLoop}</option>
                                <option value="once" ${d.animated_logo_mode === 'once' ? 'selected' : ''}>${animModeOnce}</option>
                                <option value="hover" ${d.animated_logo_mode === 'hover' ? 'selected' : ''}>${animModeHover}</option>
                            </select>
                        </div>
                        <div style="display: flex; flex-direction: column;">
                            <label style="display:block; margin-bottom:5px; font-size:10px; font-weight:700; color:#475569; text-transform: uppercase; letter-spacing: 0.3px;">${animBlendLabel}</label>
                            <select id="animatedLogoBlendSelect" onchange="saveAnimatedLogoSettingsAjax()" style="width:100%; height: 40px; padding: 0 24px 0 10px; border-radius: 10px; border: 1px solid #cbd5e1; font-size: 11.5px; background: #ffffff; color: #0f172a; font-weight: 500; outline: none; text-overflow: ellipsis; white-space: nowrap; overflow: hidden;">
                                <option value="multiply" ${(!d.animated_logo_blend || d.animated_logo_blend === 'multiply') ? 'selected' : ''}>${animBlendMultiply}</option>
                                <option value="screen" ${d.animated_logo_blend === 'screen' ? 'selected' : ''}>${animBlendScreen}</option>
                                <option value="none" ${d.animated_logo_blend === 'none' ? 'selected' : ''}>${animBlendNone}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Assets Status Table -->
            <div style="background: #ffffff; border-radius: 14px; border: 1px solid var(--md-sys-color-outline-variant, #E7E0EC); overflow: hidden;">
                <div style="padding: 10px 14px; background: var(--md-sys-color-surface-variant, #F4EFF4); border-bottom: 1px solid #E7E0EC;">
                    <span style="font-size: 11px; font-weight: 700; color: var(--md-sys-color-on-surface-variant, #49454F); text-transform: uppercase;">${assetsTitle}</span>
                </div>

                <div style="display: flex; flex-direction: column;">
                    ${renderAssetStatusRow('Favicon (32x32)', d.favicon_path, siteAssetUrl, timestamp)}
                    ${renderAssetStatusRow('Favicon (96x96)', d.favicon_96, siteAssetUrl, timestamp)}
                    ${renderAssetStatusRow('Apple Touch Icon', d.apple_touch_icon, siteAssetUrl, timestamp)}
                    ${renderAssetStatusRow('Manifest 192x192', d.manifest_192, siteAssetUrl, timestamp)}
                    ${renderAssetStatusRow('Manifest 512x512', d.manifest_512, siteAssetUrl, timestamp)}
                    ${renderAssetStatusRow('Web Manifest', d.site_webmanifest, siteAssetUrl, timestamp, true)}
                </div>
            </div>
        </div>
    `;

    container.innerHTML = html;
}

function renderAssetStatusRow(label, fileName, siteAssetUrl, ts, isManifest = false) {
    if (!fileName) {
        return `
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-bottom: 1px solid #f1f5f9;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="width: 24px; height: 24px; border: 1px dashed #cbd5e1; border-radius: 4px; display: flex; align-items: center; justify-content: center; background:#f1f5f9; overflow:hidden;">
                        <i class="bi bi-image" style="font-size:10px; color:#94a3b8;"></i>
                    </span>
                    <span style="font-size: 12px; color: var(--md-sys-color-on-surface-variant, #49454F);">${label}</span>
                </div>
                <span class="status-badge" style="width: 20px; height: 20px; border-radius: 50%; background: #f1f5f9; color: #94a3b8; display: inline-flex; align-items: center; justify-content: center;"><i class="bi bi-x-lg" style="font-size: 10px;"></i></span>
            </div>
        `;
    }
    const fullUrl = siteAssetUrl + 'static/mod_settings/' + fileName + (isManifest ? '' : '?t=' + ts);
    return `
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-bottom: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="width: 24px; height: 24px; border: 1px dashed #cbd5e1; border-radius: 4px; display: flex; align-items: center; justify-content: center; background:#f1f5f9; overflow:hidden;">
                    ${isManifest ? '<i class="bi bi-filetype-json" style="font-size:12px; color:#6366f1;"></i>' : `<img src="${fullUrl}" style="width:100%; height:100%; object-fit:contain;">`}
                </span>
                <span style="font-size: 12px; color: var(--md-sys-color-on-surface-variant, #49454F);">${label}</span>
            </div>
            <a href="${fullUrl}" target="_blank" class="status-badge ready" style="width: 20px; height: 20px; border-radius: 50%; background: #e6f4ea; color: #1e8e3e; display: inline-flex; align-items: center; justify-content: center; text-decoration: none;" title="Active">
                <i class="bi bi-check-lg" style="font-size: 12px; font-weight: 800;"></i>
            </a>
        </div>
    `;
}

function setHeaderStyleAjax(logoType) {
    if (!logoType) return;
    const siteUrl = typeof SITE_URL !== 'undefined' ? SITE_URL : '';

    const formData = new FormData();
    formData.append('logo_type', logoType);

    fetch(siteUrl + 'mod_settings/ajax_save_header_style', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            if (navigator.vibrate) navigator.vibrate(10);
            loadLogoIconAjax();
        } else {
            const lang = ((typeof adminLang !== "undefined" ? adminLang : {}) && (typeof adminLang !== "undefined" ? adminLang : {}).mod_settings) || {};
            alert(data.message || lang.generic_error || 'Error updating header style');
        }
    })
    .catch(err => {
        console.error(err);
        const lang = ((typeof adminLang !== "undefined" ? adminLang : {}) && (typeof adminLang !== "undefined" ? adminLang : {}).mod_settings) || {};
        alert(lang.generic_error || 'Error saving settings');
    });
}

function cropperAction(action, param) {
    if (!window.swalCropperInstance) return;
    if (action === 'zoom') window.swalCropperInstance.zoom(param);
    else if (action === 'rotate') window.swalCropperInstance.rotate(param);
    else if (action === 'reset') window.swalCropperInstance.reset();
}

function processSelectedLogoModalImage(input, step) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    if (!file.type || !file.type.startsWith('image/')) {
        const lang = ((typeof adminLang !== "undefined" ? adminLang : {}) && (typeof adminLang !== "undefined" ? adminLang : {}).mod_settings) || {};
        const invalidImg = lang.invalid_image || 'Please select a valid image file (PNG, JPG, WEBP).';
        alert(invalidImg);
        input.value = '';
        return;
    }

    const container = document.getElementById('logoIconModalBody');
    if (!container) return;

    const lang = ((typeof adminLang !== "undefined" ? adminLang : {}) && (typeof adminLang !== "undefined" ? adminLang : {}).mod_settings) || {};
    const cropRect = lang.crop_rect || 'Crop Rectangle Logo (3:1)';
    const cropSquare = lang.crop_square || 'Crop Square Logo (1:1)';
    const cropSymbol = lang.crop_symbol || 'Crop Symbol/Favicon (1:1)';
    const saveBtnText = lang.save || 'Save & Apply Logo';
    const cancelBtnText = (lang.button && lang.button.cancel) || 'Cancel';
    const cropInstruction = lang.crop_instruction || 'Resize or move the logo inside the frame';
    const zoomOutText = lang.zoom_out || 'Zoom Out';
    const zoomInText = lang.zoom_in || 'Zoom In';
    const rotateLeftText = lang.rotate_left || 'Rotate Left';
    const rotateRightText = lang.rotate_right || 'Rotate Right';
    const resetStandardText = lang.reset_standard || 'Standard';

    let titleText = cropRect;
    let ratio = 3 / 1;
    let targetW = 900;
    let targetH = 300;

    if (step === 1) {
        titleText = cropSquare;
        ratio = 1 / 1;
        targetW = 512;
        targetH = 512;
    } else if (step === 3) {
        titleText = cropSymbol;
        ratio = 1 / 1;
        targetW = 512;
        targetH = 512;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        const imageSrc = e.target.result;

        container.innerHTML = `
            <div style="display: flex; flex-direction: column; gap: 12px; padding: 2px;">
                <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 8px; border-bottom: 1px solid var(--md-sys-color-outline-variant, #E7E0EC);">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <button type="button" onclick="loadLogoIconAjax()" style="background: none; border: none; font-size: 18px; cursor: pointer; color: var(--md-sys-color-primary, #6750A4); padding: 0; display: flex; align-items: center;">
                            <i class="bi bi-arrow-left"></i>
                        </button>
                        <div>
                            <h4 style="margin: 0; font-size: 13.5px; font-weight: 700; color: var(--md-sys-color-on-surface, #1C1B1F);">${titleText}</h4>
                            <p style="margin: 2px 0 0; font-size: 10.5px; color: var(--md-sys-color-on-surface-variant, #49454F);">${cropInstruction}</p>
                        </div>
                    </div>
                </div>

                <div class="cropper-checkerboard-wrapper" style="width: 100%; height: 310px; border-radius: 14px; overflow: hidden; position: relative; background-color: #f8fafc; background-image: repeating-linear-gradient(45deg, #e2e8f0 25%, transparent 25%, transparent 75%, #e2e8f0 75%, #e2e8f0), repeating-linear-gradient(45deg, #e2e8f0 25%, #f8fafc 25%, #f8fafc 75%, #e2e8f0 75%, #e2e8f0); background-position: 0 0, 10px 10px; background-size: 20px 20px; border: 1px solid var(--md-sys-color-outline-variant, #cbd5e1); display: flex; align-items: center; justify-content: center;">
                    <img id="inlineCropperImage" src="${imageSrc}" style="max-width: 100%; max-height: 100%; display: block; margin: 0 auto;">
                </div>

                <!-- Cropper Toolbar Controls -->
                <div style="display: flex; align-items: center; justify-content: center; gap: 8px; background: var(--md-sys-color-surface-variant, #F4EFF4); padding: 6px 12px; border-radius: 12px;">
                    <button type="button" onclick="cropperAction('zoom', -0.1)" title="${zoomOutText}" style="width: 34px; height: 34px; border-radius: 8px; border: 1px solid var(--md-sys-color-outline-variant, #cbd5e1); background: #ffffff; color: var(--md-sys-color-on-surface, #1C1B1F); cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="bi bi-zoom-out" style="font-size: 15px;"></i>
                    </button>
                    <button type="button" onclick="cropperAction('zoom', 0.1)" title="${zoomInText}" style="width: 34px; height: 34px; border-radius: 8px; border: 1px solid var(--md-sys-color-outline-variant, #cbd5e1); background: #ffffff; color: var(--md-sys-color-on-surface, #1C1B1F); cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="bi bi-zoom-in" style="font-size: 15px;"></i>
                    </button>
                    <button type="button" onclick="cropperAction('rotate', -90)" title="${rotateLeftText}" style="width: 34px; height: 34px; border-radius: 8px; border: 1px solid var(--md-sys-color-outline-variant, #cbd5e1); background: #ffffff; color: var(--md-sys-color-on-surface, #1C1B1F); cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="bi bi-arrow-counterclockwise" style="font-size: 15px;"></i>
                    </button>
                    <button type="button" onclick="cropperAction('rotate', 90)" title="${rotateRightText}" style="width: 34px; height: 34px; border-radius: 8px; border: 1px solid var(--md-sys-color-outline-variant, #cbd5e1); background: #ffffff; color: var(--md-sys-color-on-surface, #1C1B1F); cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="bi bi-arrow-clockwise" style="font-size: 15px;"></i>
                    </button>
                    <button type="button" onclick="cropperAction('reset')" title="${resetStandardText}" style="padding: 0 10px; height: 34px; border-radius: 8px; border: 1px solid var(--md-sys-color-outline-variant, #cbd5e1); background: #ffffff; color: var(--md-sys-color-on-surface, #1C1B1F); font-size: 11.5px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                        <i class="bi bi-aspect-ratio" style="font-size: 14px;"></i>
                        <span>${resetStandardText}</span>
                    </button>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 2px;">
                    <button type="button" onclick="loadLogoIconAjax()" style="flex: 1; padding: 10px 16px; border-radius: 10px; border: 1px solid var(--md-sys-color-outline, #79747E); background: transparent; color: var(--md-sys-color-on-surface, #1C1B1F); font-size: 13px; font-weight: 600; cursor: pointer;">
                        ${cancelBtnText}
                    </button>
                    <button type="button" id="confirmInlineCropBtn" style="flex: 2; padding: 10px 16px; border-radius: 10px; border: none; background: var(--md-sys-color-primary, #6750A4); color: #ffffff; font-size: 13px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 2px 6px rgba(103, 80, 164, 0.3);">
                        <i class="bi bi-check-lg" style="font-size: 16px;"></i>
                        <span>${saveBtnText}</span>
                    </button>
                </div>
            </div>
        `;

        const img = document.getElementById('inlineCropperImage');
        const startInlineCropper = () => {
            if (typeof Cropper !== 'undefined') {
                if (window.swalCropperInstance) {
                    window.swalCropperInstance.destroy();
                    window.swalCropperInstance = null;
                }
                window.swalCropperInstance = new Cropper(img, {
                    aspectRatio: ratio,
                    viewMode: 0,
                    autoCropArea: 0.85,
                    dragMode: 'move',
                    center: true,
                    guides: true,
                    background: false,
                    cropBoxResizable: true,
                    toggleDragModeOnDblclick: false
                });
            }
        };

        if (img.complete) { startInlineCropper(); } else { img.onload = startInlineCropper; }

        document.getElementById('confirmInlineCropBtn').onclick = function() {
            let base64Data = null;
            if (window.swalCropperInstance) {
                const canvas = window.swalCropperInstance.getCroppedCanvas({
                    width: targetW,
                    height: targetH,
                    fillColor: 'transparent'
                });
                if (canvas) {
                    base64Data = canvas.toDataURL('image/png');
                }
            } else {
                const canvas = document.createElement('canvas');
                canvas.width = targetW;
                canvas.height = targetH;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, targetW, targetH);
                base64Data = canvas.toDataURL('image/png');
            }

            if (window.swalCropperInstance) {
                window.swalCropperInstance.destroy();
                window.swalCropperInstance = null;
            }

            if (base64Data) {
                uploadLogoImageModalAjax(step, base64Data);
            } else {
                loadLogoIconAjax();
            }
        };

        input.value = '';
    };
    reader.readAsDataURL(file);
}

function uploadLogoImageModalAjax(step, base64Data) {
    const siteUrl = typeof SITE_URL !== 'undefined' ? SITE_URL : '';
    const currentLang = (typeof SITE_LANG !== 'undefined' && SITE_LANG) ? SITE_LANG : 'en';

    const formData = new FormData();
    formData.append('step', step);
    formData.append('lang', currentLang);
    formData.append('image', base64Data);

    fetch(siteUrl + 'mod_settings/upload_cropped_logo_ajax', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            if (navigator.vibrate) navigator.vibrate(10);
            setTimeout(() => {
                loadLogoIconAjax();
            }, 100);
        } else {
            const lang = ((typeof adminLang !== "undefined" ? adminLang : {}) && (typeof adminLang !== "undefined" ? adminLang : {}).mod_settings) || {};
            alert(data.message || lang.generic_error || 'Error uploading image');
        }
    })
    .catch(err => {
        console.error(err);
        const lang = ((typeof adminLang !== "undefined" ? adminLang : {}) && (typeof adminLang !== "undefined" ? adminLang : {}).mod_settings) || {};
        alert(lang.generic_error || 'An error occurred while uploading');
    });
}

function removeLogoAssetModalAjax(field) {
    const lang = ((typeof adminLang !== "undefined" ? adminLang : {}) && (typeof adminLang !== "undefined" ? adminLang : {}).mod_settings) || {};
    const confirmText = lang.confirm_remove_asset || 'Are you sure you want to remove this asset?';
    if (!confirm(confirmText)) return;
    const siteUrl = typeof SITE_URL !== 'undefined' ? SITE_URL : '';
    const currentLang = (typeof SITE_LANG !== 'undefined' && SITE_LANG) ? SITE_LANG : 'en';

    const formData = new FormData();
    formData.append('field', field);
    formData.append('lang', currentLang);

    fetch(siteUrl + 'mod_settings/image_remove_image', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
        body: formData
    })
    .then(res => res.text())
    .then(text => {
        if (text.trim() === 'Success') {
            if (navigator.vibrate) navigator.vibrate(10);
            loadLogoIconAjax();
        } else {
            const lang = ((typeof adminLang !== "undefined" ? adminLang : {}) && (typeof adminLang !== "undefined" ? adminLang : {}).mod_settings) || {};
            alert(text || lang.generic_error || 'Error deleting asset');
        }
    })
    .catch(err => {
        console.error(err);
        const lang = ((typeof adminLang !== "undefined" ? adminLang : {}) && (typeof adminLang !== "undefined" ? adminLang : {}).mod_settings) || {};
        alert(lang.generic_error || 'An error occurred while removing asset');
    });
}

function uploadAnimatedLogoFileModalAjax(input) {
    if (!input.files || !input.files[0]) return;
    const siteUrl = typeof SITE_URL !== 'undefined' ? SITE_URL : '';
    const file = input.files[0];
    const formData = new FormData();
    formData.append('animated_logo_file', file);

    fetch(siteUrl + 'mod_settings/upload_animated_logo_ajax', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
        body: formData
    })
    .then(res => res.text().then(text => {
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Server response:', text);
            throw new Error('Server returned invalid response');
        }
    }))
    .then(data => {
        if (data.status === 'success') {
            loadLogoIconAjax();
        } else {
            const lang = ((typeof adminLang !== "undefined" ? adminLang : {}) && (typeof adminLang !== "undefined" ? adminLang : {}).mod_settings) || {};
            const errMsg = data.message || lang.generic_error || 'Error uploading file';
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: errMsg });
            } else {
                alert(errMsg);
            }
        }
    })
    .catch(err => {
        console.error(err);
        const lang = ((typeof adminLang !== "undefined" ? adminLang : {}) && (typeof adminLang !== "undefined" ? adminLang : {}).mod_settings) || {};
        const errMsg = err.message || lang.generic_error || 'An error occurred';
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: errMsg });
        } else {
            alert(errMsg);
        }
    });
}

function saveAnimatedLogoSettingsAjax() {
    const siteUrl = typeof SITE_URL !== 'undefined' ? SITE_URL : '';
    const useToggle = document.getElementById('animatedLogoUseToggle');
    const heightSelect = document.getElementById('animatedLogoHeightSelect');
    const aspectSelect = document.getElementById('animatedLogoAspectSelect');
    const modeSelect = document.getElementById('animatedLogoModeSelect');
    const blendSelect = document.getElementById('animatedLogoBlendSelect');

    const formData = new FormData();
    formData.append('use_animated_logo', useToggle && useToggle.checked ? 'y' : 'n');
    if (heightSelect) formData.append('animated_logo_height', heightSelect.value);
    if (aspectSelect) formData.append('animated_logo_aspect', aspectSelect.value);
    if (modeSelect) formData.append('animated_logo_mode', modeSelect.value);
    if (blendSelect) formData.append('animated_logo_blend', blendSelect.value);

    fetch(siteUrl + 'mod_settings/ajax_save_animated_logo_settings', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            if (navigator.vibrate) navigator.vibrate(10);
            loadLogoIconAjax();
        } else {
            console.error(data.message || 'Error saving settings');
        }
    })
    .catch(err => {
        console.error(err);
    });
}

function removeAnimatedLogoModalAjax() {
    const lang = ((typeof adminLang !== "undefined" ? adminLang : {}) && (typeof adminLang !== "undefined" ? adminLang : {}).mod_settings) || {};
    const confirmText = lang.confirm_remove_anim_logo || 'Are you sure you want to remove the animated logo?';
    if (!confirm(confirmText)) return;
    const siteUrl = typeof SITE_URL !== 'undefined' ? SITE_URL : '';

    fetch(siteUrl + 'mod_settings/remove_animated_logo_ajax', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            if (navigator.vibrate) navigator.vibrate(10);
            loadLogoIconAjax();
        } else {
            const lang = ((typeof adminLang !== "undefined" ? adminLang : {}) && (typeof adminLang !== "undefined" ? adminLang : {}).mod_settings) || {};
            alert(data.message || lang.generic_error || 'Error deleting animated logo');
        }
    })
    .catch(err => {
        console.error(err);
        const lang = ((typeof adminLang !== "undefined" ? adminLang : {}) && (typeof adminLang !== "undefined" ? adminLang : {}).mod_settings) || {};
        alert(lang.generic_error || 'An error occurred while deleting animated logo');
    });
}

