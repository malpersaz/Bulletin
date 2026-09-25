/**
 * mod_survey_form.js - Administrative Survey/Poll Add and Edit Form Script
 */
document.addEventListener('DOMContentLoaded', function () {
    const questionInput = document.getElementById('survey-question');
    const questionCounter = document.getElementById('question-counter');
    const optionsList = document.getElementById('options-list');
    const addOptionBtn = document.getElementById('addOptionBtn');
    const counterInput = document.getElementById('option-counter-id');

    // Question Character Counter
    function updateQuestionCounter() {
        if (!questionInput || !questionCounter) return;
        const len = questionInput.value.length;
        const maxLen = parseInt(questionInput.getAttribute('maxlength') || '255', 10);
        questionCounter.textContent = `${len} / ${maxLen}`;
        if (len > maxLen) {
            questionCounter.classList.add('is-error');
        } else {
            questionCounter.classList.remove('is-error');
        }
    }

    if (questionInput) {
        questionInput.addEventListener('input', updateQuestionCounter);
        updateQuestionCounter();
    }

    // Add Option Row
    if (addOptionBtn && optionsList) {
        let newOptionIdx = 0;
        addOptionBtn.addEventListener('click', function () {
            newOptionIdx++;
            const isEdit = optionsList.dataset.isEdit === 'true';
            const count = optionsList.children.length + 1;
            const optionLabel = addOptionBtn.dataset.optionLabel || 'Option';
            const removeLabel = addOptionBtn.dataset.removeLabel || 'Remove Option';
            const rowId = isEdit ? `option_row_new_${newOptionIdx}` : `option_row_${count}`;
            const inputName = isEdit ? `options[new_${newOptionIdx}]` : 'options[]';

            const li = document.createElement('li');
            li.className = 'option-item';
            li.id = rowId;

            li.innerHTML = `
                <div class="m3-input-wrapper">
                    <input class="m3-input-lg" type="text" name="${inputName}" placeholder=" " required>
                    <label class="m3-floating-label">${optionLabel} ${count}</label>
                </div>
                <button type="button" class="option-remove-btn" data-action="remove-option" data-target="${rowId}" aria-label="${removeLabel}">
                    <i class="bi bi-trash"></i>
                </button>
            `;

            optionsList.appendChild(li);
            if (counterInput) {
                counterInput.value = count;
            }
        });
    }

    // Remove Option Row via Event Delegation
    if (optionsList) {
        optionsList.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-action="remove-option"]');
            if (!btn) return;

            const targetId = btn.dataset.target;
            const row = document.getElementById(targetId);
            const minOptionsMsg = optionsList.dataset.minOptionsMsg || 'At least 2 options are required.';

            if (optionsList.children.length > 2) {
                if (row) {
                    row.style.opacity = '0';
                    row.style.transform = 'scale(0.9)';
                    row.style.transition = 'all 0.2s ease';
                    setTimeout(() => row.remove(), 200);
                }
            } else {
                alert(minOptionsMsg);
            }
        });
    }

    // Selection Group Active State Toggling
    document.querySelectorAll('[data-selection-group]').forEach(function (group) {
        group.addEventListener('change', function (event) {
            if (event.target.type !== 'radio') return;
            group.querySelectorAll('.selection-card').forEach(function (card) {
                const isActive = card.contains(event.target);
                card.classList.toggle('active', isActive);
            });
        });
    });
});
