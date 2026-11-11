document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-game-list]').forEach(function (list) {
        var container = list.closest('.meeple-manager-catalog-wrap');
        var search = container ? container.querySelector('[data-game-list-search]') : null;
        var noResults = container ? container.querySelector('[data-game-list-no-results]') : null;
        var cards = Array.prototype.slice.call(list.querySelectorAll('[data-game-title]'));
        var tableRows = container ? Array.prototype.slice.call(container.querySelectorAll('[data-game-list-row]')) : [];
        var tableBody = container ? container.querySelector('.meeple-manager-catalog-table tbody') : null;
        var tableView = container ? container.querySelector('[data-game-list-table]') : null;
        var viewButtons = container ? container.querySelectorAll('[data-game-list-view]') : [];
        var categoryButtons = container ? container.querySelectorAll('[data-game-category-filter]') : [];
        var categoryCount = container ? container.querySelector('[data-game-category-count]') : null;
        var sortSelect = container ? container.querySelector('[data-game-list-sort]') : null;
        var sortDirectionButton = container ? container.querySelector('[data-game-list-sort-direction]') : null;
        var enabledCategories = Object.create(null);
        var sortDirection = 'asc';
        var items = cards.map(function (card, index) {
            return {
                card: card,
                row: tableRows[index] || null
            };
        });

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
                updateCategoryCount();
                filterGames();
            });
        });

        function updateCategoryCount() {
            if (!categoryCount) {
                return;
            }

            var total = Object.keys(enabledCategories).length;
            var active = Object.keys(enabledCategories).filter(function (category) {
                return enabledCategories[category];
            }).length;

            categoryCount.textContent = active === total ? 'Alle' : (active === 0 ? 'Keine' : active + '/' + total);
        }

        function filterGames() {
            var query = search.value.trim().toLocaleLowerCase('de-DE');
            var visibleGames = 0;

            items.forEach(function (item) {
                var card = item.card;
                var title = (card.getAttribute('data-game-title') || '').toLocaleLowerCase('de-DE');
                var categories = (card.getAttribute('data-game-categories') || '').split(/\s+/).filter(Boolean);
                var matchesTitle = query === '' || title.indexOf(query) !== -1;
                var matchesCategory = categories.every(function (category) {
                    return enabledCategories[category] !== false;
                });
                var matches = matchesTitle && matchesCategory;

                card.hidden = !matches;
                if (item.row) {
                    item.row.hidden = !matches;
                }
                if (matches) {
                    visibleGames += 1;
                }
            });

            if (noResults) {
                noResults.hidden = visibleGames !== 0;
            }
        }

        function compareTitles(first, second) {
            return (first.getAttribute('data-game-title') || '').localeCompare(
                second.getAttribute('data-game-title') || '',
                'de',
                {numeric: true, sensitivity: 'base'}
            );
        }

        function numericSortValue(item, attribute) {
            var value = item.card.getAttribute(attribute);
            if (value === null || value === '') {
                return null;
            }

            var number = Number(value);
            return isFinite(number) ? number : null;
        }

        function sortGames() {
            if (!sortSelect) {
                return;
            }

            var field = sortSelect.value;
            var multiplier = sortDirection === 'asc' ? 1 : -1;
            var attribute = field === 'players'
                ? 'data-game-players-sort'
                : 'data-game-duration-sort';

            items.sort(function (first, second) {
                if (field === 'title') {
                    var firstFavorite = first.card.getAttribute('data-game-favorite') === '1';
                    var secondFavorite = second.card.getAttribute('data-game-favorite') === '1';
                    if (firstFavorite !== secondFavorite) {
                        return firstFavorite ? -1 : 1;
                    }

                    return compareTitles(first.card, second.card) * multiplier;
                }

                var firstValue = numericSortValue(first, attribute);
                var secondValue = numericSortValue(second, attribute);
                if (firstValue === null && secondValue !== null) {
                    return 1;
                }
                if (firstValue !== null && secondValue === null) {
                    return -1;
                }
                if (firstValue !== secondValue) {
                    return (firstValue - secondValue) * multiplier;
                }

                return compareTitles(first.card, second.card);
            });

            items.forEach(function (item) {
                list.appendChild(item.card);
                if (item.row && tableBody) {
                    tableBody.appendChild(item.row);
                }
            });
        }

        function updateSortDirection() {
            if (!sortDirectionButton) {
                return;
            }

            var ascending = sortDirection === 'asc';
            sortDirectionButton.textContent = ascending ? '↑' : '↓';
            sortDirectionButton.setAttribute('aria-label', ascending ? 'Absteigend sortieren' : 'Aufsteigend sortieren');
            sortDirectionButton.setAttribute('title', ascending ? 'Absteigend sortieren' : 'Aufsteigend sortieren');
        }

        function setFlipped(card, flipped) {
            card.classList.add('is-flip-controlled');
            card.classList.toggle('is-flipped', flipped);
            card.setAttribute('aria-expanded', flipped ? 'true' : 'false');
        }

        function setView(view) {
            var showTable = view === 'table';
            list.hidden = showTable;
            if (tableView) {
                tableView.hidden = !showTable;
            }

            Array.prototype.forEach.call(viewButtons, function (button) {
                var active = button.getAttribute('data-game-list-view') === view;
                button.classList.toggle('is-active', active);
                button.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
        }

        Array.prototype.forEach.call(viewButtons, function (button) {
            button.addEventListener('click', function () {
                setView(button.getAttribute('data-game-list-view'));
            });
        });

        if (sortSelect) {
            sortSelect.addEventListener('change', sortGames);
        }
        if (sortDirectionButton) {
            sortDirectionButton.addEventListener('click', function () {
                sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
                updateSortDirection();
                sortGames();
            });
        }

        function isFlipped(card) {
            var supportsHover = window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches;
            return card.classList.contains('is-flipped')
                || (!card.classList.contains('is-flip-controlled') && supportsHover && card.matches(':hover')
                    && !card.closest('.meeple-manager-catalog__item').classList.contains('is-hovering-favorite'));
        }

        list.querySelectorAll('[data-game-card]').forEach(function (card) {
            var touchInput = false;
            card.addEventListener('pointerdown', function (event) {
                touchInput = event.pointerType !== 'mouse';
                if (touchInput) {
                    // Touch can coexist with a mouse: suppress sticky CSS hover for this interaction.
                    card.classList.add('is-flip-controlled');
                }
            });

            card.addEventListener('click', function (event) {
                var supportsHover = window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches;
                if (event.detail === 0 || touchInput || !supportsHover) {
                    setFlipped(card, !isFlipped(card));
                }
            });

            card.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    if (!event.repeat) {
                        setFlipped(card, !isFlipped(card));
                    }
                }
            });

            card.addEventListener('pointerenter', function (event) {
                if (event.pointerType === 'mouse') {
                    card.setAttribute('aria-expanded', isFlipped(card) ? 'true' : 'false');
                }
            });

            card.addEventListener('pointerleave', function () {
                card.setAttribute('aria-expanded', isFlipped(card) ? 'true' : 'false');
            });

            card.addEventListener('focus', function () {
                card.setAttribute('aria-expanded', isFlipped(card) ? 'true' : 'false');
            });

            card.addEventListener('blur', function () {
                card.classList.remove('is-flip-controlled', 'is-flipped');
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

        if (sortSelect) {
            sortGames();
        }
        updateSortDirection();
        updateCategoryCount();
        setView('cards');
        filterGames();
        search.addEventListener('input', filterGames);
    });
});
