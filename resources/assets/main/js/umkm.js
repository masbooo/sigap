(function () {
    window.SigapPageInits = window.SigapPageInits || {};
    window.__sigapUmkm = window.__sigapUmkm || {
        paginationBound: false,
        popstateBound: false,
        isLoadingSection: false
    };

    function getUmkmListSection() {
        return document.getElementById('umkm-list-section');
    }

    function updateDocumentTitle(doc) {
        var titleEl = doc.querySelector('title');

        if (titleEl && titleEl.textContent) {
            document.title = titleEl.textContent;
        }
    }

    function isUmkmLocation(urlValue) {
        var url = new URL(urlValue, window.location.href);
        var normalizedPath = url.pathname.replace(/\/+$/, '');

        return /\/umkm(?:\/\d+)?$/.test(normalizedPath);
    }

    function normalizeUmkmUrl(urlValue) {
        var url = new URL(urlValue, window.location.href);
        var pathMatch = url.pathname.match(/^(.*\/umkm)(?:\/(\d+))?\/?$/);

        if (!pathMatch) {
            url.hash = '';
            return url.toString();
        }

        var basePath = pathMatch[1];
        var pathPage = parseInt(pathMatch[2] || '1', 10);
        var queryPage = parseInt(url.searchParams.get('page') || '0', 10);
        var finalPage = queryPage > 0 ? queryPage : pathPage;

        if (!isFinite(finalPage) || finalPage < 1) {
            finalPage = 1;
        }

        url.searchParams.delete('page');
        url.pathname = finalPage > 1 ? basePath + '/' + finalPage : basePath;
        url.hash = '';

        return url.toString();
    }

    function parseUmkmSectionFromHtml(html) {
        var parser = new DOMParser();
        var doc = parser.parseFromString(html, 'text/html');

        return {
            doc: doc,
            section: doc.getElementById('umkm-list-section'),
            regionDistrictMap: doc.getElementById('umkm-region-district-map')
        };
    }

    function syncRegionDistrictMap(nextMapEl) {
        if (!nextMapEl) {
            return;
        }

        var currentMapEl = document.getElementById('umkm-region-district-map');

        if (currentMapEl) {
            currentMapEl.replaceWith(nextMapEl);
            return;
        }

        document.body.appendChild(nextMapEl);
    }

    async function loadUmkmSection(url, options) {
        var settings = options || {};
        var normalizedUrl = normalizeUmkmUrl(url);
        var currentSection = getUmkmListSection();

        if (!currentSection || window.__sigapUmkm.isLoadingSection) {
            return;
        }

        window.__sigapUmkm.isLoadingSection = true;

        var previousTop = currentSection.getBoundingClientRect().top;
        currentSection.classList.add('umkm-section-loading');

        try {
            var response = await fetch(normalizedUrl, {
                method: 'GET',
                cache: 'no-store',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-UMKM-Section': 'true'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error('Gagal memuat daftar UMKM.');
            }

            var html = await response.text();
            var parsed = parseUmkmSectionFromHtml(html);

            if (!parsed.section) {
                throw new Error('Section UMKM tidak ditemukan.');
            }

            currentSection.replaceWith(parsed.section);
            syncRegionDistrictMap(parsed.regionDistrictMap);
            updateDocumentTitle(parsed.doc);

            if (settings.historyMode === 'push') {
                history.pushState(history.state, '', normalizedUrl);
            } else if (settings.historyMode === 'replace') {
                history.replaceState(history.state, '', normalizedUrl);
            }

            var nextSection = getUmkmListSection();

            if (nextSection) {
                var nextTop = nextSection.getBoundingClientRect().top;
                window.scrollBy({
                    top: nextTop - previousTop,
                    left: 0,
                    behavior: 'auto'
                });
            }

            if (typeof window.SigapPageInits.umkm === 'function') {
                window.SigapPageInits.umkm();
            }
        } catch (error) {
            window.location.href = normalizedUrl;
            return;
        } finally {
            var latestSection = getUmkmListSection();

            if (latestSection) {
                latestSection.classList.remove('umkm-section-loading');
            }

            window.__sigapUmkm.isLoadingSection = false;
        }
    }

    function bindAjaxPagination() {
        if (window.__sigapUmkm.paginationBound) {
            return;
        }

        window.__sigapUmkm.paginationBound = true;

        document.addEventListener('click', function (event) {
            var link = event.target.closest('a[data-umkm-pagination-link]');

            if (!link) {
                return;
            }

            if (
                event.defaultPrevented ||
                event.button !== 0 ||
                event.metaKey ||
                event.ctrlKey ||
                event.shiftKey ||
                event.altKey ||
                link.target === '_blank' ||
                link.hasAttribute('download')
            ) {
                return;
            }

            if (!getUmkmListSection()) {
                return;
            }

            event.preventDefault();

            if (typeof event.stopImmediatePropagation === 'function') {
                event.stopImmediatePropagation();
            }

            loadUmkmSection(link.href, {
                historyMode: 'push'
            });
        }, true);

        if (window.__sigapUmkm.popstateBound) {
            return;
        }

        window.__sigapUmkm.popstateBound = true;

        window.addEventListener('popstate', function (event) {
            if (!getUmkmListSection() || !isUmkmLocation(window.location.href)) {
                return;
            }

            if (typeof event.stopImmediatePropagation === 'function') {
                event.stopImmediatePropagation();
            }

            loadUmkmSection(window.location.href, {
                historyMode: 'none'
            });
        }, true);
    }

    function initUmkmPage() {
        var form = document.getElementById('umkm-filter-form');
        var regionSelect = document.getElementById('umkm-region-filter');
        var districtSelect = document.getElementById('umkm-district-filter');
        var dataEl = document.getElementById('umkm-region-district-map');
        var modalEl = document.getElementById('umkmDetailModal');

        if (!form || !regionSelect || !districtSelect || !dataEl) {
            return;
        }

        function applyFallbackImage(img) {
            var fallbackSrc = img.getAttribute('data-fallback-src') || '';

            if (!fallbackSrc || img.getAttribute('src') === fallbackSrc) {
                return;
            }

            img.src = fallbackSrc;
        }

        function bindFallbackImages(root) {
            (root || document).querySelectorAll('.umkm-fallback-image[data-fallback-src]').forEach(function (img) {
                if (img.dataset.boundFallbackImage !== 'true') {
                    img.dataset.boundFallbackImage = 'true';
                    img.addEventListener('error', function () {
                        applyFallbackImage(this);
                    });
                }

                if (img.complete && img.naturalWidth === 0) {
                    applyFallbackImage(img);
                }
            });
        }

        function buildRatingMarkup(ratingValue, reviewCount) {
            return String(ratingValue)
                + '<span class="text-warning">&#9733;</span>'
                + ' <span class="text-muted">(' + String(reviewCount) + ')</span>';
        }

        var regionDistrictMap = {};

        try {
            regionDistrictMap = JSON.parse(dataEl.getAttribute('data-map') || '{}') || {};
        } catch (e) {
            regionDistrictMap = {};
        }

        function buildOptions(districts, selectedValue, placeholder) {
            districtSelect.innerHTML = '';

            var defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = placeholder;
            defaultOption.selected = !selectedValue;
            districtSelect.appendChild(defaultOption);

            districts.forEach(function (district) {
                var option = document.createElement('option');
                option.value = district;
                option.textContent = district;

                if (String(selectedValue || '') === String(district)) {
                    option.selected = true;
                }

                districtSelect.appendChild(option);
            });
        }

        function syncDistrictOptions() {
            var selectedRegion = String(regionSelect.value || '').trim();
            var currentDistrict = String(districtSelect.value || '').trim();

            if (!selectedRegion) {
                districtSelect.disabled = true;
                buildOptions([], '', 'Pilih Wilayah Terlebih Dahulu');
                return;
            }

            districtSelect.disabled = false;
            var districts = regionDistrictMap[selectedRegion] || [];

            if (districts.indexOf(currentDistrict) === -1) {
                currentDistrict = '';
            }

            buildOptions(
                districts,
                currentDistrict,
                districts.length ? 'Pilih Lokasi' : 'Lokasi Belum Tersedia'
            );
        }

        if (form.dataset.boundUmkm !== 'true') {
            form.dataset.boundUmkm = 'true';

            regionSelect.addEventListener('change', function () {
                syncDistrictOptions();
            });
        }

        syncDistrictOptions();
        bindFallbackImages(document);
        bindAjaxPagination();

        var canonicalUrl = normalizeUmkmUrl(window.location.href);

        if (canonicalUrl !== window.location.href) {
            history.replaceState(history.state, '', canonicalUrl);
        }

        if (modalEl && modalEl.dataset.boundUmkmModal !== 'true') {
            modalEl.dataset.boundUmkmModal = 'true';

            modalEl.addEventListener('show.bs.modal', function (event) {
                var trigger = event.relatedTarget;
                if (!trigger) return;

                function setText(id, value) {
                    var el = document.getElementById(id);
                    if (!el) return;
                    el.textContent = value && String(value).trim() !== '' ? value : '-';
                }

                function setWhatsappLink(id, value) {
                    var el = document.getElementById(id);
                    if (!el) return;

                    var label = value && String(value).trim() !== '' ? String(value).trim() : '-';
                    var digits = label.replace(/\D/g, '');

                    el.textContent = '';

                    if (label === '-' || digits === '') {
                        el.textContent = '-';
                        return;
                    }

                    if (digits.charAt(0) === '0') {
                        digits = '62' + digits.slice(1);
                    } else if (digits.charAt(0) === '8') {
                        digits = '62' + digits;
                    }

                    var link = document.createElement('a');
                    link.href = 'https://wa.me/' + digits;
                    link.target = '_blank';
                    link.rel = 'noopener noreferrer';
                    link.className = 'umkm-contact-link';
                    link.textContent = label;

                    el.appendChild(link);
                }

                var imageEl = document.getElementById('umkm-detail-image');
                var ratingEl = document.getElementById('umkm-detail-rating');
                var imageSrc = trigger.getAttribute('data-image') || '';
                var fallbackImage = trigger.getAttribute('data-fallback-image') || imageSrc;
                var rating = trigger.getAttribute('data-rating') || '0';
                var reviewCount = trigger.getAttribute('data-reviews') || '0';
                var name = trigger.getAttribute('data-name') || '-';
                var owner = trigger.getAttribute('data-owner') || '-';

                setText('umkmDetailModalLabel', name);
                setText('umkm-detail-owner', 'By ' + owner);
                setText('umkm-detail-product', trigger.getAttribute('data-product') || 'UMKM');
                setText('umkm-detail-home-location', trigger.getAttribute('data-home-location') || '-');
                setText('umkm-detail-buildings', trigger.getAttribute('data-buildings') || '-');
                setText('umkm-detail-address', trigger.getAttribute('data-address') || '-');
                setText('umkm-detail-description', trigger.getAttribute('data-description') || '-');
                setWhatsappLink('umkm-detail-phone', trigger.getAttribute('data-phone') || '-');

                if (ratingEl) {
                    ratingEl.innerHTML = buildRatingMarkup(rating, reviewCount);
                }

                if (imageEl) {
                    imageEl.src = imageSrc;
                    imageEl.alt = name;
                    imageEl.onerror = function () {
                        this.onerror = null;
                        this.src = fallbackImage;
                    };
                }
            });
        }
    }

    window.SigapPageInits.umkm = initUmkmPage;
})();
