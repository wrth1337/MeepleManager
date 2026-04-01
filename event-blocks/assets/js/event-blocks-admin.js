document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('[data-event-blocks-style-form]');
    var preview = document.querySelector('[data-event-blocks-preview]');

    if (!form || !preview) {
        return;
    }

    var inputs = form.querySelectorAll('[data-event-blocks-setting]');
    var resetButton = form.querySelector('[data-event-blocks-reset-defaults]');

    function syncInput(input) {
        var cssVariable = input.getAttribute('data-css-variable');
        var cssUnit = input.getAttribute('data-css-unit') || '';
        var outputId = input.getAttribute('data-output-id');
        var value = input.value;

        if (cssVariable) {
            preview.style.setProperty(cssVariable, value + cssUnit);
        }

        if (outputId) {
            var output = document.getElementById(outputId);

            if (output) {
                output.value = value + cssUnit;
                output.textContent = value + cssUnit;
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
