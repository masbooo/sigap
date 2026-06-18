(function () {
    window.SigapPageInits = window.SigapPageInits || {};

    function initGedungPage() {
        const selects = document.querySelectorAll('.district-select');
        const tabButtons = document.querySelectorAll('#gedungRegionTab [data-bs-toggle="tab"]');

        if (!selects.length) return;

        const originalSlidesByRegion = {};

        function applyFallbackImage(img) {
            const fallbackSrc = img.getAttribute('data-fallback-src') || '';

            if (!fallbackSrc || img.getAttribute('src') === fallbackSrc) {
                return;
            }

            img.src = fallbackSrc;
        }

        function bindFallbackImages(root) {
            (root || document).querySelectorAll('.gedung-fallback-image[data-fallback-src]').forEach(function (img) {
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

        function normalizeDistrictValue(value) {
            return String(value || '')
                .trim()
                .replace(/\s+/g, ' ')
                .toLowerCase();
        }

        function getCarouselElements(regionKey) {
            const carousel = document.getElementById('carousel-' + regionKey);
            const carouselInner = document.getElementById('carousel-inner-' + regionKey);
            const emptyFilter = document.getElementById('empty-filter-' + regionKey);

            if (!carousel || !carouselInner) {
                return null;
            }

            const prevBtn = carousel.querySelector('.carousel-control-prev');
            const nextBtn = carousel.querySelector('.carousel-control-next');

            return {
                carousel,
                carouselInner,
                emptyFilter,
                prevBtn,
                nextBtn
            };
        }

        function cacheOriginalSlides() {
            document.querySelectorAll('[id^="carousel-inner-"]').forEach(function (carouselInner) {
                const regionKey = carouselInner.id.replace('carousel-inner-', '');
                const slides = Array.from(carouselInner.querySelectorAll('.carousel-item')).map(function (item) {
                    return item.outerHTML;
                });

                originalSlidesByRegion[regionKey] = slides;
            });
        }

        function setArrowVisibility(prevBtn, nextBtn, visibleCount) {
            const shouldShow = visibleCount > 1;

            [prevBtn, nextBtn].forEach(function (btn) {
                if (!btn) return;
                btn.style.display = shouldShow ? '' : 'none';
            });
        }

        function rebuildCarousel(regionKey, selectedDistrict) {
            const els = getCarouselElements(regionKey);
            if (!els) return;

            const carousel = els.carousel;
            const carouselInner = els.carouselInner;
            const emptyFilter = els.emptyFilter;
            const prevBtn = els.prevBtn;
            const nextBtn = els.nextBtn;

            const originalSlides = originalSlidesByRegion[regionKey] || [];

            if (window.bootstrap && bootstrap.Carousel) {
                const oldInstance = bootstrap.Carousel.getInstance(carousel);
                if (oldInstance) oldInstance.dispose();
            }

            const matchedSlides = originalSlides.filter(function (slideHtml) {
                if (!selectedDistrict) return true;

                const temp = document.createElement('div');
                temp.innerHTML = slideHtml.trim();
                const slide = temp.firstElementChild;
                if (!slide) return false;

                const districtKey = normalizeDistrictValue(slide.getAttribute('data-district-key'));
                return districtKey === normalizeDistrictValue(selectedDistrict);
            });

            if (matchedSlides.length === 0) {
                carouselInner.innerHTML = '';
                carouselInner.style.display = 'none';

                if (emptyFilter) {
                    emptyFilter.classList.remove('d-none');
                }

                setArrowVisibility(prevBtn, nextBtn, 0);
                return;
            }

            carouselInner.innerHTML = matchedSlides.join('');
            carouselInner.style.display = '';
            bindFallbackImages(carouselInner);

            const rebuiltSlides = carouselInner.querySelectorAll('.carousel-item');
            rebuiltSlides.forEach(function (item) {
                item.classList.remove('active');
            });

            if (rebuiltSlides.length > 0) {
                rebuiltSlides[0].classList.add('active');
            }

            if (emptyFilter) {
                emptyFilter.classList.add('d-none');
            }

            setArrowVisibility(prevBtn, nextBtn, rebuiltSlides.length);

            if (window.bootstrap && bootstrap.Carousel && rebuiltSlides.length > 1) {
                new bootstrap.Carousel(carousel, {
                    interval: false,
                    ride: false,
                    wrap: true,
                    touch: true
                });
            }
        }

        cacheOriginalSlides();
        bindFallbackImages(document);

        selects.forEach(function (select) {
            if (select.dataset.boundGedung === 'true') return;
            select.dataset.boundGedung = 'true';

            select.addEventListener('change', function () {
                const regionKey = this.getAttribute('data-region-key');
                rebuildCarousel(regionKey, this.value);
            });

            rebuildCarousel(select.getAttribute('data-region-key'), select.value);
        });

        tabButtons.forEach(function (button) {
            if (button.dataset.boundGedungTab === 'true') return;
            button.dataset.boundGedungTab = 'true';

            button.addEventListener('shown.bs.tab', function () {
                const target = this.getAttribute('data-bs-target');
                if (!target) return;

                const regionKey = target.replace('#pane-', '');
                const select = document.getElementById('district-select-' + regionKey);

                if (select) {
                    rebuildCarousel(regionKey, select.value);
                }
            });
        });
    }

    window.SigapPageInits.gedung = initGedungPage;
})();
