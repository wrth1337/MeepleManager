document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-game-list]').forEach(function (list) {
        var container = list.closest('.meeple-manager-catalog-wrap');
        var search = container ? container.querySelector('[data-game-list-search]') : null;
        var noResults = container ? container.querySelector('[data-game-list-no-results]') : null;
        var cards = Array.prototype.slice.call(list.querySelectorAll('[data-game-title]'));
        var categoryButtons = container ? container.querySelectorAll('[data-game-category-filter]') : [];
        var enabledCategories = Object.create(null);

        if (!search) {
            return;
        }

        Array.prototype.forEach.call(categoryButtons, function (button) {
            enabledCategories[button.getAttribute('data-game-category-filter')] = true;
            button.addEventListener('click', function () {
                var category = button.getAttribute('data-game-category-filter');
                enabledCategories[category] = !enabledCategories[category];
                button.classList.toggle('is-active', enabledCategories[category]);
                button.setAttribute('aria-pressed', enabledCategories[category] ? 'true' : 'false');
                filterGames();
            });
        });

        function filterGames() {
            var query = search.value.trim().toLocaleLowerCase('de-DE');
            var visibleGames = 0;

            cards.forEach(function (card) {
                var title = (card.getAttribute('data-game-title') || '').toLocaleLowerCase('de-DE');
                var categories = (card.getAttribute('data-game-categories') || '').split(/\s+/).filter(Boolean);
                var matchesTitle = query === '' || title.indexOf(query) !== -1;
                var matchesCategory = categories.length === 0 || categories.some(function (category) {
                    return enabledCategories[category] !== false;
                });
                var matches = matchesTitle && matchesCategory;

                card.hidden = !matches;
                if (matches) {
                    visibleGames += 1;
                }
            });

            if (noResults) {
                noResults.hidden = visibleGames !== 0;
            }
        }

        function setFlipped(card, flipped) {
            card.classList.toggle('is-flipped', flipped);
            card.setAttribute('aria-expanded', flipped ? 'true' : 'false');
        }

        function isFlipped(card) {
            var supportsHover = window.matchMedia && window.matchMedia('(hover: hover)').matches;
            return card.classList.contains('is-flipped')
                || (supportsHover && card.matches(':hover') && !card.closest('.meeple-manager-catalog__item').classList.contains('is-hovering-favorite'))
                || card.matches(':focus-visible');
        }

        list.querySelectorAll('[data-game-card]').forEach(function (card) {
            card.addEventListener('click', function (event) {
                var supportsHover = window.matchMedia && window.matchMedia('(hover: hover)').matches;
                if (event.detail === 0 || !supportsHover) {
                    setFlipped(card, !card.classList.contains('is-flipped'));
                }
            });

            card.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    setFlipped(card, !card.classList.contains('is-flipped'));
                }
            });

            card.addEventListener('pointerenter', function (event) {
                if (event.pointerType === 'mouse') {
                    card.setAttribute('aria-expanded', 'true');
                }
            });

            card.addEventListener('pointerleave', function () {
                card.setAttribute('aria-expanded', isFlipped(card) ? 'true' : 'false');
            });

            card.addEventListener('focus', function () {
                if (card.matches(':focus-visible')) {
                    card.setAttribute('aria-expanded', 'true');
                }
            });

            card.addEventListener('blur', function () {
                card.setAttribute('aria-expanded', isFlipped(card) ? 'true' : 'false');
            });

            card.querySelectorAll('[data-meeple-favorite-badge]').forEach(function (badge) {
                var item = card.closest('.meeple-manager-catalog__item');
                badge.addEventListener('pointerenter', function () {
                    item.classList.add('is-hovering-favorite');
                    card.setAttribute('aria-expanded', 'false');
                });
                badge.addEventListener('pointerleave', function () {
                    item.classList.remove('is-hovering-favorite');
                    card.setAttribute('aria-expanded', isFlipped(card) ? 'true' : 'false');
                });
            });
        });

        search.addEventListener('input', filterGames);
    });
});
