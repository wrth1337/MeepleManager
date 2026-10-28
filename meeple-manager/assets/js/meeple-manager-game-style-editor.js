document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('[data-meeple-game-style-form]');
    var preview = document.querySelector('[data-meeple-game-style-preview]');

    if (!form || !preview) {
        return;
    }

    var inputs = form.querySelectorAll('[data-meeple-game-style-setting]');
    var resetButton = form.querySelector('[data-meeple-game-style-reset]');

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

    inputs.forEach(function (input) {
        syncInput(input);
        input.addEventListener('input', function () {
            syncInput(input);
        });
        input.addEventListener('change', function () {
            syncInput(input);
        });
    });

    if (resetButton) {
        resetButton.addEventListener('click', function () {
            inputs.forEach(function (input) {
                var defaultValue = input.getAttribute('data-default');

                if (defaultValue !== null) {
                    input.value = defaultValue;
                    syncInput(input);
                }
            });
        });
    }
});
