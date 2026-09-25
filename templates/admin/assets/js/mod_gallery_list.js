/**
 * Admin Gallery List Controller (mod_gallery)
 * Material 3 Phone-Only UI, Strict Separation of Concerns
 * Amed Framework CMS
 */

(function () {
    'use strict';

    function initGalleryList() {
        var searchInput = document.getElementById('gallerySearchInput');
        var searchClear = document.getElementById('gallerySearchClear');
        var resultsCount = document.getElementById('galleryResultsCount');
        var cardsList = document.getElementById('galleryCardsList');
        var emptyState = document.getElementById('galleryEmptyState');
        var resetBtn = document.getElementById('galleryEmptyReset');
        var statCards = document.querySelectorAll('.gallery-stat-card');
        var albumCards = document.querySelectorAll('.gallery-album-card');

        var activeModule = 'all';

        function applyFilter() {
            var query = searchInput ? searchInput.value.trim().toLowerCase() : '';
            var visibleCount = 0;

            if (searchClear) {
                if (query.length > 0) {
                    searchClear.classList.add('is-visible');
                } else {
                    searchClear.classList.remove('is-visible');
                }
            }

            albumCards.forEach(function (card) {
                var title = (card.getAttribute('data-title') || '').toLowerCase();
                var mod = (card.getAttribute('data-module') || '').toLowerCase();
                var key = (card.getAttribute('data-key') || '').toLowerCase();

                var matchesQuery = !query || title.indexOf(query) !== -1 || mod.indexOf(query) !== -1 || key.indexOf(query) !== -1;
                var matchesModule = activeModule === 'all' || mod === activeModule.toLowerCase();

                if (matchesQuery && matchesModule) {
                    card.classList.remove('is-hidden');
                    visibleCount++;
                } else {
                    card.classList.add('is-hidden');
                }
            });

            if (resultsCount) {
                resultsCount.textContent = visibleCount;
            }

            if (emptyState && cardsList) {
                if (visibleCount === 0) {
                    emptyState.classList.remove('is-hidden');
                    cardsList.classList.add('is-hidden');
                } else {
                    emptyState.classList.add('is-hidden');
                    cardsList.classList.remove('is-hidden');
                }
            }
        }

        // Search Input Event
        if (searchInput) {
            searchInput.addEventListener('input', applyFilter);
        }

        // Clear Search Button
        if (searchClear) {
            searchClear.addEventListener('click', function () {
                if (searchInput) {
                    searchInput.value = '';
                    searchInput.focus();
                }
                applyFilter();
            });
        }

        // Empty State Reset Button
        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                if (searchInput) {
                    searchInput.value = '';
                }
                activeModule = 'all';
                statCards.forEach(function (c) {
                    if (c.getAttribute('data-module') === 'all') {
                        c.classList.add('active');
                    } else {
                        c.classList.remove('active');
                    }
                });
                applyFilter();
            });
        }

        // Module Filter Stat Pills
        statCards.forEach(function (card) {
            card.addEventListener('click', function () {
                var mod = card.getAttribute('data-module') || 'all';
                activeModule = mod;

                statCards.forEach(function (c) {
                    c.classList.remove('active');
                });
                card.classList.add('active');

                applyFilter();
            });
        });

        // Bottom Toolbar Action Buttons
        var toolbarActions = document.querySelectorAll('[data-gallery-action]');
        toolbarActions.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                var action = btn.getAttribute('data-gallery-action');
                if (action === 'back') {
                    e.preventDefault();
                    if (window.history.length > 1) {
                        window.history.back();
                    } else {
                        window.location.href = btn.getAttribute('href') || '/';
                    }
                } else if (action === 'focus-search') {
                    e.preventDefault();
                    if (searchInput) {
                        searchInput.focus();
                    }
                } else if (action === 'refresh') {
                    e.preventDefault();
                    window.location.reload();
                } else if (action === 'scroll-top') {
                    e.preventDefault();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });
        });

        // Initial Filter Application
        applyFilter();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initGalleryList);
    } else {
        initGalleryList();
    }
})();
