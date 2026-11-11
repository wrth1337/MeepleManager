document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-game-list]').forEach(function (list) {
        var container = list.closest('.meeple-manager-catalog-wrap');
        var search = container ? container.querySelector('[data-game-list-search]') : null;
        var noResults = container ? container.querySelector('[data-game-list-no-results]') : null;
        var cards = Array.prototype.slice.call(list.querySelectorAll('[data-game-title]'));

        if (!search) {
            return;
        }

        function filterGames() {
            var query = search.value.trim().toLocaleLowerCase('de-DE');
            var visibleGames = 0;

            cards.forEach(function (card) {
                var title = (card.getAttribute('data-game-title') || '').toLocaleLowerCase('de-DE');
                var matches = query === '' || title.indexOf(query) !== -1;
                card.hidden = !matches;

                if (matches) {
                    visibleGames += 1;
                }
            });

            if (noResults) {
                noResults.hidden = visibleGames !== 0;
            }
        }

        search.addEventListener('input', filterGames);
    });
});
