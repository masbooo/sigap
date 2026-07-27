(function () {
    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-payment-preview-action]');
        if (!trigger) {
            return;
        }

        var action = String(trigger.getAttribute('data-payment-preview-action') || '').trim();

        if (action === 'print') {
            window.print();
            return;
        }

        if (action === 'close') {
            window.close();
        }
    });
})();
