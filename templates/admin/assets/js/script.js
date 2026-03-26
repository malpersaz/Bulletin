// Mobile Interaction Scripts for M3 Prototype

function temizle(text) {
    if (!text) return "";
    // Allow any legal Unicode Letter (\p{L}), any Numeric digit (\p{N}), and Whitespace (\s)
    let clean = text.replace(/[^\p{L}\p{N}\s]+/gu, " ");
    clean = clean.trim();
    // Replace multiple spaces with a single space
    return clean.replace(/\s+/g, " ");
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

    snackbar.innerHTML = `<i class="bi ${style.icon}"></i><span>${message}</span>`;
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

function makeSeoValue(text) {
    let deger = temizle(text);
    deger = deger.replace(/\s+/g, "_");
    deger = deger.toLowerCase(); // Lowercase for URLs
    deger = deger.replace(/İ/g, 'i');
    return deger;
}
function makeKeywordsValue(text) {
    let deger = temizle(text);
    deger = deger.replace(/\s+/g, ",");
    deger = deger.toLowerCase();
    deger = deger.replace(/İ/g, 'i');
    return deger;
}

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
    const cleanedVal = (typeof temizle === 'function') ? temizle(targetVal) : targetVal;

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
        const keys = makeKeywordsValue(targetVal);
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

// Navigation Drawer Logic
// Navigation Drawer Logic
function toggleDrawer() {
    const drawer = document.getElementById('navDrawer');
    const scrim = document.getElementById('drawerScrim');

    // Debug log
    console.log('toggleDrawer called', { drawer, scrim });

    if (drawer && scrim) {
        // Force toggle class
        if (drawer.classList.contains('active')) {
            drawer.classList.remove('active');
            scrim.classList.remove('active');
        } else {
            drawer.classList.add('active');
            scrim.classList.add('active');
        }
    } else {
        console.error('Drawer or Scrim element not found!');
    }
}

function toggleGroup(header) {
    const group = header.parentElement;
    group.classList.toggle('expanded');
}


// Scroll Effect for Top Bar
const topAppBar = document.querySelector('.top-app-bar');
const scrollContent = document.querySelector('.mobile-wrapper'); // Since we scroll within wrapper in simulator

if (scrollContent) {
    scrollContent.addEventListener('scroll', () => {
        if (scrollContent.scrollTop > 10) {
            topAppBar.style.backgroundColor = '#F3EDF7'; // Surface Container slightly darker
            topAppBar.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
        } else {
            topAppBar.style.backgroundColor = 'var(--md-sys-color-surface-container)';
            topAppBar.style.boxShadow = 'none';
        }
    });
} else {
    // Fallback for real mobile logic
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


// Speed Dial Logic
function toggleFabMenu() {
    const container = document.getElementById('fabContainer');
    const overlay = document.getElementById('fabOverlay');
    if (container) {
        container.classList.toggle('active');
        if (overlay) overlay.classList.toggle('active');
        // Vibration
        navigator.vibrate?.(10);
    }
}

// Close FAB when clicking outside
document.addEventListener('click', (e) => {
    const container = document.getElementById('fabContainer');
    const overlay = document.getElementById('fabOverlay');
    // Close if clicked outside the container while it's active
    if (container && container.classList.contains('active')) {
        if (!container.contains(e.target) && (!overlay || e.target !== overlay)) {
            container.classList.remove('active');
            if (overlay) overlay.classList.remove('active');
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
    const deleteActionLabel = document.querySelector('#contextActionDelete .context-action-label');

    if (titleEl) titleEl.innerText = title;
    if (editActionLabel && adminLang?.context_menu?.edit) editActionLabel.innerText = adminLang.context_menu.edit;
    if (galleryActionLabel && adminLang?.context_menu?.gallery) galleryActionLabel.innerText = adminLang.context_menu.gallery;
    if (filesActionLabel && adminLang?.context_menu?.files) filesActionLabel.innerText = adminLang.context_menu.files;
    if (deleteActionLabel && adminLang?.context_menu?.delete) deleteActionLabel.innerText = adminLang.context_menu.delete;

    // Handle hidden actions
    const galleryAction = document.getElementById('contextActionGallery');
    const filesAction = document.getElementById('contextActionFiles');

    if (galleryAction) galleryAction.style.display = hiddenActions.includes('gallery') ? 'none' : 'flex';
    if (filesAction) filesAction.style.display = hiddenActions.includes('files') ? 'none' : 'flex';

    // Toggle Status Text based on current state
    if (statusAction) {
        if (isPublished) {
            statusAction.querySelector('.nav-item-label').innerText = adminLang.context_menu.unpublish;
            statusAction.querySelector('.nav-item-icon').className = 'bi bi-eye-slash nav-item-icon';
        } else {
            statusAction.querySelector('.nav-item-label').innerText = adminLang.context_menu.publish;
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
 * Modules like mod_content.js should override window.handleContextAction.
 */
function handleContextAction(action) {
    closeContextMenu();
    console.log("Context action triggered:", action, "on item:", activeContextItem);
}

// Close Context Menu when clicking overlay (handled by HTML onclick, but added here for safety)
// document.addEventListener('click', ...) logic handles specific containers, but overlays usually cover all.


// Hamburger Menu Binding (after DOM load)
// Hamburger Menu Binding (Removed to rely on inline onclick)
// document.addEventListener('DOMContentLoaded', () => {
//     const menuBtn = document.querySelector('.icon-btn');
//     if (menuBtn) {
//         const newBtn = menuBtn.cloneNode(true);
//         menuBtn.parentNode.replaceChild(newBtn, menuBtn);
//         newBtn.addEventListener('click', toggleDrawer);
//     }
// });

// WIZARD LOGIC
let wizardCurrentStep = 1;
const wizardTotalSteps = 8;
const wizardData = {
    lang: adminLang.filter.default_lang,
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
 * Rewşa parastî ji localStorage bixwîne û vesaz bike
 * Dema wizard vedibe an rûpel nû dibe tê bangkirin
 * @returns {boolean} true heke rewş hate vesazkirin
 */
function loadWizardState() {
    try {
        const saved = localStorage.getItem(WIZARD_STORAGE_KEY);
        if (!saved) return false;

        const state = JSON.parse(saved);

        // Time check - if older than 24 hours, clear it
        const maxAge = 24 * 60 * 60 * 1000; // 24 seet
        if (Date.now() - state.timestamp > maxAge) {
            clearWizardState();
            return false;
        }

        // Restore wizard state
        wizardCurrentStep = state.step || 1;

        // Data vesaz bike
        if (state.data) {
            wizardData.lang = state.data.lang || adminLang.filter.default_lang;
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
 * UI-ya wizard ji rewşa parastî vesaz bike
 * Form inputên heyî û hilbijartinên visual nîşan bide
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

    // Title vesaz bike
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

    // Date vesaz bike
    const dateInput = document.getElementById('wizardDateInput');
    if (dateInput && wizardData.date) {
        dateInput.value = wizardData.date;
    }

    // Intro vesaz bike
    const introInput = document.getElementById('wizardIntroInput');
    if (introInput && wizardData.intro) {
        introInput.value = wizardData.intro;
        const counter = document.getElementById('introCharCounter');
        if (counter) counter.innerText = `${wizardData.intro.length} / 255`;
    }

    // Content vesaz bike (TinyMCE an textarea)
    const contentArea = document.getElementById('wizardContentTextarea');
    if (contentArea && wizardData.content) {
        // If TinyMCE is loaded
        if (typeof tinymce !== 'undefined' && tinymce.get('wizardContentTextarea')) {
            tinymce.get('wizardContentTextarea').setContent(wizardData.content);
        } else {
            contentArea.value = wizardData.content;
        }
    }

    // Publish switch vesaz bike
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
        return introLen >= 110 && introLen <= 255;
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
            adminLang.wizard.step.language,      // Step 1
            adminLang.wizard.step.category,      // Step 2
            adminLang.wizard.step.title,         // Step 3
            adminLang.wizard.step.date,          // Step 4
            adminLang.wizard.step.intro,         // Step 5
            adminLang.wizard.step.content,       // Step 6
            adminLang.wizard.step.image,         // Step 7
            adminLang.wizard.step.finish         // Step 8
        ];
        titleEl.innerText = stepTitles[wizardCurrentStep - 1] || adminLang.wizard.step.language;
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
        if (btnNext) btnNext.style.display = 'none';
        if (btnFinish) btnFinish.style.display = 'block';
        updateSummary();
    } else {
        if (btnNext) {
            btnNext.style.display = 'block';

            // VALIDATION LOGIC FOR BUTTON COLOR
            const isValid = checkWizardStepValidity(wizardCurrentStep);
            if (isValid) {
                btnNext.classList.remove('btn-invalid');
            } else {
                btnNext.classList.add('btn-invalid');
            }
        }
        if (btnFinish) btnFinish.style.display = 'none';
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
    saveWizardState(); // Save state
}

// Step 2: Category
function closeWizardCategorySheet() {
    const sheet = document.getElementById('wizard-cat-sheet');
    const overlay = document.getElementById('wizard-cat-overlay');
    if (!sheet || !overlay) return;

    sheet.classList.remove('active');
    overlay.classList.remove('active');
    overlay.style.display = 'none';
}

function toggleWizardCategorySheet() {
    const sheet = document.getElementById('wizard-cat-sheet');
    const overlay = document.getElementById('wizard-cat-overlay');
    if (!sheet || !overlay) return;

    if (sheet.classList.contains('active')) {
        closeWizardCategorySheet();
        return;
    }

    // Detect which wizard is active
    let targetLang = adminLang.filter.default_lang;
    let isVideoWizard = false;

    const videoEmbedWizard = document.getElementById('videoEmbedWizard');
    if (videoEmbedWizard && videoEmbedWizard.classList.contains('active')) {
        targetLang = (videoEmbedWizardData.lang || adminLang.filter.default_lang).toUpperCase();
        isVideoWizard = true;
    } else if (typeof wizardData !== 'undefined' && wizardData.lang) {
        targetLang = wizardData.lang.toUpperCase();
    }

    const items = sheet.querySelectorAll('.category-item');
    let visibleCount = 0;

    items.forEach(item => {
        const itemLangAttr = item.getAttribute('data-lang');
        const itemLang = itemLangAttr ? itemLangAttr.toUpperCase() : '';

        const isVideoItem = item.classList.contains('video-cat');
        const isContentItem = item.classList.contains('content-cat');

        // Filter by Wizard Type
        let typeMatch = false;
        if (isVideoWizard && isVideoItem) typeMatch = true;
        if (!isVideoWizard && isContentItem) typeMatch = true;

        // Show if match or if no lang is defined (fallback)
        if (typeMatch && (!itemLang || itemLang === targetLang)) {
            item.style.display = 'flex';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });

    // FALLBACK: If no categories found for this language, show ALL from the same category type
    if (visibleCount === 0 && items.length > 0) {
        items.forEach(item => {
            const isVideoItem = item.classList.contains('video-cat');
            const isContentItem = item.classList.contains('content-cat');
            
            let typeMatch = false;
            if (isVideoWizard && isVideoItem) typeMatch = true;
            if (!isVideoWizard && isContentItem) typeMatch = true;
            
            if (typeMatch) {
                item.style.display = 'flex';
            }
        });
    }

    sheet.classList.add('active');
    overlay.classList.add('active');
    overlay.style.display = 'block';
}

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
            const hidden = document.getElementById('wizardHiddenCategory');
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
function previewWizardImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const img = document.getElementById('wizardImgPreview');
            if (img) {
                img.src = e.target.result;
                img.style.display = 'block';
            }
            const label = document.querySelector('.image-upload-area p');
            if (label) label.innerText = adminLang.wizard.label.change_image;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function previewEditImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const img = document.getElementById('editImgPreview');
            const area = document.querySelector('.image-upload-area');
            if (img) {
                img.src = e.target.result;
                img.style.display = 'block';
            } else if (area) {
                // If it was empty, create the image tag
                area.innerHTML = `<img id="editImgPreview" src="${e.target.result}" style="width:100%; height:auto; border-radius:8px;">
                                 <input type="file" name="image" id="editImgInput" hidden onchange="previewEditImage(this)">
                                 <button type="button" class="remove-img-btn" onclick="event.stopPropagation(); removeEditImage()"><i class="bi bi-x"></i></button>`;
            }
            // Update remove button visibility if it exists
            const removeBtn = document.querySelector('.remove-img-btn');
            if (removeBtn) removeBtn.style.display = 'flex';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function removeEditImage() {
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

function updateSummary() {
    const langSum = document.getElementById('summaryLang');
    if (langSum) langSum.innerText = wizardData.lang;

    const catSum = document.getElementById('summaryCategory');
    if (catSum) catSum.innerText = wizardData.category ? wizardData.category.name : adminLang.wizard.summary.selected_none;
}

// Wizard Submission Logic
function prepareAndSubmitWizard() {
    console.log('prepareAndSubmitWizard() hat bangkirin');

    const btnFinish = document.getElementById('btnWizardFinish');
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
    const introInput = document.getElementById('wizardIntroInput');

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

    // SEO Fields - backend vana hewce dike
    const titleValue = titleInput ? titleInput.value : '';
    formData.append('seo', makeSeoValue(titleValue));
    formData.append('title', titleValue);
    formData.append('keywords', makeKeywordsValue(titleValue));

    // description - validator requires max 255 characters
    const introValue = introInput ? introInput.value : '';
    formData.append('description', introValue.substring(0, 255));

    // Image
    const fileInput = document.getElementById('wizardImgInput');
    if (fileInput && fileInput.files[0]) {
        formData.append('image', fileInput.files[0]);
    }

    // Publish Status - from hidden input (selection cards)
    const pubHidden = document.getElementById('wizardPublishHidden');
    formData.append('status', (pubHidden && pubHidden.value === 'y') ? 'y' : 'n');

    // Deck field - validator hewce dike (y/n)
    formData.append('deck', 'n');

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

    // Send AJAX
    // Ensure SITE_URL or similar is defined, otherwise relative path
    const url = (typeof SITE_URL !== 'undefined' ? SITE_URL : '/') + WIZARD_MODULE + '/add';
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
            console.log("Wizard response:", data);

            // JSON parse bike
            let result;
            try {
                result = JSON.parse(data);
            } catch (e) {
                console.error("JSON parse error:", e);
                console.error("Raw response:", data);
                // Show raw response so user can see what the problem is
                const shortData = data.substring(0, 300);
                showSnackbar('Xeletî: Bersiva ne JSON e. Bersiv: ' + shortData, 'error', 8000);
                if (btnFinish) {
                    btnFinish.disabled = false;
                    btnFinish.innerText = adminLang.wizard.step.finish;
                }
                return;
            }

            // Status kontrol bike
            if (result.status === 'success') {
                // FIRST: Set flag so TinyMCE sync won't re-save localStorage
                wizardSubmitted = true;

                // Clear state and close wizard BEFORE showing message
                clearWizardState();
                closeWizard();

                // Show success message via snackbar (non-blocking)
                const successMsg = result.message || adminLang.wizard.messages.success_sim || 'Naverok bi serkeftî hat zêdekirin';
                showSnackbar(successMsg, 'success');

                // Redirect after delay
                setTimeout(() => {
                    if (typeof refreshContentList === 'function') {
                        refreshContentList();
                    } else {
                        window.location.reload();
                    }
                }, 1500);
            } else {
                // Error - show message
                let errorMsg = result.message || adminLang.wizard.messages.error || 'Xeletî';
                if (result.errors && Array.isArray(result.errors)) {
                    errorMsg = result.errors.join('\n');
                }
                showSnackbar(errorMsg, 'error', 6000);
                if (btnFinish) {
                    btnFinish.disabled = false;
                    btnFinish.innerText = adminLang.wizard.step.finish;
                }
            }
        })
        .catch(error => {
            console.error("Wizard error:", error);
            showSnackbar(adminLang.wizard.messages.error || 'Xeletî: ' + error, 'error', 6000);
            if (btnFinish) {
                btnFinish.disabled = false;
                btnFinish.innerText = adminLang.wizard.step.finish;
            }
        });
}

// Add Listeners dynamically if needed
document.addEventListener('DOMContentLoaded', () => {
    // Validation Listeners
    const titleInput = document.getElementById('wizardTitleInput');
    if (titleInput) {
        titleInput.addEventListener('input', function () {
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
        introInput.addEventListener('input', function () {
            const len = this.value.length;
            const counter = document.getElementById('introCharCounter');
            if (counter) counter.innerText = `${len} / 255`;

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
            const keys = makeKeywordsValue(wizardData.title);
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
            const desc = (wizardData.intro || wizardData.title); // Use RAW Title/Intro
            wizardData.seo.description = desc.substring(0, 255);
            seoDesc.value = desc.substring(0, 255);
            seoDesc.dataset.autofilled = 'true';
            wizardData.seo.autofilled.description = 'true';
            if (hSeoDesc) hSeoDesc.value = seoDesc.value;
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
            window.location.href = SITE_URL + 'mod_video_embed/ekle';
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
    sort: adminLang.filter.default_sort,
    sortCol: '6', // Default: Date (5+1)
    sortDir: 'desc', // Default: desc
    limit: '10',
    lang: adminLang.filter.default_lang,
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
const allLanguages = adminLang.languages || [];
const LANG_ALL = adminLang.ui.all;

// Single Select Logic: String or null, not array
let tempSelectedLang = adminLang.filter.default_lang;

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

    // Auto-confirm logic: No need to click "Temam"
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
    const defaultLangObj = allLanguages.find(l => l.code === adminLang.filter.default_lang);
    const defaultLangName = defaultLangObj ? defaultLangObj.name : adminLang.filter.default_lang;

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
        if (chip.innerText === adminLang.filter.default_sort || chip.innerText === '10') {
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

    alert(adminLang.ui.filter_cleared);
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
        let msg = `${adminLang.ui.filter_applied}:\n`;
        msg += `- ${adminLang.ui.sort_label}: ${filterState.sort}\n`;
        msg += `- ${adminLang.ui.limit_label}: ${filterState.limit}\n`;
        msg += `- ${adminLang.ui.lang_label}: ${filterState.lang}`;
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
    const lowerFilter = filter.toLocaleLowerCase(adminLang.date_locale);
    const langAllName = adminLang.ui.all || 'All'; // Fallback

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
        const name = l.name.toLocaleLowerCase(adminLang.date_locale);
        const code = l.code.toLocaleLowerCase(adminLang.date_locale);

        // Removed Strict Filter for 'all' variants as we want them valid if they exist in DB, 
        // though usually 'all' is virtual.
        // if (code === 'all' || name === 'hemû' || name === 'all' || name === 'hemu' || name === 'tüm' || name === 'hepsi') return false;

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
    // loadMoreContent(); // Disabled for production - using mod_content.js instead
    // setupInfiniteScroll(); // DISABLED - mod_content.js manages its own scroll listener
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
const mockTitles = adminLang.mock.titles;
const mockCategories = adminLang.mock.categories;
const mockAuthors = adminLang.mock.authors;

function getRandomItem(arr) {
    if (!arr || arr.length === 0) return "Mock Item";
    return arr[Math.floor(Math.random() * arr.length)];
}

function getRandomDate() {
    const start = new Date(2023, 0, 1);
    const end = new Date();
    const d = new Date(start.getTime() + Math.random() * (end.getTime() - start.getTime()));
    return d.toLocaleDateString(adminLang.date_locale);
}

function createCardElement(id) {
    const title = getRandomItem(mockTitles);
    const category = getRandomItem(mockCategories);
    const author = getRandomItem(mockAuthors);
    const date = getRandomDate();
    const views = Math.floor(Math.random() * 5000) + 100;

    // Status Logic
    const isPublished = Math.random() > 0.2;
    const statusText = isPublished ? adminLang.status.active : adminLang.status.draft;
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
            if (confirm(adminLang.general.delete_confirm)) {
                card.style.opacity = '0';
                setTimeout(() => card.remove(), 300); // Animation delay
            }
            return;
        }

        // Generic Card Click
        if (!e.target.closest('button')) {
            // alert(adminLang.general.card_click.replace('%s', title));
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
    lang: adminLang.filter.default_lang,
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
        adminLang.video_embed.wizard.step.language,
        adminLang.video_embed.wizard.step.category,
        adminLang.video_embed.wizard.step.embed,
        adminLang.video_embed.wizard.step.details,
        adminLang.video_embed.wizard.step.content,
        adminLang.video_embed.wizard.step.image,
        adminLang.video_embed.wizard.step.seo,
        adminLang.video_embed.wizard.step.finish
    ];

    const stepSubtitles = [
        adminLang.video_embed.wizard.subtitle.language,
        adminLang.video_embed.wizard.subtitle.category,
        adminLang.video_embed.wizard.subtitle.embed,
        adminLang.video_embed.wizard.subtitle.details,
        adminLang.video_embed.wizard.subtitle.content,
        adminLang.video_embed.wizard.subtitle.image,
        adminLang.video_embed.wizard.subtitle.seo,
        adminLang.video_embed.wizard.subtitle.finish
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
            if (label) label.innerText = adminLang.video_embed.wizard.label.change_image;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Video Wizard Summary
function updateVideoEmbedSummary() {
    const langSum = document.getElementById('videoEmbedSummaryLang');
    if (langSum) langSum.innerText = videoEmbedWizardData.lang;

    const catSum = document.getElementById('videoEmbedSummaryCategory');
    if (catSum) catSum.innerText = videoEmbedWizardData.category ? videoEmbedWizardData.category.name : adminLang.video_embed.wizard.summary.selected_none;

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
    formData.append('ekle', 'Submit');
    formData.append('ajax_request', '1');

    const url = (typeof SITE_URL !== 'undefined' ? SITE_URL : '/') + 'mod_video_embed/ekle';

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
                showSnackbar('Xeletî: Bersiva ne JSON e.', 'error');
                if (btnFinish) {
                    btnFinish.disabled = false;
                    btnFinish.innerText = adminLang.video_embed.wizard.button.save;
                }
                return;
            }

            if (result.status === 'success') {
                clearVideoEmbedWizardState();
                closeVideoEmbedWizard();
                showSnackbar(result.message || 'Vîdeo bi serkeftî hat zêdekirin', 'success');
                // Redirect after a short delay
                setTimeout(() => {
                    window.location.href = result.redirect || (SITE_URL + 'mod_video_embed/list');
                }, 1000);
            } else {
                showSnackbar(result.message || 'Şaşî çêbû', 'error', 8000);
                if (btnFinish) {
                    btnFinish.disabled = false;
                    btnFinish.innerText = adminLang.video_embed.wizard.button.save;
                }
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            showSnackbar('Tora peywendiyê qut bû.', 'error');
            if (btnFinish) {
                btnFinish.disabled = false;
                btnFinish.innerText = adminLang.video_embed.wizard.button.save;
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

// USER MENU LOGIC
function openUserMenuSheet() {
    const sheet = document.getElementById('user-menu-sheet');
    const overlay = document.getElementById('user-menu-overlay');
    if (sheet && overlay) {
        // Reset submenus to main
        showUserMenuMain();
        sheet.classList.add('active');
        overlay.classList.add('active');
        if (navigator.vibrate) navigator.vibrate(10);
    }
}

function closeUserMenuSheet() {
    const sheet = document.getElementById('user-menu-sheet');
    const overlay = document.getElementById('user-menu-overlay');
    if (sheet && overlay) {
        sheet.classList.remove('active');
        overlay.classList.remove('active');
        // Reset to main menu after transition (300ms)
        setTimeout(showUserMenuMain, 300);
    }
}

function showUserSubmenu(menuId) {
    // Hide main menu
    const mainMenu = document.getElementById('user-menu-main');
    if (mainMenu) {
        mainMenu.style.display = 'none';
        mainMenu.classList.remove('mainmenu-enter');
    }

    // Hide all submenus first
    const pms = document.getElementById('user-menu-pm');
    const admin = document.getElementById('user-menu-admin');
    if (pms) { pms.style.display = 'none'; pms.classList.remove('submenu-enter'); }
    if (admin) { admin.style.display = 'none'; admin.classList.remove('submenu-enter'); }

    // Show target submenu
    const target = document.getElementById(menuId);
    if (target) {
        target.style.display = 'block';
        // Trigger reflow for animation
        void target.offsetWidth;
        target.classList.add('submenu-enter');
    }
}

function showUserMenuMain() {
    // Hide all submenus
    const pms = document.getElementById('user-menu-pm');
    const admin = document.getElementById('user-menu-admin');
    if (pms) { pms.style.display = 'none'; pms.classList.remove('submenu-enter'); }
    if (admin) { admin.style.display = 'none'; admin.classList.remove('submenu-enter'); }

    // Show main menu
    const mainMenu = document.getElementById('user-menu-main');
    if (mainMenu) {
        mainMenu.style.display = 'block';
        // Trigger reflow for animation
        void mainMenu.offsetWidth;
        mainMenu.classList.add('mainmenu-enter');
    }
}
