/**
 * mod_faq.js - Mobile-First FAQ Manager JavaScript
 * Amed Framework (AF/Business CMS)
 *
 * Features:
 * - Dynamic addition of question cards with auto-focus
 * - Real-time character counters for Question (255 chars) and Answer (5000 chars)
 * - 25 questions ceiling limit enforcement
 * - Touch drag-and-drop reordering using SortableJS
 * - Single item and batch AJAX saves with toast/snackbar feedback
 * - Status toggle switch with immediate AJAX persistence
 * - Safe footer delete button with confirmation modal
 * - Strict Code Separation: 100% External JS
 */

(function () {
    'use strict';

    class FaqManager {
        constructor() {
            this.shell = document.querySelector('.faq-modal-shell');
            if (!this.shell) return;

            this.modName = this.shell.dataset.modName || '';
            this.vid = parseInt(this.shell.dataset.vid || '0', 10);
            this.lang = this.shell.dataset.lang || 'en';
            this.maxAllowed = 25;

            this.faqLang = {};
            if (this.shell.dataset.faqLang) {
                try {
                    this.faqLang = JSON.parse(this.shell.dataset.faqLang);
                } catch (e) {
                    console.error('Failed to parse FAQ translations:', e);
                }
            }

            this.cardList = document.getElementById('faqCardList');
            this.emptyState = document.getElementById('faqEmptyState');
            this.countBadge = document.getElementById('faqCountBadge');
            this.addHeaderBtn = document.getElementById('faqAddHeaderBtn');
            this.fabBtn = document.getElementById('faqFabBtn');
            this.emptyAddBtn = document.getElementById('faqEmptyAddBtn');
            this.saveAllBtn = document.getElementById('faqSaveAllBtn');

            this.sortableInstance = null;
            this.reorderDebounceTimer = null;

            this.init();
        }

        getLang(key, fallback = '') {
            if (!this.faqLang) return fallback;
            if (this.faqLang[key] !== undefined && typeof this.faqLang[key] === 'string') {
                return this.faqLang[key];
            }
            if (typeof key === 'string' && key.includes('.')) {
                const parts = key.split('.');
                let cur = this.faqLang;
                for (const p of parts) {
                    if (cur && cur[p] !== undefined) cur = cur[p];
                    else { cur = undefined; break; }
                }
                if (cur !== undefined && typeof cur === 'string') return cur;
            }
            if (this.faqLang.validation && this.faqLang.validation[key] !== undefined) {
                return this.faqLang.validation[key];
            }
            if (this.faqLang.message && this.faqLang.message.success && this.faqLang.message.success[key] !== undefined) {
                return this.faqLang.message.success[key];
            }
            if (this.faqLang.message && this.faqLang.message.error && this.faqLang.message.error[key] !== undefined) {
                return this.faqLang.message.error[key];
            }
            if (this.faqLang.admin && this.faqLang.admin[key] !== undefined) {
                return this.faqLang.admin[key];
            }
            if (typeof key === 'string' && key.startsWith('success_')) {
                const sub = key.replace('success_', '');
                if (this.faqLang.message && this.faqLang.message.success && this.faqLang.message.success[sub] !== undefined) {
                    return this.faqLang.message.success[sub];
                }
            }
            if (typeof key === 'string' && key.startsWith('error_')) {
                const sub = key.replace('error_', '');
                if (this.faqLang.message && this.faqLang.message.error && this.faqLang.message.error[sub] !== undefined) {
                    return this.faqLang.message.error[sub];
                }
            }
            if (typeof window !== 'undefined' && window.FAQ_LANG && window.FAQ_LANG[key] !== undefined) {
                return window.FAQ_LANG[key];
            }
            return fallback;
        }

        init() {
            this.bindGlobalEvents();
            this.bindCardEvents();
            this.initSortable();
            this.updateCounter();
        }

        bindGlobalEvents() {
            if (this.addHeaderBtn) {
                this.addHeaderBtn.addEventListener('click', () => this.addNewItem());
            }
            if (this.fabBtn) {
                this.fabBtn.addEventListener('click', () => this.addNewItem());
            }
            if (this.emptyAddBtn) {
                this.emptyAddBtn.addEventListener('click', () => this.addNewItem());
            }
            if (this.saveAllBtn) {
                this.saveAllBtn.addEventListener('click', () => this.saveAll());
            }
        }

        initSortable() {
            if (!this.cardList || typeof Sortable === 'undefined') return;

            if (this.sortableInstance) {
                this.sortableInstance.destroy();
            }

            this.sortableInstance = new Sortable(this.cardList, {
                animation: 250,
                handle: '.faq-drag-handle',
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                onEnd: () => {
                    this.handleReorder();
                }
            });
        }

        bindCardEvents() {
            if (!this.cardList) return;

            this.cardList.querySelectorAll('.faq-item-card').forEach((card) => {
                this.bindSingleCardEvents(card);
            });
        }

        bindSingleCardEvents(card) {
            const questionInput = card.querySelector('.faq-question-input');
            const answerInput = card.querySelector('.faq-answer-input');
            const questionCounter = card.querySelector('.faq-question-counter .current-count');
            const answerCounter = card.querySelector('.faq-answer-counter .current-count');
            const previewText = card.querySelector('.faq-preview-text');
            const statusToggleBtn = card.querySelector('.faq-card-status-toggle');
            const statusCheckbox = card.querySelector('.faq-status-checkbox');
            const statusLabel = card.querySelector('.faq-switch-label');
            const statusPill = card.querySelector('.faq-status-pill');
            const deleteBtn = card.querySelector('.faq-card-footer-delete-btn') || card.querySelector('.faq-card-delete-btn');
            const expandBtn = card.querySelector('.faq-card-expand-btn');
            const cardHeader = card.querySelector('.faq-card-header');
            const saveSingleBtn = card.querySelector('.faq-save-single-btn');

            // Question input live counter & preview sync
            if (questionInput) {
                questionInput.addEventListener('input', () => {
                    questionInput.classList.remove('is-duplicate');
                    const len = questionInput.value.length;
                    if (questionCounter) questionCounter.textContent = len;
                    if (previewText) previewText.textContent = questionInput.value.trim() || '...';

                    const wrapper = questionCounter?.closest('.faq-char-counter');
                    if (wrapper) {
                        wrapper.classList.toggle('limit-exceeded', len > 255);
                    }
                });
            }

            // Answer input live counter
            if (answerInput) {
                answerInput.addEventListener('input', () => {
                    const len = answerInput.value.length;
                    if (answerCounter) answerCounter.textContent = len;

                    const wrapper = answerCounter?.closest('.faq-char-counter');
                    if (wrapper) {
                        wrapper.classList.toggle('limit-exceeded', len > 5000);
                    }
                });
            }

            // Card expand / collapse toggle on header click
            if (cardHeader) {
                const toggleCardCollapse = (e) => {
                    if (e && (e.target.closest('.faq-drag-handle') ||
                        e.target.closest('.faq-card-status-toggle') ||
                        e.target.closest('.faq-card-delete-btn') ||
                        e.target.closest('.faq-card-footer-delete-btn'))) {
                        return;
                    }
                    card.classList.toggle('is-collapsed');
                    const isCollapsed = card.classList.contains('is-collapsed');
                    cardHeader.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
                    expandBtn?.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
                };

                cardHeader.addEventListener('click', toggleCardCollapse);
                cardHeader.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        if (e.target === cardHeader || e.target.closest('.faq-card-title-preview')) {
                            e.preventDefault();
                            toggleCardCollapse(null);
                        }
                    }
                });
            }

            // Status toggle button in header
            if (statusToggleBtn && statusCheckbox) {
                statusToggleBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    statusCheckbox.checked = !statusCheckbox.checked;
                    this.handleStatusChange(card, statusCheckbox.checked);
                });
            }

            // Status toggle checkbox in card body
            if (statusCheckbox) {
                statusCheckbox.addEventListener('change', () => {
                    this.handleStatusChange(card, statusCheckbox.checked);
                });
            }

            // Delete button (in footer or legacy header)
            if (deleteBtn) {
                deleteBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.deleteCard(card);
                });
            }

            // Single item save
            if (saveSingleBtn) {
                saveSingleBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.saveSingleItem(card);
                });
            }
        }

        handleStatusChange(card, isPublished) {
            const statusStr = isPublished ? 'y' : 'n';
            const statusLabelStr = isPublished ? this.getLang('published', 'Published') : this.getLang('draft', 'Draft');
            card.dataset.status = statusStr;

            const statusPill = card.querySelector('.faq-status-pill');
            if (statusPill) {
                statusPill.className = 'faq-status-pill ' + (isPublished ? 'status-published' : 'status-draft');
                statusPill.textContent = statusLabelStr;
            }

            const statusToggleBtn = card.querySelector('.faq-card-status-toggle');
            if (statusToggleBtn) {
                statusToggleBtn.dataset.status = statusStr;
            }

            const switchLabel = card.querySelector('.faq-switch-label');
            if (switchLabel) {
                switchLabel.textContent = statusLabelStr;
            }

            const itemId = parseInt(card.dataset.id || '0', 10);
            if (itemId > 0) {
                const formData = new FormData();
                formData.append('id', itemId);
                formData.append('status', statusStr);

                fetch(`${SITE_URL}mod_faq/ajax_toggle_status`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                })
                .then(res => res.json())
                .then(result => {
                    if (result.status === 'success') {
                        this.notify(this.getLang('success_status', result.message || 'Status updated.'), 'success');
                    } else {
                        this.notify(result.message || 'Failed to update status.', 'error');
                    }
                })
                .catch(err => {
                    console.error('Status toggle error:', err);
                    this.notify('Network error updating status.', 'error');
                });
            }
        }

        addNewItem() {
            const currentCount = this.cardList.querySelectorAll('.faq-item-card').length;
            if (currentCount >= this.maxAllowed) {
                this.notify(this.getLang('limit_reached', 'Maximum limit of 25 questions reached.'), 'error');
                return;
            }

            const card = document.createElement('div');
            card.className = 'faq-item-card is-new';
            card.dataset.id = '0';
            card.dataset.sort = String(currentCount);
            card.dataset.status = 'y';
            card.setAttribute('role', 'listitem');

            const publishedText = this.getLang('published', 'Published');
            const draftText = this.getLang('draft', 'Draft');
            const uid = 'new_' + Date.now() + '_' + Math.floor(Math.random() * 1000);

            card.innerHTML = `
                <div class="faq-card-header" tabindex="0" role="button" aria-expanded="true" aria-controls="faq_body_${uid}">
                    <div class="faq-drag-handle" aria-label="${this.getLang('drag_to_reorder', 'Drag to reorder')}" title="${this.getLang('drag_to_reorder', 'Drag to reorder')}">
                        <i class="bi bi-grip-vertical"></i>
                    </div>
                    <div class="faq-card-title-preview">
                        <span class="faq-preview-text"></span>
                    </div>
                    <div class="faq-card-header-actions">
                        <button type="button" class="faq-card-status-toggle" data-action="toggle-status" data-status="y" aria-label="${this.getLang('status', 'Status')}">
                            <span class="faq-status-pill status-published">${publishedText}</span>
                        </button>
                        <button type="button" class="faq-card-expand-btn" data-action="toggle-expand" aria-expanded="true" aria-controls="faq_body_${uid}" aria-label="${this.getLang('edit_question', 'Expand / Collapse')}" title="${this.getLang('edit_question', 'Expand / Collapse')}">
                            <i class="bi bi-chevron-down"></i>
                        </button>
                    </div>
                </div>

                <div class="faq-card-body" id="faq_body_${uid}" role="region" aria-label="${this.getLang('question', 'Question')} #${currentCount + 1}">
                    <div class="faq-field-group">
                        <div class="faq-field-label-row">
                            <label class="faq-label" for="faq_question_${uid}">${this.getLang('question', 'Question')}</label>
                            <span class="faq-char-counter faq-question-counter"><span class="current-count">0</span> / 255</span>
                        </div>
                        <input type="text" id="faq_question_${uid}" class="faq-input faq-question-input" name="question" maxlength="255" value="" placeholder="${this.getLang('question_placeholder', 'Enter question...')}">
                    </div>

                    <div class="faq-field-group">
                        <div class="faq-field-label-row">
                            <label class="faq-label" for="faq_answer_${uid}">${this.getLang('answer', 'Answer')}</label>
                            <span class="faq-char-counter faq-answer-counter"><span class="current-count">0</span> / 5000</span>
                        </div>
                        <textarea id="faq_answer_${uid}" class="faq-textarea faq-answer-input" name="answer" maxlength="5000" rows="3" placeholder="${this.getLang('answer_placeholder', 'Enter answer...')}"></textarea>
                    </div>

                    <div class="faq-card-footer">
                        <div class="faq-status-switch-wrapper">
                            <label class="faq-switch" for="faq_status_${uid}">
                                <input type="checkbox" id="faq_status_${uid}" class="faq-status-checkbox" checked>
                                <span class="faq-slider"></span>
                            </label>
                            <span class="faq-switch-label">${publishedText}</span>
                        </div>
                        <div class="faq-card-footer-actions">
                            <button type="button" class="faq-card-footer-delete-btn" data-action="delete" aria-label="${this.getLang('delete_question', 'Delete')}" title="${this.getLang('delete_question', 'Delete')}">
                                <i class="bi bi-trash3"></i>
                            </button>
                            <button type="button" class="faq-save-single-btn" data-action="save-single" aria-label="${this.getLang('save', 'Save')}">
                                <i class="bi bi-check2"></i>
                                <span>${this.getLang('save', 'Save')}</span>
                            </button>
                        </div>
                    </div>
                </div>
            `;

            this.cardList.appendChild(card);
            this.bindSingleCardEvents(card);
            this.updateCounter();

            const questionInput = card.querySelector('.faq-question-input');
            if (questionInput) {
                questionInput.focus();
                questionInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            if (window.navigator.vibrate) {
                window.navigator.vibrate(10);
            }
        }

        deleteCard(card) {
            const itemId = parseInt(card.dataset.id || '0', 10);

            const performRemoval = () => {
                card.style.transition = 'all 0.25s ease';
                card.style.opacity = '0';
                card.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    card.remove();
                    this.updateCounter();
                }, 250);
            };

            if (itemId === 0) {
                performRemoval();
                return;
            }

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: this.getLang('confirm_delete', 'Are you sure you want to delete this question?'),
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#b3261e',
                    cancelButtonColor: '#6750a4',
                    confirmButtonText: '<i class="bi bi-trash3"></i> ' + (window.adminLang?.context_menu?.confirm || 'Delete'),
                    cancelButtonText: '<i class="bi bi-x-lg"></i> ' + (window.adminLang?.context_menu?.cancel || 'Cancel'),
                    customClass: { popup: 'm3-swal-popup' }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const formData = new FormData();
                        formData.append('id', itemId);

                        fetch(`${SITE_URL}mod_faq/ajax_delete`, {
                            method: 'POST',
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            body: formData
                        })
                        .then(res => res.json())
                        .then(result => {
                            if (result.status === 'success') {
                                performRemoval();
                                this.notify(this.getLang('success_delete', result.message || 'Question deleted.'), 'success');
                            } else {
                                this.notify(result.message || 'Failed to delete question.', 'error');
                            }
                        })
                        .catch(err => {
                            console.error('Delete FAQ error:', err);
                            this.notify('Network error deleting question.', 'error');
                        });
                    }
                });
            } else {
                if (confirm(this.getLang('confirm_delete', 'Are you sure?'))) {
                    const formData = new FormData();
                    formData.append('id', itemId);
                    fetch(`${SITE_URL}mod_faq/ajax_delete`, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: formData
                    })
                    .then(res => res.json())
                    .then(result => {
                        if (result.status === 'success') {
                            performRemoval();
                            this.notify(this.getLang('success_delete', 'Question deleted.'), 'success');
                        } else {
                            this.notify(result.message || 'Failed to delete question.', 'error');
                        }
                    });
                }
            }
        }

        saveSingleItem(card) {
            const itemId = parseInt(card.dataset.id || '0', 10);
            const questionInput = card.querySelector('.faq-question-input');
            const answerInput = card.querySelector('.faq-answer-input');
            const statusCheckbox = card.querySelector('.faq-status-checkbox');

            const question = questionInput ? questionInput.value.trim() : '';
            const answer = answerInput ? answerInput.value.trim() : '';
            const status = (statusCheckbox && statusCheckbox.checked) ? 'y' : 'n';

            if (!question) {
                this.notify(this.getLang('question_required', 'Question is required.'), 'error');
                questionInput?.focus();
                return;
            }
            if (question.length > 255) {
                this.notify(this.getLang('question_max', 'Question cannot exceed 255 characters.'), 'error');
                questionInput?.focus();
                return;
            }

            // Check for duplicate question among open cards
            const cards = Array.from(this.cardList.querySelectorAll('.faq-item-card'));
            const normalizedQ = question.toLowerCase();
            for (const otherCard of cards) {
                if (otherCard === card) continue;
                const otherQInput = otherCard.querySelector('.faq-question-input');
                const otherQ = otherQInput ? otherQInput.value.trim().toLowerCase() : '';
                if (otherQ && otherQ === normalizedQ) {
                    this.notify(this.getLang('duplicate_question', 'This question has already been added.'), 'error');
                    questionInput?.classList.add('is-duplicate');
                    questionInput?.focus();
                    return;
                }
            }
            questionInput?.classList.remove('is-duplicate');

            if (!answer) {
                this.notify(this.getLang('answer_required', 'Answer is required.'), 'error');
                answerInput?.focus();
                return;
            }
            if (answer.length > 5000) {
                this.notify(this.getLang('answer_max', 'Answer cannot exceed 5000 characters.'), 'error');
                answerInput?.focus();
                return;
            }

            const sortOrder = cards.indexOf(card);

            const formData = new FormData();
            formData.append('id', itemId);
            formData.append('mod_name', this.modName);
            formData.append('vid', this.vid);
            formData.append('lang', this.lang);
            formData.append('question', question);
            formData.append('answer', answer);
            formData.append('sort_order', sortOrder >= 0 ? sortOrder : 0);
            formData.append('status', status);

            const saveBtn = card.querySelector('.faq-save-single-btn');
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i>';
            }

            fetch(`${SITE_URL}mod_faq/ajax_save`, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(res => res.json())
            .then(result => {
                if (result.status === 'success') {
                    if (result.item && result.item.id) {
                        card.dataset.id = String(result.item.id);
                        card.classList.remove('is-new');
                    }
                    this.notify(this.getLang(itemId > 0 ? 'success_edit' : 'success_add', result.message || 'Saved successfully.'), 'success');
                } else {
                    this.notify(result.message || 'Failed to save question.', 'error');
                    if (result.message && (result.message === this.getLang('duplicate_question') || result.message.includes(this.getLang('duplicate_question')))) {
                        questionInput?.classList.add('is-duplicate');
                    }
                }
            })
            .catch(err => {
                console.error('Save FAQ single error:', err);
                this.notify('Network error saving question.', 'error');
            })
            .finally(() => {
                if (saveBtn) {
                    saveBtn.disabled = false;
                    const btnLabel = this.getLang('save', 'Save');
                    saveBtn.innerHTML = `<i class="bi bi-check2"></i><span>${btnLabel}</span>`;
                }
            });
        }

        saveAll() {
            const cards = Array.from(this.cardList.querySelectorAll('.faq-item-card'));
            if (cards.length > this.maxAllowed) {
                this.notify(this.getLang('limit_reached', 'Maximum limit of 25 questions reached.'), 'error');
                return;
            }

            const items = [];
            const seenQuestions = new Map();
            for (let i = 0; i < cards.length; i++) {
                const card = cards[i];
                const id = parseInt(card.dataset.id || '0', 10);
                const questionInput = card.querySelector('.faq-question-input');
                const answerInput = card.querySelector('.faq-answer-input');
                const statusCheckbox = card.querySelector('.faq-status-checkbox');

                const question = questionInput ? questionInput.value.trim() : '';
                const answer = answerInput ? answerInput.value.trim() : '';
                const status = (statusCheckbox && statusCheckbox.checked) ? 'y' : 'n';

                if (!question) {
                    this.notify(`${this.getLang('question', 'Question')} #${i + 1}: ${this.getLang('question_required', 'Question is required.')}`, 'error');
                    questionInput?.focus();
                    return;
                }
                if (question.length > 255) {
                    this.notify(`${this.getLang('question', 'Question')} #${i + 1}: ${this.getLang('question_max', 'Question cannot exceed 255 characters.')}`, 'error');
                    questionInput?.focus();
                    return;
                }

                const normalizedQ = question.toLowerCase();
                if (seenQuestions.has(normalizedQ)) {
                    this.notify(`${this.getLang('question', 'Question')} #${i + 1}: ${this.getLang('duplicate_question', 'This question has already been added.')}`, 'error');
                    questionInput?.classList.add('is-duplicate');
                    questionInput?.focus();
                    return;
                }
                seenQuestions.set(normalizedQ, i);
                questionInput?.classList.remove('is-duplicate');

                if (!answer) {
                    this.notify(`${this.getLang('answer', 'Answer')} #${i + 1}: ${this.getLang('answer_required', 'Answer is required.')}`, 'error');
                    answerInput?.focus();
                    return;
                }
                if (answer.length > 5000) {
                    this.notify(`${this.getLang('answer', 'Answer')} #${i + 1}: ${this.getLang('answer_max', 'Answer cannot exceed 5000 characters.')}`, 'error');
                    answerInput?.focus();
                    return;
                }

                items.push({
                    id: id,
                    question: question,
                    answer: answer,
                    sort_order: i,
                    status: status
                });
            }

            if (this.saveAllBtn) {
                this.saveAllBtn.disabled = true;
                this.saveAllBtn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i>';
            }

            const formData = new FormData();
            formData.append('mod_name', this.modName);
            formData.append('vid', this.vid);
            formData.append('lang', this.lang);
            formData.append('items', JSON.stringify(items));

            fetch(`${SITE_URL}mod_faq/ajax_batch_save`, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(res => res.json())
            .then(result => {
                if (result.status === 'success') {
                    this.notify(this.getLang('success_batch', result.message || 'All questions saved successfully.'), 'success');
                    if (Array.isArray(result.items)) {
                        result.items.forEach((item, idx) => {
                            if (cards[idx] && item && item.id) {
                                cards[idx].dataset.id = String(item.id);
                                cards[idx].classList.remove('is-new');
                                const saveBtnText = cards[idx].querySelector('.faq-save-single-btn span');
                                if (saveBtnText) {
                                    saveBtnText.textContent = this.getLang('save', 'Save');
                                }
                            }
                        });
                    } else {
                        cards.forEach(card => card.classList.remove('is-new'));
                    }
                } else {
                    this.notify(result.message || 'Failed to batch save questions.', 'error');
                }
            })
            .catch(err => {
                console.error('Batch save error:', err);
                this.notify('Network error during batch save.', 'error');
            })
            .finally(() => {
                if (this.saveAllBtn) {
                    this.saveAllBtn.disabled = false;
                    this.saveAllBtn.innerHTML = `<i class="bi bi-cloud-arrow-up-fill"></i><span>${this.getLang('save_all', 'Save Changes')}</span>`;
                }
            });
        }

        handleReorder() {
            clearTimeout(this.reorderDebounceTimer);
            this.reorderDebounceTimer = setTimeout(() => {
                const cards = Array.from(this.cardList.querySelectorAll('.faq-item-card'));
                const order = [];

                cards.forEach((card, index) => {
                    card.dataset.sort = String(index);
                    const id = parseInt(card.dataset.id || '0', 10);
                    if (id > 0) {
                        order.push(id);
                    }
                });

                if (order.length > 0) {
                    const formData = new FormData();
                    formData.append('mod_name', this.modName);
                    formData.append('vid', this.vid);
                    formData.append('order', JSON.stringify(order));

                    fetch(`${SITE_URL}mod_faq/ajax_reorder`, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: formData
                    })
                    .then(res => res.json())
                    .then(result => {
                        if (result.status === 'success') {
                            this.notify(this.getLang('success_reorder', result.message || 'Sort order updated.'), 'success');
                        }
                    })
                    .catch(err => {
                        console.error('Reorder error:', err);
                    });
                }
            }, 300);
        }

        updateCounter() {
            const count = this.cardList ? this.cardList.querySelectorAll('.faq-item-card').length : 0;
            if (this.countBadge) {
                this.countBadge.textContent = `${count} / ${this.maxAllowed}`;
                this.countBadge.classList.toggle('limit-reached', count >= this.maxAllowed);
            }

            const isMax = count >= this.maxAllowed;
            if (this.addHeaderBtn) {
                this.addHeaderBtn.disabled = isMax;
                this.addHeaderBtn.classList.toggle('is-disabled', isMax);
            }
            if (this.fabBtn) {
                this.fabBtn.disabled = isMax;
                this.fabBtn.classList.toggle('is-disabled', isMax);
            }

            if (this.emptyState) {
                this.emptyState.classList.toggle('is-visible', count === 0);
            }
        }

        notify(message, type = 'success') {
            if (typeof window.showAFToast === 'function') {
                window.showAFToast(message, type, 2500);
            } else if (typeof window.showSnackbar === 'function') {
                window.showSnackbar(message, type, 2500);
            } else if (typeof Swal !== 'undefined') {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2500
                });
                Toast.fire({ icon: type, title: message });
            } else {
                console.log(`[FAQ Notify ${type}]:`, message);
            }
        }
    }

    // Auto-initialize when DOM ready or AJAX loaded
    function initFaqManager() {
        if (document.querySelector('.faq-modal-shell')) {
            window.faqManagerInstance = new FaqManager();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFaqManager);
    } else {
        initFaqManager();
    }

    window.initFaqManager = initFaqManager;
})();
