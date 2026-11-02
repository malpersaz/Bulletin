/**
 * Amed Framework - Event Creation 9-Step Wizard JS
 * templates/admin/assets/js/mod_event_wizard.js
 */

class EventWizardManager {
    constructor() {
        this.wizard = document.getElementById('contentWizard');
        if (!this.wizard) return;

        this.currentStep = 1;
        this.totalSteps = 9;
        this.stepTitles = [
            'Language & Category',
            'Event Title & Subject',
            'Schedule & Venue',
            'Participants & Guests',
            'Summary & Lead Text',
            'Detailed Content',
            'Poster Image (3:4)',
            'Media & Access',
            'Review & Publish'
        ];

        this.init();
    }

    init() {
        this.bindEvents();
        this.updateStepUI();

        // Auto open wizard if on add page
        const addShell = document.getElementById('eventAddShell');
        if (addShell) {
            this.wizard.classList.add('active');
        }
    }

    bindEvents() {
        // Navigation buttons
        document.querySelectorAll('[data-action="next-wizard-step"]').forEach(btn => {
            btn.addEventListener('click', () => this.nextStep());
        });

        document.querySelectorAll('[data-action="prev-wizard-step"]').forEach(btn => {
            btn.addEventListener('click', () => this.prevStep());
        });

        document.querySelectorAll('[data-action="submit-content-wizard"], [data-action="finish-wizard-step"]').forEach(btn => {
            btn.addEventListener('click', () => this.submitWizard());
        });

        document.querySelectorAll('[data-action="close-content-wizard"]').forEach(btn => {
            btn.addEventListener('click', () => {
                const listUrl = btn.dataset.listUrl || '/mod_event/list';
                location.href = listUrl;
            });
        });

        // Language selection
        document.querySelectorAll('[data-action="select-wizard-lang"]').forEach(card => {
            card.addEventListener('click', () => {
                const lang = card.dataset.lang;
                document.querySelectorAll('[data-action="select-wizard-lang"]').forEach(c => c.classList.remove('active'));
                card.classList.add('active');

                const hiddenLang = document.getElementById('wizardHiddenLang');
                if (hiddenLang) hiddenLang.value = lang;

                const summaryLang = document.getElementById('summaryLang');
                if (summaryLang) summaryLang.textContent = lang.toUpperCase();
            });
        });

        // Deck selection
        document.querySelectorAll('[data-action="select-wizard-deck"]').forEach(card => {
            card.addEventListener('click', () => {
                const val = card.dataset.value;
                document.querySelectorAll('[data-action="select-wizard-deck"]').forEach(c => c.classList.remove('active'));
                card.classList.add('active');

                const hidden = document.getElementById('wizardHiddenDeck');
                if (hidden) hidden.value = val;
            });
        });

        // Slider selection
        document.querySelectorAll('[data-action="select-wizard-slider"]').forEach(card => {
            card.addEventListener('click', () => {
                const val = card.dataset.value;
                document.querySelectorAll('[data-action="select-wizard-slider"]').forEach(c => c.classList.remove('active'));
                card.classList.add('active');

                const hidden = document.getElementById('wizardHiddenSlider');
                if (hidden) hidden.value = val;
            });
        });

        // Publish status selection
        document.querySelectorAll('[data-action="select-wizard-publish-status"]').forEach(card => {
            card.addEventListener('click', () => {
                const status = card.dataset.status;
                document.querySelectorAll('[data-action="select-wizard-publish-status"]').forEach(c => c.classList.remove('active'));
                card.classList.add('active');

                const hidden = document.getElementById('wizardPublishHidden');
                if (hidden) hidden.value = status;
            });
        });

        // Title character counter
        const titleInput = document.getElementById('wizardTitleInput');
        const titleCounter = document.getElementById('titleCharCounter');
        if (titleInput && titleCounter) {
            titleInput.addEventListener('input', () => {
                titleCounter.textContent = `${titleInput.value.length} / 70`;
            });
        }

        // Intro summary counter
        const introInput = document.getElementById('wizardIntroInput');
        const introCounter = document.getElementById('introCharCounter');
        if (introInput && introCounter) {
            introInput.addEventListener('input', () => {
                introCounter.textContent = `${introInput.value.length} / 255`;
            });
        }
    }

    validateStep(step) {
        if (step === 2) {
            const title = document.getElementById('wizardTitleInput')?.value.trim();
            if (!title) {
                alert('Please enter an event title.');
                return false;
            }
        }
        if (step === 3) {
            const startDate = document.getElementById('wizardStartDateInput')?.value;
            if (!startDate) {
                alert('Please enter an event start date.');
                return false;
            }
            const endDate = document.getElementById('wizardEndDateInput')?.value;
            if (endDate && endDate < startDate) {
                alert('Event end date cannot be earlier than start date.');
                return false;
            }
        }
        if (step === 5) {
            const summary = document.getElementById('wizardIntroInput')?.value.trim();
            if (!summary || summary.length < 90) {
                alert('Please provide an event summary with at least 90 characters.');
                return false;
            }
        }
        return true;
    }

    nextStep() {
        if (!this.validateStep(this.currentStep)) return;

        if (this.currentStep < this.totalSteps) {
            this.currentStep++;
            this.updateStepUI();
        }
    }

    prevStep() {
        if (this.currentStep > 1) {
            this.currentStep--;
            this.updateStepUI();
        }
    }

    updateStepUI() {
        // Hide all steps, show current
        for (let i = 1; i <= this.totalSteps; i++) {
            const stepEl = document.getElementById(`wizardStep${i}`);
            if (stepEl) {
                if (i === this.currentStep) {
                    stepEl.classList.add('active');
                } else {
                    stepEl.classList.remove('active');
                }
            }
        }

        // Update progress bar
        const progressFill = document.getElementById('wizardProgressFill');
        if (progressFill) {
            const pct = (this.currentStep / this.totalSteps) * 100;
            progressFill.style.width = `${pct}%`;
        }

        // Update counter & title
        const counter = document.getElementById('wizardStepCounter');
        if (counter) {
            counter.textContent = `${this.currentStep}/${this.totalSteps}`;
        }
        const title = document.getElementById('wizardStepTitle');
        if (title) {
            title.textContent = this.stepTitles[this.currentStep - 1] || '';
        }

        // Navigation buttons state
        const prevBtn = document.getElementById('btnWizardPrev');
        const nextBtn = document.getElementById('btnWizardNext');
        const finishBtn = document.getElementById('btnWizardFinish');

        if (prevBtn) prevBtn.disabled = (this.currentStep === 1);
        if (nextBtn) {
            nextBtn.hidden = (this.currentStep === this.totalSteps);
        }
        if (finishBtn) {
            finishBtn.hidden = (this.currentStep !== this.totalSteps);
        }

        // Populate step 9 summary if entering step 9
        if (this.currentStep === 9) {
            this.populateSummary();
        }
    }

    populateSummary() {
        const titleVal = document.getElementById('wizardTitleInput')?.value || '-';
        const startVal = document.getElementById('wizardStartDateInput')?.value || '-';
        const venueVal = document.getElementById('wizardLocationInput')?.value || '-';

        const summaryTitle = document.getElementById('summaryTitle');
        if (summaryTitle) summaryTitle.textContent = titleVal;

        const summarySchedule = document.getElementById('summarySchedule');
        if (summarySchedule) summarySchedule.textContent = startVal;

        const summaryVenue = document.getElementById('summaryVenue');
        if (summaryVenue) summaryVenue.textContent = venueVal;
    }

    submitWizard() {
        if (!this.validateStep(this.currentStep)) return;

        const form = document.getElementById('addForm');
        if (form) {
            form.submit();
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.eventWizardManager = new EventWizardManager();
});
