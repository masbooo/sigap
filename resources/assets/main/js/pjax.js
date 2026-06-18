(function () {
    const CONTAINER_SELECTOR = '#main-wrapper';
    const TRANSITION_MS = 180;
    let isNavigating = false;

    window.SigapPageInits = window.SigapPageInits || {};
    window.__sigapPjax = window.__sigapPjax || {
        modalEventsBound: false
    };

    function getContainer() {
        return document.querySelector(CONTAINER_SELECTOR);
    }

    function cleanupBootstrapArtifacts() {
        document.querySelectorAll('.modal-backdrop').forEach(function (el, index, list) {
            if (index < list.length - 1) {
                el.remove();
            }
        });

        const hasOpenModal = document.querySelector('.modal.show');

        if (!hasOpenModal) {
            document.querySelectorAll('.modal-backdrop').forEach(function (el) {
                el.remove();
            });

            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('padding-right');
            document.body.style.removeProperty('overflow');
        }
    }

    function removeStaleModals() {
        document.querySelectorAll('.modal').forEach(function (modalEl) {
            try {
                if (window.bootstrap && bootstrap.Modal) {
                    var instance = bootstrap.Modal.getInstance(modalEl);
                    if (instance && typeof instance.dispose === 'function') {
                        instance.dispose();
                    }
                }
            } catch (e) {}

            modalEl.remove();
        });

        document.querySelectorAll('.modal-backdrop').forEach(function (el) {
            el.remove();
        });

        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('padding-right');
        document.body.style.removeProperty('overflow');
    }

    function closeOpenModals() {
        if (!window.bootstrap || !bootstrap.Modal) {
            cleanupBootstrapArtifacts();
            return;
        }

        document.querySelectorAll('.modal.show').forEach(function (modalEl) {
            try {
                const instance = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
                instance.hide();
            } catch (e) {}
        });

        setTimeout(function () {
            cleanupBootstrapArtifacts();
        }, 200);
    }

    function bindGlobalModalCleanup() {
        if (window.__sigapPjax.modalEventsBound) return;
        window.__sigapPjax.modalEventsBound = true;

        document.addEventListener('show.bs.modal', function () {
            document.body.classList.remove('page-leaving');
            cleanupBootstrapArtifacts();
        });

        document.addEventListener('shown.bs.modal', function () {
            document.body.classList.remove('page-leaving');
            cleanupBootstrapArtifacts();
        });

        document.addEventListener('hidden.bs.modal', function () {
            setTimeout(function () {
                cleanupBootstrapArtifacts();
                document.body.classList.remove('page-leaving');
            }, 150);
        });
    }

    function isInternalLink(link) {
        if (!link || !link.href) return false;

        const href = link.getAttribute('href') || '';

        if (
            link.target === '_blank' ||
            link.hasAttribute('download') ||
            link.hasAttribute('data-no-pjax') ||
            link.hasAttribute('data-bs-toggle') ||
            link.hasAttribute('data-bs-dismiss') ||
            href.startsWith('#') ||
            href.startsWith('javascript:') ||
            href.startsWith('mailto:') ||
            href.startsWith('tel:')
        ) {
            return false;
        }

        const url = new URL(link.href, window.location.href);
        return url.origin === window.location.origin;
    }

    function samePage(url) {
        const current = new URL(window.location.href);
        return current.pathname === url.pathname && current.search === url.search;
    }

    function fadeOutCurrent() {
        document.body.classList.add('page-leaving');
    }

    function fadeInNew() {
        document.body.classList.remove('preload');
        document.body.classList.remove('page-leaving');
    }

    function updateTitle(doc) {
        const newTitle = doc.querySelector('title');
        if (newTitle) {
            document.title = newTitle.textContent;
        }
    }

    function syncBodyState(doc) {
        const newBody = doc.querySelector('body');
        if (!newBody) return;

        const keepClasses = ['preload', 'page-leaving', 'modal-open'];
        const currentClasses = (document.body.className || '')
            .split(/\s+/)
            .filter(Boolean);

        const nextClasses = (newBody.className || '')
            .split(/\s+/)
            .filter(Boolean)
            .filter(function (cls) {
                return !keepClasses.includes(cls);
            });

        const merged = Array.from(new Set(
            currentClasses.filter(function (cls) {
                return !keepClasses.includes(cls);
            }).concat(nextClasses)
        ));

        document.body.className = merged.join(' ');
    }

    function loadStylesFromFragment(fragmentDoc) {
        const links = fragmentDoc.querySelectorAll('link[rel="stylesheet"]');

        links.forEach(function (link) {
            const href = link.getAttribute('href');
            if (!href) return;

            const exists = Array.from(document.querySelectorAll('link[rel="stylesheet"]'))
                .some(function (existing) {
                    return existing.getAttribute('href') === href;
                });

            if (!exists) {
                const newLink = document.createElement('link');
                newLink.rel = 'stylesheet';
                newLink.href = href;
                document.head.appendChild(newLink);
            }
        });
    }

    function executeScriptsFromContainer(container) {
        const scripts = container.querySelectorAll('script');

        scripts.forEach(function (oldScript) {
            const newScript = document.createElement('script');

            Array.from(oldScript.attributes).forEach(function (attr) {
                newScript.setAttribute(attr.name, attr.value);
            });

            if (oldScript.src) {
                const exists = Array.from(document.scripts).some(function (s) {
                    return s !== oldScript && s.src === oldScript.src;
                });

                if (!exists) {
                    newScript.src = oldScript.src;
                    document.body.appendChild(newScript);
                }
            } else {
                newScript.textContent = oldScript.textContent;
                document.body.appendChild(newScript);
            }

            oldScript.remove();
        });
    }

    function moveFragmentModalsToBody(fragmentDoc) {
        const modals = fragmentDoc.querySelectorAll('.modal');
        modals.forEach(function (modal) {
            fragmentDoc.body.appendChild(modal);
        });
    }

    function swapContainer(fragmentDoc) {
        const currentContainer = getContainer();
        const newContainer = fragmentDoc.querySelector(CONTAINER_SELECTOR);

        if (!currentContainer || !newContainer) {
            throw new Error('PJAX container tidak ditemukan.');
        }

        moveFragmentModalsToBody(fragmentDoc);

        currentContainer.replaceWith(newContainer);
        executeScriptsFromContainer(newContainer);
        removeStaleModals();

        fragmentDoc.querySelectorAll('.modal').forEach(function (newModal) {
            if (newModal.id) {
                document.querySelectorAll('[id="' + newModal.id + '"]').forEach(function (existing) {
                    existing.remove();
                });
            }

            document.body.appendChild(newModal);
            executeScriptsFromContainer(newModal);
        });
    }

    function runPageInitializers() {
        const inits = window.SigapPageInits || {};

        if (typeof inits.common === 'function') {
            inits.common();
        }

        if (document.querySelector('.district-select') && typeof inits.gedung === 'function') {
            inits.gedung();
        }

        if (document.getElementById('umkm-filter-form') && typeof inits.umkm === 'function') {
            inits.umkm();
        }

        if (
            document.querySelector('[data-calendar-instance], #calendar-landing, #calendar-user, #calendar-admin') &&
            typeof inits.calendar === 'function'
        ) {
            inits.calendar();
        }

        if (document.querySelector('[data-user-rating-page], #user-rating-page') && typeof inits.rating === 'function') {
            inits.rating();
        }

        if (document.getElementById('contact-map') && typeof inits.kontak === 'function') {
            inits.kontak();
        }
    }

    function dispatchPjaxLoad(url) {
        document.dispatchEvent(new CustomEvent('pjax:load', {
            detail: { url: url }
        }));
    }

    async function fetchPage(url) {
        const response = await fetch(url, {
            method: 'GET',
            cache: 'no-store',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-PJAX': 'true'
            },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error('Gagal memuat halaman.');
        }

        return await response.text();
    }

    async function navigate(url, pushState = true) {
        if (isNavigating) return;
        isNavigating = true;

        try {
            closeOpenModals();
            fadeOutCurrent();

            await new Promise(function (resolve) {
                setTimeout(resolve, TRANSITION_MS);
            });

            const html = await fetchPage(url);
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            cleanupBootstrapArtifacts();
            loadStylesFromFragment(doc);
            updateTitle(doc);
            syncBodyState(doc);
            swapContainer(doc);

            if (pushState) {
                history.pushState({ url: url }, '', url);
            }

            window.scrollTo({ top: 0, behavior: 'auto' });

            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    runPageInitializers();
                    dispatchPjaxLoad(url);
                    cleanupBootstrapArtifacts();
                    fadeInNew();
                });
            });
        } catch (error) {
            window.location.href = url;
            return;
        } finally {
            isNavigating = false;
        }
    }

    document.addEventListener('click', function (e) {
        if (e.defaultPrevented) return;
        if (e.button !== 0) return;
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

        if (document.querySelector('.modal.show')) {
            return;
        }

        if (
            e.target.closest('.modal') ||
            e.target.closest('.modal-backdrop') ||
            e.target.closest('.fc-event') ||
            e.target.closest('.fc-daygrid-event') ||
            e.target.closest('.fc-more-link') ||
            e.target.closest('[data-bs-toggle]') ||
            e.target.closest('[data-bs-dismiss]')
        ) {
            return;
        }

        const link = e.target.closest('a[href]');
        if (!link || !isInternalLink(link)) return;

        const url = new URL(link.href, window.location.href);
        if (samePage(url)) return;

        e.preventDefault();
        navigate(url.href, true);
    });

    window.addEventListener('popstate', function () {
        navigate(window.location.href, false);
    });

    function initialReveal() {
        bindGlobalModalCleanup();

        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                runPageInitializers();
                cleanupBootstrapArtifacts();
                document.body.classList.remove('preload');
                document.body.classList.remove('page-leaving');
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialReveal, { once: true });
    } else {
        initialReveal();
    }

    window.addEventListener('pageshow', initialReveal);
})();
