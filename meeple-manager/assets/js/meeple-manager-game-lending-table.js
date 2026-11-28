document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-meeple-manager-game-table]').forEach(function (table) {
        var container = table.closest('.meeple-manager-table-wrap');
        var body = table.tBodies[0];

        if (!container || !body) {
            return;
        }

        var rows = Array.prototype.slice.call(body.rows);
        var search = container.querySelector('[data-game-title-search]');
        var noResults = container.querySelector('[data-game-title-no-results]');
        var sortButtons = container.querySelectorAll('[data-sort-button]');

        function updateSortIndicators(column, direction) {
            sortButtons.forEach(function (button) {
                var active = button.getAttribute('data-sort-button') === column;
                var heading = button.closest('th');
                var indicator = button.querySelector('.meeple-manager-sort-indicator');
                if (heading) {
                    heading.setAttribute('aria-sort', active ? (direction === 'asc' ? 'ascending' : 'descending') : 'none');
                }

                if (active && indicator) {
                    indicator.textContent = direction === 'asc' ? '↑' : '↓';
                } else if (indicator) {
                    indicator.textContent = '↕';
                }

                var buttonColumnLabel = button.getAttribute('data-sort-button') === 'inventory'
                    ? 'Inventarnummer'
                    : 'Titel';
                var nextDirection = active ? (direction === 'asc' ? 'absteigend' : 'aufsteigend') : 'aufsteigend';
                button.setAttribute('aria-label', 'Nach ' + buttonColumnLabel + ' ' + nextDirection + ' sortieren');
            });

            table.setAttribute('data-sort-column', column);
            table.setAttribute('data-sort-direction', direction);
        }

        function sortRows(column, direction) {
            var key = column === 'inventory' ? 'data-inventory-number' : 'data-game-title';
            var multiplier = direction === 'asc' ? 1 : -1;

            rows.sort(function (first, second) {
                var firstValue = first.getAttribute(key) || '';
                var secondValue = second.getAttribute(key) || '';

                return firstValue.localeCompare(secondValue, 'de', {
                    numeric: true,
                    sensitivity: 'base'
                }) * multiplier;
            });

            rows.forEach(function (row) {
                body.appendChild(row);
            });

            updateSortIndicators(column, direction);
        }

        function filterRows() {
            var query = search ? search.value.trim().toLocaleLowerCase('de-DE') : '';
            var visibleRows = 0;

            rows.forEach(function (row) {
                var title = (row.getAttribute('data-game-title') || '').toLocaleLowerCase('de-DE');
                var matches = query === '' || title.indexOf(query) !== -1;
                row.hidden = !matches;

                if (matches) {
                    visibleRows += 1;
                }
            });

            if (noResults) {
                noResults.hidden = visibleRows !== 0;
            }
        }

        sortButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                var column = button.getAttribute('data-sort-button');
                var currentColumn = table.getAttribute('data-sort-column');
                var currentDirection = table.getAttribute('data-sort-direction');
                var direction = currentColumn === column && currentDirection === 'asc' ? 'desc' : 'asc';

                sortRows(column, direction);
            });
        });

        if (search) {
            search.addEventListener('input', filterRows);
        }

        sortRows('title', 'asc');
    });
});
