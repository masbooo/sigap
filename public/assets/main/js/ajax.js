// ===============================
// GLOBAL STATE
// ===============================
let isLoading = false;
window.calendarInstance = null;
window.bootstrapCarousels = [];

function destroyCalendarInstances() {
    if (window.calendarInstances) {
        Object.keys(window.calendarInstances).forEach(function (key) {
            const instance = window.calendarInstances[key];

            if (!instance || typeof instance.destroy !== 'function') return;

            try {
                instance.destroy();
            } catch (e) {
                console.warn(e);
            }
        });

        window.calendarInstances = {};
    }

    if (window.calendarRawEvents) {
        window.calendarRawEvents = {};
    }

    if (window.calendarInstance) {
        try {
            window.calendarInstance.destroy();
        } catch (e) {
            console.warn(e);
        }

        window.calendarInstance = null;
    }
}

function destroyModalInstances() {
    document.querySelectorAll('.modal').forEach(function (modalEl) {
        try {
            if (window.bootstrap && bootstrap.Modal) {
                const instance = bootstrap.Modal.getInstance(modalEl);
                if (instance && typeof instance.dispose === 'function') {
                    instance.dispose();
                }
            }
        } catch (e) {
            console.warn(e);
        }

        modalEl.remove();
    });

    document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
        backdrop.remove();
    });

    document.body.classList.remove('modal-open');
    document.body.style.removeProperty('padding-right');
    document.body.style.removeProperty('overflow');
}

function executeScriptsInScope(scope) {
    if (!scope || !scope.querySelectorAll) return;

    scope.querySelectorAll('script').forEach(function (oldScript) {
        const newScript = document.createElement('script');

        Array.from(oldScript.attributes).forEach(function (attr) {
            newScript.setAttribute(attr.name, attr.value);
        });

        if (oldScript.src) {
            const exists = Array.from(document.scripts).some(function (script) {
                return script !== oldScript && script.src === oldScript.src;
            });

            if (exists) {
                oldScript.remove();
                return;
            }

            newScript.src = oldScript.src;
        } else {
            newScript.textContent = oldScript.textContent;
        }

        oldScript.parentNode.replaceChild(newScript, oldScript);
    });
}

function runPageInitializers(scope) {
    const root = scope && scope.querySelector ? scope : document;
    const inits = window.SigapPageInits || {};

    if (typeof initPage === 'function') {
        try {
            initPage();
        } catch (e) {
            console.warn(e);
        }
    }

    if (
        root.querySelector('[data-calendar-instance], #calendar-landing, #calendar-user, #calendar-admin') &&
        typeof inits.calendar === 'function'
    ) {
        try {
            inits.calendar();
        } catch (e) {
            console.warn(e);
        }
    }

    if (root.querySelector('[data-user-rating-page], #user-rating-page') && typeof inits.rating === 'function') {
        try {
            inits.rating();
        } catch (e) {
            console.warn(e);
        }
    }
}

// ===============================
// DESTROY CURRENT PAGE
// ===============================
function destroyPage() {
    destroyCalendarInstances();
    destroyModalInstances();

    window.bootstrapCarousels.forEach(carousel => {
        try { carousel.dispose(); } catch (e) {}
    });
    window.bootstrapCarousels = [];

    window.scrollTo(0, 0);
}

// ===============================
// RE-INIT BOOTSTRAP CAROUSELS
// ===============================
function initBootstrapCarousels(scope = document) {
    if (typeof bootstrap === 'undefined') return;

    scope.querySelectorAll('.carousel').forEach(carouselEl => {
        try {
            if (carouselEl._carouselInstance) {
                carouselEl._carouselInstance.dispose();
            }

            const carousel = new bootstrap.Carousel(carouselEl, {
                interval: 5000,
                ride: 'carousel'
            });

            carouselEl._carouselInstance = carousel;
            window.bootstrapCarousels.push(carousel);
        } catch (e) {
            console.warn('Bootstrap carousel init failed', e);
        }
    });
}

// ===============================
// RE-INIT OWL CAROUSELS
// ===============================
function parseOwlBoolean(value, fallback) {
    if (value === undefined || value === null || value === '') return fallback;
    if (typeof value === 'boolean') return value;

    const normalized = String(value).trim().toLowerCase();

    if (['true', '1', 'yes', 'on'].includes(normalized)) return true;
    if (['false', '0', 'no', 'off'].includes(normalized)) return false;

    return fallback;
}

function parseOwlNumber(value, fallback) {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : fallback;
}

function buildOwlResponsiveOptions($element, fallbackResponsive) {
    const responsiveJson = $element.attr('data-owl-responsive');
    const mergedFallbackResponsive = Object.assign({}, fallbackResponsive);

    if (responsiveJson) {
        try {
            const parsed = JSON.parse(responsiveJson);

            if (parsed && typeof parsed === 'object') {
                Object.keys(parsed).forEach(function (breakpoint) {
                    mergedFallbackResponsive[breakpoint] = Object.assign(
                        {},
                        mergedFallbackResponsive[breakpoint] || {},
                        parsed[breakpoint]
                    );
                });

                return mergedFallbackResponsive;
            }
        } catch (e) {
            console.warn('Invalid Owl responsive config', e);
        }
    }

    const breakpointMap = [
        ['0', 'data-owl-items'],
        ['576', 'data-owl-sm-items'],
        ['768', 'data-owl-md-items'],
        ['992', 'data-owl-lg-items'],
        ['1200', 'data-owl-xl-items'],
        ['1400', 'data-owl-xxl-items']
    ];

    const responsive = Object.assign({}, fallbackResponsive);
    let hasCustomResponsive = false;

    breakpointMap.forEach(function (item) {
        const breakpoint = item[0];
        const attributeName = item[1];
        const rawValue = $element.attr(attributeName);

        if (rawValue === undefined || rawValue === null || rawValue === '') return;

        responsive[breakpoint] = {
            items: parseOwlNumber(rawValue, 1)
        };
        hasCustomResponsive = true;
    });

    return hasCustomResponsive ? responsive : fallbackResponsive;
}

function initOwlCarousels(scope = document) {
    if (typeof $ === 'undefined' || !$.fn.owlCarousel) return;

    $(scope).find('.owl-carousel').each(function () {
        const $this = $(this);
        const defaultResponsive = {
            0: { items: 1 },
            576: { items: 2 },
            768: { items: 3 },
            992: { items: 4 },
            1200: { items: 5 }
        };
        const owlOptions = {
            loop: parseOwlBoolean($this.attr('data-owl-loop'), true),
            margin: parseOwlNumber($this.attr('data-owl-margin'), 10),
            nav: parseOwlBoolean($this.attr('data-owl-nav'), false),
            dots: parseOwlBoolean($this.attr('data-owl-dots'), true),
            autoplay: parseOwlBoolean($this.attr('data-owl-autoplay'), false),
            autoplayTimeout: parseOwlNumber($this.attr('data-owl-autoplay-timeout'), 5000),
            smartSpeed: parseOwlNumber($this.attr('data-owl-smart-speed'), 450),
            slideBy: $this.attr('data-owl-slide-by') === 'page'
                ? 'page'
                : parseOwlNumber($this.attr('data-owl-slide-by'), 1),
            responsive: buildOwlResponsiveOptions($this, defaultResponsive)
        };

        try {
            if ($this.hasClass('owl-loaded')) {
                $this.trigger('destroy.owl.carousel');
                $this.removeClass('owl-loaded');
                $this.find('.owl-stage-outer').children().unwrap();
            }

            $this.owlCarousel(owlOptions);
        } catch (e) {
            console.warn('Owl carousel init failed', e);
        }
    });
}

// ===============================
// CLOSE TOPBAR DROPDOWNS
// ===============================
function closeTopbarDropdowns() {
    document.querySelectorAll('.topbar .nav-item.dropdown').forEach(dropdown => {
        dropdown.classList.remove('dropdown-open', 'show');

        const menu = dropdown.querySelector(':scope > .dropdown-menu');
        const trigger = dropdown.querySelector(':scope > .nav-link');

        if (menu) menu.classList.remove('show');
        if (trigger) trigger.setAttribute('aria-expanded', 'false');
    });
}

// ===============================
// AJAX NAVIGATION
// ===============================
document.addEventListener('click', function (e) {
    const link = e.target.closest('a.ajax-link');
    if (!link) return;

    const href = link.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('javascript') || link.hasAttribute('data-bs-toggle')) return;

    if (link.target && link.target !== '_self') return;
    if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;

    e.preventDefault();
    loadPage(href);
});

// ===============================
// EXTRACT ONLY #main-content FROM RESPONSE
// ===============================
function extractMainContent(htmlText) {
    const parser = new DOMParser();
    const doc = parser.parseFromString(htmlText, 'text/html');

    const newContent = doc.querySelector('#main-content');
    const newTitle = doc.querySelector('title');

    return {
        contentHtml: newContent ? newContent.innerHTML : null,
        title: newTitle ? newTitle.textContent : null
    };
}

// ===============================
// LOAD PAGE VIA AJAX
// ===============================
function loadPage(url, push = true) {
    if (isLoading) return;
    isLoading = true;

    const content = document.getElementById('main-content');
    const loader = document.getElementById('page-loader');

    if (!content) {
        window.location.href = url;
        return;
    }

    if (loader) loader.classList.remove('hide');

    content.style.pointerEvents = 'none';
    destroyPage();

    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(res => {
        if (!res.ok) throw new Error('Request failed');
        return res.text().then(html => ({
            html: html,
            finalUrl: res.redirected && res.url ? res.url : url
        }));
    })
    .then(({ html, finalUrl }) => {
        if (!html || html.trim() === '') {
            window.location.href = finalUrl;
            return;
        }

        const extracted = extractMainContent(html);

        // kalau server tidak mengembalikan layout yang punya #main-content,
        // fallback ke reload normal
        if (!extracted.contentHtml) {
            window.location.href = finalUrl;
            return;
        }

        requestAnimationFrame(() => {
            content.style.opacity = '0';

            setTimeout(() => {
                content.innerHTML = extracted.contentHtml;
                executeScriptsInScope(content);

                if (extracted.title) {
                    document.title = extracted.title;
                }

                if (push) {
                    history.pushState(null, '', finalUrl);
                }

                setActiveMenu();
                closeTopbarDropdowns();
                runPageInitializers(content);

                initBootstrapCarousels(content);
                initOwlCarousels(content);

                requestAnimationFrame(() => {
                    content.style.opacity = '1';
                    content.style.pointerEvents = '';
                });

                if (loader) loader.classList.add('hide');
                isLoading = false;
            }, 100);
        });
    })
    .catch((err) => {
        console.warn('AJAX load failed:', err);
        window.location.href = url;
    });
}

// ===============================
// BACK / FORWARD BROWSER
// ===============================
window.addEventListener('popstate', () => loadPage(location.href, false));

// ===============================
// SET ACTIVE MENU
// ===============================
function setActiveMenu() {
    const path = window.location.pathname.replace(/\/$/, '');

    document.querySelectorAll('.sidebar-link.menu-active').forEach(link => {
        link.classList.remove('menu-active');
    });

    document.querySelectorAll('.collapse.show').forEach(c => {
        c.classList.remove('show');
        const trigger = document.querySelector(`[data-bs-toggle="collapse"][href="#${c.id}"]`);
        if (trigger) {
            trigger.classList.add('collapsed');
            trigger.setAttribute('aria-expanded', 'false');
        }
    });

    const links = Array.from(document.querySelectorAll('.sidebar-link')).filter(link => {
        const href = link.getAttribute('href');
        if (!href || href === '#' || href.startsWith('javascript')) return false;

        const linkPath = new URL(href, window.location.origin).pathname.replace(/\/$/, '');
        return linkPath === path;
    });

    if (!links.length) return;

    const activeLink = links[0];
    activeLink.classList.add('menu-active');

    let parent = activeLink.closest('.collapse');
    while (parent) {
        parent.classList.add('show');

        const trigger = document.querySelector(`[data-bs-toggle="collapse"][href="#${parent.id}"]`);
        if (trigger) {
            trigger.classList.remove('collapsed');
            trigger.setAttribute('aria-expanded', 'true');
        }

        parent = parent.parentElement.closest('.collapse');
    }
}

// ===============================
// INIT AWAL
// ===============================
document.addEventListener('DOMContentLoaded', () => {
    const content = document.getElementById('main-content');

    if (content) {
        content.style.transition = 'opacity 0.2s ease';
        content.style.opacity = '1';
    }

    setActiveMenu();
    runPageInitializers(document);

    initBootstrapCarousels(document);
    initOwlCarousels(document);

    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            document.body.classList.remove('preload');
        });
    });
});
