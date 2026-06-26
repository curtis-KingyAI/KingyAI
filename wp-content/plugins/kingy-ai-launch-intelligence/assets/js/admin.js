(function () {
    function activateTab(container, button) {
        var targetId = button.getAttribute('data-kingy-ali-meta-tab');
        if (!targetId) {
            return;
        }

        var buttons = container.querySelectorAll('[data-kingy-ali-meta-tab]');
        var panels = container.querySelectorAll('.kingy-ali-meta-tabs__panel');

        buttons.forEach(function (tabButton) {
            var isActive = tabButton === button;
            tabButton.classList.toggle('is-active', isActive);
            tabButton.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        panels.forEach(function (panel) {
            var isActive = panel.id === targetId;
            panel.classList.toggle('is-active', isActive);
            panel.hidden = !isActive;
        });
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-kingy-ali-meta-tab]');
        if (!button) {
            return;
        }

        var container = button.closest('[data-kingy-ali-meta-tabs]');
        if (!container) {
            return;
        }

        event.preventDefault();
        activateTab(container, button);
    });
})();
