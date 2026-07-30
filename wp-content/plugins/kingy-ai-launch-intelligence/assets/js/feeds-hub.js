(function () {
    'use strict';

    var status = document.querySelector('[data-kingy-copy-status]');

    function announce(message) {
        if (!status) {
            return;
        }
        status.textContent = '';
        window.setTimeout(function () {
            status.textContent = message;
        }, 20);
    }

    function fallbackCopy(value) {
        var field = document.createElement('textarea');
        field.value = value;
        field.setAttribute('readonly', '');
        field.style.position = 'fixed';
        field.style.opacity = '0';
        field.style.pointerEvents = 'none';
        document.body.appendChild(field);
        field.select();
        var copied = false;
        try {
            copied = document.execCommand('copy');
        } catch (error) {
            copied = false;
        }
        field.remove();
        return copied ? Promise.resolve() : Promise.reject(new Error('Copy failed'));
    }

    function copyValue(value) {
        if (
            navigator.clipboard
            && typeof navigator.clipboard.writeText === 'function'
            && window.isSecureContext
        ) {
            return navigator.clipboard.writeText(value);
        }
        return fallbackCopy(value);
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest ? event.target.closest('[data-kingy-copy]') : null;
        if (!button) {
            return;
        }

        var value = button.getAttribute('data-kingy-copy') || '';
        var label = button.getAttribute('data-kingy-copy-label') || 'feed URL';
        if (!value) {
            announce('Nothing to copy.');
            return;
        }

        var original = button.textContent;
        button.disabled = true;
        copyValue(value).then(function () {
            button.textContent = 'Copied';
            announce('Copied ' + label + '.');
        }).catch(function () {
            button.textContent = 'Copy failed';
            announce('Could not copy ' + label + '. Select the visible URL or code instead.');
        }).finally(function () {
            window.setTimeout(function () {
                button.textContent = original;
                button.disabled = false;
            }, 1800);
        });
    });
}());
