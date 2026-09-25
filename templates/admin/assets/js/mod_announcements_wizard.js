/**
 * mod_announcements_wizard.js
 * Material 3 Phone-First 9-Step Announcements Wizard Controller
 * Strict Code Separation, Touch Interactions, Kurdish Typo Correction, and Offline Persistence.
 */

(function () {
    'use strict';

    const STORAGE_KEY = 'announcementWizardState';
    const STATE_MAX_AGE = 24 * 60 * 60 * 1000; // 24 Hours
    const TOTAL_STEPS = 9;

    class AnnouncementWizardController {
        constructor() {
            this.form = document.getElementById('announcementWizardForm');
            if (!this.form) return;

            this.shell = document.getElementById('announcementWizardShell');
            this.currentStep = 1;
            this.wizardData = {
                lang: '',
                name: '',
                seo: '',
                created_at: '',
                summary: '',
                content: '',
                image: '',
                deck: 'n',
                acl_id: '0',
                status: 'y'
            };

            // Parse language dictionary from data attribute on shell
            this.parseLanguageData();

            this.stepTitles = [
                this.getI18n('step.language', 'Language Selection'),
                this.getI18n('step.title', 'Announcement Title'),
                this.getI18n('step.date', 'Announcement Date'),
                this.getI18n('step.intro', 'Intro Text (Deck)'),
                this.getI18n('step.content', 'Announcement Content'),
                this.getI18n('step.image', 'Cover Image'),
                this.getI18n('step.featured', 'Display in Featured'),
                this.getI18n('step.acl', 'Access Control'),
                this.getI18n('step.finish', 'Publish Status')
            ];

            this.init();
        }

        parseLanguageData() {
            if (this.shell && this.shell.dataset && this.shell.dataset.wizardLang) {
                try {
                    window.ANNOUNCEMENT_WIZARD_LANG = JSON.parse(this.shell.dataset.wizardLang);
                } catch (e) {
                    console.warn('Failed to parse wizard language data:', e);
                }
            }
        }

        getI18n(path, fallback) {
            try {
                const parts = path.split('.');
                let current = window.ANNOUNCEMENT_WIZARD_LANG || (window.adminLang && window.adminLang.mod_announcements?.wizard);
                for (const part of parts) {
                    if (!current || typeof current[part] === 'undefined') return fallback;
                    current = current[part];
                }
                return current || fallback;
            } catch (e) {
                return fallback;
            }
        }

        init() {
            this.initDomReferences();
            this.initDefaultDate();
            this.bindEvents();
            this.initImageUploader();
            this.restoreState();
            this.updateUI();
            this.initRichEditor();
        }

        initDomReferences() {
            this.stepNameEl = document.getElementById('wizardStepName');
            this.stepBadgeEl = document.getElementById('wizardStepBadge');
            this.progressFillEl = document.getElementById('wizardProgressFill');
            this.btnPrev = document.getElementById('btnWizardPrev');
            this.btnNext = document.getElementById('btnWizardNext');
            this.btnFinish = document.getElementById('btnWizardFinish');
            this.btnQuickSave = document.getElementById('btnWizardQuickSave');
            this.btnClose = document.getElementById('btnWizardClose');

            // Hidden inputs
            this.hiddenLang = document.getElementById('wizardHiddenLang');
            this.hiddenSeo = document.getElementById('wizardHiddenSeo');
            this.hiddenDeck = document.getElementById('wizardHiddenDeck');
            this.hiddenAcl = document.getElementById('wizardHiddenAcl');
            this.hiddenStatus = document.getElementById('wizardHiddenStatus');

            // Step Inputs
            this.inputTitle = document.getElementById('wizardTitleInput');
            this.counterTitle = document.getElementById('wizardTitleCounter');
            this.msgTitle = document.getElementById('wizardTitleMsg');

            this.inputDate = document.getElementById('wizardDateInput');

            this.inputIntro = document.getElementById('wizardIntroInput');
            this.counterIntro = document.getElementById('wizardIntroCounter');
            this.msgIntro = document.getElementById('wizardIntroMsg');

            this.inputContent = document.getElementById('wizardContentTextarea');

            // Image elements
            this.imgInput = document.getElementById('wizardImgInput');
            this.imgDropzone = document.getElementById('wizardImageDropzone');
            this.imgEmpty = document.getElementById('wizardImageEmptyState');
            this.imgPreviewCard = document.getElementById('wizardImagePreviewCard');
            this.imgPreview = document.getElementById('wizardImgPreview');
            this.imgRemoveBtn = document.getElementById('wizardImageRemoveBtn');
            this.imgMeta = document.getElementById('wizardImageMeta');
            this.imgFileName = document.getElementById('wizardImageFileName');
            this.imgFileDetails = document.getElementById('wizardImageFileDetails');
            this.imgUrlInput = document.getElementById('wizardImageUrlInput');
            this.imgImportBtn = document.getElementById('wizardImageUrlImportBtn');

            // Summary Elements
            this.sumLang = document.getElementById('sumWizardLang');
            this.sumTitle = document.getElementById('sumWizardTitle');
            this.sumDate = document.getElementById('sumWizardDate');
            this.sumFeatured = document.getElementById('sumWizardFeatured');
            this.sumAcl = document.getElementById('sumWizardAcl');
            this.sumStatus = document.getElementById('sumWizardStatus');
        }

        initDefaultDate() {
            if (this.inputDate && !this.inputDate.value) {
                try {
                    const now = new Date();
                    const localISO = new Date(now.getTime() - (now.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);
                    this.inputDate.value = localISO;
                    this.wizardData.created_at = localISO;
                } catch (e) {
                    console.warn('Failed to set default local datetime:', e);
                }
            }
        }

        initRichEditor() {
            if (typeof window.initRichEditor === 'function') {
                window.initRichEditor('#wizardContentTextarea', 280);
            }
        }

        initImageUploader() {
            if (this.imgDropzone && this.imgInput) {
                this.imgDropzone.addEventListener('click', (e) => {
                    if (e.target.closest('#wizardImageRemoveBtn')) return;
                    this.imgInput.click();
                });

                ['dragenter', 'dragover'].forEach(eventName => {
                    this.imgDropzone.addEventListener(eventName, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        this.imgDropzone.classList.add('is-dragover');
                    }, false);
                });

                ['dragleave', 'drop'].forEach(eventName => {
                    this.imgDropzone.addEventListener(eventName, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        this.imgDropzone.classList.remove('is-dragover');
                    }, false);
                });

                this.imgDropzone.addEventListener('drop', (e) => {
                    const dt = e.dataTransfer;
                    if (dt && dt.files && dt.files.length > 0) {
                        this.handleImageFile(dt.files[0]);
                    }
                });
            }

            if (this.imgInput) {
                this.imgInput.addEventListener('change', () => {
                    if (this.imgInput.files && this.imgInput.files.length > 0) {
                        this.handleImageFile(this.imgInput.files[0]);
                    }
                });
            }

            if (this.imgRemoveBtn) {
                this.imgRemoveBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.removeImage();
                });
            }

            if (this.imgImportBtn && this.imgUrlInput) {
                this.imgImportBtn.addEventListener('click', () => {
                    const url = (this.imgUrlInput.value || '').trim();
                    if (url) {
                        this.handleImageUrl(url);
                    }
                });
            }
        }

        handleImageFile(file) {
            if (!file || !file.type.match(/^image\//)) {
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                const src = e.target.result;
                this.displayImagePreview(src, file.name, `${(file.size / 1024).toFixed(1)} KB`);
            };
            reader.readAsDataURL(file);
        }

        handleImageUrl(url) {
            const fileName = url.split('/').pop() || 'imported_image.jpg';
            this.displayImagePreview(url, fileName, 'Remote URL');
        }

        displayImagePreview(src, name, details) {
            if (this.imgPreview) {
                this.imgPreview.src = src;
                this.imgPreview.hidden = false;
            }
            if (this.imgEmpty) {
                this.imgEmpty.hidden = true;
            }
            if (this.imgPreviewCard) {
                this.imgPreviewCard.hidden = false;
            }
            if (this.imgMeta) {
                this.imgMeta.hidden = false;
            }
            if (this.imgFileName) {
                this.imgFileName.textContent = name;
            }
            if (this.imgFileDetails) {
                this.imgFileDetails.textContent = details || '';
            }
            const shell = document.getElementById('wizardImageShell');
            if (shell) shell.classList.add('has-image');
        }

        removeImage() {
            if (this.imgInput) this.imgInput.value = '';
            if (this.imgUrlInput) this.imgUrlInput.value = '';
            if (this.imgPreview) {
                this.imgPreview.src = '';
                this.imgPreview.hidden = true;
            }
            if (this.imgPreviewCard) this.imgPreviewCard.hidden = true;
            if (this.imgEmpty) this.imgEmpty.hidden = false;
            if (this.imgMeta) this.imgMeta.hidden = true;

            const shell = document.getElementById('wizardImageShell');
            if (shell) shell.classList.remove('has-image');
        }

        bindEvents() {
            // Navigation
            if (this.btnNext) {
                this.btnNext.addEventListener('click', () => this.nextStep());
            }
            if (this.btnPrev) {
                this.btnPrev.addEventListener('click', () => this.prevStep());
            }
            if (this.btnFinish) {
                this.btnFinish.addEventListener('click', () => this.submitWizard());
            }
            if (this.btnQuickSave) {
                this.btnQuickSave.addEventListener('click', () => this.submitWizard());
            }
            if (this.btnClose) {
                this.btnClose.addEventListener('click', () => this.handleClose());
            }

            // Step 1: Language selection
            document.querySelectorAll('[data-wizard-select="lang"]').forEach(card => {
                card.addEventListener('click', () => {
                    const lang = card.dataset.value;
                    this.selectLanguage(lang, card);
                });
            });

            // Step 2: Title input & real-time normalization
            if (this.inputTitle) {
                this.inputTitle.addEventListener('input', () => {
                    this.handleTitleInput();
                });
            }

            // Step 3: Date input
            if (this.inputDate) {
                this.inputDate.addEventListener('input', () => {
                    this.wizardData.created_at = this.inputDate.value;
                    this.saveState();
                });
            }

            // Step 4: Introduction input & real-time normalization
            if (this.inputIntro) {
                this.inputIntro.addEventListener('input', () => {
                    this.handleIntroInput();
                });
            }

            // Step 5: Content sync
            if (this.inputContent) {
                this.inputContent.addEventListener('input', () => {
                    this.wizardData.content = this.inputContent.value;
                    this.saveState();
                });
            }

            // Step 7: Featured (Deck) selection
            document.querySelectorAll('[data-wizard-select="deck"]').forEach(card => {
                card.addEventListener('click', () => {
                    const deckVal = card.dataset.value;
                    this.selectDeck(deckVal, card);
                });
            });

            // Step 8: ACL selection
            document.querySelectorAll('[data-wizard-select="acl"]').forEach(card => {
                card.addEventListener('click', () => {
                    const aclVal = card.dataset.value;
                    const aclLabel = card.querySelector('.wizard-card-title')?.textContent || '';
                    this.selectAcl(aclVal, aclLabel, card);
                });
            });

            // Step 9: Publish status selection
            document.querySelectorAll('[data-wizard-select="status"]').forEach(card => {
                card.addEventListener('click', () => {
                    const statusVal = card.dataset.value;
                    this.selectStatus(statusVal, card);
                });
            });
        }

        handleClose() {
            const listUrl = (window.SITE_URL || '/') + 'mod_announcements/list';
            window.location.href = listUrl;
        }

        selectLanguage(lang, targetCard) {
            document.querySelectorAll('[data-wizard-select="lang"]').forEach(c => c.classList.remove('active'));
            if (targetCard) targetCard.classList.add('active');

            this.wizardData.lang = lang;
            if (this.hiddenLang) this.hiddenLang.value = lang;

            this.updateSummary();
            this.saveState();
            this.updateUI();
        }

        selectDeck(deckVal, targetCard) {
            document.querySelectorAll('[data-wizard-select="deck"]').forEach(c => c.classList.remove('active'));
            if (targetCard) targetCard.classList.add('active');

            this.wizardData.deck = deckVal;
            if (this.hiddenDeck) this.hiddenDeck.value = deckVal;

            this.updateSummary();
            this.saveState();
            this.updateUI();
        }

        selectAcl(aclVal, aclLabel, targetCard) {
            document.querySelectorAll('[data-wizard-select="acl"]').forEach(c => c.classList.remove('active'));
            if (targetCard) targetCard.classList.add('active');

            this.wizardData.acl_id = aclVal;
            this.wizardData.acl_label = aclLabel;
            if (this.hiddenAcl) this.hiddenAcl.value = aclVal;

            this.updateSummary();
            this.saveState();
            this.updateUI();
        }

        selectStatus(statusVal, targetCard) {
            document.querySelectorAll('[data-wizard-select="status"]').forEach(c => c.classList.remove('active'));
            if (targetCard) targetCard.classList.add('active');

            this.wizardData.status = statusVal;
            if (this.hiddenStatus) this.hiddenStatus.value = statusVal;

            this.updateSummary();
            this.saveState();
            this.updateUI();
        }

        handleTitleInput() {
            if (!this.inputTitle) return;

            const start = this.inputTitle.selectionStart;
            const end = this.inputTitle.selectionEnd;
            const originalVal = this.inputTitle.value;

            // Normalize Kurdish typos if normalizer is present
            const normalizedVal = typeof window.normalizeKurdishTypos === 'function'
                ? window.normalizeKurdishTypos(originalVal, this.wizardData.lang || 'ku')
                : originalVal;

            if (originalVal !== normalizedVal) {
                this.inputTitle.value = normalizedVal;
                this.inputTitle.setSelectionRange(start, end);
            }

            const len = this.inputTitle.value.length;
            if (this.counterTitle) {
                this.counterTitle.textContent = `${len} / 70`;
            }

            // Auto-generate legacy SEO URL (Wikipedia style parity: case preserved, spaces to _)
            const seoVal = this.generateLegacySeo(this.inputTitle.value);
            if (this.hiddenSeo) {
                this.hiddenSeo.value = seoVal;
            }

            this.wizardData.name = this.inputTitle.value;
            this.wizardData.seo = seoVal;

            if (this.msgTitle && len >= 1 && len <= 70) {
                this.msgTitle.classList.remove('is-visible');
            }

            this.updateSummary();
            this.updateUI();
            this.saveState();
        }

        handleIntroInput() {
            if (!this.inputIntro) return;

            const start = this.inputIntro.selectionStart;
            const end = this.inputIntro.selectionEnd;
            const originalVal = this.inputIntro.value;

            // Normalize Kurdish typos
            const normalizedVal = typeof window.normalizeKurdishTypos === 'function'
                ? window.normalizeKurdishTypos(originalVal, this.wizardData.lang || 'ku')
                : originalVal;

            if (originalVal !== normalizedVal) {
                this.inputIntro.value = normalizedVal;
                this.inputIntro.setSelectionRange(start, end);
            }

            const len = this.inputIntro.value.length;
            if (this.counterIntro) {
                this.counterIntro.textContent = `${len} / 120`;
                this.counterIntro.classList.remove('is-valid', 'is-warning', 'is-error');

                if (len >= 90 && len <= 120) {
                    this.counterIntro.classList.add('is-valid');
                    if (this.msgIntro) this.msgIntro.classList.remove('is-visible');
                } else if (len > 120) {
                    this.counterIntro.classList.add('is-error');
                    if (this.msgIntro) this.msgIntro.classList.add('is-visible');
                } else if (len > 0) {
                    this.counterIntro.classList.add('is-warning');
                    if (this.msgIntro) this.msgIntro.classList.add('is-visible');
                }
            }

            this.wizardData.summary = this.inputIntro.value;
            this.updateUI();
            this.saveState();
        }

        generateLegacySeo(text) {
            if (!text) return '';
            if (typeof window.makeLegacySeoValue === 'function') {
                return window.makeLegacySeoValue(text);
            }
            return text
                .replace(/['’"”„“]/g, '')
                .replace(/[^\p{L}\p{N}\s_-]/gu, ' ')
                .trim()
                .replace(/[\s-]+/g, '_')
                .replace(/_+/g, '_');
        }

        getRichContent() {
            if (typeof window.tinymce !== 'undefined' && window.tinymce.get('wizardContentTextarea')) {
                return window.tinymce.get('wizardContentTextarea').getContent() || '';
            }
            return this.inputContent ? this.inputContent.value : '';
        }

        validateStep(step) {
            switch (step) {
                case 1: // Lang
                    return Boolean(this.wizardData.lang || this.hiddenLang?.value);
                case 2: { // Title (1..70 chars)
                    const title = (this.inputTitle?.value || '').trim();
                    return title.length >= 1 && title.length <= 70;
                }
                case 3: { // Date
                    return Boolean(this.inputDate?.value || this.wizardData.created_at);
                }
                case 4: { // Intro (90..120 chars)
                    const intro = (this.inputIntro?.value || '').trim();
                    return intro.length >= 90 && intro.length <= 120;
                }
                case 5: { // Content (min 10 characters text)
                    const rawContent = this.getRichContent();
                    const cleanText = rawContent.replace(/<[^>]*>/g, '').trim();
                    return cleanText.length >= 10;
                }
                case 6: // Image (optional in wizard, or valid)
                    return true;
                case 7: // Featured (y or n)
                    return Boolean(this.wizardData.deck || this.hiddenDeck?.value);
                case 8: // ACL (valid ID)
                    return typeof this.wizardData.acl_id !== 'undefined';
                case 9: // Publish (y or n)
                    return Boolean(this.wizardData.status || this.hiddenStatus?.value);
                default:
                    return true;
            }
        }

        nextStep() {
            // Sync TinyMCE content before validation
            this.wizardData.content = this.getRichContent();

            if (!this.validateStep(this.currentStep)) {
                this.shakeCurrentStep();
                return;
            }

            if (this.currentStep < TOTAL_STEPS) {
                this.currentStep++;
                this.updateUI();
                this.saveState();
            }
        }

        prevStep() {
            if (this.currentStep > 1) {
                this.currentStep--;
                this.updateUI();
                this.saveState();
            }
        }

        shakeCurrentStep() {
            const stepEl = document.getElementById(`wizardStep${this.currentStep}`);
            if (stepEl) {
                stepEl.classList.remove('shake');
                void stepEl.offsetWidth; // Trigger reflow
                stepEl.classList.add('shake');

                // Highlight specific messages
                if (this.currentStep === 2) {
                    if (this.msgTitle) this.msgTitle.classList.add('is-visible');
                    this.inputTitle?.focus();
                } else if (this.currentStep === 4) {
                    if (this.msgIntro) this.msgIntro.classList.add('is-visible');
                    this.inputIntro?.focus();
                }
            }
        }

        updateUI() {
            // Show active step
            document.querySelectorAll('.wizard-step').forEach((stepEl, idx) => {
                const stepNum = idx + 1;
                stepEl.classList.toggle('active', stepNum === this.currentStep);
            });

            // Update Header
            if (this.stepNameEl) {
                this.stepNameEl.textContent = this.stepTitles[this.currentStep - 1] || '';
            }
            if (this.stepBadgeEl) {
                this.stepBadgeEl.textContent = `${this.currentStep}/${TOTAL_STEPS}`;
            }

            // Update Progress Bar
            if (this.progressFillEl) {
                const progressPct = ((this.currentStep / TOTAL_STEPS) * 100).toFixed(2);
                this.progressFillEl.style.width = `${progressPct}%`;
            }

            // Update Footer Buttons
            if (this.btnPrev) {
                this.btnPrev.disabled = (this.currentStep === 1);
            }

            const isValid = this.validateStep(this.currentStep);

            if (this.btnNext) {
                this.btnNext.style.display = (this.currentStep === TOTAL_STEPS) ? 'none' : 'inline-flex';
                this.btnNext.classList.toggle('btn-invalid', !isValid);
            }

            if (this.btnFinish) {
                this.btnFinish.style.display = (this.currentStep === TOTAL_STEPS) ? 'inline-flex' : 'none';
            }

            // Update summary if on step 9
            if (this.currentStep === TOTAL_STEPS) {
                this.updateSummary();
            }

            // Scroll container to top on step change
            const scrollContainer = document.querySelector('.announcements-wizard-content');
            if (scrollContainer) {
                scrollContainer.scrollTop = 0;
            }
        }

        updateSummary() {
            if (this.sumLang) {
                this.sumLang.textContent = (this.wizardData.lang || this.hiddenLang?.value || '').toUpperCase();
            }
            if (this.sumTitle) {
                this.sumTitle.textContent = this.inputTitle?.value || this.wizardData.name || '-';
            }
            if (this.sumDate) {
                this.sumDate.textContent = this.inputDate?.value || this.wizardData.created_at || '-';
            }
            if (this.sumFeatured) {
                const isFeatured = (this.wizardData.deck === 'y' || this.hiddenDeck?.value === 'y');
                this.sumFeatured.textContent = isFeatured
                    ? this.getI18n('label.featured_yes', 'Featured')
                    : this.getI18n('label.featured_no', 'Standard');
                this.sumFeatured.className = `wizard-summary-badge ${isFeatured ? 'badge-primary' : 'badge-draft'}`;
            }
            if (this.sumAcl) {
                const aclId = this.wizardData.acl_id || this.hiddenAcl?.value || '0';
                this.sumAcl.textContent = aclId === '0'
                    ? this.getI18n('label.acl_public', 'Public')
                    : (this.wizardData.acl_label || `ACL #${aclId}`);
            }
            if (this.sumStatus) {
                const isPublished = (this.wizardData.status === 'y' || this.hiddenStatus?.value === 'y');
                this.sumStatus.textContent = isPublished
                    ? this.getI18n('label.publish_yes', 'Published')
                    : this.getI18n('label.publish_no', 'Draft');
                this.sumStatus.className = `wizard-summary-badge ${isPublished ? 'badge-success' : 'badge-draft'}`;
            }
        }

        saveState() {
            try {
                const state = {
                    currentStep: this.currentStep,
                    wizardData: this.wizardData,
                    timestamp: Date.now()
                };
                localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
            } catch (e) {
                console.warn('Failed to save wizard state to localStorage:', e);
            }
        }

        restoreState() {
            try {
                const raw = localStorage.getItem(STORAGE_KEY);
                if (!raw) return;

                const parsed = JSON.parse(raw);
                if (!parsed || !parsed.timestamp || (Date.now() - parsed.timestamp > STATE_MAX_AGE)) {
                    localStorage.removeItem(STORAGE_KEY);
                    return;
                }

                if (parsed.wizardData) {
                    this.wizardData = Object.assign(this.wizardData, parsed.wizardData);

                    // Restore inputs
                    if (this.wizardData.lang) {
                        const langCard = document.querySelector(`[data-wizard-select="lang"][data-value="${this.wizardData.lang}"]`);
                        if (langCard) this.selectLanguage(this.wizardData.lang, langCard);
                    }
                    if (this.wizardData.name && this.inputTitle) {
                        this.inputTitle.value = this.wizardData.name;
                        this.handleTitleInput();
                    }
                    if (this.wizardData.created_at && this.inputDate) {
                        this.inputDate.value = this.wizardData.created_at;
                    }
                    if (this.wizardData.summary && this.inputIntro) {
                        this.inputIntro.value = this.wizardData.summary;
                        this.handleIntroInput();
                    }
                    if (this.wizardData.content && this.inputContent) {
                        this.inputContent.value = this.wizardData.content;
                    }
                    if (this.wizardData.deck) {
                        const deckCard = document.querySelector(`[data-wizard-select="deck"][data-value="${this.wizardData.deck}"]`);
                        if (deckCard) this.selectDeck(this.wizardData.deck, deckCard);
                    }
                    if (this.wizardData.acl_id) {
                        const aclCard = document.querySelector(`[data-wizard-select="acl"][data-value="${this.wizardData.acl_id}"]`);
                        if (aclCard) {
                            const label = aclCard.querySelector('.wizard-card-title')?.textContent || '';
                            this.selectAcl(this.wizardData.acl_id, label, aclCard);
                        }
                    }
                    if (this.wizardData.status) {
                        const statusCard = document.querySelector(`[data-wizard-select="status"][data-value="${this.wizardData.status}"]`);
                        if (statusCard) this.selectStatus(this.wizardData.status, statusCard);
                    }
                }

                if (parsed.currentStep && parsed.currentStep >= 1 && parsed.currentStep <= TOTAL_STEPS) {
                    this.currentStep = parsed.currentStep;
                }
            } catch (e) {
                console.warn('Failed to restore wizard state from localStorage:', e);
            }
        }

        clearSavedState() {
            try {
                localStorage.removeItem(STORAGE_KEY);
            } catch (e) {
                console.warn('Failed to clear wizard state:', e);
            }
        }

        submitWizard() {
            // Sync Rich Content
            this.wizardData.content = this.getRichContent();
            if (this.inputContent) {
                this.inputContent.value = this.wizardData.content;
            }

            // Validate all steps from 1 to 9
            for (let step = 1; step <= TOTAL_STEPS; step++) {
                if (!this.validateStep(step)) {
                    this.currentStep = step;
                    this.updateUI();
                    this.shakeCurrentStep();
                    return;
                }
            }

            // Sync hidden form fields
            if (this.hiddenLang) this.hiddenLang.value = this.wizardData.lang;
            if (this.hiddenSeo) this.hiddenSeo.value = this.wizardData.seo;
            if (this.hiddenDeck) this.hiddenDeck.value = this.wizardData.deck;
            if (this.hiddenAcl) this.hiddenAcl.value = this.wizardData.acl_id;
            if (this.hiddenStatus) this.hiddenStatus.value = this.wizardData.status;

            // Clear saved state upon submission
            this.clearSavedState();

            // Submit form
            if (this.btnFinish) {
                this.btnFinish.disabled = true;
                this.btnFinish.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>...';
            }
            if (this.btnQuickSave) {
                this.btnQuickSave.disabled = true;
            }

            this.form.submit();
        }
    }

    // Initialize on DOMContentLoaded
    document.addEventListener('DOMContentLoaded', () => {
        window.announcementWizard = new AnnouncementWizardController();
    });

})();
