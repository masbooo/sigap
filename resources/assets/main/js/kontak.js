(function () {
    window.SigapPageInits = window.SigapPageInits || {};
    window.__sigapKontak = window.__sigapKontak || {
        map: null,
        mapWrapper: null,
        surabayaMarker: null,
        markers: {},
        activeRegion: null,
        isBound: false,
        tabBound: false
    };

    function getNavbarOffset() {
        const navbar =
            document.querySelector('.header-fp') ||
            document.querySelector('header') ||
            document.querySelector('.navbar') ||
            document.querySelector('.sticky-top') ||
            document.querySelector('.fixed-top');

        return navbar ? navbar.offsetHeight : 90;
    }

    function getContactFromElement(el) {
        if (!el) return null;

        return {
            id: parseInt(el.dataset.id || '0', 10),
            lat: parseFloat(el.dataset.lat || '0'),
            lng: parseFloat(el.dataset.lng || '0'),
            district: el.dataset.name || '',
            address: el.dataset.address || '',
            phone: el.dataset.phone || ''
        };
    }

    function normalizeRegionName(region) {
        return String(region || '').trim().toLowerCase();
    }

    function getRegionContacts(regionName) {
        const regionMap = window.contactGroupByRegion || {};
        const key = String(regionName || '').trim();

        if (regionMap[key] && Array.isArray(regionMap[key].contacts)) {
            return regionMap[key].contacts;
        }

        const fallback = window.contactAllData || [];
        const normalized = normalizeRegionName(regionName);

        return fallback.filter(function (item) {
            const itemRegion = normalizeRegionName(item.region || item.wilayah || item.area || '');
            return itemRegion === normalized;
        });
    }

    function buildPopupContent(item) {
        const district = item.district || item.name || '-';
        const address = item.address || '-';
        const phone = item.phone || '-';

        return `<b>Kecamatan ${district}</b><br>${address}<br>${phone}`;
    }

    function createMarker(item) {
        if (!item || !item.lat || !item.lng) return null;

        return L.marker([item.lat, item.lng]).bindPopup(buildPopupContent(item));
    }

    function clearRegionMarkers() {
        const state = window.__sigapKontak;

        Object.keys(state.markers).forEach(function (id) {
            const marker = state.markers[id];
            if (marker && state.map && state.map.hasLayer(marker)) {
                state.map.removeLayer(marker);
            }
        });

        state.markers = {};
    }

    function renderRegionMarkers(regionName, shouldFit) {
        const state = window.__sigapKontak;
        if (!state.map) return;

        const contacts = getRegionContacts(regionName);
        const validContacts = contacts.filter(function (item) {
            return item && item.lat && item.lng;
        });

        clearRegionMarkers();

        if (state.surabayaMarker && state.map.hasLayer(state.surabayaMarker)) {
            state.map.removeLayer(state.surabayaMarker);
        }

        if (!validContacts.length) {
            if (state.surabayaMarker) {
                state.surabayaMarker.addTo(state.map);
                state.surabayaMarker.openPopup();
            }

            const fallback = window.contactMapDefault || {
                lat: -7.2756,
                lng: 112.7508,
                zoom: 12
            };

            state.map.setView([fallback.lat, fallback.lng], fallback.zoom);
            state.activeRegion = regionName || null;
            return;
        }

        const bounds = L.latLngBounds([]);

        validContacts.forEach(function (item, index) {
            const marker = createMarker(item);
            if (!marker) return;

            const markerId = item.id || ('region-marker-' + index);
            marker.addTo(state.map);
            state.markers[markerId] = marker;
            bounds.extend([item.lat, item.lng]);
        });

        state.activeRegion = regionName || null;

        if (shouldFit && bounds.isValid()) {
            state.map.fitBounds(bounds, {
                padding: [40, 40],
                maxZoom: 15
            });

            if (validContacts.length === 1) {
                state.map.setZoom(15);
            }
        }

        if (validContacts.length === 1) {
            const firstKey = Object.keys(state.markers)[0];
            if (firstKey && state.markers[firstKey]) {
                setTimeout(function () {
                    state.markers[firstKey].openPopup();
                }, 300);
            }
        }
    }

    function focusToContact(contact) {
        const state = window.__sigapKontak;
        if (!state.map || !contact || !contact.lat || !contact.lng) return;

        if (state.mapWrapper) {
            const offsetTop =
                state.mapWrapper.getBoundingClientRect().top +
                window.pageYOffset -
                getNavbarOffset() -
                16;

            window.scrollTo({
                top: Math.max(offsetTop, 0),
                behavior: 'smooth'
            });
        }

        setTimeout(function () {
            state.map.invalidateSize();

            state.map.flyTo([contact.lat, contact.lng], 16, {
                animate: true,
                duration: 1.2
            });

            Object.keys(state.markers).forEach(function (id) {
                if (state.markers[id]) {
                    state.markers[id].closePopup();
                }
            });

            const exactMarker = Object.values(state.markers).find(function (marker) {
                const latlng = marker.getLatLng();
                return Number(latlng.lat) === Number(contact.lat) && Number(latlng.lng) === Number(contact.lng);
            });

            if (exactMarker) {
                setTimeout(function () {
                    exactMarker.openPopup();
                }, 450);
            }
        }, 300);
    }

    function focusToRegion(regionName) {
        const state = window.__sigapKontak;
        if (!state.map) return;

        if (state.mapWrapper) {
            const offsetTop =
                state.mapWrapper.getBoundingClientRect().top +
                window.pageYOffset -
                getNavbarOffset() -
                16;

            window.scrollTo({
                top: Math.max(offsetTop, 0),
                behavior: 'smooth'
            });
        }

        setTimeout(function () {
            state.map.invalidateSize();
            renderRegionMarkers(regionName, true);
        }, 200);
    }

    function bindGlobalKontakEvents() {
        if (window.__sigapKontak.isBound) return;
        window.__sigapKontak.isBound = true;

        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-contact-detail');
            if (btn) {
                e.preventDefault();
                e.stopPropagation();

                const card = btn.closest('.contact-kecamatan-card');
                focusToContact(getContactFromElement(card));
                return;
            }

            const card = e.target.closest('.contact-kecamatan-card');
            if (card) {
                focusToContact(getContactFromElement(card));
            }
        });
    }

    function bindTabRegionEvents() {
        if (window.__sigapKontak.tabBound) return;
        window.__sigapKontak.tabBound = true;

        document.addEventListener('shown.bs.tab', function (e) {
            const tab = e.target.closest('[data-bs-toggle="tab"][data-region]');
            if (!tab) return;

            const regionName = tab.getAttribute('data-region') || '';
            focusToRegion(regionName);
        });
    }

    function getInitialActiveRegion() {
        const activeTab = document.querySelector('#contactRegionTab [data-bs-toggle="tab"].active[data-region]');
        if (!activeTab) return 'Pusat';
        return activeTab.getAttribute('data-region') || 'Pusat';
    }

    function destroyKontakMap() {
        if (window.__sigapKontak.map) {
            try {
                window.__sigapKontak.map.remove();
            } catch (e) {}
        }

        window.__sigapKontak.map = null;
        window.__sigapKontak.mapWrapper = null;
        window.__sigapKontak.surabayaMarker = null;
        window.__sigapKontak.markers = {};
        window.__sigapKontak.activeRegion = null;
    }

    function initKontakPage() {
        const mapEl = document.getElementById('contact-map');
        if (!mapEl || typeof L === 'undefined') return;

        destroyKontakMap();
        bindGlobalKontakEvents();
        bindTabRegionEvents();

        const defaultMap = window.contactMapDefault || {
            lat: -7.2756,
            lng: 112.7508,
            zoom: 12
        };

        const mapWrapper = document.querySelector('.contact-map-wrapper');

        const map = L.map('contact-map').setView([defaultMap.lat, defaultMap.lng], defaultMap.zoom);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const surabayaMarker = L.marker([defaultMap.lat, defaultMap.lng])
            .bindPopup('<b>Kota Surabaya</b>');

        window.__sigapKontak.map = map;
        window.__sigapKontak.mapWrapper = mapWrapper;
        window.__sigapKontak.surabayaMarker = surabayaMarker;

        const initialRegion = getInitialActiveRegion();

        setTimeout(function () {
            map.invalidateSize();
            renderRegionMarkers(initialRegion, true);
        }, 80);
    }

    window.SigapPageInits.kontak = initKontakPage;
})();
