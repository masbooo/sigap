(function () {
    const TRANSITION_DELAY = 160;

    function preloadImage(src) {
        return new Promise(function (resolve) {
            if (!src) {
                resolve();
                return;
            }

            const img = new Image();
            img.onload = resolve;
            img.onerror = resolve;
            img.src = src;
        });
    }

    async function waitCriticalAssets() {
        const logoSources = [
            "{{ asset('assets/custom/images/logos/logotxt_sigap_b.svg') }}",
            "{{ asset('assets/custom/images/logos/logotxt_sigap_w.svg') }}"
        ];

        const waits = [
            ...logoSources.map(preloadImage)
        ];

        if (document.fonts && document.fonts.ready) {
            waits.push(document.fonts.ready);
        }

        await Promise.all(waits);
    }

    async function revealPage() {
        await waitCriticalAssets();

        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                document.body.classList.remove('preload');
                document.body.classList.remove('page-leaving');
            });
        });
    }

    if (document.readyState === 'complete') {
        revealPage();
    } else {
        window.addEventListener('load', revealPage);
    }

    window.addEventListener('pageshow', function () {
        revealPage();
    });

    document.addEventListener('click', function (e) {
        const link = e.target.closest('a[href]');
        if (!link) return;

        const href = link.getAttribute('href') || '';

        if (
            e.defaultPrevented ||
            link.target === '_blank' ||
            link.hasAttribute('download') ||
            e.metaKey || e.ctrlKey || e.shiftKey || e.altKey ||
            href.startsWith('#') ||
            href.startsWith('javascript:') ||
            href.startsWith('mailto:') ||
            href.startsWith('tel:')
        ) {
            return;
        }

        const url = new URL(link.href, window.location.href);

        if (url.origin !== window.location.origin) return;
        if (url.href === window.location.href) return;

        e.preventDefault();
        document.body.classList.add('page-leaving');

        setTimeout(function () {
            window.location.href = url.href;
        }, TRANSITION_DELAY);
    });
})();
