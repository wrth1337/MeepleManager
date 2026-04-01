document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('[data-event-blocks-style-form]');
    var preview = document.querySelector('[data-event-blocks-preview]');

    if (!form || !preview) {
        return;
    }

    var inputs = form.querySelectorAll('[data-event-blocks-setting]');
    var resettableInputs = form.querySelectorAll('[data-default]');
    var resetButton = form.querySelector('[data-event-blocks-reset-defaults]');
    var announcementLabelInput = form.querySelector('[data-event-blocks-announcement-label]');

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

    function syncAnnouncementLabel() {
        if (!announcementLabelInput) {
            return;
        }

        var value = announcementLabelInput.value;

        preview.querySelectorAll('.event-blocks-status').forEach(function (element) {
            element.textContent = value || announcementLabelInput.getAttribute('data-default') || 'Vorankuendigung';
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

    if (announcementLabelInput) {
        syncAnnouncementLabel();

        announcementLabelInput.addEventListener('input', syncAnnouncementLabel);
        announcementLabelInput.addEventListener('change', syncAnnouncementLabel);
    }

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

            syncAnnouncementLabel();
        });
    }
});
