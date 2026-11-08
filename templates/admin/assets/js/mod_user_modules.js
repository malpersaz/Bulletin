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

        function apply() {
            var query = normalize(input.value.trim());
            clearBtn.hidden = query === '';
            var anyCardMatched = false;
            var terms = query.split(/\s+/).filter(Boolean);
            cards.forEach(function (card) {
                var searchData = normalize(card.getAttribute('data-search') || '');
                var hit = terms.length > 0 && terms.every(function (term) {
                    return searchData.indexOf(term) !== -1;
                });
                card.hidden = terms.length > 0 && !hit;
                if (hit) anyCardMatched = true;
            });
            var anyVisible = false;
            cats.forEach(function (cat) {
                var visible = Array.prototype.slice.call(cat.querySelectorAll('.panel-module-card'))
                    .filter(function (card) { return !card.hidden; });
                // Category label matches only expand as a fallback: when no card
                // matched anywhere, prevent the empty state by revealing the
                // matching category's cards.
                var catSearchData = normalize(cat.getAttribute('data-cat-search') || '');
                var categoryHit = terms.length > 0 && !anyCardMatched && terms.every(function (term) {
                    return catSearchData.indexOf(term) !== -1;
                });
                if (categoryHit) {
                    visible = Array.prototype.slice.call(cat.querySelectorAll('.panel-module-card'));
                    visible.forEach(function (card) { card.hidden = false; });
                }
                cat.hidden = visible.length === 0;
                if (!cat.hidden) anyVisible = true;
            });
            noResults.hidden = anyVisible || query === '';
        }

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
