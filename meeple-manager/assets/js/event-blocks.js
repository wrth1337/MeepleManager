document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.event-blocks-card--clickable').forEach(function (card) {
        card.addEventListener('click', function (event) {
            if (event.target.closest('a')) {
                return;
            }

            var href = card.getAttribute('data-href');

            if (href) {
                window.location.href = href;
            }
        });

        card.addEventListener('keydown', function (event) {
            if (event.target.closest('a')) {
                return;
            }

            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();

                var href = card.getAttribute('data-href');

                if (href) {
                    window.location.href = href;
                }
            }
        });
    });
});
