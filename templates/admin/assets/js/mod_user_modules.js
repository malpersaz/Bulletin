/**
 * mod_user panel — module directory live search
 */
(function () {
    function init() {
        var input = document.getElementById('panelSearchInput');
        var clearBtn = document.getElementById('panelSearchClear');
        var noResults = document.getElementById('panelNoResults');
        if (!input) return;

        var cats = Array.prototype.slice.call(document.querySelectorAll('.panel-cat'));
        var cards = Array.prototype.slice.call(document.querySelectorAll('.panel-module-card'));

        function normalize(value) {
            // Case- and diacritic-insensitive: Turkish dotted/dotless I and
            // combining marks from decomposed characters fold to ASCII.
            return (value || '')
                .toLowerCase()
                .replace(/ı/g, 'i')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '');
        }

        function resetCard(card) {
            card.hidden = false;
            var mainLink = card.querySelector('.panel-module-main');
            var titleEl = card.querySelector('.panel-module-title');
            var contextEl = card.querySelector('.panel-module-context');
            var linksContainer = card.querySelector('.panel-module-links');

            if (mainLink) {
                var defaultUrl = mainLink.getAttribute('data-default-url');
                if (defaultUrl) {
                    mainLink.setAttribute('href', defaultUrl);
                }
                mainLink.removeAttribute('data-action');
            }
            if (titleEl) {
                var defaultTitle = titleEl.getAttribute('data-default-title');
                if (defaultTitle) {
                    titleEl.textContent = defaultTitle;
                }
            }
            if (contextEl) {
                contextEl.hidden = true;
                contextEl.textContent = '';
            }
            if (linksContainer) {
                linksContainer.hidden = false;
            }
            var links = Array.prototype.slice.call(card.querySelectorAll('.panel-module-link'));
            links.forEach(function (link) {
                link.hidden = false;
            });
        }

        function apply() {
            var query = normalize(input.value.trim());
            clearBtn.hidden = query === '';
            var terms = query.split(/\s+/).filter(Boolean);

            if (terms.length === 0) {
                cards.forEach(resetCard);
                cats.forEach(function (cat) {
                    cat.hidden = false;
                });
                noResults.hidden = true;
                return;
            }

            var anyCardMatched = false;

            cards.forEach(function (card) {
                var titleSearchData = normalize(card.getAttribute('data-title-search') || '');
                var fullSearchData = normalize(card.getAttribute('data-search') || '');
                var mainLink = card.querySelector('.panel-module-main');
                var titleEl = card.querySelector('.panel-module-title');
                var contextEl = card.querySelector('.panel-module-context');
                var linksContainer = card.querySelector('.panel-module-links');
                var defaultUrl = mainLink ? mainLink.getAttribute('data-default-url') : '';
                var defaultTitle = titleEl ? titleEl.getAttribute('data-default-title') : '';
                var links = Array.prototype.slice.call(card.querySelectorAll('.panel-module-link'));

                // Does the module title or module keywords match all query terms?
                var titleHit = terms.every(function (term) {
                    return titleSearchData.indexOf(term) !== -1;
                });

                if (titleHit) {
                    // Whole module match: restore original title, show all links
                    card.hidden = false;
                    if (mainLink && defaultUrl) {
                        mainLink.setAttribute('href', defaultUrl);
                        mainLink.removeAttribute('data-action');
                    }
                    if (titleEl && defaultTitle) {
                        titleEl.textContent = defaultTitle;
                    }
                    if (contextEl) {
                        contextEl.hidden = true;
                        contextEl.textContent = '';
                    }
                    if (linksContainer) {
                        linksContainer.hidden = false;
                    }
                    links.forEach(function (link) {
                        link.hidden = false;
                    });
                    anyCardMatched = true;
                } else {
                    // Check individual links
                    var visibleLinks = [];
                    links.forEach(function (link) {
                        var linkSearch = normalize(link.getAttribute('data-link-search') || link.textContent || '');
                        var linkHit = terms.every(function (term) {
                            return linkSearch.indexOf(term) !== -1;
                        });
                        link.hidden = !linkHit;
                        if (linkHit) {
                            visibleLinks.push(link);
                        }
                    });

                    if (visibleLinks.length === 1) {
                        // Exactly one link matched: elevate it to the primary card title and URL!
                        // Eliminates UI/UX ambiguity and prevents duplicate link rows.
                        card.hidden = false;
                        anyCardMatched = true;

                        var singleLink = visibleLinks[0];
                        var targetHref = singleLink.getAttribute('href') || defaultUrl;
                        var targetText = singleLink.textContent.trim().replace(/^[›\s]+/, '');

                        if (titleEl) {
                            titleEl.textContent = targetText;
                        }
                        if (contextEl && defaultTitle) {
                            contextEl.textContent = defaultTitle;
                            contextEl.hidden = false;
                        }
                        if (mainLink) {
                            if (singleLink.tagName.toLowerCase() === 'button' || singleLink.hasAttribute('data-action')) {
                                mainLink.setAttribute('href', '#');
                                mainLink.setAttribute('data-action', singleLink.getAttribute('data-action') || '');
                            } else {
                                mainLink.setAttribute('href', targetHref);
                                mainLink.removeAttribute('data-action');
                            }
                        }
                        if (linksContainer) {
                            linksContainer.hidden = true;
                        }
                    } else if (visibleLinks.length > 1) {
                        // Multiple links matched: retain module title and display matching links below
                        card.hidden = false;
                        anyCardMatched = true;

                        if (titleEl && defaultTitle) {
                            titleEl.textContent = defaultTitle;
                        }
                        if (contextEl) {
                            contextEl.hidden = true;
                            contextEl.textContent = '';
                        }
                        if (linksContainer) {
                            linksContainer.hidden = false;
                        }
                        if (mainLink) {
                            var first = visibleLinks[0];
                            if (first.tagName.toLowerCase() === 'button' || first.hasAttribute('data-action')) {
                                mainLink.setAttribute('href', '#');
                                mainLink.setAttribute('data-action', first.getAttribute('data-action') || '');
                            } else {
                                mainLink.setAttribute('href', first.getAttribute('href') || defaultUrl);
                                mainLink.removeAttribute('data-action');
                            }
                        }
                    } else {
                        // Fallback: check full combined card search data
                        var fullHit = terms.every(function (term) {
                            return fullSearchData.indexOf(term) !== -1;
                        });
                        if (fullHit) {
                            card.hidden = false;
                            if (mainLink && defaultUrl) {
                                mainLink.setAttribute('href', defaultUrl);
                                mainLink.removeAttribute('data-action');
                            }
                            if (titleEl && defaultTitle) {
                                titleEl.textContent = defaultTitle;
                            }
                            if (contextEl) {
                                contextEl.hidden = true;
                            }
                            if (linksContainer) {
                                linksContainer.hidden = false;
                            }
                            links.forEach(function (link) {
                                link.hidden = false;
                            });
                            anyCardMatched = true;
                        } else {
                            card.hidden = true;
                        }
                    }
                }
            });

            var anyVisible = false;
            cats.forEach(function (cat) {
                var visibleCards = Array.prototype.slice.call(cat.querySelectorAll('.panel-module-card'))
                    .filter(function (card) { return !card.hidden; });

                var catSearchData = normalize(cat.getAttribute('data-cat-search') || '');
                var categoryHit = !anyCardMatched && terms.every(function (term) {
                    return catSearchData.indexOf(term) !== -1;
                });

                if (categoryHit) {
                    visibleCards = Array.prototype.slice.call(cat.querySelectorAll('.panel-module-card'));
                    visibleCards.forEach(resetCard);
                }

                cat.hidden = visibleCards.length === 0;
                if (!cat.hidden) anyVisible = true;
            });

            noResults.hidden = anyVisible;
        }

        // Delegate clicks on card main header when redirected to a modal action
        document.addEventListener('click', function (e) {
            var target = e.target.closest('.panel-module-main[data-action]');
            if (!target) return;
            var action = target.getAttribute('data-action');
            if (action) {
                e.preventDefault();
                var card = target.closest('.panel-module-card');
                var matchingBtn = card ? card.querySelector('button[data-action="' + action + '"]') : null;
                if (matchingBtn) {
                    matchingBtn.click();
                }
            }
        });

        input.addEventListener('input', apply);
        clearBtn.addEventListener('click', function () {
            input.value = '';
            apply();
            input.focus();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
