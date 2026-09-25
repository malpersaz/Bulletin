/**
 * Amed Framework
 * Business Working Hours & Closures - Admin Client Script (100% AJAX Engine)
 * templates/admin/assets/js/working_hours.js
 * Version: 2.2
 */

(function () {
    'use strict';

    function initWorkingHoursPage() {
        var page = document.querySelector('.wh-page');
        if (!page) return;

        // Prevent double initialization on the same DOM element
        if (page.dataset.whInitialized === 'true') return;
        page.dataset.whInitialized = 'true';

        var saveUrl = page.getAttribute('data-save-url') || (window.location.pathname + window.location.search);
        var autoSaveTimer = null;
        var isSaving = false;
        var pendingSave = false;

        var indicator = document.getElementById('whAutosaveIndicator');
        var workingHoursForm = document.getElementById('workingHoursForm');

        var texts = {
            saving: page.getAttribute('data-auto-saving') || 'Saving...',
            saved: page.getAttribute('data-auto-saved') || 'Saved',
            open: page.getAttribute('data-status-open') || 'Open',
            closed: page.getAttribute('data-status-closed') || 'Closed',
            error: page.getAttribute('data-error-msg') || 'An error occurred while saving.',
            confirmDelete: page.getAttribute('data-confirm-delete') || 'Are you sure you want to delete this closure?',
            deleteBtn: page.getAttribute('data-delete-btn') || 'Delete',
            cancelBtn: page.getAttribute('data-cancel-btn') || 'Cancel',
            deleteSuccess: page.getAttribute('data-delete-success') || 'Closure record deleted successfully.',
            addSuccess: page.getAttribute('data-add-success') || 'Closure record saved successfully.',
            typeHoliday: page.getAttribute('data-type-holiday') || 'Public Holiday',
            typeVacation: page.getAttribute('data-type-vacation') || 'Staff Vacation',
            typeMaintenance: page.getAttribute('data-type-maintenance') || 'Facility Maintenance',
            typeEmergency: page.getAttribute('data-type-emergency') || 'Emergency Closure',
            fullDay: page.getAttribute('data-full-day') || 'Full Day Closure',
            yearlyRecurring: page.getAttribute('data-yearly-recurring') || 'Repeats Yearly',
            noClosures: page.getAttribute('data-no-closures') || 'No upcoming closures or holidays defined.',
            copyWeekdaysSuccess: page.getAttribute('data-copy-weekdays-success') || 'Monday working hours copied to all weekdays.',
            closedSummary: page.getAttribute('data-closed-summary') || 'Closed all day',
            breakLabel: page.getAttribute('data-break-label') || 'Break / Midday Pause'
        };

        initDayToggles();
        initTimeAndNoteInputs();
        initBreakControls();
        initCopyWeekdaysShortcut();
        initClosureModal();
        initClosureDelete();

        /**
         * Sets the visual status of the auto-save indicator.
         * Appears dynamically on active saving/saved, completely hidden when idle.
         */
        function setIndicatorState(state, customText) {
            if (!indicator) return;
            var textEl = indicator.querySelector('.wh-sync-text');

            indicator.classList.remove('is-saving', 'is-saved', 'is-error', 'is-hidden');

            if (state === 'saving') {
                indicator.classList.add('is-saving');
                if (textEl) textEl.textContent = customText || texts.saving;
            } else if (state === 'saved') {
                indicator.classList.add('is-saved');
                if (textEl) textEl.textContent = customText || texts.saved;
                setTimeout(function () {
                    if (!indicator.classList.contains('is-saving')) {
                        indicator.classList.remove('is-saved');
                        indicator.classList.add('is-hidden');
                        if (textEl) textEl.textContent = '';
                    }
                }, 2000);
            } else if (state === 'error') {
                indicator.classList.add('is-error');
                if (textEl) textEl.textContent = customText || texts.error;
                setTimeout(function () {
                    indicator.classList.remove('is-error');
                    indicator.classList.add('is-hidden');
                    if (textEl) textEl.textContent = '';
                }, 3000);
            } else {
                indicator.classList.add('is-hidden');
                if (textEl) textEl.textContent = '';
            }
        }

        /**
         * Dispatches AJAX auto-save of the weekly working hours form.
         */
        function triggerAutoSave() {
            if (!workingHoursForm) return;

            if (isSaving) {
                pendingSave = true;
                return;
            }

            isSaving = true;
            setIndicatorState('saving');

            var formData = new FormData(workingHoursForm);
            formData.set('save_working_hours', '1');

            fetch(saveUrl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(function (result) {
                isSaving = false;
                if (result && result.status === 'success') {
                    setIndicatorState('saved', result.message || texts.saved);
                } else {
                    setIndicatorState('error', (result && result.message) ? result.message : texts.error);
                }

                if (pendingSave) {
                    pendingSave = false;
                    triggerAutoSave();
                }
            })
            .catch(function (err) {
                console.error('AutoSave error:', err);
                isSaving = false;
                setIndicatorState('error', texts.error);

                if (pendingSave) {
                    pendingSave = false;
                    setTimeout(triggerAutoSave, 1500);
                }
            });
        }

        /**
         * Schedules debounced auto-save.
         */
        function scheduleAutoSave(delayMs) {
            if (autoSaveTimer) clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(function () {
                triggerAutoSave();
            }, delayMs || 500);
        }

        /**
         * Initializes the 7-day open/closed toggle switches with smooth collapse.
         */
        function initDayToggles() {
            var dayCheckboxes = page.querySelectorAll('.wh-toggle-checkbox');

            dayCheckboxes.forEach(function (checkbox) {
                checkbox.addEventListener('change', function () {
                    var dayNum = this.getAttribute('data-day');
                    var dayCard = document.getElementById('whDayCard_' + dayNum);
                    var isClosedInput = document.getElementById('isClosedInput_' + dayNum);
                    var statusLabel = document.getElementById('whStatusLabel_' + dayNum);
                    var dayBody = document.getElementById('whDayBody_' + dayNum);

                    if (!dayCard || !isClosedInput) return;

                    var isOpen = this.checked;
                    isClosedInput.value = isOpen ? 'n' : 'y';

                    if (isOpen) {
                        dayCard.classList.remove('is-closed');
                        if (statusLabel) statusLabel.textContent = texts.open;
                        if (dayBody) setDayInputsDisabled(dayBody, false);
                    } else {
                        dayCard.classList.add('is-closed');
                        if (statusLabel) statusLabel.textContent = texts.closed;
                        if (dayBody) setDayInputsDisabled(dayBody, true);
                    }

                    // Auto-save immediately upon toggle
                    triggerAutoSave();
                });
            });
        }

        /**
         * Helper to enable or disable all inputs inside a day body.
         */
        function setDayInputsDisabled(dayBody, isDisabled) {
            var inputs = dayBody.querySelectorAll('input, button');
            inputs.forEach(function (el) {
                el.disabled = isDisabled;
            });
        }

        /**
         * Initializes time pickers and note inputs for auto-saving.
         */
        function initTimeAndNoteInputs() {
            // Time changes: trigger auto-save immediately on change
            var timeInputs = page.querySelectorAll('.wh-time-input');
            timeInputs.forEach(function (input) {
                input.addEventListener('change', function () {
                    scheduleAutoSave(150);
                });
            });

            // Note inputs: debounced save while typing (600ms), immediate on blur
            var noteInputs = page.querySelectorAll('.wh-note-input');
            noteInputs.forEach(function (input) {
                input.addEventListener('input', function () {
                    scheduleAutoSave(600);
                });
                input.addEventListener('blur', function () {
                    if (autoSaveTimer) clearTimeout(autoSaveTimer);
                    triggerAutoSave();
                });
            });
        }

        /**
         * Updates the dynamic break button text based on start and end inputs.
         */
        function updateBreakButtonText(dayNum) {
            var startInput = document.getElementById('breakStart_' + dayNum);
            var endInput = document.getElementById('breakEnd_' + dayNum);
            var btnTextEl = document.getElementById('breakBtnText_' + dayNum);
            var btn = document.getElementById('breakToggleBtn_' + dayNum);

            if (!btnTextEl || !btn) return;

            var startVal = startInput ? startInput.value.trim() : '';
            var endVal = endInput ? endInput.value.trim() : '';

            if (startVal && endVal) {
                btnTextEl.textContent = texts.breakLabel + ': ' + startVal.substring(0, 5) + ' - ' + endVal.substring(0, 5);
                btn.classList.add('has-break');
            } else {
                btnTextEl.textContent = texts.breakLabel;
                btn.classList.remove('has-break');
            }
        }

        /**
         * Initializes break accordion toggles and clear break actions.
         */
        function initBreakControls() {
            // Break accordion toggle
            var breakButtons = page.querySelectorAll('[data-action="toggle-break"]');
            breakButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var targetId = this.getAttribute('data-target');
                    var targetPanel = document.getElementById(targetId);
                    if (!targetPanel) return;

                    var isVisible = targetPanel.classList.toggle('is-visible');
                    this.classList.toggle('is-active', isVisible);
                });
            });

            // Break input change listeners to dynamically update button label
            var breakInputs = page.querySelectorAll('.wh-break-input');
            breakInputs.forEach(function (input) {
                input.addEventListener('change', function () {
                    var dayNum = this.getAttribute('data-day');
                    if (dayNum) updateBreakButtonText(dayNum);
                });
            });

            // Clear break action
            var clearButtons = page.querySelectorAll('[data-action="clear-break"]');
            clearButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var dayNum = this.getAttribute('data-day');
                    if (!dayNum) return;

                    var startInput = document.getElementById('breakStart_' + dayNum);
                    var endInput = document.getElementById('breakEnd_' + dayNum);
                    var targetPanel = document.getElementById('breakFields_' + dayNum);
                    var toggleBtn = document.getElementById('breakToggleBtn_' + dayNum);

                    if (startInput) startInput.value = '';
                    if (endInput) endInput.value = '';

                    updateBreakButtonText(dayNum);

                    if (targetPanel) targetPanel.classList.remove('is-visible');
                    if (toggleBtn) toggleBtn.classList.remove('is-active');

                    scheduleAutoSave(100);
                });
            });
        }

        /**
         * Initializes the 1-tap "Copy to Weekdays" shortcut on Monday (Day 1).
         */
        function initCopyWeekdaysShortcut() {
            var copyBtn = document.getElementById('copyWeekdaysBtn');
            if (!copyBtn) return;

            copyBtn.addEventListener('click', function () {
                var monOpen = document.getElementById('openTime_1');
                var monClose = document.getElementById('closeTime_1');
                var monBreakStart = document.getElementById('breakStart_1');
                var monBreakEnd = document.getElementById('breakEnd_1');
                var monToggle = document.getElementById('dayToggle_1');

                if (!monOpen || !monClose) return;

                var openVal = monOpen.value;
                var closeVal = monClose.value;
                var breakStartVal = monBreakStart ? monBreakStart.value : '';
                var breakEndVal = monBreakEnd ? monBreakEnd.value : '';
                var isMonOpen = monToggle ? monToggle.checked : true;

                // Apply to Tuesday (2) through Friday (5)
                for (var d = 2; d <= 5; d++) {
                    var openInput = document.getElementById('openTime_' + d);
                    var closeInput = document.getElementById('closeTime_' + d);
                    var breakStartInput = document.getElementById('breakStart_' + d);
                    var breakEndInput = document.getElementById('breakEnd_' + d);
                    var toggle = document.getElementById('dayToggle_' + d);
                    var isClosedInput = document.getElementById('isClosedInput_' + d);
                    var dayCard = document.getElementById('whDayCard_' + d);
                    var statusLabel = document.getElementById('whStatusLabel_' + d);
                    var dayBody = document.getElementById('whDayBody_' + d);

                    if (openInput) openInput.value = openVal;
                    if (closeInput) closeInput.value = closeVal;
                    if (breakStartInput) breakStartInput.value = breakStartVal;
                    if (breakEndInput) breakEndInput.value = breakEndVal;

                    if (isMonOpen && toggle && !toggle.checked) {
                        toggle.checked = true;
                        if (isClosedInput) isClosedInput.value = 'n';
                        if (dayCard) dayCard.classList.remove('is-closed');
                        if (statusLabel) statusLabel.textContent = texts.open;
                        if (dayBody) setDayInputsDisabled(dayBody, false);
                    }

                    updateBreakButtonText(d);
                }

                // Auto-save and show confirmation toast
                triggerAutoSave();
                showToast(texts.copyWeekdaysSuccess, 'success');
            });
        }

        /**
         * Displays a sleek floating mobile toast.
         */
        function showToast(message, type) {
            var container = document.getElementById('whToastContainer');
            if (!container) {
                container = document.createElement('div');
                container.id = 'whToastContainer';
                container.className = 'wh-toast-container';
                document.body.appendChild(container);
            }

            var toast = document.createElement('div');
            toast.className = 'wh-toast ' + (type === 'error' ? 'wh-toast-error' : 'wh-toast-success');

            var iconClass = type === 'error' ? 'bi-exclamation-circle-fill' : 'bi-check-circle-fill';
            toast.innerHTML = '<i class="bi ' + iconClass + '"></i><span>' + escapeHtml(message) + '</span>';

            container.appendChild(toast);

            setTimeout(function () {
                toast.classList.add('is-visible');
            }, 20);

            setTimeout(function () {
                toast.classList.remove('is-visible');
                setTimeout(function () {
                    if (toast.parentNode) toast.parentNode.removeChild(toast);
                }, 300);
            }, 3000);
        }

        /**
         * Escapes HTML strings safely.
         */
        function escapeHtml(str) {
            if (!str) return '';
            var div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        /**
         * Builds the HTML for a single closure article item.
         */
        function buildClosureItemHtml(c) {
            var isFull = (c.is_full_day === 'y' || c.is_full_day === '1');
            var typeBadgeLabel = texts.typeHoliday;
            var typeBadgeIcon = 'bi-calendar-event';

            if (c.closure_type === 'vacation') {
                typeBadgeLabel = texts.typeVacation;
                typeBadgeIcon = 'bi-person-walking';
            } else if (c.closure_type === 'maintenance') {
                typeBadgeLabel = texts.typeMaintenance;
                typeBadgeIcon = 'bi-tools';
            } else if (c.closure_type === 'emergency') {
                typeBadgeLabel = texts.typeEmergency;
                typeBadgeIcon = 'bi-exclamation-triangle-fill';
            }

            var dateRange = escapeHtml(c.start_date);
            if (c.end_date && c.end_date !== c.start_date) {
                dateRange += ' &rarr; ' + escapeHtml(c.end_date);
            }

            var timeHtml = isFull
                ? '<i class="bi bi-clock"></i> ' + escapeHtml(texts.fullDay)
                : '<i class="bi bi-clock"></i> ' + escapeHtml(c.open_time ? c.open_time.substring(0, 5) : '') + ' - ' + escapeHtml(c.close_time ? c.close_time.substring(0, 5) : '');

            var recurringHtml = (c.is_recurring_yearly === 'y')
                ? '<span class="wh-recurring-badge" title="' + escapeHtml(texts.yearlyRecurring) + '"><i class="bi bi-repeat"></i></span>'
                : '';

            return '<article class="wh-closure-item wh-item-enter" data-id="' + c.id + '">' +
                '<div class="wh-closure-meta">' +
                    '<div class="wh-closure-top">' +
                        '<span class="wh-closure-type-badge type-' + escapeHtml(c.closure_type) + '">' +
                            '<i class="bi ' + typeBadgeIcon + '"></i> ' + escapeHtml(typeBadgeLabel) +
                        '</span>' +
                        recurringHtml +
                    '</div>' +
                    '<h3 class="wh-closure-reason">' + escapeHtml(c.reason) + '</h3>' +
                    '<div class="wh-closure-details">' +
                        '<span class="wh-closure-date">' +
                            '<i class="bi bi-calendar3"></i> ' + dateRange +
                        '</span>' +
                        '<span class="wh-closure-time">' +
                            timeHtml +
                        '</span>' +
                    '</div>' +
                '</div>' +
                '<div class="wh-closure-actions">' +
                    '<button type="button" class="wh-btn-delete" data-action="delete-closure" data-id="' + c.id + '" aria-label="' + escapeHtml(texts.deleteBtn) + '">' +
                        '<i class="bi bi-trash3-fill"></i>' +
                    '</button>' +
                '</div>' +
            '</article>';
        }

        /**
         * Initializes the Add Closure bottom sheet modal with 100% AJAX submission.
         */
        function initClosureModal() {
            var modal = document.getElementById('addClosureModal');
            var openBtn = document.getElementById('openAddClosureBtn');
            var closeBtn = document.getElementById('closeAddClosureModalBtn');
            var cancelBtn = document.getElementById('cancelAddClosureBtn');
            var fullDayCheckbox = document.getElementById('is_full_day');
            var customHoursWrap = document.getElementById('customHoursWrap');
            var startDateInput = document.getElementById('closure_start_date');
            var endDateInput = document.getElementById('closure_end_date');
            var addForm = document.getElementById('addClosureForm');
            var submitBtn = document.getElementById('saveClosureSubmitBtn');
            var closuresList = document.getElementById('closuresList');

            if (!modal) return;

            function openModal() {
                modal.classList.remove('is-hidden');
                document.body.style.overflow = 'hidden';
                var firstInput = modal.querySelector('input[type="text"]');
                if (firstInput) {
                    setTimeout(function () { firstInput.focus(); }, 150);
                }
            }

            function closeModal() {
                modal.classList.add('is-hidden');
                document.body.style.overflow = '';
            }

            if (openBtn) openBtn.addEventListener('click', openModal);
            if (closeBtn) closeBtn.addEventListener('click', closeModal);
            if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

            // Dismiss modal on backdrop click
            modal.addEventListener('click', function (e) {
                if (e.target === modal) closeModal();
            });

            // Toggle custom hours visibility
            if (fullDayCheckbox && customHoursWrap) {
                fullDayCheckbox.addEventListener('change', function () {
                    if (this.checked) {
                        customHoursWrap.classList.add('is-hidden');
                    } else {
                        customHoursWrap.classList.remove('is-hidden');
                    }
                });
            }

            // Synchronize end date with start date
            if (startDateInput && endDateInput) {
                startDateInput.addEventListener('change', function () {
                    if (!endDateInput.value || endDateInput.value < this.value) {
                        endDateInput.value = this.value;
                    }
                });
            }

            // Intercept form submission for 100% AJAX creation
            if (addForm) {
                addForm.addEventListener('submit', function (e) {
                    e.preventDefault();

                    var originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<span class="wh-spinner wh-spinner-light"></span> <span>' + texts.saving + '</span>';
                    }

                    var formData = new FormData(addForm);
                    formData.set('add_closure', '1');

                    fetch(saveUrl, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                    .then(function (res) {
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        return res.json();
                    })
                    .then(function (data) {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalBtnHtml;
                        }

                        if (data && data.status === 'success') {
                            closeModal();
                            addForm.reset();
                            if (customHoursWrap) customHoursWrap.classList.add('is-hidden');

                            // Remove empty state if present
                            if (closuresList) {
                                var emptyState = closuresList.querySelector('.wh-empty-state');
                                if (emptyState) emptyState.remove();

                                // Prepend new closure item
                                if (data.closure) {
                                    var tempDiv = document.createElement('div');
                                    tempDiv.innerHTML = buildClosureItemHtml(data.closure);
                                    var newItem = tempDiv.firstElementChild;
                                    closuresList.prepend(newItem);

                                    // Remove entrance animation class after render
                                    setTimeout(function () {
                                        if (newItem) newItem.classList.remove('wh-item-enter');
                                    }, 400);
                                }
                            }

                            showToast(data.message || texts.addSuccess, 'success');
                        } else {
                            showToast((data && data.message) ? data.message : texts.error, 'error');
                        }
                    })
                    .catch(function (err) {
                        console.error('Add closure error:', err);
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalBtnHtml;
                        }
                        showToast(texts.error, 'error');
                    });
                });
            }
        }

        /**
         * Initializes closure item deletion with 100% AJAX and smooth animations.
         */
        function initClosureDelete() {
            var closuresList = document.getElementById('closuresList');
            if (!closuresList) return;

            closuresList.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-action="delete-closure"]');
                if (!btn) return;

                var closureId = btn.getAttribute('data-id');
                if (!closureId) return;

                var closureItem = btn.closest('.wh-closure-item');

                function executeDelete() {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="wh-spinner"></span>';

                    var formData = new FormData();
                    formData.set('delete_closure_id', closureId);

                    fetch(saveUrl, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                    .then(function (res) {
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        return res.json();
                    })
                    .then(function (data) {
                        if (data && data.status === 'success') {
                            if (closureItem) {
                                closureItem.classList.add('wh-item-exit');
                                setTimeout(function () {
                                    closureItem.remove();

                                    // Check if list is empty
                                    var remainingItems = closuresList.querySelectorAll('.wh-closure-item');
                                    if (remainingItems.length === 0) {
                                        closuresList.innerHTML = '<div class="wh-empty-state wh-item-enter">' +
                                            '<div class="wh-empty-icon"><i class="bi bi-calendar-check"></i></div>' +
                                            '<p class="wh-empty-text">' + escapeHtml(texts.noClosures) + '</p>' +
                                        '</div>';
                                    }
                                }, 280);
                            }
                            showToast(data.message || texts.deleteSuccess, 'success');
                        } else {
                            btn.disabled = false;
                            btn.innerHTML = '<i class="bi bi-trash3-fill"></i>';
                            showToast((data && data.message) ? data.message : texts.error, 'error');
                        }
                    })
                    .catch(function (err) {
                        console.error('Delete closure error:', err);
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bi bi-trash3-fill"></i>';
                        showToast(texts.error, 'error');
                    });
                }

                if (window.Swal) {
                    window.Swal.fire({
                        title: texts.confirmDelete,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc2626',
                        cancelButtonColor: '#64748b',
                        confirmButtonText: texts.deleteBtn,
                        cancelButtonText: texts.cancelBtn
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            executeDelete();
                        }
                    });
                } else {
                    if (window.confirm(texts.confirmDelete)) {
                        executeDelete();
                    }
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initWorkingHoursPage);
    } else {
        initWorkingHoursPage();
    }

    // Export globally for modal reload hook
    window.initWorkingHoursPage = initWorkingHoursPage;
})();
