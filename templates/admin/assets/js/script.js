// Mobile Interaction Scripts for M3 Prototype

function sanitizeText(text) {
    if (!text) return "";
    // Allow any legal Unicode Letter (\p{L}), any Numeric digit (\p{N}), and Whitespace (\s)
    let clean = text.replace(/[^\p{L}\p{N}\s]+/gu, " ");
    clean = clean.trim();
    // Replace multiple spaces with a single space
    return clean.replace(/\s+/g, " ");
}

function isKurdishLocale(locale) {
    if (!locale) return false;
    const l = String(locale).toLowerCase().trim();
    return ['ku', 'ckb', 'zza'].includes(l);
}

function getCurrentLocale(context) {
    if (context && typeof context.querySelector === 'function') {
        const localLangEl = context.querySelector('select[name="lang"], input[name="lang"], #lang, #editLang, #wizardHiddenLang, #langSelect, #editLangSelect');
        if (localLangEl && localLangEl.value) {
            return String(localLangEl.value).toLowerCase().trim();
        }
    }

    const editLang = document.getElementById('editLang');
    if (editLang && editLang.value) return String(editLang.value).toLowerCase().trim();

    const wizardLang = document.getElementById('wizardHiddenLang');
    if (wizardLang && wizardLang.value) return String(wizardLang.value).toLowerCase().trim();

    if (typeof wizardData !== 'undefined' && wizardData.lang) return String(wizardData.lang).toLowerCase().trim();

    const langSelects = document.querySelectorAll('select[name="lang"], input[name="lang"], #lang, #langSelect, #editLangSelect, #image-category-lang, #video-category-lang');
    for (let i = 0; i < langSelects.length; i++) {
        if (langSelects[i] && langSelects[i].value) {
            return String(langSelects[i].value).toLowerCase().trim();
        }
    }

    if (document.documentElement && document.documentElement.lang) {
        return String(document.documentElement.lang).toLowerCase().trim();
    }

    return 'en';
}

function normalizeKurdishTypos(text, locale) {
    if (!text) return "";
    const loc = locale || getCurrentLocale();
    if (isKurdishLocale(loc)) {
        text = text.replace(/İ/g, 'Î');
        text = text.replace(/ı/g, 'i');
        text = text.replace(/Ü/g, 'U');
        text = text.replace(/ü/g, 'u');
        text = text.replace(/Ö/g, 'O');
        text = text.replace(/ö/g, 'o');
        text = text.replace(/Ğ/g, 'X');
        text = text.replace(/ğ/g, 'x');
    }
    return text;
}

function makeLegacySeoValue(text) {
    if (!text) return "";
    let clean = String(text).replace(/['’"”„“]/g, "");
    clean = clean.replace(/[^\p{L}\p{N}\s_-]/gu, " ");
    clean = clean.trim();
    clean = clean.replace(/[\s-]+/g, "_");
    clean = clean.replace(/_+/g, "_");
    return clean;
}

function makeSeoValue(text, explicitLocale) {
    if (!text) return "";
    let locale = (explicitLocale || getCurrentLocale() || 'en').toLowerCase().trim();
    if (isKurdishLocale(locale)) {
        text = normalizeKurdishTypos(text, locale);
    }
    
    // Remove apostrophes and quotation marks
    let clean = String(text).replace(/['’"”„“]/g, "");
    let value = sanitizeText(clean);
    value = value.replace(/[\s-]+/g, "_");
    
    // Locale-aware lowercasing
    try {
        value = locale ? value.toLocaleLowerCase(locale) : value.toLowerCase();
    } catch (e) {
        value = value.toLowerCase();
    }
    
    // Normalize Unicode to NFC and strip stray combining dot above (U+0307)
    if (value.normalize) {
        value = value.normalize('NFC');
    }
    value = value.replace(/\u0307/g, "");
    
    // Kurdish keyboard typo normalizations apply exclusively to Kurdish
    if (isKurdishLocale(locale)) {
        value = value.replace(/ı/g, 'i');
        value = value.replace(/İ/g, 'i');
    }
    
    value = value.replace(/_+/g, "_").replace(/^_+|_+$/g, "");
    return value;
}

function makeKeywordsValue(text, explicitLocale) {
    if (!text) return "";
    let locale = (explicitLocale || getCurrentLocale() || 'en').toLowerCase().trim();
    if (isKurdishLocale(locale)) {
        text = normalizeKurdishTypos(text, locale);
    }
    
    let clean = String(text).replace(/['’"”„“]/g, "");
    let value = sanitizeText(clean);
    value = value.replace(/[\s-]+/g, ",");
    
    try {
        value = locale ? value.toLocaleLowerCase(locale) : value.toLowerCase();
    } catch (e) {
        value = value.toLowerCase();
    }
    
    if (value.normalize) {
        value = value.normalize('NFC');
    }
    value = value.replace(/\u0307/g, "");
    
    if (isKurdishLocale(locale)) {
        value = value.replace(/ı/g, 'i');
        value = value.replace(/İ/g, 'i');
    }
    
    value = value.replace(/,+/g, ",").replace(/^,+|,+$/g, "");
    return value;
}

window.Amed = window.Amed || {
    notify: function(message, type = 'info') {
        if (typeof showSnackbar === 'function') {
            showSnackbar(message, type);
        } else {
            console.log(`[${type}] ${message}`);
        }
    }
};

// Global SweetAlert2 Target Resolver for Top-Layer Modal & Gallery Sheet Parity
if (typeof Swal !== 'undefined' && Swal.fire && !Swal.__targetIntercepted) {
    const originalSwalFire = Swal.fire.bind(Swal);
    Swal.fire = function(...args) {
        const detailSheet = document.getElementById('galleryDetailSheet');
        const activeDialog = document.querySelector('dialog[open]');
        const activeTarget = (detailSheet && detailSheet.classList.contains('active'))
            ? detailSheet
            : (activeDialog || document.body);

        if (args.length === 1 && typeof args[0] === 'object' && args[0] !== null) {
            if (!args[0].target || args[0].target === document.body) {
                args[0].target = activeTarget;
            }
        }
        return originalSwalFire(...args);
    };
    Swal.__targetIntercepted = true;
}

/**
 * Snackbar / Toast Notification
 * Shows non-blocking notification - M3 Style
 * @param {string} message - The message to display
 * @param {string} type - 'success', 'error', 'info', 'warning'
 * @param {number} duration - How long to show the message in ms (default: 4000)
 */
function showSnackbar(message, type = 'info', duration = 4000) {
    // Check or create snackbar container
    let container = document.getElementById('snackbar-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'snackbar-container';
        container.style.cssText = `
            position: fixed;
            bottom: 80px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10000;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            pointer-events: none;
        `;
        document.body.appendChild(container);
    }

    // Create snackbar element
    const snackbar = document.createElement('div');
    snackbar.className = 'snackbar snackbar-' + type;

    // Colors based on type
    const colors = {
        success: { bg: '#1B5E20', color: '#fff', icon: 'bi-check-circle-fill' },
        error: { bg: '#B71C1C', color: '#fff', icon: 'bi-x-circle-fill' },
        warning: { bg: '#E65100', color: '#fff', icon: 'bi-exclamation-triangle-fill' },
        info: { bg: '#0D47A1', color: '#fff', icon: 'bi-info-circle-fill' }
    };
    const style = colors[type] || colors.info;

    snackbar.style.cssText = `
        background: ${style.bg};
        color: ${style.color};
        padding: 12px 24px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.3s ease;
        pointer-events: auto;
        max-width: 90vw;
    `;

    // Extract clean plain text if message contains HTML (e.g. server error pages)
    let cleanMessage = message;
    if (typeof cleanMessage === 'string' && cleanMessage.includes('<')) {
        const tmp = document.createElement('div');
        tmp.innerHTML = cleanMessage;
        cleanMessage = tmp.textContent || tmp.innerText || 'An error occurred';
        cleanMessage = cleanMessage.trim().replace(/\s+/g, ' ');
        if (cleanMessage.length > 150) {
            cleanMessage = cleanMessage.substring(0, 150) + '...';
        }
    }

    snackbar.innerHTML = `<i class="bi ${style.icon}"></i><span>${cleanMessage}</span>`;
    container.appendChild(snackbar);

    // Animate in
    requestAnimationFrame(() => {
        snackbar.style.opacity = '1';
        snackbar.style.transform = 'translateY(0)';
    });

    // Remove after duration
    setTimeout(() => {
        snackbar.style.opacity = '0';
        snackbar.style.transform = 'translateY(-20px)';
        setTimeout(() => snackbar.remove(), 300);
    }, duration);
}

const safeAdminLang = (typeof adminLang !== 'undefined' && adminLang) ? adminLang : {};

const ADMIN_DEFAULT_LANG = (
    safeAdminLang.filter &&
    typeof safeAdminLang.filter.default_lang === 'string' &&
    safeAdminLang.filter.default_lang !== ''
) ? safeAdminLang.filter.default_lang : (
    Array.isArray(safeAdminLang.languages) && safeAdminLang.languages.length > 0 && safeAdminLang.languages[0].code
        ? String(safeAdminLang.languages[0].code).toLowerCase()
        : 'en'
);

const ADMIN_DEFAULT_SORT = (
    safeAdminLang.filter &&
    typeof safeAdminLang.filter.default_sort === 'string' &&
    safeAdminLang.filter.default_sort !== ''
) ? safeAdminLang.filter.default_sort : 'Date';

const ADMIN_UI_ALL = (
    safeAdminLang.ui &&
    typeof safeAdminLang.ui.all === 'string' &&
    safeAdminLang.ui.all !== ''
) ? safeAdminLang.ui.all : 'All';

const ADMIN_UI = {
    all: ADMIN_UI_ALL,
    reset: (safeAdminLang.ui && typeof safeAdminLang.ui.reset === 'string') ? safeAdminLang.ui.reset : 'Reset',
    filter_cleared: (safeAdminLang.ui && typeof safeAdminLang.ui.filter_cleared === 'string') ? safeAdminLang.ui.filter_cleared : 'Filters cleared',
    filter_applied: (safeAdminLang.ui && typeof safeAdminLang.ui.filter_applied === 'string') ? safeAdminLang.ui.filter_applied : 'Filters applied',
    sort_label: (safeAdminLang.ui && typeof safeAdminLang.ui.sort_label === 'string') ? safeAdminLang.ui.sort_label : 'Sort',
    limit_label: (safeAdminLang.ui && typeof safeAdminLang.ui.limit_label === 'string') ? safeAdminLang.ui.limit_label : 'Limit',
    lang_label: (safeAdminLang.ui && typeof safeAdminLang.ui.lang_label === 'string') ? safeAdminLang.ui.lang_label : 'Language'
};

const ADMIN_STATUS = {
    active: (safeAdminLang.status && typeof safeAdminLang.status.active === 'string') ? safeAdminLang.status.active : 'Active',
    draft: (safeAdminLang.status && typeof safeAdminLang.status.draft === 'string') ? safeAdminLang.status.draft : 'Draft'
};

const ADMIN_GENERAL = {
    delete_confirm: (safeAdminLang.general && typeof safeAdminLang.general.delete_confirm === 'string') ? safeAdminLang.general.delete_confirm : 'Are you sure?',
    card_click: (safeAdminLang.general && typeof safeAdminLang.general.card_click === 'string') ? safeAdminLang.general.card_click : '%s'
};

const ADMIN_MOCK = {
    titles: (safeAdminLang.mock && Array.isArray(safeAdminLang.mock.titles)) ? safeAdminLang.mock.titles : [],
    categories: (safeAdminLang.mock && Array.isArray(safeAdminLang.mock.categories)) ? safeAdminLang.mock.categories : [],
    authors: (safeAdminLang.mock && Array.isArray(safeAdminLang.mock.authors)) ? safeAdminLang.mock.authors : []
};

// Video Embed Wizard SEO Auto-fill (global scope - called from wizard UI, state restore, and input listeners)
function updateVideoEmbedWizardSEOAutoFill() {
    const nameVal = (document.getElementById('videoNameInput')?.value || '').trim();
    const origVal = (document.getElementById('videoOriginalTitleInput')?.value || '').trim();
    const contVal = (document.getElementById('videoEmbedWizardContentTextarea')?.value || '').trim();

    const seoTitleInput = document.getElementById('videoEmbedWizardSeoTitleInput');
    const seoKeywordsInput = document.getElementById('videoEmbedWizardSeoKeywordsInput');
    const seoDescInput = document.getElementById('videoEmbedWizardSeoDescriptionInput');

    // Hidden fields for submission
    const hTitle = document.getElementById('videoEmbedWizardHiddenSeoTitle');
    const hKeywords = document.getElementById('videoEmbedWizardHiddenSeoKeywords');
    const hDesc = document.getElementById('videoEmbedWizardHiddenSeoDescription');

    const targetVal = nameVal || origVal;
    const cleanedVal = (typeof sanitizeText === 'function') ? sanitizeText(targetVal) : targetVal;

    // 1. SEO Title Auto-fill (Mixed Case, cleaned)
    if (seoTitleInput && seoTitleInput.dataset.autofilled !== 'false') {
        seoTitleInput.value = cleanedVal;
        seoTitleInput.dataset.autofilled = 'true';
        if (hTitle) hTitle.value = cleanedVal;
    } else if (seoTitleInput && hTitle) {
        hTitle.value = seoTitleInput.value;
    }

    // 2. SEO Keywords Auto-fill (Lowercase)
    if (seoKeywordsInput && seoKeywordsInput.dataset.autofilled !== 'false') {
        const activeLocale = typeof getCurrentLocale === 'function' ? getCurrentLocale() : 'en';
        const keys = makeKeywordsValue(targetVal, activeLocale);
        seoKeywordsInput.value = keys;
        seoKeywordsInput.dataset.autofilled = 'true';
        if (hKeywords) hKeywords.value = keys;
    } else if (seoKeywordsInput && hKeywords) {
        hKeywords.value = seoKeywordsInput.value;
    }

    // 3. SEO Description Auto-fill (Mixed Case, cleaned)
    if (seoDescInput && seoDescInput.dataset.autofilled !== 'false') {
        let descVal = contVal || cleanedVal;
        descVal = descVal.substring(0, 255);
        seoDescInput.value = descVal;
        seoDescInput.dataset.autofilled = 'true';
        if (hDesc) hDesc.value = descVal;
    } else if (seoDescInput && hDesc) {
        hDesc.value = seoDescInput.value;
    }
}

// Bottom Sheet / Filter Logic
function openSheet() {
    const sheet = document.getElementById('sheet-menu');
    const overlay = document.getElementById('sheet-overlay');
    if (sheet && overlay) {
        sheet.classList.add('active');
        overlay.classList.add('active');
    }
}

function closeSheet() {
    const sheet = document.getElementById('sheet-menu');
    const overlay = document.getElementById('sheet-overlay');
    if (sheet && overlay) {
        sheet.classList.remove('active');
        overlay.classList.remove('active');
    }
}

// Scroll Effect for Top Bar
const topAppBar = document.querySelector('.top-app-bar');
const scrollContent = document.querySelector('.mobile-wrapper');

if (scrollContent) {
    scrollContent.addEventListener('scroll', () => {
        if (scrollContent.scrollTop > 10) {
            topAppBar.style.backgroundColor = '#F3EDF7';
            topAppBar.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
        } else {
            topAppBar.style.backgroundColor = 'var(--md-sys-color-surface-container)';
            topAppBar.style.boxShadow = 'none';
        }
    });
} else {
    window.addEventListener('scroll', () => {
        if (window.scrollY > 10) {
            topAppBar.style.backgroundColor = '#F3EDF7';
            topAppBar.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
        } else {
            topAppBar.style.backgroundColor = 'var(--md-sys-color-surface-container)';
            topAppBar.style.boxShadow = 'none';
        }
    });
}

// Selection Mode Simulation (Long Press)
const cards = document.querySelectorAll('.m3-card');
let pressTimer;

cards.forEach(card => {
    // Mouse Interaction
    card.addEventListener('mousedown', () => {
        pressTimer = window.setTimeout(() => toggleSelectionMode(), 800);
    });
    card.addEventListener('mouseup', () => clearTimeout(pressTimer));

    // Touch Interaction
    card.addEventListener('touchstart', () => {
        pressTimer = window.setTimeout(() => toggleSelectionMode(), 800);
    });
    card.addEventListener('touchend', () => clearTimeout(pressTimer));
});

function toggleSelectionMode() {
    document.body.classList.toggle('selection-mode');

    // Vibration effect (visual only)
    if (document.body.classList.contains('selection-mode')) {
        navigator.vibrate?.(50); // If device supports it
    }
}


// Navigation Drawer & Accordion Handlers
function toggleDrawer() {
    const drawer = document.getElementById('navDrawer');
    const scrim = document.getElementById('drawerScrim');
    if (drawer) drawer.classList.toggle('active');
    if (scrim) scrim.classList.toggle('active');
}

function toggleGroup(btn) {
    const group = btn ? btn.closest('.nav-group') : null;
    if (group) {
        group.classList.toggle('expanded');
    }
}

function filterDrawerNav(query) {
    const q = (query || '').toLowerCase().trim();
    const navDrawer = document.getElementById('navDrawer');
    const emptyEl = document.getElementById('drawerSearchEmpty');
    const groups = document.querySelectorAll('.nav-group');
    const rootLinks = document.querySelector('.drawer-root-links');

    if (!q) {
        if (navDrawer) navDrawer.classList.remove('search-mode');
        document.querySelectorAll('.is-filtered-out').forEach(el => el.classList.remove('is-filtered-out'));
        if (emptyEl) emptyEl.hidden = true;
        return;
    }

    if (navDrawer) navDrawer.classList.add('search-mode');
    let totalVisible = 0;
    groups.forEach(group => {
        let groupVisible = 0;
        const items = group.querySelectorAll('.nav-item');
        items.forEach(item => {
            const label = (item.getAttribute('data-nav-label') || item.textContent || '').toLowerCase();
            if (label.includes(q)) {
                item.classList.remove('is-filtered-out');
                groupVisible++;
                totalVisible++;
            } else {
                item.classList.add('is-filtered-out');
            }
        });
        if (groupVisible > 0) {
            group.classList.remove('is-filtered-out');
            group.classList.add('expanded');
        } else {
            group.classList.add('is-filtered-out');
        }
    });

    if (rootLinks) {
        let rootVisible = 0;
        rootLinks.querySelectorAll('.nav-item').forEach(item => {
            const label = (item.getAttribute('data-nav-label') || item.textContent || '').toLowerCase();
            if (label.includes(q)) {
                item.classList.remove('is-filtered-out');
                rootVisible++;
                totalVisible++;
            } else {
                item.classList.add('is-filtered-out');
            }
        });
        if (rootVisible > 0) rootLinks.classList.remove('is-filtered-out');
        else rootLinks.classList.add('is-filtered-out');
    }

    if (emptyEl) emptyEl.hidden = (totalVisible > 0);
}

// Global Drawer & FAB Event Listener Init
document.addEventListener('DOMContentLoaded', () => {
    const drawerScrim = document.getElementById('drawerScrim');
    if (drawerScrim) drawerScrim.addEventListener('click', toggleDrawer);

    const drawerCloseBtn = document.getElementById('drawerCloseBtn');
    if (drawerCloseBtn) drawerCloseBtn.addEventListener('click', toggleDrawer);

    const drawerSearchInput = document.getElementById('drawerSearchInput');
    if (drawerSearchInput) {
        drawerSearchInput.addEventListener('input', (e) => filterDrawerNav(e.target.value));
    }

    document.querySelectorAll('.nav-group-header').forEach(header => {
        header.addEventListener('click', () => toggleGroup(header));
    });

    const fabOverlay = document.getElementById('fabOverlay');
    if (fabOverlay) fabOverlay.addEventListener('click', toggleFabMenu);

    const fabLarge = document.querySelector('.fab-large');
    if (fabLarge) fabLarge.addEventListener('click', toggleFabMenu);
});

// Speed Dial Logic
function toggleFabMenu() {
    const container = document.getElementById('fabContainer');
    const overlay = document.getElementById('fabOverlay');
    if (container) {
        container.classList.toggle('active');
        if (overlay) overlay.classList.toggle('active');
        navigator.vibrate?.(10);
    }
    const mainFab = document.getElementById('mainFab');
    const overlayPanel = document.getElementById('overlayPanel');
    const fabMenuContainer = document.getElementById('fabMenuContainer');
    if (mainFab && overlayPanel && fabMenuContainer) {
        mainFab.classList.toggle('active');
        overlayPanel.classList.toggle('active');
        fabMenuContainer.classList.toggle('active');
        navigator.vibrate?.(10);
    }
}

// Close FAB when clicking outside
document.addEventListener('click', (e) => {
    const container = document.getElementById('fabContainer');
    const overlay = document.getElementById('fabOverlay');
    // Close if clicked outside the container while it's active
    if (container && container.classList.contains('active')) {
        if (!container.contains(e.target) && (!overlay || !overlay.contains(e.target))) {
            container.classList.remove('active');
            if (overlay) overlay.classList.remove('active');
        }
    }
    const mainFabContainer = document.getElementById('fabMenuContainer');
    const mainOverlay = document.getElementById('overlayPanel');
    const mainFab = document.getElementById('mainFab');
    if (mainFabContainer && mainFabContainer.classList.contains('active')) {
        if (!mainFabContainer.contains(e.target) && (!mainFab || !mainFab.contains(e.target)) && (!mainOverlay || !mainOverlay.contains(e.target))) {
            mainFabContainer.classList.remove('active');
            if (mainOverlay) mainOverlay.classList.remove('active');
            if (mainFab) mainFab.classList.remove('active');
        }
    }
});

// CONTEXT MENU LOGIC for Cards
let activeContextItem = { id: null, title: '', status: false };

function openContextMenu(id, title, isPublished, hiddenActions = []) {
    activeContextItem = { id, title, status: isPublished };
    window.currentContentId = id;

    const sheet = document.getElementById('context-menu-sheet');
    const overlay = document.getElementById('context-menu-overlay');
    const titleEl = document.getElementById('contextMenuTitle');
    const statusAction = document.getElementById('contextActionStatus');
    const editActionLabel = document.querySelector('#contextActionEdit .context-action-label');
    const galleryActionLabel = document.querySelector('#contextActionGallery .context-action-label');
    const filesActionLabel = document.querySelector('#contextActionFiles .context-action-label');
    const faqActionLabel = document.querySelector('#contextActionFaq .context-action-label');
    const deleteActionLabel = document.querySelector('#contextActionDelete .context-action-label');

    if (titleEl) titleEl.innerText = title;
    if (editActionLabel && safeAdminLang?.context_menu?.edit) editActionLabel.innerText = safeAdminLang.context_menu.edit;
    if (galleryActionLabel && safeAdminLang?.context_menu?.gallery) galleryActionLabel.innerText = safeAdminLang.context_menu.gallery;
    if (filesActionLabel && safeAdminLang?.context_menu?.files) filesActionLabel.innerText = safeAdminLang.context_menu.files;
    if (faqActionLabel && safeAdminLang?.context_menu?.faq) faqActionLabel.innerText = safeAdminLang.context_menu.faq;
    if (deleteActionLabel && safeAdminLang?.context_menu?.delete) deleteActionLabel.innerText = safeAdminLang.context_menu.delete;

    // Handle hidden actions
    const galleryAction = document.getElementById('contextActionGallery');
    const filesAction = document.getElementById('contextActionFiles');
    const faqAction = document.getElementById('contextActionFaq');

    if (galleryAction) galleryAction.style.display = hiddenActions.includes('gallery') ? 'none' : 'flex';
    if (filesAction) filesAction.style.display = hiddenActions.includes('files') ? 'none' : 'flex';
    if (faqAction) faqAction.style.display = hiddenActions.includes('faq') ? 'none' : 'flex';

    // Toggle Status Text based on current state
    if (statusAction) {
        if (isPublished) {
            statusAction.querySelector('.nav-item-label').innerText = safeAdminLang?.context_menu?.unpublish || 'Unpublish';
            statusAction.querySelector('.nav-item-icon').className = 'bi bi-eye-slash nav-item-icon';
        } else {
            statusAction.querySelector('.nav-item-label').innerText = safeAdminLang?.context_menu?.publish || 'Publish';
            statusAction.querySelector('.nav-item-icon').className = 'bi bi-eye nav-item-icon';
        }
    }

    if (sheet && overlay) {
        sheet.classList.add('active');
        overlay.classList.add('active');
        navigator.vibrate?.(10);

        const fab = document.getElementById('fabContainer');
        if (fab) fab.classList.add('hidden');
    }
}

function closeContextMenu() {
    const sheet = document.getElementById('context-menu-sheet');
    const overlay = document.getElementById('context-menu-overlay');
    if (sheet && overlay) {
        sheet.classList.remove('active');
        overlay.classList.remove('active');

        // Restore FAB
        const fab = document.getElementById('fabContainer');
        if (fab) fab.classList.remove('hidden');
    }
}

/**
 * Global fallback for context actions. 
 * Modules like mod_news.js should override window.handleContextAction.
 */
function handleContextAction(action) {
    closeContextMenu();
    console.log("Context action triggered:", action, "on item:", activeContextItem);
}

// Close Context Menu when clicking overlay (handled by HTML onclick, but added here for safety)
// document.addEventListener('click', ...) logic handles specific containers, but overlays usually cover all.

// WIZARD LOGIC
let wizardCurrentStep = 1;
const wizardTotalSteps = 8;
const wizardData = {
    lang: ADMIN_DEFAULT_LANG,
    category: null,
    title: '',
    date: '',
    intro: '',
    content: '',
    image: null,
    published: true,
    seo: {
        title: '',
        keywords: '',
        description: '',
        autofilled: {
            title: 'true',
            keywords: 'true',
            description: 'true'
        }
    }
};

// Flag to indicate wizard was submitted - prevents re-save
let wizardSubmitted = false;

const WIZARD_STORAGE_KEY = 'contentWizardState';

/**
 * Save wizard state to localStorage
 * Called on every change
 */
function saveWizardState() {
    // If wizard was already submitted, don't save
    if (wizardSubmitted) {
        return;
    }

    try {
        const state = {
            step: wizardCurrentStep,
            data: {
                lang: wizardData.lang,
                category: wizardData.category,
                title: wizardData.title,
                date: wizardData.date,
                intro: wizardData.intro,
                content: wizardData.content,
                published: wizardData.published,
                seo: wizardData.seo
            },
            timestamp: Date.now()
        };
        localStorage.setItem(WIZARD_STORAGE_KEY, JSON.stringify(state));
    } catch (e) {
        console.warn('Wizard state save error:', e);
    }
}

/**
 * Read and restore saved wizard state from localStorage
 * Called when the wizard opens or page reloads
 * @returns {boolean} true if state was restored
 */
function loadWizardState() {
    try {
        const saved = localStorage.getItem(WIZARD_STORAGE_KEY);
        if (!saved) return false;

        const state = JSON.parse(saved);

        // Time check - if older than 24 hours, clear it
        const maxAge = 24 * 60 * 60 * 1000; // 24 hours
        if (Date.now() - state.timestamp > maxAge) {
            clearWizardState();
            return false;
        }

        // Restore wizard state
        wizardCurrentStep = state.step || 1;

        // Restore wizard data
        if (state.data) {
            wizardData.lang = state.data.lang || ADMIN_DEFAULT_LANG;
            wizardData.category = state.data.category || null;
            wizardData.title = state.data.title || '';
            wizardData.date = state.data.date || '';
            wizardData.intro = state.data.intro || '';
            wizardData.content = state.data.content || '';
            wizardData.published = state.data.published !== undefined ? state.data.published : true;
            wizardData.seo = state.data.seo || wizardData.seo;
        }

        return true;
    } catch (e) {
        console.warn('Wizard state load error:', e);
        return false;
    }
}

/**
 * Clear wizard state from localStorage
 * Called on successful submit or when wizard is closed
 */
function clearWizardState() {
    try {
        localStorage.removeItem(WIZARD_STORAGE_KEY);
        // Set temporary flag to prevent auto-restore after reload
        localStorage.setItem('wizardJustSubmitted', Date.now().toString());
    } catch (e) {
        console.warn('Wizard state clear error:', e);
    }
}

/**
 * Restore wizard UI from saved state
 * Re-populate form inputs and visual selections
 */
function restoreWizardUI() {
    // Restore language selection
    const langCards = document.querySelectorAll('#wizardStep1 .selection-card');
    langCards.forEach(card => {
        card.classList.remove('active');
        if (card.getAttribute('onclick')?.includes(`'${wizardData.lang}'`)) {
            card.classList.add('active');
        }
    });
    const hiddenLang = document.getElementById('wizardHiddenLang');
    if (hiddenLang) hiddenLang.value = wizardData.lang;

    // Restore category selection
    if (wizardData.category) {
        const label = document.getElementById('wizardSelectedCategory');
        const hidden = document.getElementById('wizardHiddenCategory');
        if (label) {
            label.innerText = wizardData.category.name;
            label.style.color = 'var(--md-sys-color-on-surface)';
        }
        if (hidden) hidden.value = wizardData.category.id;
    }

    ensureWizardCategoryMatchesLanguage();
    syncWizardCategorySheet();

    // Configure title
    const titleInput = document.getElementById('wizardTitleInput');
    if (titleInput && wizardData.title) {
        titleInput.value = wizardData.title;
        const counter = document.getElementById('titleCharCounter');
        if (counter) counter.innerText = `${wizardData.title.length} / 70`;
    }

    // Restore SEO fields and their autofilled markers
    const seoTitle = document.getElementById('wizardSeoTitleInput');
    const seoKeywords = document.getElementById('wizardSeoKeywordsInput');
    const seoDesc = document.getElementById('wizardSeoDescriptionInput');
    if (seoTitle) {
        seoTitle.value = wizardData.seo.title;
        seoTitle.dataset.autofilled = wizardData.seo.autofilled.title;
    }
    if (seoKeywords) {
        seoKeywords.value = wizardData.seo.keywords;
        seoKeywords.dataset.autofilled = wizardData.seo.autofilled.keywords;
    }
    if (seoDesc) {
        seoDesc.value = wizardData.seo.description;
        seoDesc.dataset.autofilled = wizardData.seo.autofilled.description;
    }

    // Configure date
    const dateInput = document.getElementById('wizardDateInput');
    if (dateInput && wizardData.date) {
        dateInput.value = wizardData.date;
    }

    // Intro restore
    const introInput = document.getElementById('wizardIntroInput');
    if (introInput && wizardData.intro) {
        introInput.value = wizardData.intro;
        const counter = document.getElementById('introCharCounter');
        const len = wizardData.intro.length;
        if (counter) {
            counter.innerText = `${len} / 120`;
            counter.classList.remove('is-valid', 'is-warning', 'is-error');
            if (len >= 110 && len <= 120) {
                counter.classList.add('is-valid');
            } else if (len > 120) {
                counter.classList.add('is-error');
            } else if (len > 0) {
                counter.classList.add('is-warning');
            }
        }
    }

    // Configure content (TinyMCE or textarea)
    const contentArea = document.getElementById('wizardContentTextarea');
    if (contentArea && wizardData.content) {
        // If TinyMCE is loaded
        if (typeof tinymce !== 'undefined' && tinymce.get('wizardContentTextarea')) {
            tinymce.get('wizardContentTextarea').setContent(wizardData.content);
        } else {
            contentArea.value = wizardData.content;
        }
    }

    // Configure publish switch
    const pubSwitch = document.getElementById('wizardPublishSwitch');
    if (pubSwitch) {
        pubSwitch.checked = wizardData.published;
    }

    // Trigger auto-fill after restore to ensure synced values
    if (typeof updateWizardSEOAutoFill === 'function') {
        updateWizardSEOAutoFill();
    }
}

function openWizard() {
    const wizard = document.getElementById('contentWizard');
    if (wizard) {
        wizard.classList.add('active');

        // Read saved state (if exists)
        const hasState = loadWizardState();
        if (hasState) {
            // Restore UI from saved state
            restoreWizardUI();
        } else {
            // Reset to step 1
            wizardCurrentStep = 1;
        }

        updateWizardUI();

        // Close other menus (FAB)
        const fab = document.getElementById('fabContainer');
        if (fab) {
            fab.classList.remove('active');
            fab.style.display = 'none';
        }
    }
}

function closeWizard() {
    const wizard = document.getElementById('contentWizard');
    if (wizard) wizard.classList.remove('active');

    // Restore FAB
    const fab = document.getElementById('fabContainer');
    if (fab) fab.style.display = 'flex';

    // Clear saved state
    clearWizardState();

    closeWizardCategorySheet();
}


function checkWizardStepValidity(step) {
    if (step === 2) {
        return wizardData.category !== null;
    }
    if (step === 3) {
        return wizardData.title && wizardData.title.trim().length >= 1;
    }
    if (step === 5) {
        const introLen = wizardData.intro ? wizardData.intro.trim().length : 0;
        return introLen >= 90 && introLen <= 120;
    }
    if (step === 6) {
        // Content must not be empty - minimum 10 characters
        const content = wizardData.content || '';
        // Strip HTML tags for accurate count
        const textContent = content.replace(/<[^>]*>/g, '').trim();
        return textContent.length >= 10;
    }
    return true; // Other steps valid by default or handled elsewhere
}

function updateWizardUI() {
    // Hide all steps
    document.querySelectorAll('.wizard-step').forEach(step => {
        step.classList.remove('active');
    });
    // Show current
    const currentStepEl = document.getElementById('wizardStep' + wizardCurrentStep);
    if (currentStepEl) currentStepEl.classList.add('active');

    // Update Header
    const counterEl = document.getElementById('wizardStepCounter');
    if (counterEl) counterEl.innerText = `${wizardCurrentStep}/${wizardTotalSteps}`;

    // Dynamic Title Update
    const titleEl = document.getElementById('wizardStepTitle');
    if (titleEl) {
        const stepTitles = [
            safeAdminLang?.wizard?.step?.language || '',
            safeAdminLang?.wizard?.step?.category || '',
            safeAdminLang?.wizard?.step?.title || '',
            safeAdminLang?.wizard?.step?.date || '',
            safeAdminLang?.wizard?.step?.intro || '',
            safeAdminLang?.wizard?.step?.content || '',
            safeAdminLang?.wizard?.step?.image || '',
            safeAdminLang?.wizard?.step?.finish || ''
        ];
        titleEl.innerText = stepTitles[wizardCurrentStep - 1] || safeAdminLang?.wizard?.step?.language || '';
    }

    // Update Progress Bar
    const progress = (wizardCurrentStep / wizardTotalSteps) * 100;
    const progressFill = document.getElementById('wizardProgressFill');
    if (progressFill) progressFill.style.width = `${progress}%`;

    // Update Buttons
    const btnPrev = document.getElementById('btnWizardPrev');
    const btnNext = document.getElementById('btnWizardNext');
    const btnFinish = document.getElementById('btnWizardFinish');

    if (btnPrev) btnPrev.disabled = (wizardCurrentStep === 1);

    if (wizardCurrentStep === wizardTotalSteps) {
        if (btnNext) {
            btnNext.style.display = 'none';
            btnNext.hidden = true;
        }
        if (btnFinish) {
            btnFinish.hidden = false;
            btnFinish.style.display = 'block';
        }
        updateSummary();
    } else {
        if (btnNext) {
            btnNext.hidden = false;
            btnNext.style.display = 'block';

            // VALIDATION LOGIC FOR BUTTON COLOR
            const isValid = checkWizardStepValidity(wizardCurrentStep);
            if (isValid) {
                btnNext.classList.remove('btn-invalid');
            } else {
                btnNext.classList.add('btn-invalid');
            }
        }
        if (btnFinish) {
            btnFinish.style.display = 'none';
            btnFinish.hidden = true;
        }
    }

    // Auto-fill SEO when entering SEO step (Step 8)
    if (wizardCurrentStep === 8) {
        if (typeof updateWizardSEOAutoFill === 'function') {
            updateWizardSEOAutoFill();
        }
    }
}

function nextWizardStep() {
    // Sync TinyMCE content before validation
    if (typeof tinymce !== 'undefined' && tinymce.get('wizardContentTextarea')) {
        wizardData.content = tinymce.get('wizardContentTextarea').getContent();
    }

    // Check validity on click
    if (!checkWizardStepValidity(wizardCurrentStep)) {
        // VISUAL FEEDBACK FOR ERRORS
        if (wizardCurrentStep === 3) { // Title Step
            const subhead = document.querySelector('#wizardStep3 .step-subhead'); // Assuming typical structure
            // Or better, specific ID if available. Looking at previous file view, maybe not specific ID.
            // Using generic class selector for current step's subhead:
            const stepEl = document.getElementById('wizardStep' + wizardCurrentStep);
            if (stepEl) {
                const sub = stepEl.querySelector('.step-subhead') || stepEl.querySelector('p'); // Fallback
                if (sub) {
                    sub.classList.add('text-error');
                    // Remove after animation
                    setTimeout(() => sub.classList.remove('text-error'), 1000);
                }

                const titleInput = document.getElementById('wizardTitleInput');
                if (titleInput) titleInput.focus();

                const msg = document.getElementById('titleValidationMsg');
                if (msg) msg.style.display = 'block';
            }
        }
        // Step 2 Category Error Feedback
        if (wizardCurrentStep === 2) {
            const stepEl = document.getElementById('wizardStep' + wizardCurrentStep);
            if (stepEl) {
                const sub = stepEl.querySelector('.step-subhead');
                if (sub) {
                    sub.classList.add('text-error');
                    setTimeout(() => sub.classList.remove('text-error'), 1000);
                }
                toggleWizardCategorySheet();
            }
        }
        // Step 5 Intro Error Feedback
        if (wizardCurrentStep === 5) {
            const stepEl = document.getElementById('wizardStep' + wizardCurrentStep);
            if (stepEl) {
                const sub = stepEl.querySelector('.step-subhead');
                if (sub) {
                    sub.classList.add('text-error');
                    setTimeout(() => sub.classList.remove('text-error'), 1000);
                }
                const introInput = document.getElementById('wizardIntroInput');
                if (introInput) introInput.focus();

                const msg = document.getElementById('introValidationMsg');
                if (msg) msg.style.display = 'block';
            }
        }
        return; // DATA INVALID, STOP.
    }

    if (wizardCurrentStep < wizardTotalSteps) {
        wizardCurrentStep++;

        // Auto-fill Date logic
        if (wizardCurrentStep === 4 && !wizardData.date) {
            try {
                const now = new Date();
                const localDate = new Date(now.getTime() - (now.getTimezoneOffset() * 60000));
                const formattedDate = localDate.toISOString().slice(0, 16);

                wizardData.date = formattedDate;
                const dateInput = document.getElementById('wizardDateInput');
                if (dateInput) dateInput.value = formattedDate;
            } catch (e) {
                console.warn('Auto-fill date error:', e);
            }
        }

        updateWizardUI();
        saveWizardState(); // Save state
    }
}

function prevWizardStep() {
    if (wizardCurrentStep > 1) {
        wizardCurrentStep--;
        updateWizardUI();
        saveWizardState(); // Save state
    }
}

// REMOVED validateWizardStep as logic is integrated into nextWizardStep and checkWizardStepValidity

// Step 1: Language
function selectWizardLang(el, lang) {
    document.querySelectorAll('.selection-card').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
    wizardData.lang = lang;
    const hidden = document.getElementById('wizardHiddenLang');
    if (hidden) hidden.value = lang;
    ensureWizardCategoryMatchesLanguage();
    syncWizardCategorySheet();
    updateWizardUI();
    if (typeof updateWizardSEOAutoFill === 'function') {
        updateWizardSEOAutoFill();
    }
    saveWizardState(); // Save state
}

// Step 2: Category
function getWizardCategorySheetContext() {
    let targetLang = ADMIN_DEFAULT_LANG;
    let isVideoWizard = false;
    let selectedCategoryId = null;

    const videoEmbedWizard = document.getElementById('videoEmbedWizard');
    if (videoEmbedWizard && videoEmbedWizard.classList.contains('active')) {
        targetLang = (videoEmbedWizardData.lang || ADMIN_DEFAULT_LANG).toUpperCase();
        isVideoWizard = true;
        selectedCategoryId = videoEmbedWizardData?.category?.id || null;
    } else if (typeof wizardData !== 'undefined' && wizardData.lang) {
        targetLang = wizardData.lang.toUpperCase();
        selectedCategoryId = wizardData?.category?.id || null;
    }

    return { targetLang, isVideoWizard, selectedCategoryId };
}

function getLanguageNameByCode(code) {
    const normalized = String(code || '').toUpperCase();
    const matched = Array.isArray(safeAdminLang.languages)
        ? safeAdminLang.languages.find(item => item.code === normalized)
        : null;

    return matched?.name || normalized;
}

function resetWizardCategorySelection() {
    if (typeof wizardData === 'undefined') {
        return;
    }

    wizardData.category = null;

    const label = document.getElementById('wizardSelectedCategory');
    const hidden = document.getElementById('wizardHiddenCategory');
    if (label) {
        label.innerText = safeAdminLang.wizard?.label?.category_select || safeAdminLang.wizard?.summary?.selected_none || '';
        label.style.color = 'var(--md-sys-color-on-surface-variant)';
    }
    if (hidden) {
        hidden.value = 0;
    }
}

function ensureWizardCategoryMatchesLanguage() {
    if (typeof wizardData === 'undefined' || !wizardData.category?.id) {
        return;
    }

    const selectedItem = document.querySelector(
        `.content-cat[data-category-id="${String(wizardData.category.id)}"]`
    );
    const selectedLang = selectedItem?.getAttribute('data-lang')?.toUpperCase();
    const activeLang = String(wizardData.lang || ADMIN_DEFAULT_LANG).toUpperCase();

    if (selectedLang && selectedLang !== activeLang) {
        resetWizardCategorySelection();
    }
}

function syncWizardCategorySheet() {
    const sheet = document.getElementById('wizard-cat-sheet');
    if (!sheet) return;

    const { targetLang, isVideoWizard, selectedCategoryId } = getWizardCategorySheetContext();
    const searchInput = document.getElementById('wizardCategorySearchInput');
    const query = (searchInput?.value || '').trim().toLocaleLowerCase();
    const items = Array.from(sheet.querySelectorAll('.category-item'));
    const normalizedTargetLang = String(targetLang || '').trim().toUpperCase();

    // Check if any categories match the exact target language
    let matchingExactLang = false;
    if (normalizedTargetLang) {
        matchingExactLang = items.some(item => {
            const isVideoItem = item.classList.contains('video-cat');
            const isContentItem = item.classList.contains('content-cat');
            const typeMatch = isVideoWizard ? isVideoItem : isContentItem;
            if (!typeMatch) return false;
            const itemLang = String(item.getAttribute('data-lang') || '').trim().toUpperCase();
            return itemLang === normalizedTargetLang;
        });
    }

    let visibleCount = 0;

    items.forEach(item => {
        const itemLang = String(item.getAttribute('data-lang') || '').trim().toUpperCase();
        const itemName = (item.getAttribute('data-name') || '').toLocaleLowerCase();
        const itemId = item.getAttribute('data-category-id');

        const isVideoItem = item.classList.contains('video-cat');
        const isContentItem = item.classList.contains('content-cat');
        const typeMatch = isVideoWizard ? isVideoItem : isContentItem;

        // If exact language matches exist, filter by target language; otherwise fallback to all categories of that type
        const langMatch = (!matchingExactLang) || (!itemLang) || (!normalizedTargetLang) || (itemLang === normalizedTargetLang);
        const searchMatch = !query || itemName.includes(query);
        const isVisible = typeMatch && langMatch && searchMatch;

        item.style.display = isVisible ? 'flex' : 'none';
        item.classList.toggle('is-selected', String(selectedCategoryId) === String(itemId));

        if (isVisible) {
            visibleCount++;
        }
    });

    const noCatMsg = document.getElementById('no-category-msg');
    if (noCatMsg) {
        noCatMsg.style.display = (visibleCount === 0) ? 'block' : 'none';
    }

    const countEl = document.getElementById('wizardCatVisibleCount');
    if (countEl) {
        countEl.innerText = String(visibleCount);
    }

    const activeLangEl = document.getElementById('wizardCatActiveLang');
    if (activeLangEl) {
        activeLangEl.innerText = getLanguageNameByCode(targetLang);
    }
}

function closeWizardCategorySheet() {
    const sheet = document.getElementById('wizard-cat-sheet');
    const overlay = document.getElementById('wizard-cat-overlay');
    const searchInput = document.getElementById('wizardCategorySearchInput');
    if (!sheet || !overlay) return;

    sheet.classList.remove('active');
    overlay.classList.remove('active');
    overlay.style.display = 'none';

    if (searchInput) {
        searchInput.value = '';
    }
}

function toggleWizardCategorySheet() {
    const sheet = document.getElementById('wizard-cat-sheet');
    const overlay = document.getElementById('wizard-cat-overlay');
    if (!sheet || !overlay) return;

    if (sheet.classList.contains('active')) {
        closeWizardCategorySheet();
        return;
    }

    sheet.classList.add('active');
    overlay.classList.add('active');
    overlay.style.display = 'block';

    syncWizardCategorySheet();

    const searchInput = document.getElementById('wizardCategorySearchInput');
    if (searchInput) {
        searchInput.focus();
    }
}

function filterWizardCategorySheet() {
    syncWizardCategorySheet();
}

let activeSelectPickerId = null;

function getPickerLanguageLabel(code) {
    const normalized = String(code || '').toUpperCase();
    const matched = Array.isArray(safeAdminLang.languages)
        ? safeAdminLang.languages.find(item => item.code === normalized)
        : null;

    return matched?.name || normalized || ADMIN_DEFAULT_LANG;
}

function getSelectPickerLangCode(select) {
    if (!select) return '';

    const sourceId = select.dataset.pickerLangSource;
    const source = sourceId ? document.getElementById(sourceId) : null;
    const value = source ? (source.value || source.textContent || '') : '';
    return String(value || '').trim().toUpperCase();
}

function getSelectPickerTrigger(selectId) {
    return document.querySelector(`[data-picker-trigger-for="${selectId}"]`);
}

function updateSelectPickerTrigger(selectId) {
    const select = document.getElementById(selectId);
    const trigger = getSelectPickerTrigger(selectId);
    if (!select || !trigger) return;

    const label = trigger.querySelector('.select-picker-label') || trigger;
    const selectedOption = select.options[select.selectedIndex];
    const placeholder = select.dataset.pickerPlaceholder || safeAdminLang.wizard?.summary?.selected_none || '';
    const selectedValue = String(select.value || '0');
    const text = selectedOption && selectedValue !== '0'
        ? (selectedOption.dataset.triggerLabel || selectedOption.textContent || placeholder)
        : placeholder;

    label.textContent = text.trim();
    trigger.classList.toggle('has-value', selectedValue !== '0');
}

function syncSelectPickerSelectionWithLang(select) {
    if (!select || !select.dataset.pickerLangSource) return;

    const selectedOption = select.options[select.selectedIndex];
    const selectedLang = String(selectedOption?.dataset?.lang || '').toUpperCase();
    const activeLang = getSelectPickerLangCode(select);

    if (selectedLang && activeLang && selectedLang !== activeLang) {
        select.value = '0';
        select.dispatchEvent(new Event('change', { bubbles: true }));
    } else {
        updateSelectPickerTrigger(select.id);
    }
}

function getPickerFileTypeMeta(label, extHint = '') {
    const rawExt = String(extHint || (String(label || '').includes('.') ? String(label).split('.').pop() : '')).trim().toLowerCase();
    if (rawExt === 'pdf') {
        return { icon: 'bi-file-earmark-pdf', modifier: 'select-picker-item-meta--pdf', badge: 'PDF' };
    }
    if (['doc', 'docx', 'odt', 'rtf', 'txt'].includes(rawExt)) {
        return { icon: 'bi-file-earmark-word', modifier: 'select-picker-item-meta--doc', badge: rawExt.toUpperCase() };
    }
    if (['xls', 'xlsx', 'csv', 'ods'].includes(rawExt)) {
        return { icon: 'bi-file-earmark-excel', modifier: 'select-picker-item-meta--sheet', badge: rawExt.toUpperCase() };
    }
    if (['zip', 'rar', '7z', 'tar', 'gz'].includes(rawExt)) {
        return { icon: 'bi-file-earmark-zip', modifier: 'select-picker-item-meta--archive', badge: rawExt.toUpperCase() };
    }
    if (['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'].includes(rawExt)) {
        return { icon: 'bi-file-earmark-image', modifier: 'select-picker-item-meta--image', badge: rawExt.toUpperCase() };
    }
    return {
        icon: 'bi-file-earmark-text',
        modifier: '',
        badge: rawExt && rawExt.length <= 5 ? rawExt.toUpperCase() : ''
    };
}

function renderSelectPickerOptions() {
    const select = activeSelectPickerId ? document.getElementById(activeSelectPickerId) : null;
    const list = document.getElementById('selectPickerList');
    const empty = document.getElementById('selectPickerEmpty');
    const emptyAddBtn = document.getElementById('selectPickerEmptyAddBtn');
    const count = document.getElementById('selectPickerVisibleCount');
    const langEl = document.getElementById('selectPickerActiveLang');
    const searchInput = document.getElementById('selectPickerSearchInput');

    if (!select || !list) return;

    const query = String(searchInput?.value || '').trim().toLocaleLowerCase();
    const activeLang = getSelectPickerLangCode(select);
    const currentSelectedVal = String(select.value || '0');
    const options = Array.from(select.options).sort((a, b) => {
        const aSelected = String(a.value || '0') !== '0' && String(a.value || '0') === currentSelectedVal;
        const bSelected = String(b.value || '0') !== '0' && String(b.value || '0') === currentSelectedVal;
        if (aSelected && !bSelected) return -1;
        if (!aSelected && bSelected) return 1;
        return 0;
    });
    let visibleCount = 0;

    list.innerHTML = '';

    options.forEach(option => {
        const value = String(option.value || '0');
        const label = (option.dataset.triggerLabel || option.textContent || '').trim();
        const optionLang = String(option.dataset.lang || '').toUpperCase();
        const searchValue = `${label} ${optionLang}`.toLocaleLowerCase();
        const langMatch = !optionLang || !activeLang || optionLang === activeLang || value === '0';
        const searchMatch = !query || searchValue.includes(query);

        if (!langMatch || !searchMatch) {
            return;
        }

        if (value === '0') {
            return; // Hide the '0' (None) option from the visual list
        }

        visibleCount++;

        const item = document.createElement('button');
        item.type = 'button';
        item.className = 'select-picker-item';
        if (select.value === value) {
            item.classList.add('is-selected');
        }

        const isFiles = window.currentMediaPickerType === 'files';
        const isGallery = window.currentMediaPickerType === 'gallery';
        const isVideo = window.currentMediaPickerType === 'video';

        let iconClass = 'bi-bookmark-star';
        let metaModifierClass = '';
        let fileBadge = '';

        if (value === '0') {
            iconClass = 'bi-eraser';
            metaModifierClass = 'select-picker-item-meta--reset';
        } else if (isFiles) {
            const fileMeta = getPickerFileTypeMeta(label, option.dataset.ext || '');
            iconClass = fileMeta.icon;
            metaModifierClass = fileMeta.modifier;
            fileBadge = fileMeta.badge;
        } else if (isGallery) {
            iconClass = 'bi-images';
        } else if (isVideo) {
            iconClass = 'bi-play-btn';
        }

        const subtitleText = (optionLang && optionLang !== ADMIN_UI.all && value !== '0')
            ? optionLang
            : (isFiles && fileBadge ? fileBadge : '');
        const subtitleHtml = subtitleText
            ? `<span class="select-picker-item-subtitle">${subtitleText}</span>`
            : '';

        let removeLabel = 'Clear Selection';
        if (typeof CONTENT_LANG !== 'undefined' && CONTENT_LANG.picker && CONTENT_LANG.picker.remove) {
            removeLabel = CONTENT_LANG.picker.remove;
        } else if (ADMIN_UI.reset) {
            removeLabel = ADMIN_UI.reset;
        }

        const itemTitle = (value === '0') ? removeLabel : (label || (select.dataset.pickerPlaceholder || ''));

        item.innerHTML = `
            <span class="select-picker-item-meta ${metaModifierClass}"><i class="bi ${iconClass}"></i></span>
            <span class="select-picker-item-body">
                <span class="select-picker-item-title">${itemTitle}</span>
                ${subtitleHtml}
            </span>
            <i class="bi bi-check-circle-fill select-picker-item-check"></i>
        `;

        item.addEventListener('click', () => {
            if (select.value === value) {
                select.value = '0';
            } else {
                select.value = value;
            }
            select.dispatchEvent(new Event('change', { bubbles: true }));
            updateSelectPickerTrigger(select.id);
            closeSelectPickerSheet();
        });

        list.appendChild(item);
    });

    if (count) {
        count.textContent = String(visibleCount);
    }

    if (langEl) {
        langEl.textContent = activeLang ? getPickerLanguageLabel(activeLang) : ADMIN_UI.all;
    }

    if (empty) {
        empty.style.display = visibleCount === 0 ? 'block' : 'none';
    }

    if (emptyAddBtn) {
        const showEmptyUpload = visibleCount === 0 && window.currentMediaPickerType === 'files';
        emptyAddBtn.classList.toggle('is-hidden', !showEmptyUpload);
    }
}

function openSelectPickerSheet(selectId) {
    const select = document.getElementById(selectId);
    const sheet = document.getElementById('select-picker-sheet');
    const overlay = document.getElementById('select-picker-overlay');
    const titleEl = document.getElementById('selectPickerTitle');
    const searchInput = document.getElementById('selectPickerSearchInput');
    if (!select || !sheet || !overlay) return;

    const addBtn = document.getElementById('selectPickerAddBtn');
    const manageBtn = document.getElementById('selectPickerManageBtn');
    const emptyAddBtn = document.getElementById('selectPickerEmptyAddBtn');

    const isMediaSelect = selectId === 'dynamicMediaSelect' || selectId === 'dynamicPageFileSelect';
    if (!isMediaSelect) {
        window.currentMediaPickerType = null;
        addBtn?.classList.add('is-hidden');
        manageBtn?.classList.add('is-hidden');
        emptyAddBtn?.classList.add('is-hidden');
    } else {
        if (addBtn) {
            addBtn.style.display = '';
            addBtn.classList.remove('is-hidden');
        }
        if (manageBtn) {
            manageBtn.style.display = '';
            manageBtn.classList.remove('is-hidden');
        }
        if (!window.currentMediaPickerManageUrl && window.currentMediaPickerAddUrl) {
            window.currentMediaPickerManageUrl = window.currentMediaPickerAddUrl;
        }
        if (!window.currentMediaPickerRecordId) {
            window.currentMediaPickerRecordId = window.currentMediaPickerPubId || window.currentMediaPickerContentId || 0;
        }
        if (!window.currentMediaPickerModule && window.currentMediaPickerAddUrl) {
            const modMatch = String(window.currentMediaPickerAddUrl).match(/\/(mod_[a-z0-9_]+)\/\d+/i);
            if (modMatch && modMatch[1]) {
                window.currentMediaPickerModule = modMatch[1];
            }
        }
    }

    activeSelectPickerId = selectId;

    if (titleEl) {
        titleEl.textContent = select.dataset.pickerTitle || select.name || 'Select';
    }

    if (searchInput) {
        searchInput.value = '';
    }

    sheet.classList.add('active');
    overlay.classList.add('active');
    overlay.style.display = 'block';

    renderSelectPickerOptions();

    if (searchInput) {
        searchInput.focus();
    }
}

function closeSelectPickerSheet() {
    if (window.keepSelectPickerOpenOnce) {
        window.keepSelectPickerOpenOnce = false;
        return;
    }
    const sheet = document.getElementById('select-picker-sheet');
    const overlay = document.getElementById('select-picker-overlay');
    const list = document.getElementById('selectPickerList');
    const searchInput = document.getElementById('selectPickerSearchInput');
    if (!sheet || !overlay) return;

    activeSelectPickerId = null;
    sheet.classList.remove('active');
    overlay.classList.remove('active');
    overlay.style.display = 'none';

    document.getElementById('selectPickerAddBtn')?.classList.add('is-hidden');
    document.getElementById('selectPickerManageBtn')?.classList.add('is-hidden');
    document.getElementById('selectPickerEmptyAddBtn')?.classList.add('is-hidden');

    if (list) {
        list.innerHTML = '';
    }
    if (searchInput) {
        searchInput.value = '';
    }
}

function filterSelectPickerSheet() {
    renderSelectPickerOptions();
}

window.handleSelectPickerAddDirect = function() {
    if (window.currentMediaPickerType === 'files') {
        const fileInput = document.getElementById('pickerAttachmentFileInput');
        if (fileInput) {
            window.lastFileInputTime = Date.now();
            fileInput.value = '';
            fileInput.click();
            return;
        }
    }
    if (window.currentMediaPickerAddUrl) {
        if (window.AFModalEngine && typeof window.AFModalEngine.push === 'function') {
            window.AFModalEngine.push(window.currentMediaPickerAddUrl, { skipHistory: true });
        } else {
            window.location.href = window.currentMediaPickerAddUrl;
        }
    }
};

window.handleSelectPickerAdd = window.handleSelectPickerAddDirect;

window.handleSelectPickerManage = function() {
    const targetUrl = window.currentMediaPickerManageUrl || window.currentMediaPickerAddUrl;
    if (!targetUrl) return;

    if (window.AFModalEngine && typeof window.AFModalEngine.push === 'function') {
        const dynamicBody = document.getElementById('modal-dynamic-body');
        if (dynamicBody) {
            dynamicBody.dataset.loadedUrl = '';
            dynamicBody.innerHTML = '';
        }
        const dynamicDialog = document.getElementById('modal-dynamic-dialog');
        if (dynamicDialog) {
            dynamicDialog.addEventListener('close', () => {
                if (typeof window.refreshActiveMediaPicker === 'function') {
                    window.refreshActiveMediaPicker();
                }
            }, { once: true });
        }
        window.AFModalEngine.push(targetUrl, { skipHistory: true });
    } else {
        window.location.href = targetUrl;
    }
};

window.refreshActiveMediaPicker = function(preferredId = null) {
    const recordId = window.currentMediaPickerRecordId || window.currentMediaPickerPubId || window.currentMediaPickerContentId;
    const type = window.currentMediaPickerType || 'files';
    const modName = window.currentMediaPickerModule || 'mod_publication';
    const baseUrl = (typeof SITE_URL !== 'undefined' && SITE_URL) ? SITE_URL : ((window.siteData && window.siteData.saddress) ? window.siteData.saddress : '/');
    if (!recordId) return;

    const endpoint = type === 'gallery'
        ? `${baseUrl}${modName}/ajax_get_galleries`
        : `${baseUrl}${modName}/ajax_get_files`;

    const formData = new FormData();
    formData.append('q', '');
    const currentData = new FormData();
    currentData.append('id', String(recordId));

    Promise.all([
        fetch(endpoint, { method: 'POST', body: formData }).then(r => r.json()),
        fetch(`${baseUrl}${modName}/ajax_get_current_media`, { method: 'POST', body: currentData }).then(r => r.json())
    ])
    .then(([data, currentMedia]) => {
        const select = document.getElementById('dynamicMediaSelect') || document.getElementById('dynamicPageFileSelect');
        if (!select || !Array.isArray(data)) return;

        select.innerHTML = '';
        data.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item.value || item.id;
            opt.textContent = item.name || item.text || item.title || ('ID: ' + item.id);
            select.appendChild(opt);
        });

        let currentValue = '0';
        if (preferredId) {
            currentValue = String(preferredId);
        } else if (type === 'files' && currentMedia && currentMedia.file_id) {
            currentValue = String(currentMedia.file_id);
        } else if (type === 'gallery' && currentMedia && currentMedia.gallery_id) {
            currentValue = String(currentMedia.gallery_id);
        }

        const hasMatch = Array.from(select.options).some(o => String(o.value) === currentValue);
        select.value = hasMatch ? currentValue : '0';

        if (typeof renderSelectPickerOptions === 'function') {
            renderSelectPickerOptions();
        }
        const mgr = window.newsManager || window.publicationManager;
        if (mgr && typeof mgr.loadData === 'function') {
            mgr.loadData(true);
        }
    })
    .catch(err => console.error('Refresh media picker error:', err));
};

window.handleDirectAttachmentUpload = function(fileInput) {
    if (!fileInput || !fileInput.files || !fileInput.files.length) return;
    const file = fileInput.files[0];
    const addBtn = document.getElementById('selectPickerAddBtn');
    const modName = window.currentMediaPickerModule || 'mod_publication';
    const recordId = window.currentMediaPickerRecordId || window.currentMediaPickerPubId || window.currentMediaPickerContentId || 0;
    const baseUrl = (typeof SITE_URL !== 'undefined' && SITE_URL) ? SITE_URL : ((window.siteData && window.siteData.saddress) ? window.siteData.saddress : '/');

    if (addBtn) {
        addBtn.classList.add('is-loading');
        addBtn.innerHTML = '<i class="bi bi-arrow-repeat"></i>';
    }

    const formData = new FormData();
    formData.append('file', file);
    formData.append('isim', 'file');
    formData.append('vid', String(recordId));
    formData.append('mod_name', modName);
    formData.append('response_format', 'json');

    fetch(`${baseUrl}mod_attachment/upload`, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res && res.status === 'success' && res.id) {
            const newId = String(res.id);
            const displayName = res.name || res.filename || res.original_name || file.name;
            const select = activeSelectPickerId ? document.getElementById(activeSelectPickerId) : document.getElementById('dynamicMediaSelect');

            if (select) {
                let opt = Array.from(select.options).find(o => String(o.value) === newId);
                if (!opt) {
                    opt = document.createElement('option');
                    opt.value = newId;
                    select.appendChild(opt);
                }
                opt.textContent = displayName;
                opt.dataset.triggerLabel = displayName;
                if (res.ext) {
                    opt.dataset.ext = res.ext;
                }
                select.value = newId;
                window.keepSelectPickerOpenOnce = true;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                renderSelectPickerOptions();
                document.getElementById('selectPickerList')?.scrollTo({ top: 0, behavior: 'smooth' });
            } else if (recordId) {
                const relData = new FormData();
                relData.append('id', String(recordId));
                relData.append('type', 'files');
                relData.append('value', newId);
                fetch(`${baseUrl}${modName}/ajax_update_relation`, {
                    method: 'POST',
                    body: relData
                }).then(() => {
                    if (typeof window.refreshActiveMediaPicker === 'function') {
                        window.refreshActiveMediaPicker(newId);
                    }
                    const mgr = window.newsManager || window.publicationManager;
                    if (mgr && typeof mgr.loadData === 'function') {
                        mgr.loadData(true);
                    }
                });
            }

            if (typeof showToast === 'function') {
                showToast(displayName, 'success');
            } else if (window.AFModalEngine && typeof window.AFModalEngine.showToast === 'function') {
                window.AFModalEngine.showToast(displayName, 'success');
            }
        } else {
            const errMsg = (res && res.message) ? res.message : 'Upload failed';
            if (typeof showToast === 'function') {
                showToast(errMsg, 'error');
            } else if (window.AFModalEngine && typeof window.AFModalEngine.showToast === 'function') {
                window.AFModalEngine.showToast(errMsg, 'error');
            }
        }
    })
    .catch(err => {
        console.error('Direct attachment upload error:', err);
        if (typeof showToast === 'function') {
            showToast('Upload error', 'error');
        }
    })
    .finally(() => {
        if (addBtn) {
            addBtn.classList.remove('is-loading');
            addBtn.innerHTML = '<i class="bi bi-plus-lg"></i>';
        }
        fileInput.value = '';
    });
};

function initSelectPickerSheets(root = document) {
    const selects = root.querySelectorAll('select[data-picker-sheet="category"]');

    selects.forEach(select => {
        if (!select.id || select.dataset.pickerReady === 'true') {
            updateSelectPickerTrigger(select.id);
            return;
        }

        select.dataset.pickerReady = 'true';
        updateSelectPickerTrigger(select.id);

        select.addEventListener('change', () => {
            updateSelectPickerTrigger(select.id);
            if (activeSelectPickerId === select.id) {
                renderSelectPickerOptions();
            }
        });

        const trigger = getSelectPickerTrigger(select.id);
        if (trigger) {
            trigger.addEventListener('click', () => openSelectPickerSheet(select.id));
        }

        const langSourceId = select.dataset.pickerLangSource;
        const langSource = langSourceId ? document.getElementById(langSourceId) : null;
        if (langSource) {
            langSource.addEventListener('change', () => {
                syncSelectPickerSelectionWithLang(select);
                if (activeSelectPickerId === select.id) {
                    renderSelectPickerOptions();
                }
            });
        }

        syncSelectPickerSelectionWithLang(select);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initSelectPickerSheets();
});

function selectWizardCategory(name, id) {
    const videoEmbedWizard = document.getElementById('videoEmbedWizard');
    if (videoEmbedWizard && videoEmbedWizard.classList.contains('active')) {
        // Video Wizard Context
        if (typeof videoEmbedWizardData !== 'undefined') {
            videoEmbedWizardData.category = { id, name };
            const label = document.getElementById('videoEmbedWizardSelectedCategory');
            const hidden = document.getElementById('videoEmbedWizardHiddenCategory');
            if (label) {
                label.innerText = name;
                label.style.color = 'var(--md-sys-color-on-surface)';
            }
            if (hidden) hidden.value = id;
            toggleWizardCategorySheet();
            updateVideoEmbedWizardUI();
        }
    } else {
        // Content Wizard Context (Default)
        if (typeof wizardData !== 'undefined') {
            wizardData.category = { id, name };
            const label = document.getElementById('wizardSelectedCategory');
            const hidden = document.getElementById('wizardHiddenCategory') || document.getElementById('editCategoryId');
            if (label) {
                label.innerText = name;
                label.style.color = 'var(--md-sys-color-on-surface)';
            }
            if (hidden) hidden.value = id;
            toggleWizardCategorySheet();
            updateWizardUI(); // Trigger button update
            saveWizardState(); // Save state
        }
    }
}

// Step 8: Publish Status Selection
function selectWizardPublishStatus(el, val) {
    // Remove active from all cards in wizardStep8's selection-grid only
    const step8 = document.getElementById('wizardStep8');
    if (step8) {
        step8.querySelectorAll('.selection-grid .selection-card').forEach(c => c.classList.remove('active'));
    }
    el.classList.add('active');

    // Update hidden input
    const hidden = document.getElementById('wizardPublishHidden');
    if (hidden) hidden.value = val;

    // Update wizardData
    wizardData.published = (val === 'y');
    saveWizardState();
}

// Step 7: Image Preview
let wizardImageUploader = null;
let editImageUploader = null;

function previewWizardImage(input) {
    const file = input?.files?.[0];
    if (file) {
        setWizardImageFile(file, { syncInput: false });
    }
}

function triggerWizardImagePicker() {
    const input = document.getElementById('wizardImgInput');
    if (input) {
        input.click();
    }
}

function getWizardImageI18n() {
    const shell = document.getElementById('wizardImageShell');
    const data = shell ? shell.dataset : {};
    const selectedNone = adminLang?.wizard?.summary?.selected_none || '';
    const imageLabel = adminLang?.wizard?.step?.image || '';
    const changeImage = adminLang?.wizard?.label?.change_image || '';

    return {
        dropTitle: data.dropTitle || '',
        dropDescription: data.dropDescription || '',
        uploadLink: data.uploadLink || '',
        linkSeparator: data.linkSeparator || '',
        linkPlaceholder: data.linkPlaceholder || '',
        linkAction: data.linkAction || '',
        hintLoading: data.hintLoading || '',
        hintInvalidType: data.hintInvalidType || '',
        hintNoDropData: data.hintNoDropData || '',
        hintRemoteFailed: data.hintRemoteFailed || '',
        emptyName: data.emptyName || selectedNone,
        emptyMeta: data.emptyMeta || '',
        imageLabel,
        changeImage
    };
}

function setWizardImageInputFile(file) {
    const uploader = wizardImageUploader || initializeWizardImageInteractions();
    if (uploader && file) {
        uploader.setFile(file);
    }
}

function formatWizardFileSize(size) {
    if (!Number.isFinite(size) || size <= 0) {
        return '0 KB';
    }

    const units = ['B', 'KB', 'MB', 'GB'];
    let value = size;
    let unitIndex = 0;

    while (value >= 1024 && unitIndex < units.length - 1) {
        value /= 1024;
        unitIndex += 1;
    }

    const precision = unitIndex === 0 ? 0 : 1;
    return `${value.toFixed(precision)} ${units[unitIndex]}`;
}

function resolveImageUploaderElements(config = {}) {
    const root = config.root || (config.rootId ? document.getElementById(config.rootId) : null);
    const input = config.input || (config.inputId ? document.getElementById(config.inputId) : null);
    const preview = config.preview || (config.previewId ? document.getElementById(config.previewId) : null);
    const emptyState = config.emptyState || (config.emptyStateId ? document.getElementById(config.emptyStateId) : null);
    const previewCard = config.previewCard || (config.previewCardId ? document.getElementById(config.previewCardId) : null);
    const fileName = config.fileName || (config.fileNameId ? document.getElementById(config.fileNameId) : null);
    const fileDetails = config.fileDetails || (config.fileDetailsId ? document.getElementById(config.fileDetailsId) : null);
    const dropzone = config.dropzone || (config.dropzoneId ? document.getElementById(config.dropzoneId) : null);
    const urlInput = config.urlInput || (config.urlInputId ? document.getElementById(config.urlInputId) : null);
    const urlImportBtn = config.urlImportBtn || (config.urlImportBtnId ? document.getElementById(config.urlImportBtnId) : null);
    const divider = config.divider || (root ? root.querySelector('.wizard-image-divider') : null);
    const urlRow = config.urlRow || (root ? root.querySelector('.wizard-image-url-row') : null);

    if (urlInput && !urlInput.dataset.defaultPlaceholder) {
        urlInput.dataset.defaultPlaceholder = urlInput.placeholder || '';
    }

    return {
        root,
        input,
        preview,
        emptyState,
        previewCard,
        fileName,
        fileDetails,
        dropzone,
        urlInput,
        urlImportBtn,
        divider,
        urlRow
    };
}

function createImageUploader(config = {}) {
    const elements = resolveImageUploaderElements(config);
    if (!elements.root || !elements.input || !elements.dropzone) {
        return null;
    }

    let bound = false;

    const getI18n = () => (typeof config.getI18n === 'function' ? config.getI18n() : {});

    const setInputFile = (file) => {
        if (!elements.input || !file) {
            return;
        }

        const transfer = new DataTransfer();
        transfer.items.add(file);
        elements.input.files = transfer.files;
    };

    const showFeedback = (message, type = 'default') => {
        if (!elements.urlInput) {
            return;
        }

        if (type === 'error') {
            elements.urlInput.value = '';
            elements.urlInput.placeholder = message;
            elements.urlInput.dataset.state = 'error';
            return;
        }

        elements.urlInput.dataset.state = '';
        elements.urlInput.placeholder = getI18n().linkPlaceholder || elements.urlInput.dataset.defaultPlaceholder || '';
    };

    const notifyChange = (payload) => {
        if (typeof config.onChange === 'function') {
            config.onChange(payload, api);
        }
    };

    const applyPreviewState = ({ src, file, name, metaText, source = 'new' }) => {
        const i18n = getI18n();

        if (elements.preview) {
            elements.preview.src = src;
            elements.preview.hidden = false;
        }
        if (elements.emptyState) {
            elements.emptyState.hidden = true;
        }
        if (elements.previewCard) {
            elements.previewCard.hidden = false;
        }
        if (elements.divider) {
            elements.divider.hidden = true;
        }
        if (elements.urlRow) {
            elements.urlRow.hidden = true;
        }
        if (elements.fileName) {
            elements.fileName.textContent = name || i18n.emptyName || '';
        }
        if (elements.fileDetails) {
            if (metaText) {
                elements.fileDetails.textContent = metaText;
            } else {
                const details = [];
                if (file?.type) {
                    details.push(file.type);
                }
                details.push(formatWizardFileSize(file?.size || 0));
                elements.fileDetails.textContent = details.join(' • ');
            }
        }
        if (elements.dropzone) {
            elements.dropzone.classList.add('has-image');
        }
        elements.root.classList.add('has-image');
        if (elements.urlInput) {
            elements.urlInput.value = '';
        }

        showFeedback('', 'success');
        notifyChange({
            file: file || null,
            source,
            name: name || file?.name || i18n.emptyName || '',
            type: file?.type || '',
            size: file?.size || 0
        });
    };

    const reset = (options = {}) => {
        if (!options.preserveInput && elements.input) {
            elements.input.value = '';
        }
        if (elements.preview) {
            elements.preview.removeAttribute('src');
            elements.preview.hidden = true;
        }
        if (elements.emptyState) {
            elements.emptyState.hidden = false;
        }
        if (elements.previewCard) {
            elements.previewCard.hidden = true;
        }
        if (elements.divider) {
            elements.divider.hidden = false;
        }
        if (elements.urlRow) {
            elements.urlRow.hidden = false;
        }
        if (elements.fileName) {
            elements.fileName.textContent = getI18n().emptyName || '';
        }
        if (elements.fileDetails) {
            elements.fileDetails.textContent = getI18n().emptyMeta || '';
        }
        if (elements.dropzone) {
            elements.dropzone.classList.remove('has-image', 'is-dragover');
        }
        elements.root.classList.remove('has-image');
        if (elements.urlInput) {
            elements.urlInput.value = '';
        }

        showFeedback('', 'default');
        notifyChange({ file: null, source: options.source || 'reset' });
    };

    const setFile = (file, options = {}) => {
        if (!file) {
            return;
        }

        const i18n = getI18n();
        if (!file.type || !file.type.startsWith('image/')) {
            showFeedback(i18n.hintInvalidType, 'error');
            return;
        }

        if (options.syncInput !== false) {
            setInputFile(file);
        }

        const reader = new FileReader();
        reader.onload = function (event) {
            applyPreviewState({
                src: event.target.result,
                file,
                name: file.name || i18n.emptyName,
                source: options.source || 'new'
            });
        };
        reader.readAsDataURL(file);
    };

    const setFileFromUrl = async (url, options = {}) => {
        if (!url) {
            return;
        }

        const i18n = getI18n();
        showFeedback(i18n.hintLoading, 'loading');

        try {
            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const blob = await response.blob();
            if (!blob.type.startsWith('image/')) {
                throw new Error('Not an image');
            }

            const cleanUrl = url.split('?')[0];
            const fileNameFromUrl = cleanUrl.split('/').pop() || `${i18n.imageLabel || 'image'}.${(blob.type.split('/')[1] || 'png')}`;
            const file = new File([blob], fileNameFromUrl, { type: blob.type });
            setFile(file, { source: options.source || 'remote' });
        } catch (error) {
            console.warn('Remote image import failed:', error);
            showFeedback(i18n.hintRemoteFailed, 'error');
        }
    };

    const renderExistingImage = ({ src, name, metaText, type = '', size = 0 }) => {
        if (!src) {
            return false;
        }

        applyPreviewState({
            src,
            file: { type, size },
            name,
            metaText,
            source: 'existing'
        });

        return true;
    };

    const importFromInput = async () => {
        const rawValue = elements.urlInput ? elements.urlInput.value.trim() : '';
        const i18n = getI18n();

        if (!rawValue) {
            showFeedback(i18n.linkPlaceholder, 'error');
            return;
        }
        if (!/^https?:\/\//i.test(rawValue)) {
            showFeedback(i18n.hintRemoteFailed, 'error');
            return;
        }

        await setFileFromUrl(rawValue);
    };

    const bind = () => {
        if (bound || elements.dropzone.dataset.bound === 'true') {
            return;
        }

        bound = true;
        elements.dropzone.dataset.bound = 'true';

        elements.dropzone.addEventListener('click', (event) => {
            if (event.target.closest('.wizard-image-remove-btn')) {
                return;
            }
            if (typeof config.onTriggerPicker === 'function') {
                config.onTriggerPicker(api);
            } else {
                elements.input.click();
            }
        });

        elements.dropzone.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                if (typeof config.onTriggerPicker === 'function') {
                    config.onTriggerPicker(api);
                } else {
                    elements.input.click();
                }
            }
        });

        ['dragenter', 'dragover'].forEach((eventName) => {
            elements.dropzone.addEventListener(eventName, (event) => {
                event.preventDefault();
                event.stopPropagation();
                elements.dropzone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'dragend'].forEach((eventName) => {
            elements.dropzone.addEventListener(eventName, (event) => {
                event.preventDefault();
                event.stopPropagation();
                elements.dropzone.classList.remove('is-dragover');
            });
        });

        elements.dropzone.addEventListener('drop', async (event) => {
            event.preventDefault();
            event.stopPropagation();
            elements.dropzone.classList.remove('is-dragover');

            const files = Array.from(event.dataTransfer?.files || []).filter(file => file.type.startsWith('image/'));
            if (files.length) {
                setFile(files[0]);
                return;
            }

            const imageUrl = extractImageUrlFromDataTransfer(event.dataTransfer);
            if (imageUrl) {
                await setFileFromUrl(imageUrl);
                return;
            }

            showFeedback(getI18n().hintNoDropData, 'error');
        });

        document.addEventListener('paste', (event) => {
            if (typeof config.shouldHandlePaste === 'function' && !config.shouldHandlePaste(event, api)) {
                return;
            }

            const clipboardItems = Array.from(event.clipboardData?.items || []);
            const imageItem = clipboardItems.find(item => item.type && item.type.startsWith('image/'));
            if (imageItem) {
                const file = imageItem.getAsFile();
                if (file) {
                    event.preventDefault();
                    const ext = (file.type.split('/')[1] || 'png').replace('jpeg', 'jpg');
                    const namedFile = new File([file], `${getI18n().imageLabel || 'image'}.${ext}`, { type: file.type });
                    setFile(namedFile);
                }
                return;
            }

            const pastedText = event.clipboardData?.getData('text/plain') || '';
            if (/^https?:\/\//i.test(pastedText.trim())) {
                event.preventDefault();
                setFileFromUrl(pastedText.trim());
            }
        });

        if (elements.urlImportBtn) {
            elements.urlImportBtn.addEventListener('click', () => {
                importFromInput();
            });
        }

        if (elements.urlInput) {
            elements.urlInput.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    importFromInput();
                }
            });

            elements.urlInput.addEventListener('paste', () => {
                setTimeout(() => {
                    if (elements.urlInput.value.trim()) {
                        showFeedback('', 'default');
                    }
                }, 0);
            });
        }

        if (config.bindInputChange !== false) {
            elements.input.addEventListener('change', () => {
                const file = elements.input.files?.[0];
                if (file) {
                    setFile(file, { syncInput: false });
                }
            });
        }

        showFeedback('', 'default');
    };

    const api = {
        elements,
        bind,
        reset,
        setFile,
        setFileFromUrl,
        renderExistingImage,
        showFeedback,
        getSelectedFile: () => elements.input?.files?.[0] || null
    };

    return api;
}

window.createImageUploader = createImageUploader;

document.addEventListener('click', (event) => {
    const closeWizardBtn = event.target.closest('[data-action="close-content-wizard"]');
    if (closeWizardBtn) {
        event.preventDefault();
        if (window.location.pathname.endsWith('/add')) {
            window.location.href = closeWizardBtn.dataset.listUrl || '/';
        } else if (typeof closeWizard === 'function') {
            closeWizard();
        }
        return;
    }

    const submitWizardBtn = event.target.closest('[data-action="submit-content-wizard"]');
    if (submitWizardBtn) {
        event.preventDefault();
        if (typeof prepareAndSubmitWizard === 'function') {
            prepareAndSubmitWizard();
        }
        return;
    }

    const selectLangCard = event.target.closest('[data-action="select-wizard-lang"]');
    if (selectLangCard) {
        if (typeof selectWizardLang === 'function') {
            selectWizardLang(selectLangCard, selectLangCard.dataset.lang || '');
        }
        return;
    }

    const toggleCatSheet = event.target.closest('[data-action="toggle-wizard-category-sheet"]');
    if (toggleCatSheet) {
        if (typeof toggleWizardCategorySheet === 'function') {
            toggleWizardCategorySheet();
        }
        return;
    }

    const selectPublishCard = event.target.closest('[data-action="select-wizard-publish-status"]');
    if (selectPublishCard) {
        if (typeof selectWizardPublishStatus === 'function') {
            selectWizardPublishStatus(selectPublishCard, selectPublishCard.dataset.status || 'y');
        }
        return;
    }

    const prevStepBtn = event.target.closest('[data-action="prev-wizard-step"]');
    if (prevStepBtn) {
        event.preventDefault();
        if (typeof prevWizardStep === 'function') {
            prevWizardStep();
        }
        return;
    }

    const nextStepBtn = event.target.closest('[data-action="next-wizard-step"]');
    if (nextStepBtn) {
        event.preventDefault();
        if (typeof nextWizardStep === 'function') {
            nextWizardStep();
        }
        return;
    }

    const removeBtn = event.target.closest('[data-action="remove-uploader-image"]');
    if (removeBtn) {
        if (window.announcementWizard) {
            return;
        }
        event.preventDefault();
        event.stopPropagation();
        const shell = removeBtn.closest('.wizard-image-shell');
        if (!shell) return;
        if (shell.id === 'wizardImageShell') {
            removeWizardImage();
        } else if (shell.id === 'editImageShell') {
            if (window.announcementFormManager && typeof window.announcementFormManager.removeImage === 'function') {
                window.announcementFormManager.removeImage();
            } else {
                removeEditImage();
            }
        } else if (shell.id === 'listImageShell') {
            if (window.newsManager && typeof window.newsManager.handleListImageRemoveAction === 'function') {
                window.newsManager.handleListImageRemoveAction();
            } else if (window.contentManager && typeof window.contentManager.handleListImageRemoveAction === 'function') {
                window.contentManager.handleListImageRemoveAction();
            } else if (window.announcementsManager && typeof window.announcementsManager.handleListImageRemoveAction === 'function') {
                window.announcementsManager.handleListImageRemoveAction();
            }
        }
        return;
    }

    const dropzone = event.target.closest('[data-action="trigger-image-picker"]');
    if (dropzone && dropzone.dataset.bound !== 'true' && !window.announcementWizard) {
        const shell = dropzone.closest('.wizard-image-shell');
        const fileInput = shell ? shell.querySelector('input[type="file"]') : null;
        if (fileInput) {
            fileInput.click();
        }
    }
});

document.addEventListener('change', (event) => {
    const fileInput = event.target.closest('[data-action="uploader-file-input"]');
    if (!fileInput) return;
    if (window.announcementFormManager || window.announcementWizard) {
        return;
    }
    const shell = fileInput.closest('.wizard-image-shell');
    const dropzone = shell ? shell.querySelector('.wizard-image-dropzone') : null;
    if (dropzone && dropzone.dataset.bound === 'true') {
        return;
    }
    if (fileInput.id === 'wizardImgInput' && typeof previewWizardImage === 'function') {
        previewWizardImage(fileInput);
    } else if (fileInput.id === 'editImgInput' && typeof previewEditImage === 'function') {
        previewEditImage(fileInput);
    }
});

function showWizardImageFeedback(message, type = 'default') {
    const uploader = wizardImageUploader || initializeWizardImageInteractions();
    if (uploader) {
        uploader.showFeedback(message, type);
    }
}

function renderWizardImagePreview(file, src) {
    const uploader = wizardImageUploader || initializeWizardImageInteractions();
    if (uploader && file) {
        uploader.setFile(file, { syncInput: false });
        if (src && uploader.elements.preview) {
            uploader.elements.preview.src = src;
        }
    }
}

function setWizardImageFile(file, options = {}) {
    const uploader = wizardImageUploader || initializeWizardImageInteractions();
    if (uploader) {
        uploader.setFile(file, options);
    }
}

async function setWizardImageFromUrl(url) {
    const uploader = wizardImageUploader || initializeWizardImageInteractions();
    if (uploader) {
        await uploader.setFileFromUrl(url);
    }
}

function removeWizardImage() {
    const uploader = wizardImageUploader || initializeWizardImageInteractions();
    if (uploader) {
        uploader.reset();
    }
}

window.previewWizardImage = previewWizardImage;
window.triggerWizardImagePicker = triggerWizardImagePicker;
window.removeWizardImage = removeWizardImage;

function extractImageUrlFromDataTransfer(dataTransfer) {
    if (!dataTransfer) {
        return '';
    }

    const uriList = dataTransfer.getData('text/uri-list');
    if (uriList) {
        const url = uriList.split('\n').map(item => item.trim()).find(item => item && !item.startsWith('#'));
        if (url) {
            return url;
        }
    }

    const html = dataTransfer.getData('text/html');
    if (html) {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const img = doc.querySelector('img');
        if (img && img.src) {
            return img.src;
        }
    }

    const text = dataTransfer.getData('text/plain');
    if (text && /^https?:\/\//i.test(text.trim())) {
        return text.trim();
    }

    return '';
}

function initializeWizardImageInteractions() {
    if (!wizardImageUploader) {
        wizardImageUploader = createImageUploader({
            rootId: 'wizardImageShell',
            inputId: 'wizardImgInput',
            previewId: 'wizardImgPreview',
            emptyStateId: 'wizardImageEmptyState',
            previewCardId: 'wizardImagePreviewCard',
            fileNameId: 'wizardImageFileName',
            fileDetailsId: 'wizardImageFileDetails',
            dropzoneId: 'wizardImageDropzone',
            urlInputId: 'wizardImageUrlInput',
            urlImportBtnId: 'wizardImageUrlImportBtn',
            getI18n: getWizardImageI18n,
            onTriggerPicker: () => triggerWizardImagePicker(),
            shouldHandlePaste: () => wizardCurrentStep === 7,
            onChange: ({ file, name, type, size }) => {
                wizardData.image = file ? { name, type, size } : null;
            }
        });
    }

    wizardImageUploader?.bind();
    return wizardImageUploader;
}

function triggerEditImagePicker() {
    const input = document.getElementById('editImgInput');
    if (input) {
        input.click();
    }
}

function getEditImageI18n() {
    const shell = document.getElementById('editImageShell');
    const data = shell ? shell.dataset : {};
    const selectedNone = adminLang?.wizard?.summary?.selected_none || '';
    const imageLabel = adminLang?.wizard?.step?.image || '';

    return {
        dropTitle: data.dropTitle || '',
        dropDescription: data.dropDescription || '',
        uploadLink: data.uploadLink || '',
        linkSeparator: data.linkSeparator || '',
        linkPlaceholder: data.linkPlaceholder || '',
        linkAction: data.linkAction || '',
        hintLoading: data.hintLoading || '',
        hintInvalidType: data.hintInvalidType || '',
        hintNoDropData: data.hintNoDropData || '',
        hintRemoteFailed: data.hintRemoteFailed || '',
        emptyName: data.emptyName || selectedNone,
        emptyMeta: data.emptyMeta || '',
        imageLabel
    };
}

function showEditImageFeedback(message, type = 'default') {
    const uploader = editImageUploader || initializeEditImageInteractions();
    if (uploader) {
        uploader.showFeedback(message, type);
    }
}

function setEditImageInputFile(file) {
    const uploader = editImageUploader || initializeEditImageInteractions();
    if (uploader && file) {
        uploader.setFile(file);
    }
}

function renderEditImagePreview(file, src) {
    const uploader = editImageUploader || initializeEditImageInteractions();
    if (uploader && file) {
        uploader.setFile(file, { syncInput: false });
        if (src && uploader.elements.preview) {
            uploader.elements.preview.src = src;
        }
    }
}

function renderEditExistingImage() {
    const shell = document.getElementById('editImageShell');
    if (!shell || shell.dataset.existingImage !== 'true' || !shell.dataset.existingSrc) {
        return false;
    }

    const uploader = editImageUploader || initializeEditImageInteractions();
    if (!uploader) {
        return false;
    }

    const rendered = uploader.renderExistingImage({
        src: shell.dataset.existingSrc,
        name: shell.dataset.existingName || getEditImageI18n().emptyName,
        metaText: shell.dataset.existingMeta || getEditImageI18n().emptyMeta
    });

    shell.dataset.pendingImage = 'false';
    return rendered;
}

function previewEditImage(input) {
    const file = input?.files?.[0];
    if (!file) {
        return;
    }

    if (!document.getElementById('editImageShell')) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const img = document.getElementById('editImgPreview');
            const area = document.querySelector('.image-upload-area');
            if (img) {
                img.src = e.target.result;
                img.style.display = 'block';
            } else if (area) {
                area.innerHTML = `<img id="editImgPreview" src="${e.target.result}" style="width:100%; height:auto; border-radius:8px;">
                                 <input type="file" name="image" id="editImgInput" hidden onchange="previewEditImage(this)">
                                 <button type="button" class="remove-img-btn" onclick="event.stopPropagation(); removeEditImage()"><i class="bi bi-trash"></i></button>`;
            }
            const removeBtn = document.querySelector('.remove-img-btn');
            if (removeBtn) {
                removeBtn.style.display = 'flex';
            }
        };
        reader.readAsDataURL(file);
        return;
    }

    setEditImageFile(file, { syncInput: false });
}

function setEditImageFile(file, options = {}) {
    const uploader = editImageUploader || initializeEditImageInteractions();
    if (uploader) {
        uploader.setFile(file, options);
    }
}

async function setEditImageFromUrl(url) {
    const uploader = editImageUploader || initializeEditImageInteractions();
    if (uploader) {
        await uploader.setFileFromUrl(url);
    }
}

function removeEditImage() {
    const shell = document.getElementById('editImageShell');
    if (shell) {
        const input = document.getElementById('editImgInput');
        const deleteFlag = document.getElementById('delete_image_flag');
        const hadPendingImage = shell.dataset.pendingImage === 'true';

        if (hadPendingImage) {
            if (input) {
                input.value = '';
            }

            if (deleteFlag) {
                deleteFlag.remove();
            }

            if (renderEditExistingImage()) {
                showEditImageFeedback('', 'default');
                return;
            }
        }

        if (shell.dataset.existingImage === 'true') {
            Swal.fire({
                title: shell.dataset.removeTitle || '',
                text: shell.dataset.removeConfirm || '',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ba1a1a',
                cancelButtonColor: '#6e7881',
                confirmButtonText: shell.dataset.removeConfirmText || 'Yes',
                cancelButtonText: shell.dataset.removeCancelText || 'No'
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                const formData = new URLSearchParams();
                formData.append('id', shell.dataset.removeId || '');

                fetch(shell.dataset.removeUrl || '', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData
                })
                    .then(r => r.text())
                    .then(data => {
                        if (data.trim() !== 'Success') {
                            Swal.fire(shell.dataset.errorTitle || 'Error', data, 'error');
                            return;
                        }

                        shell.dataset.existingImage = 'false';
                        shell.dataset.existingSrc = '';
                        shell.dataset.existingName = '';
                        shell.dataset.existingMeta = '';
                        shell.dataset.pendingImage = 'false';

                        if (input) {
                            input.value = '';
                        }

                        resetEditImageShell();
                        Swal.fire(shell.dataset.successTitle || '', shell.dataset.successMessage || '', 'success');
                    });
            });
            return;
        }

        resetEditImageShell();
        return;
    }

    const img = document.getElementById('editImgPreview');
    const input = document.getElementById('editImgInput');
    const removeBtn = document.querySelector('.remove-img-btn');
    const area = document.querySelector('.image-upload-area');

    if (input) input.value = '';

    // Add a hidden input to signal deletion to backend if needed
    let delInput = document.getElementById('delete_image_flag');
    if (!delInput) {
        delInput = document.createElement('input');
        delInput.type = 'hidden';
        delInput.name = 'delete_image';
        delInput.id = 'delete_image_flag';
        delInput.value = '1';
        document.getElementById('editForm').appendChild(delInput);
    }

    // Reset area UI
    if (area) {
        area.innerHTML = `
            <i class="bi bi-cloud-arrow-up-fill upload-icon"></i>
            <p>${typeof adminLang !== 'undefined' ? adminLang.wizard.label.upload_text : 'Upload Image'}</p>
            <input type="file" name="image" id="editImgInput" hidden onchange="previewEditImage(this)">
        `;
    }
}

function resetEditImageShell() {
    const shell = document.getElementById('editImageShell');
    if (!shell) {
        return;
    }
    const deleteFlag = document.getElementById('delete_image_flag');
    shell.dataset.pendingImage = 'false';

    if (deleteFlag) {
        deleteFlag.remove();
    }

    const uploader = editImageUploader || initializeEditImageInteractions();
    uploader?.reset();
}

function initializeEditImageInteractions() {
    const shell = document.getElementById('editImageShell');
    if (!shell) {
        return null;
    }

    if (!editImageUploader) {
        editImageUploader = createImageUploader({
            rootId: 'editImageShell',
            inputId: 'editImgInput',
            previewId: 'editImgPreview',
            emptyStateId: 'editImageEmptyState',
            previewCardId: 'editImagePreviewCard',
            fileNameId: 'editImageFileName',
            fileDetailsId: 'editImageFileDetails',
            dropzoneId: 'editImageDropzone',
            urlInputId: 'editImageUrlInput',
            urlImportBtnId: 'editImageUrlImportBtn',
            getI18n: getEditImageI18n,
            onTriggerPicker: () => triggerEditImagePicker(),
            shouldHandlePaste: () => {
                const activeElement = document.activeElement;
                return !activeElement || activeElement === document.body || shell.contains(activeElement);
            },
            onChange: ({ file, source }) => {
                const deleteFlag = document.getElementById('delete_image_flag');
                if (file && deleteFlag) {
                    deleteFlag.remove();
                }

                shell.dataset.pendingImage = file && source !== 'existing' ? 'true' : 'false';
            }
        });
    }

    editImageUploader.bind();

    if (shell.dataset.existingImage === 'true') {
        renderEditExistingImage();
    } else {
        resetEditImageShell();
    }

    return editImageUploader;
}

window.triggerEditImagePicker = triggerEditImagePicker;
window.initializeEditImageInteractions = initializeEditImageInteractions;

function updateSummary() {
    const langSum = document.getElementById('summaryLang');
    if (langSum) langSum.innerText = wizardData.lang;

    const catSum = document.getElementById('summaryCategory');
    if (catSum) catSum.innerText = wizardData.category ? wizardData.category.name : (safeAdminLang?.wizard?.summary?.selected_none || '');
}

// Wizard Submission Logic
function prepareAndSubmitWizard() {
    console.log('prepareAndSubmitWizard() called');

    const btnFinish = document.getElementById('btnWizardFinish');
    const introInput = document.getElementById('wizardIntroInput');
    const introVal = introInput ? introInput.value.trim() : (wizardData.intro ? wizardData.intro.trim() : '');
    const introLen = introVal.length;

    // Validate intro / deck length (must be between 90 and 120 characters)
    if (introLen < 90 || introLen > 120) {
        if (btnFinish) {
            btnFinish.disabled = false;
            btnFinish.innerText = safeAdminLang?.wizard?.step?.finish || 'Finish';
        }
        const msg = document.getElementById('introValidationMsg');
        if (msg) msg.style.display = 'block';
        if (typeof showSnackbar === 'function') {
            const alertMsg = safeAdminLang?.validator?.deck_length || 'Please enter an introductory text between 90 and 120 characters.';
            showSnackbar(alertMsg, 'warning');
        }
        if (typeof wizardCurrentStep !== 'undefined' && wizardCurrentStep !== 5) {
            wizardCurrentStep = 5;
            updateWizardUI();
        }
        if (introInput) introInput.focus();
        return;
    }

    if (btnFinish) {
        btnFinish.disabled = true;
        btnFinish.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ...';
    }

    const formData = new FormData();
    // Use wizardData to get language and category which are state-managed
    formData.append('lang', wizardData.lang);
    formData.append('category_id', wizardData.category ? wizardData.category.id : 0);

    // Get visible inputs directly from DOM
    const titleInput = document.getElementById('wizardTitleInput');
    const dateInput = document.getElementById('wizardDateInput');

    // For TinyMCE or Textarea
    let contentVal = wizardData.content || ''; // Fallback to wizardData
    try {
        const contentArea = document.getElementById('wizardContentTextarea');
        if (typeof tinymce !== 'undefined' && tinymce.get('wizardContentTextarea')) {
            const editor = tinymce.get('wizardContentTextarea');
            if (editor && typeof editor.getContent === 'function') {
                contentVal = editor.getContent();
            }
        } else if (contentArea && contentArea.value) {
            contentVal = contentArea.value;
        }
    } catch (e) {
        console.warn('TinyMCE getContent error:', e);
        // Use wizardData.content as fallback
    }

    formData.append('name', titleInput ? titleInput.value : '');
    formData.append('created_at', dateInput ? dateInput.value.replace('T', ' ') : ''); // Backend expects 'created_at' and Y-m-d H:i format
    formData.append('summary', introInput ? introInput.value : '');
    formData.append('content', contentVal);

    // SEO Fields - backend requires these
    const titleValue = titleInput ? titleInput.value : '';
    const activeWizardLang = (typeof wizardData !== 'undefined' && wizardData.lang) ? wizardData.lang : getCurrentLocale();
    formData.append('seo', makeSeoValue(titleValue, activeWizardLang));
    formData.append('title', titleValue);
    formData.append('keywords', makeKeywordsValue(titleValue, activeWizardLang));

    // description - max 120 characters from intro
    const introValue = introInput ? introInput.value : '';
    formData.append('description', introValue.substring(0, 120));

    // Image
    const fileInput = document.getElementById('wizardImgInput');
    if (fileInput && fileInput.files[0]) {
        formData.append('image', fileInput.files[0]);
    }

    // Publish Status - from hidden input (selection cards)
    const pubHidden = document.getElementById('wizardPublishHidden');
    formData.append('status', (pubHidden && pubHidden.value === 'y') ? 'y' : 'n');

    // Display & Homepage Flags - validator(s) expect these enum values (y/n)
    formData.append('deck', 'n');
    formData.append('highlighted', 'n');
    formData.append('show_on_homepage', 'y');

    // Integer fields - must be 0, not empty string
    formData.append('gallery_id', '0');
    formData.append('file_id', '0');
    formData.append('acl_id', '0');
    formData.append('menu', '0');
    formData.append('video', '');

    // AJAX flag - backend reads this to know JSON response is needed
    formData.append('ajax_request', '1');

    // Required Backend Flags
    formData.append('add', 'Submit');

    // Include cropped image if exists
    if (typeof wizardCroppedBlob !== 'undefined' && wizardCroppedBlob) {
        formData.append('image', wizardCroppedBlob, 'hero.jpg');
    }

    // Send AJAX
    const wizardModuleName = (typeof WIZARD_MODULE !== 'undefined' && WIZARD_MODULE) ? WIZARD_MODULE : 'mod_news';
    const url = (typeof SITE_URL !== 'undefined' ? SITE_URL : '/') + wizardModuleName + '/add';
    console.log('Sending to URL:', url);
    console.log('FormData entries:');
    for (let pair of formData.entries()) {
        console.log('  ', pair[0] + ': ' + (pair[1] instanceof File ? pair[1].name : pair[1]));
    }

    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.text())
        .then(data => {
            console.log("Raw response from wizard:", data);

            // Try to parse JSON
            let result;
            try {
                result = JSON.parse(data);
            } catch (e) {
                console.error("JSON parse error:", e, "Raw data:", data);
                const shortData = data.substring(0, 300);
                showSnackbar('Error: Invalid JSON response: ' + shortData, 'error', 8000);
                if (btnFinish) {
                    btnFinish.disabled = false;
                    btnFinish.innerText = safeAdminLang?.wizard?.step?.finish || 'Finish';
                }
                return;
            }

            // Check status
            if (result.status === 'success') {
                // FIRST: Set flag so TinyMCE sync won't re-save localStorage
                wizardSubmitted = true;

                // Clear state and close wizard BEFORE showing message
                clearWizardState();
                closeWizard();

                // Show success message via snackbar (non-blocking)
                const successMsg = result.message || safeAdminLang?.wizard?.messages?.success_sim || 'Content added successfully';
                showSnackbar(successMsg, 'success');

                // Redirect after delay
                setTimeout(() => {
                    if (typeof refreshContent === 'function') {
                        refreshContent();
                    } else if (typeof refreshContentList === 'function') {
                        refreshContentList();
                    } else if (result.redirect) {
                        window.location.href = result.redirect;
                    } else {
                        window.location.reload();
                    }
                }, 1500);
            } else {
                // Error - show message
                let errorMsg = result.message || safeAdminLang?.wizard?.messages?.error || 'Error';
                if (result.errors && Array.isArray(result.errors)) {
                    errorMsg = result.errors.join('\n');
                }
                showSnackbar(errorMsg, 'error', 6000);
                if (btnFinish) {
                    btnFinish.disabled = false;
                    btnFinish.innerText = safeAdminLang?.wizard?.step?.finish || 'Finish';
                }
            }
        })
        .catch(error => {
            console.error("Wizard error:", error);
            showSnackbar(safeAdminLang?.wizard?.messages?.error || 'Error: ' + error, 'error', 6000);
            if (btnFinish) {
                btnFinish.disabled = false;
                btnFinish.innerText = safeAdminLang?.wizard?.step?.finish || 'Finish';
            }
        });
}

// Add Listeners dynamically if needed
document.addEventListener('DOMContentLoaded', () => {
    initializeWizardImageInteractions();

    // Validation Listeners
    const titleInput = document.getElementById('wizardTitleInput');
    if (titleInput) {
        titleInput.addEventListener('input', function () {
            const locale = getCurrentLocale();
            const start = this.selectionStart;
            const end = this.selectionEnd;
            const originalVal = this.value;
            const normalizedVal = normalizeKurdishTypos(originalVal, locale);
            
            if (originalVal !== normalizedVal) {
                this.value = normalizedVal;
                this.setSelectionRange(start, end);
            }
            
            const len = this.value.length;
            const counter = document.getElementById('titleCharCounter');
            if (counter) counter.innerText = `${len} / 70`;

            wizardData.title = this.value;
            updateWizardSEOAutoFill(); // Trigger centralized SEO auto-fill
            updateWizardUI();
            saveWizardState();

            const msg = document.getElementById('titleValidationMsg');
            if (msg && checkWizardStepValidity(3)) msg.style.display = 'none';
        });
    }

    const introInput = document.getElementById('wizardIntroInput');
    if (introInput) {
        const updateIntroCounter = function (len) {
            const counter = document.getElementById('introCharCounter');
            if (counter) {
                counter.innerText = `${len} / 120`;
                counter.classList.remove('is-valid', 'is-warning', 'is-error');
                if (len >= 90 && len <= 120) {
                    counter.classList.add('is-valid');
                } else if (len > 120) {
                    counter.classList.add('is-error');
                } else if (len > 0) {
                    counter.classList.add('is-warning');
                }
            }
        };

        introInput.addEventListener('input', function () {
            const locale = getCurrentLocale();
            const start = this.selectionStart;
            const end = this.selectionEnd;
            const originalVal = this.value;
            const normalizedVal = typeof normalizeKurdishTypos === 'function' ? normalizeKurdishTypos(originalVal, locale) : originalVal;
            
            if (originalVal !== normalizedVal) {
                this.value = normalizedVal;
                this.setSelectionRange(start, end);
            }

            const len = this.value.length;
            updateIntroCounter(len);

            wizardData.intro = this.value;
            updateWizardSEOAutoFill(); // Trigger centralized SEO auto-fill
            updateWizardUI();
            saveWizardState();

            const msg = document.getElementById('introValidationMsg');
            if (msg && checkWizardStepValidity(5)) msg.style.display = 'none';
        });
    }

    const contentArea = document.getElementById('wizardContentTextarea');
    if (contentArea) {
        contentArea.addEventListener('input', function () {
            wizardData.content = this.value;
            updateWizardSEOAutoFill();
            saveWizardState();
        });
    }

    // Date Input Listener
    const dateInput = document.getElementById('wizardDateInput');
    if (dateInput) {
        // Sync initial value if any
        if (dateInput.value) wizardData.date = dateInput.value;

        const dateSync = function () {
            wizardData.date = this.value;
            saveWizardState(); // Save state
        };
        dateInput.addEventListener('change', dateSync);
        dateInput.addEventListener('input', dateSync);
    }

    // Wizard Finish Button Event Listener (better than inline onclick)
    const btnWizardFinish = document.getElementById('btnWizardFinish');
    if (btnWizardFinish) {
        btnWizardFinish.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('btnWizardFinish clicked!');
            prepareAndSubmitWizard();
        });
    }

    // Centralized SEO Auto-fill for Content Wizard
    function updateWizardSEOAutoFill() {
        const seoTitle = document.getElementById('wizardSeoTitleInput');
        const seoKeywords = document.getElementById('wizardSeoKeywordsInput');
        const seoDesc = document.getElementById('wizardSeoDescriptionInput');

        // Hidden fields
        const hSeoTitle = document.getElementById('title');
        const hSeoKeywords = document.getElementById('keywords');
        const hSeoDesc = document.getElementById('description');

        // Update Data and Inputs
        if (seoTitle && (seoTitle.dataset.autofilled !== 'false')) {
            wizardData.seo.title = wizardData.title;
            seoTitle.value = wizardData.title;
            seoTitle.dataset.autofilled = 'true';
            wizardData.seo.autofilled.title = 'true';
            if (hSeoTitle) hSeoTitle.value = wizardData.title;
        } else if (seoTitle) {
            wizardData.seo.title = seoTitle.value; // Manual sync
            if (hSeoTitle) hSeoTitle.value = seoTitle.value;
        }

        if (seoKeywords && (seoKeywords.dataset.autofilled !== 'false')) {
            const activeWizardLang = (typeof wizardData !== 'undefined' && wizardData.lang) ? wizardData.lang : getCurrentLocale();
            const keys = makeKeywordsValue(wizardData.title, activeWizardLang);
            wizardData.seo.keywords = keys;
            seoKeywords.value = keys;
            seoKeywords.dataset.autofilled = 'true';
            wizardData.seo.autofilled.keywords = 'true';
            if (hSeoKeywords) hSeoKeywords.value = keys;
        } else if (seoKeywords) {
            wizardData.seo.keywords = seoKeywords.value;
            if (hSeoKeywords) hSeoKeywords.value = seoKeywords.value;
        }

        if (seoDesc && (seoDesc.dataset.autofilled !== 'false')) {
            const desc = wizardData.intro ? wizardData.intro.trim() : '';
            const cleanDesc = desc.substring(0, 120);
            wizardData.seo.description = cleanDesc;
            seoDesc.value = cleanDesc;
            seoDesc.dataset.autofilled = 'true';
            wizardData.seo.autofilled.description = 'true';
            if (hSeoDesc) hSeoDesc.value = cleanDesc;
        } else if (seoDesc) {
            wizardData.seo.description = seoDesc.value;
            if (hSeoDesc) hSeoDesc.value = seoDesc.value;
        }
    }

    // Manual Edit Trackers for content SEO
    ['wizardSeoTitleInput', 'wizardSeoKeywordsInput', 'wizardSeoDescriptionInput'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', () => { // Changed to 'input' for better tracking
                el.dataset.autofilled = 'false';
                // Sync to wizardData immediately
                if (id === 'wizardSeoTitleInput') wizardData.seo.autofilled.title = 'false';
                if (id === 'wizardSeoKeywordsInput') wizardData.seo.autofilled.keywords = 'false';
                if (id === 'wizardSeoDescriptionInput') wizardData.seo.autofilled.description = 'false';
                updateWizardSEOAutoFill(); // Trigger sync to hidden fields
                saveWizardState();
            });
        }
    });

    // Connect FAB
    const fabBtns = document.querySelectorAll('.fab-mini');
    fabBtns.forEach(btn => {
        if (btn.getAttribute('data-action') === 'content-add') {
            btn.onclick = (e) => {
                e.preventDefault();
                e.stopPropagation();
                openWizard();
            };
        }
    });

    // Connect FAB Video
    const fabVideoBtns = document.querySelectorAll('.fab-mini[data-action="video-embed-add"]');
    fabVideoBtns.forEach(btn => {
        btn.onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            window.location.href = SITE_URL + 'mod_video_embed/add';
        };
    });

    // AUTO-RESTORE: If saved state exists and wizard is in DOM
    // This is for when F5 is pressed and page reloads
    const savedState = localStorage.getItem(WIZARD_STORAGE_KEY);
    const wizardElement = document.getElementById('contentWizard');

    // Check if wizard was just submitted (within last 10 seconds)
    const justSubmitted = localStorage.getItem('wizardJustSubmitted');
    if (justSubmitted) {
        const submittedTime = parseInt(justSubmitted, 10);
        const now = Date.now();
        // If submitted within last 10 seconds, clear flag and skip auto-restore
        if (now - submittedTime < 10000) {
            localStorage.removeItem('wizardJustSubmitted');
            localStorage.removeItem(WIZARD_STORAGE_KEY);
            console.log('Wizard auto-restore skipped: recently submitted');
        } else {
            // Flag is old, clear it
            localStorage.removeItem('wizardJustSubmitted');
        }
    }

    // Re-check savedState after potential cleanup
    const currentSavedState = localStorage.getItem(WIZARD_STORAGE_KEY);
    if (currentSavedState && wizardElement) {
        // State exists - open wizard automatically
        try {
            const state = JSON.parse(currentSavedState);
            // Time check
            const maxAge = 24 * 60 * 60 * 1000;
            if (Date.now() - state.timestamp < maxAge) {
                // State is still valid - open wizard
                openWizard();
            }
        } catch (e) {
            console.warn('Auto-restore error:', e);
        }
    }

    // TinyMCE Sync Interval - every 1 second
    setInterval(function () {
        if (typeof tinymce !== 'undefined') {
            const editor = tinymce.get('wizardContentTextarea');
            if (editor && !editor.isHidden()) {
                const content = editor.getContent();
                if (content !== wizardData.content) {
                    wizardData.content = content;
                    updateWizardUI();
                    saveWizardState();
                }
            }
        }
    }, 1000);
});



// SEARCH BAR LOGIC
const searchInput = document.getElementById('searchInput');
const clearSearchBtn = document.getElementById('clearSearchBtn');

if (searchInput) {
    searchInput.addEventListener('input', (e) => {
        if (e.target.value.length > 0) {
            clearSearchBtn.style.display = 'flex';
        } else {
            clearSearchBtn.style.display = 'none';
        }
    });
}

function clearSearch() {
    if (searchInput) {
        searchInput.value = '';
        searchInput.focus();
        clearSearchBtn.style.display = 'none';
    }
}

// FILTER LOGIC
const filterState = {
    sort: ADMIN_DEFAULT_SORT,
    sortCol: '6', // Default: Date (5+1)
    sortDir: 'desc', // Default: desc
    limit: '10',
    lang: ADMIN_DEFAULT_LANG,
    dateStart: '',
    dateEnd: ''
};

function selectChip(el, type) {
    // Remove active class from other chips in parent container
    const container = el.parentElement;
    container.querySelectorAll('.filter-chip').forEach(chip => {
        chip.classList.remove('active');
    });

    // Activate selected item
    el.classList.add('active');

    // Update State
    if (type) {
        filterState[type] = el.innerText;

        // If sorting, capture data attributes
        if (type === 'sort') {
            filterState.sortCol = el.getAttribute('data-sort-col') || '1';
            filterState.sortDir = el.getAttribute('data-sort-dir') || 'desc';
        }
    }
}

// Language Logic (Advanced - Content Focused / Single Select)
const allLanguages = safeAdminLang.languages || [];
const LANG_ALL = ADMIN_UI_ALL;

// Single Select Logic: String or null, not array
let tempSelectedLang = ADMIN_DEFAULT_LANG;

function openLangSheet() {
    const sheet = document.getElementById('lang-sheet');
    const overlay = document.getElementById('lang-sheet-overlay');

    // Sync logic: Load current filterState
    // filterState.lang is now a single string
    tempSelectedLang = filterState.lang;

    renderLangList();

    if (sheet && overlay) {
        sheet.classList.add('active');
        overlay.classList.add('active');
        overlay.style.display = 'block';
    }
}

function closeLangSheet() {
    const sheet = document.getElementById('lang-sheet');
    const overlay = document.getElementById('lang-sheet-overlay');
    if (sheet && overlay) {
        sheet.classList.remove('active');
        overlay.style.display = 'none';
        overlay.classList.remove('active');
    }
}


function filterLanguages() {
    const input = document.getElementById('langSearchInput');
    renderLangList(input.value);
}

function toggleLangSelection(code) {
    // Single Select Logic: Just set it
    tempSelectedLang = code;

    // Auto-confirm logic: No need to click confirm
    confirmLangSelection();
}

function confirmLangSelection() {
    // Save to main filter state
    filterState.lang = tempSelectedLang;
    updateMainLangSummary();
    closeLangSheet();
}

function updateMainLangSummary() {
    const container = document.getElementById('langSummaryContainer');
    if (!container) return;

    // Find the name of the current language
    let langName = '';

    // Resolve dynamic default name
    const defaultLangObj = allLanguages.find(l => l.code === ADMIN_DEFAULT_LANG);
    const defaultLangName = defaultLangObj ? defaultLangObj.name : ADMIN_DEFAULT_LANG;

    langName = defaultLangName; // Default fallback

    if (filterState.lang) {
        if (filterState.lang === LANG_ALL || filterState.lang === '') {
            langName = LANG_ALL;
        } else {
            const langObj = allLanguages.find(l => l.code === filterState.lang);
            if (langObj) {
                langName = langObj.name;
            } else {
                langName = filterState.lang;
            }
        }
    }

    // Add Edit Button with Language Name
    let html = `
        <button class="add-lang-btn" onclick="openLangSheet()">
            <i class="bi bi-pencil-fill" style="font-size: 12px; margin-right: 6px;"></i> ${langName}
        </button>
    `;

    container.innerHTML = html;
}

function clearFilters() {
    // Reset state visual only (In a real app, logic would reset too)
    document.querySelectorAll('.filter-chip').forEach(chip => chip.classList.remove('active'));

    // Set defaults active
    const chips = document.querySelectorAll('.filter-chip');
    chips.forEach(chip => {
        if (chip.innerText === ADMIN_DEFAULT_SORT || chip.innerText === '10') {
            chip.classList.add('active');
        }
    });

    // Reset Language to All
    filterState.lang = LANG_ALL; // Single string
    updateMainLangSummary();

    // Reset Dates
    // Reset Dates
    const inputs = document.querySelectorAll('.bottom-sheet input[type="date"]');
    inputs.forEach(input => input.value = '');

    // Reset Sort State
    filterState.sortCol = '6'; // Date
    filterState.sortDir = 'desc';

    alert(ADMIN_UI.filter_cleared);
}

function applyFilters() {
    closeSheet();

    // Update Hidden Form Inputs if they exist
    const filterLangInput = document.getElementById('filterLang');
    if (filterLangInput) {
        // If "All" is selected, send empty string or specific value
        filterLangInput.value = (filterState.lang === LANG_ALL) ? '' : filterState.lang;
    }

    const filterLengthInput = document.getElementById('filterLength');
    if (filterLengthInput) {
        filterLengthInput.value = parseInt(filterState.limit) || 10;
    }

    // Update Sort Hidden Inputs
    const filterSortColInput = document.getElementById('filterSortColumn');
    if (filterSortColInput) {
        filterSortColInput.value = filterState.sortCol || '1';
    }

    const filterSortDirInput = document.getElementById('filterSortDir');
    if (filterSortDirInput) {
        filterSortDirInput.value = filterState.sortDir || 'desc';
    }

    // Trigger Refresh
    if (typeof window.refreshContent === 'function') {
        window.refreshContent();
    } else {
        console.warn('refreshContent function not found');
        // Fallback for prototype
        let msg = `${ADMIN_UI.filter_applied}:\n`;
        msg += `- ${ADMIN_UI.sort_label}: ${filterState.sort}\n`;
        msg += `- ${ADMIN_UI.limit_label}: ${filterState.limit}\n`;
        msg += `- ${ADMIN_UI.lang_label}: ${filterState.lang}`;
        alert(msg);
    }
}

// Initialization
document.addEventListener('DOMContentLoaded', () => {
    // Initial UI updates
    updateMainLangSummary();

    // Close sheets when clicking overlay
    const overlay = document.getElementById('sheet-overlay');
    // Note: The ID in openSheet is 'sheet-overlay', but earlier code referenced 'lang-sheet-overlay'. 
    // We should be careful. The code at 573 refers to a variable `overlay` which is NOT defined in global scope!

    // Fix: Re-select overlay here
    if (overlay) {
        overlay.addEventListener('click', () => {
            closeSheet();
            closeFabMenu();
            const wizardOverlay = document.getElementById('wizard-overlay');
            if (wizardOverlay && wizardOverlay.classList.contains('active')) {
                // Wizard overlay closing logic if needed
            }
        });
    }
});

function renderLangList(filter = '') {
    const container = document.getElementById('langListContainer');
    if (!container) return;

    container.innerHTML = '';

    // Create wrapper for modern spacing
    const listWrapper = document.createElement('div');
    listWrapper.className = 'lang-list-modern';

    // 1. Add "ALL" Option explicitly
    // Check if filter matches "all" or is empty to show it
    const dateLocale = safeAdminLang.date_locale || 'en';
    const lowerFilter = filter.toLocaleLowerCase(dateLocale);
    const langAllName = ADMIN_UI.all || 'All';

    if (filter === '' || langAllName.toLocaleLowerCase().includes(lowerFilter)) {
        const isAllSelected = (tempSelectedLang === LANG_ALL || tempSelectedLang === '');

        const cardAll = document.createElement('div');
        cardAll.className = `lang-item-card ${isAllSelected ? 'selected' : ''}`;
        cardAll.id = 'lang_all'; // ID for potential debugging or specific styling
        cardAll.onclick = () => toggleLangSelection(LANG_ALL);

        // Avatar (Globe icon for All)
        const avatarAll = document.createElement('div');
        avatarAll.className = 'lang-avatar';
        avatarAll.innerHTML = '<i class="bi bi-globe" style="font-size: 14px;"></i>';

        // Info
        const infoAll = document.createElement('div');
        infoAll.className = 'lang-info';

        const nameElAll = document.createElement('div');
        nameElAll.className = 'lang-name-text';
        nameElAll.innerText = langAllName;

        const codeElAll = document.createElement('div');
        codeElAll.className = 'lang-code-text';
        codeElAll.innerText = '*';

        infoAll.appendChild(nameElAll);
        infoAll.appendChild(codeElAll);

        // Checkmark
        const checkAll = document.createElement('i');
        checkAll.className = 'bi bi-check-lg lang-check';

        // Assemble
        cardAll.appendChild(avatarAll);
        cardAll.appendChild(infoAll);
        cardAll.appendChild(checkAll);

        listWrapper.appendChild(cardAll);
    }

    // 2. Filter and Render other languages
    const filtered = allLanguages.filter(l => {
        const name = l.name.toLocaleLowerCase(dateLocale);
        const code = l.code.toLocaleLowerCase(dateLocale);

        // Removed Strict Filter for 'all' variants as we want them valid if they exist in DB, 
        // though usually 'all' is virtual.
        // if (code === 'all' || name.toLowerCase() === 'all') return false;

        return name.includes(lowerFilter) || code.includes(lowerFilter);
    });

    filtered.forEach(lang => {
        const isSelected = (tempSelectedLang === lang.code);

        const card = document.createElement('div');
        card.className = `lang-item-card ${isSelected ? 'selected' : ''}`;
        card.onclick = () => toggleLangSelection(lang.code);

        // Avatar (First 2 letters of code)
        const avatar = document.createElement('div');
        avatar.className = 'lang-avatar';
        avatar.innerText = lang.code.substring(0, 2).toUpperCase();

        // Info
        const info = document.createElement('div');
        info.className = 'lang-info';

        const nameEl = document.createElement('div');
        nameEl.className = 'lang-name-text';
        nameEl.innerText = lang.name;

        const codeEl = document.createElement('div');
        codeEl.className = 'lang-code-text';
        codeEl.innerText = lang.code;

        info.appendChild(nameEl);
        info.appendChild(codeEl);

        // Checkmark
        const check = document.createElement('i');
        check.className = 'bi bi-check-lg lang-check';

        // Assemble
        card.appendChild(avatar);
        card.appendChild(info);
        card.appendChild(check);

        listWrapper.appendChild(card);
    });

    container.appendChild(listWrapper);
}

// PAGINATION LOGIC
let currentPage = 1;
let totalItems = 124; // Mock total
let itemsPerPage = 10;

function updatePaginationUI() {
    // Parse limit from filterState
    itemsPerPage = parseInt(filterState.limit) || 10;

    const totalPages = Math.ceil(totalItems / itemsPerPage);

    // Ensure current page is valid
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    const startItem = (currentPage - 1) * itemsPerPage + 1;
    let endItem = currentPage * itemsPerPage;
    if (endItem > totalItems) endItem = totalItems;

    // Update Text
    const infoEl = document.getElementById('paginationInfo');
    if (infoEl) {
        infoEl.innerText = `${startItem} - ${endItem} / ${totalItems}`;
    }

    // Update Buttons
    const btnPrev = document.getElementById('btnPagePrev');
    const btnNext = document.getElementById('btnPageNext');

    if (btnPrev) btnPrev.disabled = (currentPage === 1);
    if (btnNext) btnNext.disabled = (currentPage === totalPages);
}

function nextPage() {
    const totalPages = Math.ceil(totalItems / itemsPerPage);
    if (currentPage < totalPages) {
        currentPage++;
        updatePaginationUI();
        // Simulate refresh or scroll to top
        const scrollFrame = document.querySelector('.app-scroll-frame');
        if (scrollFrame) scrollFrame.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

function prevPage() {
    if (currentPage > 1) {
        currentPage--;
        updatePaginationUI();
        const scrollFrame = document.querySelector('.app-scroll-frame');
        if (scrollFrame) scrollFrame.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

// Update pagination on load
document.addEventListener('DOMContentLoaded', () => {
    updatePaginationUI();
    // Initial Load
    setupPullToRefresh();
});


/* =========================================
   INFINITE SCROLL & PULL-TO-REFRESH LOGIC
   ========================================= */

let isLoading = false;
let ptrStartY = 0;
let ptrDistance = 0;
const PTR_THRESHOLD = 80; // Pixels to pull down to trigger refresh

const appScrollFrame = document.querySelector('.app-scroll-frame');
const mainContent = document.getElementById('mainContent');
const ptrSpinner = document.getElementById('ptr-spinner');
const infiniteLoader = document.getElementById('infinite-loader');

// Mock Data Generators - Replaced with Localized Mock Data
const mockTitles = ADMIN_MOCK.titles;
const mockCategories = ADMIN_MOCK.categories;
const mockAuthors = ADMIN_MOCK.authors;

function getRandomItem(arr) {
    if (!arr || arr.length === 0) return "Mock Item";
    return arr[Math.floor(Math.random() * arr.length)];
}

function getRandomDate() {
    const start = new Date(2023, 0, 1);
    const end = new Date();
    const d = new Date(start.getTime() + Math.random() * (end.getTime() - start.getTime()));
    return d.toLocaleDateString(safeAdminLang.date_locale || 'en');
}

function createCardElement(id) {
    const title = getRandomItem(mockTitles);
    const category = getRandomItem(mockCategories);
    const author = getRandomItem(mockAuthors);
    const date = getRandomDate();
    const views = Math.floor(Math.random() * 5000) + 100;

    // Status Logic
    const isPublished = Math.random() > 0.2;
    const statusText = isPublished ? ADMIN_STATUS.active : ADMIN_STATUS.draft;
    const statusColor = isPublished ? "var(--md-sys-color-primary)" : "#FFAB00";

    // Create Card with M3 Classes
    const card = document.createElement('div');
    card.className = 'm3-card'; // Using the class from style.css

    // Inner HTML Structure matching the expected layout in style.css
    card.innerHTML = `
        <div class="card-content-layout">
            <!-- Thumbnail Placeholder -->
            <div class="card-thumbnail" style="display:flex; align-items:center; justify-content:center; color:#999;">
                <i class="bi bi-image" style="font-size: 24px;"></i>
            </div>
            
            <div class="card-body-col">
                <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                     <div>
                        <div class="meta-row" style="margin-top:0; margin-bottom:4px;">
                            <span style="color:${statusColor}; font-weight:500;">${statusText}</span>
                            <span>•</span>
                            <span>${category}</span>
                        </div>
                        <h3 style="margin:0; font-size:16px; font-weight:500; line-height:1.4;">${title}</h3>
                     </div>
                     <button class="m3-icon-btn" style="margin-top:-8px; margin-right:-8px;" onclick="openContextMenu('${id}', '${title.replace(/'/g, "\\'")}', ${isPublished})">
                        <i class="bi bi-three-dots-vertical"></i>
                     </button>
                </div>

                <div class="meta-row" style="margin-top:8px;">
                     <span><i class="bi bi-calendar3" style="font-size:11px; margin-right:4px;"></i>${date}</span>
                     <span><i class="bi bi-eye" style="font-size:11px; margin-right:4px;"></i>${views}</span>
                     <span style="margin-left:auto;">${author}</span>
                </div>
            </div>
        </div>

    `;

    // Click handler for card interactions
    card.addEventListener('click', (e) => {
        // Handle Delete Button Specific Logic
        const deleteBtn = e.target.closest('.delete-btn');
        if (deleteBtn) {
            if (confirm(ADMIN_GENERAL.delete_confirm)) {
                card.style.opacity = '0';
                setTimeout(() => card.remove(), 300); // Animation delay
            }
            return;
        }

        // Generic Card Click
        if (!e.target.closest('button')) {
            // alert(ADMIN_GENERAL.card_click.replace('%s', title));
        }
    });

    return card;
}

function loadMoreContent(isRefresh = false) {
    // Disabled for production to prevents mock data injection
    return;

    /* Original Mock Logic - Kept for reference but disabled
    if (isLoading) return;
    isLoading = true;
    ...
    */
}

/* --------------------------
   Infinite Scroll Listener
   -------------------------- */
function setupInfiniteScroll() {
    if (!appScrollFrame) return;

    appScrollFrame.addEventListener('scroll', () => {
        const { scrollTop, scrollHeight, clientHeight } = appScrollFrame;

        // Load more when user is 100px from the bottom
        if (scrollTop + clientHeight >= scrollHeight - 100) {
            // Check limits (using mock total)
            const currentTotal = (currentPage - 1) * itemsPerPage;
            if (currentTotal < totalItems) {
                loadMoreContent(false);
            }
        }
    });
}

/* --------------------------
   Pull-to-Refresh Logic
   -------------------------- */
function setupPullToRefresh() {
    if (!appScrollFrame || !ptrSpinner) return;

    appScrollFrame.addEventListener('touchstart', (e) => {
        if (appScrollFrame.scrollTop === 0) {
            ptrStartY = e.touches[0].clientY;
        }
    }, { passive: true });

    appScrollFrame.addEventListener('touchmove', (e) => {
        if (isLoading) return;

        const currentY = e.touches[0].clientY;
        if (appScrollFrame.scrollTop <= 0) {
            // Only tracking if at top
            ptrDistance = currentY - ptrStartY;

            if (ptrDistance > 0) {
                // Resistance effect
                const move = Math.min(ptrDistance * 0.5, 100);
                ptrSpinner.style.height = `${move}px`;
                ptrSpinner.style.opacity = Math.min(move / 60, 1);
            }
        }
    }, { passive: true });

    appScrollFrame.addEventListener('touchend', (e) => {
        if (appScrollFrame.scrollTop <= 0 && ptrDistance > PTR_THRESHOLD && !isLoading) {
            // Trigger Refresh
            loadMoreContent(true);
        } else {
            // Snap back
            if (ptrDistance > 0) {
                ptrSpinner.style.height = '0px';
                ptrSpinner.style.opacity = '0';
            }
        }
        ptrDistance = 0;
    });
}


/* --------------------------
   VIDEO EMBED WIZARD LOGIC
   -------------------------- */
let videoEmbedWizardCurrentStep = 1;
let videoEmbedWizardTotalSteps = 8;
let videoEmbedWizardData = {
    lang: ADMIN_DEFAULT_LANG,
    category: null,
    embedCode: '',
    originalTitle: '',
    name: '',
    duration: '',
    date: '',
    content: '',
    image: null,
    seoTitle: '',
    seoKeywords: '',
    seoDescription: '',
    published: true,
    acl_id: 0
};

function openVideoEmbedWizard() {
    const wizard = document.getElementById('videoEmbedWizard');
    if (wizard) {
        wizard.classList.add('active');
        videoEmbedWizardCurrentStep = 1;
        updateVideoEmbedWizardUI();

        const fab = document.getElementById('fabContainer');
        if (fab) fab.classList.remove('active');

        saveVideoEmbedWizardState();
    }
}

function closeVideoEmbedWizard() {
    const wizard = document.getElementById('videoEmbedWizard');
    if (wizard) wizard.classList.remove('active');

    // We keep data in localStorage even if closed (unless submitted) 
    // but we should mark it as NOT active
    saveVideoEmbedWizardState(false);
}

function updateVideoEmbedWizardUI() {
    // Hide all steps
    document.querySelectorAll('#videoEmbedWizard .wizard-step').forEach(step => {
        step.classList.remove('active');
    });
    // Show current
    const currentStepEl = document.getElementById('videoEmbedWizardStep' + videoEmbedWizardCurrentStep);
    if (currentStepEl) currentStepEl.classList.add('active');

    // Update Header
    const counterEl = document.getElementById('videoEmbedWizardStepCounter');
    if (counterEl) counterEl.innerText = `${videoEmbedWizardCurrentStep}/${videoEmbedWizardTotalSteps}`;

    const stepTitles = [
        safeAdminLang?.video_embed?.wizard?.step?.language || '',
        safeAdminLang?.video_embed?.wizard?.step?.category || '',
        safeAdminLang?.video_embed?.wizard?.step?.embed || '',
        safeAdminLang?.video_embed?.wizard?.step?.details || '',
        safeAdminLang?.video_embed?.wizard?.step?.content || '',
        safeAdminLang?.video_embed?.wizard?.step?.image || '',
        safeAdminLang?.video_embed?.wizard?.step?.seo || '',
        safeAdminLang?.video_embed?.wizard?.step?.finish || ''
    ];

    const stepSubtitles = [
        safeAdminLang?.video_embed?.wizard?.subtitle?.language || '',
        safeAdminLang?.video_embed?.wizard?.subtitle?.category || '',
        safeAdminLang?.video_embed?.wizard?.subtitle?.embed || '',
        safeAdminLang?.video_embed?.wizard?.subtitle?.details || '',
        safeAdminLang?.video_embed?.wizard?.subtitle?.content || '',
        safeAdminLang?.video_embed?.wizard?.subtitle?.image || '',
        safeAdminLang?.video_embed?.wizard?.subtitle?.seo || '',
        safeAdminLang?.video_embed?.wizard?.subtitle?.finish || ''
    ];

    // Dynamic Title Update
    const titleEl = document.getElementById('videoEmbedWizardStepTitle');
    if (titleEl) {
        titleEl.innerText = stepTitles[videoEmbedWizardCurrentStep - 1] || '';
    }

    // Dynamic Step Headline & Subhead Update
    const curHeadline = document.getElementById(`videoEmbedWizardStepHeadline${videoEmbedWizardCurrentStep}`);
    const curSubhead = document.getElementById(`videoEmbedWizardStepSubhead${videoEmbedWizardCurrentStep}`);
    if (curHeadline) curHeadline.innerText = stepTitles[videoEmbedWizardCurrentStep - 1] || '';
    if (curSubhead) curSubhead.innerText = stepSubtitles[videoEmbedWizardCurrentStep - 1] || '';

    // Update Progress Bar
    const progress = (videoEmbedWizardCurrentStep / videoEmbedWizardTotalSteps) * 100;
    const progressFill = document.getElementById('videoEmbedWizardProgressFill');
    if (progressFill) progressFill.style.width = `${progress}%`;

    // Update Buttons
    const btnPrev = document.getElementById('btnVideoEmbedWizardPrev');
    const btnNext = document.getElementById('btnVideoEmbedWizardNext');
    const btnFinish = document.getElementById('btnVideoEmbedWizardFinish');

    if (btnPrev) btnPrev.disabled = (videoEmbedWizardCurrentStep === 1);

    if (videoEmbedWizardCurrentStep === videoEmbedWizardTotalSteps) {
        if (btnNext) btnNext.style.display = 'none';
        if (btnFinish) btnFinish.style.display = 'block';
        updateVideoEmbedSummary();
    } else {
        if (btnNext) btnNext.style.display = 'block';
        if (btnFinish) btnFinish.style.display = 'none';
    }

    // Auto-fill SEO when entering SEO step (Step 7)
    if (videoEmbedWizardCurrentStep === 7) {
        if (typeof updateVideoEmbedWizardSEOAutoFill === 'function') {
            updateVideoEmbedWizardSEOAutoFill();
        }
    }
}

function nextVideoEmbedWizardStep() {
    if (!validateVideoEmbedWizardStep(videoEmbedWizardCurrentStep)) return;

    if (videoEmbedWizardCurrentStep < videoEmbedWizardTotalSteps) {
        videoEmbedWizardCurrentStep++;
        updateVideoEmbedWizardUI();
        saveVideoEmbedWizardState();
    }
}

function prevVideoEmbedWizardStep() {
    if (videoEmbedWizardCurrentStep > 1) {
        videoEmbedWizardCurrentStep--;
        updateVideoEmbedWizardUI();
        saveVideoEmbedWizardState();
    }
}


// Video Wizard Validation
function selectVideoEmbedWizardCategory(name, id) {
    videoEmbedWizardData.category = { id, name };
    const label = document.getElementById('videoEmbedWizardSelectedCategory');
    const hidden = document.getElementById('videoEmbedWizardHiddenCategory');
    if (label) {
        label.innerText = name;
        label.style.color = 'var(--md-sys-color-on-surface)';
    }
    if (hidden) hidden.value = id;
    toggleWizardCategorySheet();
    updateVideoEmbedWizardUI();
    saveVideoEmbedWizardState();
}

function validateVideoEmbedWizardStep(step) {
    // Step 2: Category
    if (step === 2) {
        if (!videoEmbedWizardData.category) {
            toggleWizardCategorySheet();
            return false;
        }
    }
    // Step 3: Embed Code
    if (step === 3) {
        const embedInput = document.getElementById('videoEmbedInput');
        if (embedInput && embedInput.value.trim().length < 10) {
            embedInput.focus();
            return false;
        }
    }
    // Step 4: Name
    if (step === 4) {
        const nameInput = document.getElementById('videoNameInput');
        if (nameInput && nameInput.value.trim().length < 3) {
            nameInput.focus();
            return false;
        }
    }
    return true;
}

// Step 1: Language
function selectVideoEmbedWizardLang(el, lang) {
    document.querySelectorAll('#videoEmbedWizard .selection-card').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
    videoEmbedWizardData.lang = lang;
    const hidden = document.getElementById('videoEmbedWizardHiddenLang');
    if (hidden) hidden.value = lang;
    saveVideoEmbedWizardState();
}

// Video Wizard Image Preview
function previewVideoEmbedWizardImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const img = document.getElementById('videoEmbedWizardImgPreview');
            if (img) {
                img.src = e.target.result;
                img.style.display = 'block';
            }
            const label = document.getElementById('videoEmbedWizardUploadText');
            if (label) label.innerText = safeAdminLang?.video_embed?.wizard?.label?.change_image || '';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Video Wizard Summary
function updateVideoEmbedSummary() {
    const langSum = document.getElementById('videoEmbedSummaryLang');
    if (langSum) langSum.innerText = videoEmbedWizardData.lang;

    const catSum = document.getElementById('videoEmbedSummaryCategory');
    if (catSum) catSum.innerText = videoEmbedWizardData.category ? videoEmbedWizardData.category.name : (safeAdminLang?.video_embed?.wizard?.summary?.selected_none || '');

    const titleSum = document.getElementById('videoEmbedSummaryTitle');
    if (titleSum) titleSum.innerText = document.getElementById('videoNameInput')?.value || '-';
}

function prepareAndSubmitVideoEmbedWizard() {
    const btnFinish = document.querySelector('#videoEmbedWizard .m3-btn-text');
    if (btnFinish) {
        btnFinish.disabled = true;
        btnFinish.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ...';
    }

    const formData = new FormData();
    // Use hidden fields and Data object
    const langVal = document.getElementById('videoEmbedWizardHiddenLang')?.value || 'KU';
    const catVal = document.getElementById('videoEmbedWizardHiddenCategory')?.value || '0';
    const aclVal = document.getElementById('videoEmbedWizardHiddenAcl')?.value || '0';
    const statusVal = document.getElementById('videoEmbedWizardHiddenPublish')?.value || 'y';

    formData.append('lang', langVal);
    formData.append('category_id', catVal);
    formData.append('acl_id', aclVal);
    formData.append('status', statusVal);

    // Visible inputs
    const embedVal = document.getElementById('videoEmbedInput')?.value || '';
    const nameVal = document.getElementById('videoNameInput')?.value || '';
    const origVal = document.getElementById('videoOriginalTitleInput')?.value || '';
    const durationVal = (document.getElementById('videoDurationInput')?.value || '').trim();
    const dateVal = (document.getElementById('videoDateInput')?.value || '').replace('T', ' ');
    const contextVal = document.getElementById('videoEmbedWizardContentTextarea')?.value || '';

    formData.append('embed', embedVal);
    formData.append('name', nameVal);
    formData.append('original', origVal);
    formData.append('sure', durationVal);
    formData.append('created_at', dateVal);
    formData.append('content', contextVal);

    // SEO
    const seoTitle = document.getElementById('videoEmbedWizardSeoTitleInput')?.value || '';
    const seoKeywords = document.getElementById('videoEmbedWizardSeoKeywordsInput')?.value || '';
    const seoDescription = document.getElementById('videoEmbedWizardSeoDescriptionInput')?.value || '';

    formData.append('title', seoTitle);
    formData.append('keywords', seoKeywords);
    formData.append('description', seoDescription);

    // Image
    const fileInput = document.getElementById('videoEmbedWizardImgInput');
    if (fileInput && fileInput.files[0]) {
        formData.append('image', fileInput.files[0]);
    }

    // Required Backend Flags
    formData.append('add', 'Submit');
    formData.append('ajax_request', '1');

    const url = (typeof SITE_URL !== 'undefined' ? SITE_URL : '/') + 'mod_video_embed/add';

    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.text())
        .then(data => {
            let result;
            try {
                result = JSON.parse(data);
            } catch (e) {
                console.error("JSON parse error:", e);
                showSnackbar(safeAdminLang?.video_embed?.wizard?.messages?.error || 'Error: Invalid JSON response.', 'error');
                if (btnFinish) {
                    btnFinish.disabled = false;
                    btnFinish.innerText = safeAdminLang?.video_embed?.wizard?.button?.save || 'Save';
                }
                return;
            }

            if (result.status === 'success') {
                clearVideoEmbedWizardState();
                closeVideoEmbedWizard();
                showSnackbar(result.message || safeAdminLang?.video_embed?.wizard?.messages?.success || 'Video added successfully', 'success');
                // Redirect after a short delay
                setTimeout(() => {
                    window.location.href = result.redirect || (SITE_URL + 'mod_video_embed/list');
                }, 1000);
            } else {
                showSnackbar(result.message || safeAdminLang?.video_embed?.wizard?.messages?.error || 'An error occurred', 'error', 8000);
                if (btnFinish) {
                    btnFinish.disabled = false;
                    btnFinish.innerText = safeAdminLang?.video_embed?.wizard?.button?.save || 'Save';
                }
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            showSnackbar(safeAdminLang?.video_embed?.wizard?.messages?.network_error || 'Network connection error.', 'error');
            if (btnFinish) {
                btnFinish.disabled = false;
                btnFinish.innerText = safeAdminLang?.video_embed?.wizard?.button?.save || 'Save';
            }
        });
}

// PERSISTENCE LOGIC
function saveVideoEmbedWizardState(isActive = true) {
    const state = {
        currentStep: videoEmbedWizardCurrentStep,
        data: videoEmbedWizardData,
        isActive: isActive && document.getElementById('videoEmbedWizard').classList.contains('active'),
        // Capture live input values too
        inputs: {
            embed: document.getElementById('videoEmbedInput')?.value || '',
            original: document.getElementById('videoOriginalTitleInput')?.value || '',
            name: document.getElementById('videoNameInput')?.value || '',
            duration: document.getElementById('videoDurationInput')?.value || '',
            date: document.getElementById('videoDateInput')?.value || '',
            content: document.getElementById('videoEmbedWizardContentTextarea')?.value || '',
            seoTitle: document.getElementById('videoEmbedWizardSeoTitleInput')?.value || '',
            seoKeywords: document.getElementById('videoEmbedWizardSeoKeywordsInput')?.value || '',
            seoDescription: document.getElementById('videoEmbedWizardSeoDescriptionInput')?.value || ''
        },
        autofilled: {
            title: document.getElementById('videoEmbedWizardSeoTitleInput')?.dataset.autofilled || 'true',
            keywords: document.getElementById('videoEmbedWizardSeoKeywordsInput')?.dataset.autofilled || 'true',
            description: document.getElementById('videoEmbedWizardSeoDescriptionInput')?.dataset.autofilled || 'true'
        }
    };
    localStorage.setItem('videoEmbedWizardState', JSON.stringify(state));
}

function loadVideoEmbedWizardState() {
    const saved = localStorage.getItem('videoEmbedWizardState');
    if (!saved) return;

    try {
        const state = JSON.parse(saved);
        videoEmbedWizardCurrentStep = state.currentStep || 1;
        videoEmbedWizardData = state.data || videoEmbedWizardData;

        // Restore inputs
        if (state.inputs) {
            if (document.getElementById('videoEmbedInput')) document.getElementById('videoEmbedInput').value = state.inputs.embed;
            if (document.getElementById('videoOriginalTitleInput')) document.getElementById('videoOriginalTitleInput').value = state.inputs.original;
            if (document.getElementById('videoNameInput')) document.getElementById('videoNameInput').value = state.inputs.name;
            if (document.getElementById('videoDurationInput')) document.getElementById('videoDurationInput').value = state.inputs.duration;
            if (document.getElementById('videoDateInput')) document.getElementById('videoDateInput').value = state.inputs.date;
            if (document.getElementById('videoEmbedWizardContentTextarea')) document.getElementById('videoEmbedWizardContentTextarea').value = state.inputs.content;
            if (document.getElementById('videoEmbedWizardSeoTitleInput')) document.getElementById('videoEmbedWizardSeoTitleInput').value = state.inputs.seoTitle;
            if (document.getElementById('videoEmbedWizardSeoKeywordsInput')) document.getElementById('videoEmbedWizardSeoKeywordsInput').value = state.inputs.seoKeywords;
            if (document.getElementById('videoEmbedWizardSeoDescriptionInput')) document.getElementById('videoEmbedWizardSeoDescriptionInput').value = state.inputs.seoDescription;
        }

        // Restore Autofilled Status
        if (state.autofilled) {
            const t = document.getElementById('videoEmbedWizardSeoTitleInput');
            const k = document.getElementById('videoEmbedWizardSeoKeywordsInput');
            const d = document.getElementById('videoEmbedWizardSeoDescriptionInput');
            if (t) t.dataset.autofilled = state.autofilled.title;
            if (k) k.dataset.autofilled = state.autofilled.keywords;
            if (d) d.dataset.autofilled = state.autofilled.description;
        }

        // Restore Hidden Fields
        const hLang = document.getElementById('videoEmbedWizardHiddenLang');
        if (hLang) hLang.value = videoEmbedWizardData.lang;

        const hCat = document.getElementById('videoEmbedWizardHiddenCategory');
        if (hCat && videoEmbedWizardData.category) {
            hCat.value = videoEmbedWizardData.category.id;
            const label = document.getElementById('videoEmbedWizardSelectedCategory');
            if (label) {
                label.innerText = videoEmbedWizardData.category.name;
                label.style.color = 'var(--md-sys-color-on-surface)';
            }
        }

        // Trigger UI update
        updateVideoEmbedWizardUI();

        // Ensure SEO is updated after load if inputs exist
        if (typeof updateVideoEmbedWizardSEOAutoFill === 'function') {
            updateVideoEmbedWizardSEOAutoFill();
        }

        // If it was active, reopen it
        if (state.isActive) {
            const wizard = document.getElementById('videoEmbedWizard');
            if (wizard) wizard.classList.add('active');
        }

    } catch (e) {
        console.error('Error loading wizard state:', e);
    }
}

function clearVideoEmbedWizardState() {
    localStorage.removeItem('videoEmbedWizardState');
}

// Connect Global FAB "Add Video" Button
document.addEventListener('DOMContentLoaded', () => {
    const fabBtns = document.querySelectorAll('.fab-mini');
    fabBtns.forEach(btn => {
        if (btn.getAttribute('data-action') === 'video-embed-add') {
            // Override existing click
            btn.onclick = (e) => {
                e.preventDefault();
                e.stopPropagation();
                openVideoEmbedWizard();
            };
        }
    });

    // Initialize Wizards if they exist
    if (document.getElementById('wizardContentTextarea')) {
        initRichEditor('#wizardContentTextarea', 300);
    }

    // Load Video Embed Wizard State
    if (document.getElementById('videoEmbedWizard')) {
        loadVideoEmbedWizardState();

        // Attach auto-save to inputs
        const wizardInputs = [
            'videoEmbedInput', 'videoOriginalTitleInput', 'videoNameInput',
            'videoDurationInput', 'videoDateInput', 'videoEmbedWizardContentTextarea',
            'videoEmbedWizardSeoTitleInput', 'videoEmbedWizardSeoKeywordsInput',
            'videoEmbedWizardSeoDescriptionInput'
        ];
        wizardInputs.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', () => {
                    updateVideoEmbedWizardSEOAutoFill();
                    saveVideoEmbedWizardState();
                });
            }
        });

        // Track manual edits to SEO fields to stop auto-filling
        ['videoEmbedWizardSeoTitleInput', 'videoEmbedWizardSeoKeywordsInput', 'videoEmbedWizardSeoDescriptionInput'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', () => {
                    el.dataset.autofilled = 'false';
                    saveVideoEmbedWizardState();
                });
            }
        });

        const publishSwitch = document.getElementById('videoEmbedWizardPublishSwitch');
        if (publishSwitch) {
            publishSwitch.addEventListener('change', () => {
                videoEmbedWizardData.published = publishSwitch.checked;
                saveVideoEmbedWizardState();
            });
        }

        const aclSelect = document.getElementById('videoEmbedWizardAclSelect');
        if (aclSelect) {
            aclSelect.addEventListener('change', () => {
                videoEmbedWizardData.acl_id = aclSelect.value;
                saveVideoEmbedWizardState();
            });
        }
    }
});
window.submitEditForm = function (formId) {
    if (typeof tinymce !== 'undefined') {
        tinymce.triggerSave();
    }
    const form = document.getElementById(formId);
    if (!form) return;
    if (typeof form.requestSubmit === 'function') {
        form.requestSubmit();
        return;
    }
    form.submit();
};

// Rich Text Editor (TinyMCE) Helper
window.initRichEditor = function (selector, height = 400) {
    if (typeof tinymce === 'undefined') {
        console.error('TinyMCE not loaded!');
        return;
    }

    tinymce.init({
        license_key: 'gpl',
        selector: selector,
        height: height,
        menubar: false,
        statusbar: false,
        branding: false,
        promotion: false,
        mobile: {
            menubar: false,
            toolbar: 'undo redo | bold italic | bullist numlist | link | code'
        },
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount'
        ],
        toolbar: 'undo redo | blocks | ' +
            'bold italic forecolor | alignleft aligncenter ' +
            'alignright alignjustify | bullist numlist outdent indent | ' +
            'removeformat | link image | code fullscreen',
        content_style: 'body { font-family:Roboto,Helvetica,Arial,sans-serif; font-size:16px; color: #1C1B1F; background-color: #ffffff; padding: 10px; } ' +
            'p { margin-bottom: 10px; } ' +
            'span { background-color: transparent !important; color: inherit !important; }', // Anti-highlight fix
        setup: function (editor) {
            editor.on('change', function () {
                editor.save();
                const el = document.querySelector(selector);
                if (el) el.dispatchEvent(new Event('input', { bubbles: true }));
            });
        }
    });
};
// Admin Session Liveness Monitor & Heartbeat
(function () {
    let sessionExpiredModalShown = false;
    const CHECK_INTERVAL = 120000; // 2 minutes
    let checkTimer = null;

    function checkAdminSession() {
        if (sessionExpiredModalShown) return;
        const checkUrl = (window.SITE_URL || '/') + 'mod_user/session_check?ajax=1';

        fetch(checkUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            cache: 'no-store'
        })
        .then(res => {
            if (res.status === 401) {
                return res.json().then(data => handleSessionExpired(data)).catch(() => handleSessionExpired(null));
            }
            if (!res.ok) {
                return;
            }
            return res.json().then(data => {
                if (data && data.authenticated === false) {
                    handleSessionExpired(data);
                }
            }).catch(() => {});
        })
        .catch(() => {
            // Ignore network hiccups
        });
    }

    function handleSessionExpired(data) {
        if (sessionExpiredModalShown) return;
        sessionExpiredModalShown = true;

        if (checkTimer) {
            clearInterval(checkTimer);
        }

        const targetLogin = (data && data.redirect) ? data.redirect : ((window.SITE_URL || '/') + '?login=modal&session_expired=1');
        const lang = window.SITE_LANG || 'tr';

        let title = 'Danişîn bi dawî bû!';
        let text = 'Dema danişîna we qediya ye. Ji bo ewlehiyê, ji kerema xwe ji nû ve têkevin.';
        let btnLogin = 'Têkeve';
        let btnCancel = 'Betal bike';

        if (lang === 'tr') {
            title = 'Oturum Sona Erdi!';
            text = 'Güvenliğiniz için oturum süresi doldu. Lütfen tekrar giriş yapın.';
            btnLogin = 'Giriş Yap';
            btnCancel = 'İptal';
        } else if (lang === 'en') {
            title = 'Session Expired!';
            text = 'Your session has timed out. Please sign in again.';
            btnLogin = 'Log In';
            btnCancel = 'Cancel';
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: title,
                text: text,
                showCancelButton: true,
                confirmButtonText: btnLogin,
                cancelButtonText: btnCancel,
                confirmButtonColor: '#1a73e8',
                allowOutsideClick: false
            }).then(result => {
                if (result.isConfirmed) {
                    window.open(targetLogin, '_blank');
                }
            });
        }
    }

    // Run heartbeat every 2 minutes
    checkTimer = setInterval(checkAdminSession, CHECK_INTERVAL);

    // Also check on tab focus if page was idle/hidden for more than 5 minutes
    let lastActiveTime = Date.now();
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            const now = Date.now();
            if (now - lastActiveTime > 300000) { // 5 minutes
                checkAdminSession();
            }
            lastActiveTime = now;
        }
    });
})();

// ==========================================================================
// GLOBAL MATERIAL 3 PHONE-FIRST DESELECTION & CONTEXTUAL FAB CONTROLLER
// ==========================================================================
(function() {
    function getCancelLabel() {
        if (typeof adminLang !== 'undefined') {
            if (adminLang.button && adminLang.button.cancel) return adminLang.button.cancel;
            if (adminLang.context_menu && adminLang.context_menu.cancel) return adminLang.context_menu.cancel;
        }
        return 'Cancel';
    }

    window.updateGlobalFabState = function(active) {
        const fab = document.getElementById('mainFab');
        if (!fab) return;

        const icon = fab.querySelector('i');
        if (active) {
            if (!fab.dataset.originalLabel) {
                fab.dataset.originalLabel = fab.getAttribute('aria-label') || '';
            }
            fab.classList.add('is-cancel');
            const cancelLabel = getCancelLabel();
            fab.setAttribute('aria-label', cancelLabel);
            fab.setAttribute('title', cancelLabel);
            if (icon) {
                icon.className = 'bi bi-x-lg';
            }
        } else {
            fab.classList.remove('is-cancel');
            const defaultLabel = fab.dataset.originalLabel || 'Add';
            fab.setAttribute('aria-label', defaultLabel);
            fab.setAttribute('title', defaultLabel);
            if (icon) {
                icon.className = 'bi bi-plus-lg';
            }
        }
    };

    window.clearActiveSelection = function() {
        if (typeof window.clearSelection === 'function') {
            window.clearSelection();
            return;
        }

        const candidateManagers = [
            window.newsManager,
            window.videoManager,
            window.publicationManager,
            window.announcementManager,
            window.sliderManager,
            window.documentManager,
            window.surveyManager
        ];

        for (const mgr of candidateManagers) {
            if (mgr && typeof mgr.clearSelection === 'function') {
                mgr.clearSelection();
                return;
            }
        }

        const selectedElements = document.querySelectorAll('.m3-card.selected, .content-card.selected');
        if (selectedElements.length > 0) {
            selectedElements.forEach(el => el.classList.remove('selected'));
            document.querySelectorAll('.standard-toolbar .toolbar-item.active').forEach(b => {
                b.classList.remove('active');
                b.style.opacity = '0.3';
                b.style.pointerEvents = 'none';
            });
            window.updateGlobalFabState(false);
            if (window.navigator.vibrate) {
                window.navigator.vibrate(8);
            }
        }
    };

    function initGlobalDeselectionListeners() {
        const fab = document.getElementById('mainFab');
        if (fab) {
            if (!fab.dataset.originalLabel) {
                fab.dataset.originalLabel = fab.getAttribute('aria-label') || '';
            }
            fab.addEventListener('click', (e) => {
                const hasSelected = document.querySelector('.m3-card.selected, .content-card.selected');
                if (fab.classList.contains('is-cancel') || hasSelected) {
                    e.preventDefault();
                    e.stopPropagation();
                    window.clearActiveSelection();
                }
            });
        }

        document.addEventListener('click', (e) => {
            const hasSelected = document.querySelector('.m3-card.selected, .content-card.selected');
            if (!hasSelected) return;

            if (e.target.closest('.content-card') ||
                e.target.closest('.m3-card') ||
                e.target.closest('.standard-toolbar') ||
                e.target.closest('.news-toolbar') ||
                e.target.closest('#mainFab') ||
                e.target.closest('.news-image-modal') ||
                e.target.closest('.bottom-sheet') ||
                e.target.closest('.m3-bottom-sheet') ||
                e.target.closest('.overlay') ||
                e.target.closest('.swal2-container') ||
                e.target.closest('dialog[open]')) {
                return;
            }

            window.clearActiveSelection();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                if (document.querySelector('.news-image-modal.active, .bottom-sheet.active, .m3-bottom-sheet.active, .swal2-container, dialog[open]')) {
                    return;
                }
                const hasSelected = document.querySelector('.m3-card.selected, .content-card.selected');
                if (hasSelected) {
                    window.clearActiveSelection();
                }
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initGlobalDeselectionListeners);
    } else {
        initGlobalDeselectionListeners();
    }
})();

