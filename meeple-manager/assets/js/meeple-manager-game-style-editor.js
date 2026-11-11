document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('[data-meeple-game-style-form]');
    var preview = document.querySelector('[data-meeple-game-style-preview]');

    if (!form || !preview) {
        return;
    }

    var inputs = form.querySelectorAll('[data-meeple-game-style-setting]');
    var resetButton = form.querySelector('[data-meeple-game-style-reset]');
    var placeholderImageInput = form.querySelector('[data-placeholder-image-id]');
    var selectPlaceholderButton = form.querySelector('[data-select-placeholder-image]');
    var clearPlaceholderButton = form.querySelector('[data-clear-placeholder-image]');
    var placeholderControlPreview = form.querySelector('[data-placeholder-image-control-preview]');

    function syncInput(input) {
        var cssVariable = input.getAttribute('data-css-variable');
        var cssUnit = input.getAttribute('data-css-unit') || '';
        var outputId = input.getAttribute('data-output-id');

        if (cssVariable) {
            preview.style.setProperty(cssVariable, input.value + cssUnit);
        }

        if (outputId) {
            var output = document.getElementById(outputId);

            if (output) {
                output.value = input.value + cssUnit;
                output.textContent = input.value + cssUnit;
            }
        }
    }

    function makePlaceholderImage(url) {
        var image = document.createElement('img');
        image.src = url;
        image.alt = '';
        image.className = 'meeple-manager-game-card__image meeple-manager-game-card__image--placeholder';
        return image;
    }

    function renderPlaceholderTarget(target, url) {
        target.classList.add('meeple-manager-game-card__image-wrap--placeholder');
        while (target.firstChild) {
            target.removeChild(target.firstChild);
        }

        if (url) {
            target.appendChild(makePlaceholderImage(url));
            return;
        }

        var placeholder = document.createElement('div');
        placeholder.className = 'meeple-manager-game-card__placeholder';
        placeholder.textContent = 'Platzhalterbild';
        target.appendChild(placeholder);
    }

    function setPlaceholderImage(id, url) {
        if (placeholderImageInput) {
            placeholderImageInput.value = id || 0;
        }

        if (placeholderControlPreview) {
            while (placeholderControlPreview.firstChild) {
                placeholderControlPreview.removeChild(placeholderControlPreview.firstChild);
            }

            if (url) {
                var image = document.createElement('img');
                image.src = url;
                image.alt = '';
                placeholderControlPreview.appendChild(image);
            } else {
                var label = document.createElement('span');
                label.textContent = 'Kein Platzhalterbild ausgewählt';
                placeholderControlPreview.appendChild(label);
            }
        }

        preview.querySelectorAll('[data-placeholder-image-target]').forEach(function (target) {
            renderPlaceholderTarget(target, url);
        });
    }

    inputs.forEach(function (input) {
        syncInput(input);
        input.addEventListener('input', function () {
            syncInput(input);
        });
        input.addEventListener('change', function () {
            syncInput(input);
        });
    });

    if (selectPlaceholderButton && window.wp && wp.media) {
        selectPlaceholderButton.addEventListener('click', function (event) {
            event.preventDefault();
            var mediaFrame = wp.media({
                title: 'Platzhalterbild auswählen',
                button: { text: 'Als Platzhalter verwenden' },
                library: { type: 'image' },
                multiple: false
            });

            mediaFrame.on('select', function () {
                var attachment = mediaFrame.state().get('selection').first().toJSON();
                var sizes = attachment.sizes || {};
                var previewSize = sizes.large || sizes.medium_large || sizes.medium;
                setPlaceholderImage(attachment.id, previewSize ? previewSize.url : attachment.url);
            });

            mediaFrame.open();
        });
    }

    if (clearPlaceholderButton) {
        clearPlaceholderButton.addEventListener('click', function (event) {
            event.preventDefault();
            setPlaceholderImage(0, '');
        });
    }

    if (resetButton) {
        resetButton.addEventListener('click', function () {
            inputs.forEach(function (input) {
                var defaultValue = input.getAttribute('data-default');

                if (defaultValue !== null) {
                    input.value = defaultValue;
                    syncInput(input);
                }
            });
            setPlaceholderImage(0, '');
        });
    }
});
