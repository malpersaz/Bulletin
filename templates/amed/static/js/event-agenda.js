/**
 * City Events Agenda Widget Engine
 * Amed Framework - AF/Bulletin
 * templates/amed/static/js/event-agenda.js
 * Strict Code Separation: Zero Inline JavaScript
 */

(function () {
    'use strict';

    function initEventAgenda() {
        var widget = document.querySelector('.event-agenda-widget');
        if (!widget) return;

        // 1. Fast Filter Tabs
        var filterButtons = widget.querySelectorAll('.event-agenda-filter-btn');
        var items = widget.querySelectorAll('.event-agenda-item');
        var emptyState = widget.querySelector('.event-agenda-empty-state');

        filterButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var filterType = this.getAttribute('data-filter') || 'all';

                filterButtons.forEach(function (b) {
                    b.classList.remove('is-active');
                    b.setAttribute('aria-selected', 'false');
                });
                this.classList.add('is-active');
                this.setAttribute('aria-selected', 'true');

                var visibleCount = 0;
                items.forEach(function (item) {
                    var isUpcoming = item.getAttribute('data-is-upcoming') === '1';
                    var isThisWeek = item.getAttribute('data-is-this-week') === '1';
                    var isToday = item.getAttribute('data-is-today') === '1';

                    var show = false;
                    if (filterType === 'all') {
                        show = true;
                    } else if (filterType === 'this-week') {
                        show = isThisWeek || isToday;
                    } else if (filterType === 'upcoming') {
                        show = isUpcoming;
                    }

                    if (show) {
                        item.removeAttribute('hidden');
                        visibleCount++;
                    } else {
                        item.setAttribute('hidden', '');
                    }
                });

                if (emptyState) {
                    if (visibleCount === 0) {
                        emptyState.removeAttribute('hidden');
                    } else {
                        emptyState.setAttribute('hidden', '');
                    }
                }
            });
        });

        // 2. Calendar Action Dropdown Popovers
        var calActions = widget.querySelectorAll('.event-agenda-cal-action');

        calActions.forEach(function (calAction) {
            var trigger = calAction.querySelector('.event-agenda-cal-trigger');
            var menu = calAction.querySelector('.event-agenda-dropdown-menu');
            if (!trigger || !menu) return;

            trigger.addEventListener('click', function (e) {
                e.stopPropagation();
                var isOpen = calAction.classList.contains('is-open');

                // Close other open popovers
                calActions.forEach(function (other) {
                    if (other !== calAction) {
                        other.classList.remove('is-open');
                        var otherTrigger = other.querySelector('.event-agenda-cal-trigger');
                        if (otherTrigger) otherTrigger.setAttribute('aria-expanded', 'false');
                    }
                });

                if (isOpen) {
                    calAction.classList.remove('is-open');
                    trigger.setAttribute('aria-expanded', 'false');
                } else {
                    calAction.classList.add('is-open');
                    trigger.setAttribute('aria-expanded', 'true');
                }
            });
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function (e) {
            calActions.forEach(function (calAction) {
                if (!calAction.contains(e.target)) {
                    calAction.classList.remove('is-open');
                    var trigger = calAction.querySelector('.event-agenda-cal-trigger');
                    if (trigger) trigger.setAttribute('aria-expanded', 'false');
                }
            });
        });

        // Close on Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                calActions.forEach(function (calAction) {
                    calAction.classList.remove('is-open');
                    var trigger = calAction.querySelector('.event-agenda-cal-trigger');
                    if (trigger) trigger.setAttribute('aria-expanded', 'false');
                });
            }
        });

        // 3. Apple / Outlook .ics File Generator & Downloader
        widget.querySelectorAll('.ics-download-btn').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                var item = btn.closest('.event-agenda-item');
                if (!item) return;

                var title = item.getAttribute('data-title') || 'Event';
                var location = item.getAttribute('data-location') || '';
                var summary = item.getAttribute('data-summary') || '';
                var isoStart = item.getAttribute('data-iso-start') || '';
                var isoEnd = item.getAttribute('data-iso-end') || '';
                var eventUrl = item.getAttribute('data-url') || window.location.href;
                try {
                    eventUrl = decodeURI(eventUrl);
                } catch (err) {}

                // Build standard RFC 5545 iCalendar data
                var cleanSummary = summary.replace(/\r?\n/g, ' ').replace(/,/g, '\\,');
                var cleanTitle = title.replace(/\r?\n/g, ' ').replace(/,/g, '\\,');
                var cleanLocation = location.replace(/\r?\n/g, ' ').replace(/,/g, '\\,');

                var icsContent = [
                    'BEGIN:VCALENDAR',
                    'VERSION:2.0',
                    'PRODID:-//Amed Bulletin//City Events Agenda//TR',
                    'CALSCALE:GREGORIAN',
                    'METHOD:PUBLISH',
                    'BEGIN:VEVENT',
                    'UID:' + Date.now() + '@amedbulletin.com',
                    'DTSTAMP:' + new Date().toISOString().replace(/[-:]/g, '').split('.')[0] + 'Z',
                    isoStart ? ('DTSTART:' + isoStart) : '',
                    isoEnd ? ('DTEND:' + isoEnd) : '',
                    'SUMMARY:' + cleanTitle,
                    'DESCRIPTION:' + cleanSummary + (eventUrl ? ' - ' + eventUrl : ''),
                    'LOCATION:' + cleanLocation,
                    'URL:' + eventUrl,
                    'STATUS:CONFIRMED',
                    'END:VEVENT',
                    'END:VCALENDAR'
                ].filter(Boolean).join('\r\n');

                var blob = new Blob([icsContent], { type: 'text/calendar;charset=utf-8;' });
                var downloadLink = document.createElement('a');
                downloadLink.href = window.URL.createObjectURL(blob);
                downloadLink.setAttribute('download', 'etkinlik-' + (Date.now()) + '.ics');
                document.body.appendChild(downloadLink);
                downloadLink.click();
                document.body.removeChild(downloadLink);
                setTimeout(function () {
                    window.URL.revokeObjectURL(downloadLink.href);
                }, 1000);

                var calAction = btn.closest('.event-agenda-cal-action');
                if (calAction) calAction.classList.remove('is-open');
            });
        });

        // 4. One-Click Copy Event Link
        widget.querySelectorAll('.copy-link-btn').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                var item = btn.closest('.event-agenda-item');
                if (!item) return;

                var eventUrl = item.getAttribute('data-url') || window.location.href;
                try {
                    eventUrl = decodeURI(eventUrl);
                } catch (err) {}
                var copyLabel = btn.querySelector('.copy-text');
                var copiedText = btn.getAttribute('data-copied-text') || 'Copied!';
                var originalText = copyLabel ? copyLabel.textContent : '';

                var onSuccess = function () {
                    if (copyLabel) copyLabel.textContent = copiedText;
                    btn.classList.add('is-copied');
                    setTimeout(function () {
                        if (copyLabel) copyLabel.textContent = originalText;
                        btn.classList.remove('is-copied');
                        var calAction = btn.closest('.event-agenda-cal-action');
                        if (calAction) calAction.classList.remove('is-open');
                    }, 1800);
                };

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(eventUrl).then(onSuccess).catch(function () {
                        fallbackCopy(eventUrl, onSuccess);
                    });
                } else {
                    fallbackCopy(eventUrl, onSuccess);
                }
            });
        });

        function fallbackCopy(text, callback) {
            var tempInput = document.createElement('input');
            tempInput.value = text;
            document.body.appendChild(tempInput);
            tempInput.select();
            try {
                document.execCommand('copy');
                if (callback) callback();
            } catch (err) {
                // Ignore copy errors silently
            }
            document.body.removeChild(tempInput);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEventAgenda);
    } else {
        initEventAgenda();
    }
})();
