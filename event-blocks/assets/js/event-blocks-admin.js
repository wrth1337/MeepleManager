document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('[data-event-blocks-style-form]');
    var preview = document.querySelector('[data-event-blocks-preview]');

    if (!form || !preview) {
        return;
    }

    var inputs = form.querySelectorAll('[data-event-blocks-setting]');
    var textInputs = form.querySelectorAll('[data-event-blocks-text-setting]');
    var resettableInputs = form.querySelectorAll('[data-default]');
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

    function syncTextInput(input) {
        var key = input.getAttribute('data-event-blocks-text-setting');
        var value = input.value || input.getAttribute('data-default') || '';

        if (!key) {
            return;
        }

        preview.querySelectorAll('[data-event-blocks-text="' + key + '"]').forEach(function (element) {
            element.textContent = value;
        });

        if (key === 'time_suffix') {
            preview.querySelectorAll('[data-event-blocks-time-value]').forEach(function (element) {
                var baseValue = element.getAttribute('data-event-blocks-time-value') || '';
                element.textContent = value ? baseValue + ' ' + value : baseValue;
            });
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

    textInputs.forEach(function (input) {
        syncTextInput(input);

        input.addEventListener('input', function () {
            syncTextInput(input);
        });

        input.addEventListener('change', function () {
            syncTextInput(input);
        });
    });

    if (resetButton) {
        resetButton.addEventListener('click', function () {
            resettableInputs.forEach(function (input) {
                var defaultValue = input.getAttribute('data-default');

                if (defaultValue !== null) {
                    input.value = defaultValue;
                    if (input.hasAttribute('data-event-blocks-setting')) {
                        syncInput(input);
                    }
                }
            });
            textInputs.forEach(function (input) {
                syncTextInput(input);
            });
        });
    }
});
