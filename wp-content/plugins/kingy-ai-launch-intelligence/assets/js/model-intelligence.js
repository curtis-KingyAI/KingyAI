(function () {
    var forms = document.querySelectorAll('[data-kingy-model-compare-form]');
    if (!forms.length) {
        return;
    }

    forms.forEach(function (form) {
        var button = form.querySelector('button[type="submit"]');
        var selects = form.querySelectorAll('[data-kingy-model-compare-select]');
        if (!button || selects.length < 2) {
            return;
        }

        var updateState = function () {
            button.disabled = !(selects[0].value && selects[1].value && selects[0].value !== selects[1].value);
        };

        selects.forEach(function (select) {
            select.addEventListener('change', updateState);
        });

        updateState();
    });
}());
