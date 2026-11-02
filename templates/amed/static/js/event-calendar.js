/**
 * Event Calendar Engine
 * Amed Framework - AF/Bulletin
 * templates/amed/static/js/event-calendar.js
 * Strict Code Separation: Zero Inline JavaScript
 */

(function () {
    'use strict';

    function initCalendarApp() {
        var app = document.getElementById('calendarApp');
        if (!app) return;

        var apiUrl = app.getAttribute('data-api');
        var initialYear = parseInt(app.getAttribute('data-year'), 10) || new Date().getFullYear();
        var initialMonth = parseInt(app.getAttribute('data-month'), 10) || (new Date().getMonth() + 1);
        var emptyMessageText = app.getAttribute('data-empty-msg') || 'No events found for this date.';
        var todayLabelText = app.getAttribute('data-today-label') || '';
        var userLang = document.documentElement.lang || 'tr';

        var currentYear = initialYear;
        var currentMonth = initialMonth;
        var currentCategory = '';
        var currentEvents = [];

        var displayEl = document.getElementById('calendarCurrentMonthYear');
        var spinnerEl = document.getElementById('calendarSpinner');
        var gridEl = document.getElementById('calendarDaysGrid');
        var eventsContainer = document.getElementById('calendarEventsContainer');
        var selectedTitleEl = document.getElementById('selectedDateTitle');
        var prevBtn = document.getElementById('calendarPrevBtn');
        var nextBtn = document.getElementById('calendarNextBtn');
        var todayBtn = document.getElementById('calendarTodayBtn');
        var categoryButtons = document.querySelectorAll('.calendar-cat-btn');

        function formatMonthHeader(year, month) {
            try {
                var d = new Date(year, month - 1, 1);
                var formatter = new Intl.DateTimeFormat(userLang, { month: 'long', year: 'numeric' });
                return formatter.format(d);
            } catch (e) {
                var monthNames = [
                    'January', 'February', 'March', 'April', 'May', 'June',
                    'July', 'August', 'September', 'October', 'November', 'December'
                ];
                return monthNames[month - 1] + ' ' + year;
            }
        }

        function formatSelectedDayHeader(day, month, year) {
            if (!day) return formatMonthHeader(year, month);
            try {
                var d = new Date(year, month - 1, day);
                var formatter = new Intl.DateTimeFormat(userLang, { day: 'numeric', month: 'long', year: 'numeric' });
                return formatter.format(d);
            } catch (e) {
                return day + ' ' + formatMonthHeader(year, month);
            }
        }

        function fetchEvents(year, month, category) {
            if (spinnerEl) spinnerEl.classList.remove('d-none');

            var url = apiUrl + '?year=' + encodeURIComponent(year) + '&month=' + encodeURIComponent(month);
            if (category) {
                url += '&category=' + encodeURIComponent(category);
            }

            fetch(url)
                .then(function (res) {
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.json();
                })
                .then(function (data) {
                    currentEvents = (data && data.events) ? data.events : [];
                    renderCalendar(year, month, currentEvents);
                    if (spinnerEl) spinnerEl.classList.add('d-none');
                })
                .catch(function (err) {
                    console.error('Failed to load events:', err);
                    if (spinnerEl) spinnerEl.classList.add('d-none');
                    renderCalendar(year, month, []);
                });
        }

        function renderCalendar(year, month, events) {
            if (displayEl) {
                displayEl.textContent = formatMonthHeader(year, month);
            }

            if (!gridEl) return;
            gridEl.innerHTML = '';

            var firstDayIndex = new Date(year, month - 1, 1).getDay(); // 0 is Sun
            var startingCol = (firstDayIndex === 0) ? 6 : (firstDayIndex - 1); // Mon is 0

            var daysInMonth = new Date(year, month, 0).getDate();
            var prevMonthDays = new Date(year, month - 1, 0).getDate();

            var today = new Date();
            var isCurrentMonthToday = (today.getFullYear() === year && (today.getMonth() + 1) === month);
            var todayDate = today.getDate();

            // Map events by day (multi-day events span across all their covered days)
            var eventsByDay = {};
            events.forEach(function (ev) {
                var spanDays = Array.isArray(ev.span_days) && ev.span_days.length > 0 ? ev.span_days : [parseInt(ev.start_day, 10)];
                spanDays.forEach(function (d) {
                    if (!eventsByDay[d]) eventsByDay[d] = [];
                    var alreadyIn = eventsByDay[d].some(function (it) { return it.id === ev.id; });
                    if (!alreadyIn) {
                        eventsByDay[d].push(ev);
                    }
                });
            });

            // Previous month fill
            for (var p = startingCol - 1; p >= 0; p--) {
                var cellPrev = document.createElement('div');
                cellPrev.className = 'calendar-day-cell is-other-month';
                cellPrev.innerHTML = '<span class="calendar-day-num">' + (prevMonthDays - p) + '</span>';
                gridEl.appendChild(cellPrev);
            }

            // Current month days
            for (var d = 1; d <= daysInMonth; d++) {
                var cell = document.createElement('div');
                cell.className = 'calendar-day-cell';
                cell.setAttribute('data-day', d);

                if (isCurrentMonthToday && d === todayDate) {
                    cell.classList.add('is-today');
                }

                var dayEvents = eventsByDay[d] || [];
                var countHtml = '';
                if (dayEvents.length > 0) {
                    cell.classList.add('has-events');
                    countHtml = '<span class="calendar-day-event-count">' + dayEvents.length + '</span>';
                }

                cell.innerHTML = '<span class="calendar-day-num">' + d + '</span>' + countHtml;

                (function (dayNum, dayEvs, cellEl) {
                    cellEl.addEventListener('click', function () {
                        gridEl.querySelectorAll('.calendar-day-cell').forEach(function (c) {
                            c.classList.remove('is-selected');
                        });
                        cellEl.classList.add('is-selected');
                        showEventsForDay(dayNum, dayEvs);
                    });
                })(d, dayEvents, cell);

                gridEl.appendChild(cell);
            }

            // Next month fill to complete rows (multiple of 7)
            var totalCells = startingCol + daysInMonth;
            var remaining = (7 - (totalCells % 7)) % 7;
            for (var n = 1; n <= remaining; n++) {
                var cellNext = document.createElement('div');
                cellNext.className = 'calendar-day-cell is-other-month';
                cellNext.innerHTML = '<span class="calendar-day-num">' + n + '</span>';
                gridEl.appendChild(cellNext);
            }

            // If there are events this month, select today or first event day
            if (isCurrentMonthToday && eventsByDay[todayDate]) {
                var todayCell = gridEl.querySelector('.calendar-day-cell.is-today');
                if (todayCell) {
                    todayCell.classList.add('is-selected');
                    showEventsForDay(todayDate, eventsByDay[todayDate]);
                }
            } else if (events.length > 0) {
                var firstEvDay = parseInt(events[0].start_day, 10);
                if (Array.isArray(events[0].span_days) && events[0].span_days.length > 0) {
                    firstEvDay = events[0].span_days[0];
                }
                var firstCell = gridEl.querySelector('.calendar-day-cell[data-day="' + firstEvDay + '"]');
                if (firstCell) {
                    firstCell.classList.add('is-selected');
                    showEventsForDay(firstEvDay, eventsByDay[firstEvDay]);
                } else {
                    showEventsForDay(null, []);
                }
            } else {
                showEventsForDay(null, []);
            }
        }

        function showEventsForDay(day, events) {
            if (!eventsContainer) return;

            if (selectedTitleEl) {
                selectedTitleEl.textContent = formatSelectedDayHeader(day, currentMonth, currentYear);
            }

            if (!events || events.length === 0) {
                eventsContainer.innerHTML = '<p class="text-muted small py-3 text-center">' + escapeHtml(emptyMessageText) + '</p>';
                return;
            }

            var html = '';
            events.forEach(function (ev) {
                html += '<div class="calendar-event-card-mini p-2 mb-2 rounded border">';
                if (ev.subject) {
                    html += '<span class="badge bg-primary-subtle text-primary mb-1">' + escapeHtml(ev.subject) + '</span>';
                }
                if (ev.is_today && todayLabelText) {
                    html += '<span class="badge bg-danger text-white ms-1 mb-1">' + escapeHtml(todayLabelText) + '</span>';
                }
                html += '<h3 class="calendar-event-card-mini-title h6 mb-1">';
                html += '<a href="' + ev.url + '" class="text-decoration-none text-reset">' + escapeHtml(ev.name) + '</a>';
                html += '</h3>';
                html += '<div class="calendar-event-card-mini-meta small text-muted d-flex flex-wrap gap-2">';
                if (ev.start_time) {
                    html += '<span><i class="bi bi-clock me-1"></i>' + escapeHtml(ev.start_time) + '</span>';
                }
                if (ev.location) {
                    html += '<span><i class="bi bi-geo-alt me-1"></i>' + escapeHtml(ev.location) + '</span>';
                }
                html += '</div>';
                html += '</div>';
            });

            eventsContainer.innerHTML = html;
        }

        function escapeHtml(str) {
            if (!str) return '';
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', function () {
                currentMonth--;
                if (currentMonth < 1) {
                    currentMonth = 12;
                    currentYear--;
                }
                fetchEvents(currentYear, currentMonth, currentCategory);
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                currentMonth++;
                if (currentMonth > 12) {
                    currentMonth = 1;
                    currentYear++;
                }
                fetchEvents(currentYear, currentMonth, currentCategory);
            });
        }

        if (todayBtn) {
            todayBtn.addEventListener('click', function () {
                var now = new Date();
                currentYear = now.getFullYear();
                currentMonth = now.getMonth() + 1;
                fetchEvents(currentYear, currentMonth, currentCategory);
            });
        }

        // Category filter buttons
        categoryButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                categoryButtons.forEach(function (b) {
                    b.classList.remove('active', 'btn-primary');
                    b.classList.add('btn-outline-secondary');
                });
                this.classList.add('active', 'btn-primary');
                this.classList.remove('btn-outline-secondary');

                currentCategory = this.getAttribute('data-category') || '';
                fetchEvents(currentYear, currentMonth, currentCategory);
            });
        });

        // Initial fetch
        fetchEvents(currentYear, currentMonth, currentCategory);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCalendarApp);
    } else {
        initCalendarApp();
    }
})();
