(function () {
    const NAV_LINKS = '.header-fp .landing-navbar-link, #offcanvasRight .landing-navbar-link';

    function normalizePath(path) {
        if (!path) return '/';
        let clean = path.replace(/\/+$/, '');
        if (clean === '') clean = '/';
        return clean;
    }

    function isHomePath(path) {
        return path === '/sigap' || path === '/';
    }

    function isCurrentPageLink(linkPath, currentPath) {
        return (isHomePath(linkPath) && isHomePath(currentPath)) || linkPath === currentPath;
    }

    function resetLinkState(link) {
        link.classList.remove('active', 'show', 'menu-active', 'current-page-link');
        link.removeAttribute('aria-current');
        link.removeAttribute('aria-disabled');
        link.removeAttribute('tabindex');
        delete link.dataset.currentPageLink;
    }

    function markLinkAsActive(link) {
        link.classList.add('active', 'menu-active', 'current-page-link');
        link.setAttribute('aria-current', 'page');
        link.setAttribute('aria-disabled', 'true');
        link.setAttribute('tabindex', '-1');
        link.dataset.currentPageLink = 'true';
    }

    function clearNavbarState() {
        document.querySelectorAll(NAV_LINKS).forEach(function (link) {
            resetLinkState(link);
        });
    }

    function setNavbarActive() {
        clearNavbarState();

        const currentPath = normalizePath(window.location.pathname);

        document.querySelectorAll(NAV_LINKS).forEach(function (link) {
            let linkPath = '/';

            try {
                linkPath = normalizePath(new URL(link.href, window.location.origin).pathname);
            } catch (e) {
                return;
            }

            if (isCurrentPageLink(linkPath, currentPath)) {
                markLinkAsActive(link);
            }
        });
    }

    function shouldBlockCurrentLink(link) {
        return !!link && link.matches(NAV_LINKS) && link.dataset.currentPageLink === 'true';
    }

    document.addEventListener('DOMContentLoaded', setNavbarActive);
    document.addEventListener('pjax:load', setNavbarActive);
    window.addEventListener('pageshow', setNavbarActive);
    window.addEventListener('popstate', function () {
        setTimeout(setNavbarActive, 0);
    });

    document.addEventListener('click', function (e) {
        const link = e.target.closest(NAV_LINKS);
        if (!shouldBlockCurrentLink(link)) return;

        e.preventDefault();
        e.stopPropagation();
    }, true);

    document.addEventListener('click', function (e) {
        const link = e.target.closest(NAV_LINKS);
        if (!link) return;

        if (shouldBlockCurrentLink(link)) {
            e.preventDefault();
            return;
        }

        setTimeout(setNavbarActive, 0);
    });
})();
