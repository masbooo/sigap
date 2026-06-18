(function () {
    window.SigapPageInits = window.SigapPageInits || {};
    window.sigapCalendarData = window.sigapCalendarData || {};
    window.calendarInstances = window.calendarInstances || {};
    window.calendarRawEvents = window.calendarRawEvents || {};
    window.__sigapCalendar = window.__sigapCalendar || {
        resizeBound: false,
        modalBoundIds: {},
        refreshTimers: {},
        refreshInFlight: {},
        refreshBindingReady: false
    };

    function normalizeDate(date) {
        const d = new Date(date);
        return new Date(d.getFullYear(), d.getMonth(), d.getDate());
    }

    function formatDateYMDLocal(date) {
        const d = normalizeDate(date);
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    }

    function getTodayLocal() {
        return normalizeDate(new Date());
    }

    function formatTanggalIndonesia(dateStr) {
        if (!dateStr) return '-';

        const d = new Date(`${String(dateStr).slice(0, 10)}T00:00:00`);

        return d.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'long',
            year: 'numeric'
        });
    }

    function formatTanggalSewa(startStr, endStr) {
        if (!startStr) return '-';

        const start = String(startStr).slice(0, 10);
        const end = String(endStr || startStr).slice(0, 10);

        if (start === end) {
            return formatTanggalIndonesia(start);
        }

        return `${formatTanggalIndonesia(start)} s/d ${formatTanggalIndonesia(end)}`;
    }

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function isPastDate(date) {
        return normalizeDate(date) < getTodayLocal();
    }

    function getDayHeaderLabel(date) {
        const full = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const medium = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        const small = ['M', 'S', 'S', 'R', 'K', 'J', 'S'];

        const day = date.getDay();
        const width = window.innerWidth;

        if (width >= 1200) return full[day];
        if (width >= 768) return medium[day];
        return small[day];
    }

    function buildDayHeaderContent() {
        return function (arg) {
            return {
                html: `<span style="font-weight:700;">${getDayHeaderLabel(arg.date)}</span>`
            };
        };
    }

    function showAlert(icon, title, text, callback) {
        if (typeof Swal !== 'undefined') {
            let btnClass = 'btn btn-primary px-4';

            if (icon === 'error') btnClass = 'btn btn-danger px-4';
            if (icon === 'warning') btnClass = 'btn btn-warning px-4';
            if (icon === 'success') btnClass = 'btn btn-success px-4';
            if (icon === 'info') btnClass = 'btn btn-info px-4';

            Swal.fire({
                timer: 3000,
                timerProgressBar: true,
                icon,
                title,
                html: text,
                confirmButtonText: 'OK',
                buttonsStyling: false,
                customClass: {
                    confirmButton: btnClass,
                    popup: 'rounded-4',
                    title: 'fw-bold'
                }
            }).then(function () {
                if (typeof callback === 'function') callback();
            });
        } else {
            alert(text || title);
            if (typeof callback === 'function') callback();
        }
    }

    function getNormalizedCalendarStatusKey(status) {
        const normalized = String(status || '').trim().toUpperCase();

        if (normalized === 'PROSES' || normalized === 'RESERVASI BARU') {
            return 'RESERVASI BARU';
        }

        if (normalized === 'BERKAS RESERVASI TIDAK SESUAI') {
            return 'BERKAS RESERVASI TIDAK SESUAI';
        }

        if (normalized === 'KERJASAMA UMKM') {
            return 'KERJASAMA UMKM';
        }

        if (normalized === 'VERIFIKASI' || normalized === 'PROSES VERIFIKASI') {
            return 'PROSES VERIFIKASI';
        }

        if (normalized === 'BERKAS VERIFIKASI TIDAK SESUAI') {
            return 'BERKAS VERIFIKASI TIDAK SESUAI';
        }

        if (normalized === 'KEMBALI' || normalized === 'BERKAS TIDAK LENGKAP' || normalized === 'BERKAS TIDAK SESUAI') {
            return 'BERKAS TIDAK SESUAI';
        }

        if (normalized === 'SETUJU' || normalized === 'DISETUJUI' || normalized === 'MENUNGGU PEMBAYARAN') {
            return 'MENUNGGU PEMBAYARAN';
        }

        if (normalized === 'CEK PEMBAYARAN') {
            return 'CEK PEMBAYARAN';
        }

        if (normalized === 'BERKAS PEMBAYARAN TIDAK SESUAI') {
            return 'BERKAS PEMBAYARAN TIDAK SESUAI';
        }

        if (normalized === 'TOLAK' || normalized === 'DITOLAK' || normalized === 'PERMOHONAN DITOLAK') {
            return 'PERMOHONAN DITOLAK';
        }

        if (normalized === 'BATAL' || normalized === 'DIBATALKAN PEMOHON') {
            return 'DIBATALKAN PEMOHON';
        }

        if (normalized === 'LUNAS' || normalized === 'PEMBAYARAN LUNAS') {
            return 'PEMBAYARAN LUNAS';
        }

        if (normalized === 'SELESAI' || normalized === 'ACARA SELESAI') {
            return 'ACARA SELESAI';
        }

        return normalized || 'RESERVASI BARU';
    }

    function getStatusMeta(status) {
        const s = getNormalizedCalendarStatusKey(status);

        const map = {
            'RESERVASI BARU': {
                className: 'fc-status-proses',
                modalClass: 'status-proses',
                bg: '#f59e0b',
                softBg: '#fff4db',
                textColor: '#d97706',
                accent: '#f59e0b',
                text: 'Reservasi Baru'
            },
            'BERKAS RESERVASI TIDAK SESUAI': {
                className: 'fc-status-batal',
                modalClass: 'status-batal',
                bg: '#1f2937',
                softBg: '#e5e7eb',
                textColor: '#111827',
                accent: '#1f2937',
                text: 'Berkas Reservasi Tidak Sesuai'
            },
            'KERJASAMA UMKM': {
                className: 'fc-status-setuju',
                modalClass: 'status-setuju',
                bg: '#06b6d4',
                softBg: '#cffafe',
                textColor: '#0891b2',
                accent: '#06b6d4',
                text: 'Kerjasama UMKM'
            },
            'PROSES VERIFIKASI': {
                className: 'fc-status-proses',
                modalClass: 'status-proses',
                bg: '#f59e0b',
                softBg: '#fff4db',
                textColor: '#d97706',
                accent: '#f59e0b',
                text: 'Proses Verifikasi'
            },
            'BERKAS VERIFIKASI TIDAK SESUAI': {
                className: 'fc-status-batal',
                modalClass: 'status-batal',
                bg: '#1f2937',
                softBg: '#e5e7eb',
                textColor: '#111827',
                accent: '#1f2937',
                text: 'Berkas Verifikasi Tidak Sesuai'
            },
            'MENUNGGU PEMBAYARAN': {
                className: 'fc-status-setuju',
                modalClass: 'status-setuju',
                bg: '#0ea5e9',
                softBg: '#e0f2fe',
                textColor: '#2563eb',
                accent: '#4f7cff',
                text: 'Menunggu Pembayaran'
            },
            'CEK PEMBAYARAN': {
                className: 'fc-status-proses',
                modalClass: 'status-proses',
                bg: '#f59e0b',
                softBg: '#fff4db',
                textColor: '#d97706',
                accent: '#f59e0b',
                text: 'Cek Pembayaran'
            },
            'BERKAS PEMBAYARAN TIDAK SESUAI': {
                className: 'fc-status-batal',
                modalClass: 'status-batal',
                bg: '#1f2937',
                softBg: '#e5e7eb',
                textColor: '#111827',
                accent: '#1f2937',
                text: 'Berkas Pembayaran Tidak Sesuai'
            },
            'PEMBAYARAN LUNAS': {
                className: 'fc-status-lunas',
                modalClass: 'status-lunas',
                bg: '#10b981',
                softBg: '#dcfce7',
                textColor: '#059669',
                accent: '#10b981',
                text: 'Pembayaran Lunas'
            },
            'DIBATALKAN PEMOHON': {
                className: 'fc-status-batal',
                modalClass: 'status-batal',
                bg: '#ef4444',
                softBg: '#fee2e2',
                textColor: '#dc2626',
                accent: '#ef4444',
                text: 'Dibatalkan Pemohon'
            },
            'PERMOHONAN DITOLAK': {
                className: 'fc-status-batal',
                modalClass: 'status-batal',
                bg: '#b91c1c',
                softBg: '#fee2e2',
                textColor: '#b91c1c',
                accent: '#b91c1c',
                text: 'Permohonan Ditolak'
            },
            'ACARA SELESAI': {
                className: 'fc-status-selesai',
                modalClass: 'status-selesai',
                bg: '#3b82f6',
                softBg: '#dbeafe',
                textColor: '#2563eb',
                accent: '#3b82f6',
                text: 'Acara Selesai'
            },
            'BERKAS TIDAK SESUAI': {
                className: 'fc-status-batal',
                modalClass: 'status-batal',
                bg: '#1f2937',
                softBg: '#e5e7eb',
                textColor: '#111827',
                accent: '#1f2937',
                text: 'Berkas Tidak Sesuai'
            }
        };

        return map[s] || map['RESERVASI BARU'];
    }

    function isVisibleCalendarEventStatus(status) {
        const normalizedStatus = getNormalizedCalendarStatusKey(status);

        return normalizedStatus === 'PEMBAYARAN LUNAS'
            || normalizedStatus === 'ACARA SELESAI';
    }

    function filterVisibleCalendarEvents(events) {
        if (!Array.isArray(events)) {
            return [];
        }

        return events.filter(function (event) {
            const extendedProps = event && typeof event === 'object'
                ? (event.extendedProps || {})
                : {};

            return isVisibleCalendarEventStatus(extendedProps.status || event.status || '');
        });
    }

    function cleanupModalArtifacts() {
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

    function ensureModalOnBody(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return null;

        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }

        modal.style.zIndex = '1060';
        return modal;
    }

    function bindModalLifecycle(modal) {
        if (!modal || !modal.id) return;
        if (window.__sigapCalendar.modalBoundIds[modal.id]) return;

        window.__sigapCalendar.modalBoundIds[modal.id] = true;

        modal.addEventListener('show.bs.modal', cleanupModalArtifacts);
        modal.addEventListener('shown.bs.modal', cleanupModalArtifacts);
        modal.addEventListener('hidden.bs.modal', function () {
            setTimeout(cleanupModalArtifacts, 150);
        });
    }

    function showBootstrapModal(modal, options) {
        if (!modal || typeof bootstrap === 'undefined' || !bootstrap.Modal) return;

        cleanupModalArtifacts();

        const instance = bootstrap.Modal.getOrCreateInstance(modal, options || {});
        instance.show();
    }

    function openLandingModal(dateStr) {
        const modal = ensureModalOnBody('eventModalLanding');
        if (!modal) return;

        bindModalLifecycle(modal);

        const titleField = modal.querySelector('#event-title');
        const startField = modal.querySelector('#event-start-date');
        const endField = modal.querySelector('#event-end-date');

        if (titleField) titleField.value = '';
        if (startField) startField.value = dateStr;
        if (endField) endField.value = dateStr;

        showBootstrapModal(modal, {
            backdrop: 'static',
            keyboard: false
        });
    }

    function openEventDetailModal(eventObj) {
        const modal = ensureModalOnBody('eventDetailModal');
        if (!modal || !eventObj) return;

        bindModalLifecycle(modal);

        const props = eventObj.extendedProps || {};
        const statusMeta = getStatusMeta(props.status);

        const header = document.getElementById('eventDetailHeader');
        const badge = document.getElementById('eventDetailStatusBadge');
        const title = document.getElementById('eventDetailModalLabel');
        const building = document.getElementById('detailBuildingName');
        const rental = document.getElementById('detailRentalDate');
        const session = document.getElementById('detailSessionName');
        const acara = document.getElementById('detailAcaraName');

        if (header) header.style.backgroundColor = statusMeta.bg;
        if (title) title.innerHTML = '<b>DETAIL RESERVASI</b>';
        if (badge) {
            badge.className = `status-pill-modal ${statusMeta.modalClass}`;
            badge.textContent = statusMeta.text;
        }

        if (building) building.textContent = props.building_name || '-';
        if (rental) rental.textContent = formatTanggalSewa(props.start_date, props.end_date);
        if (session) session.textContent = props.session_name || '-';
        if (acara) acara.textContent = props.acara_name || '-';

        showBootstrapModal(modal);
    }

    function getEmbeddedCalendarConfig(type) {
        let configElement = null;

        if (type === 'user') {
            configElement = document.getElementById('user-reservation-config');
        }

        if (!configElement) {
            return null;
        }

        try {
            return JSON.parse(configElement.textContent || '{}');
        } catch (error) {
            console.warn('Embedded calendar config parse failed', error);
            return null;
        }
    }

    function getCalendarConfig(type) {
        const embeddedConfig = getEmbeddedCalendarConfig(type) || {};
        const runtimeConfig = window.sigapCalendarData[type] || {};
        const config = Object.assign({}, runtimeConfig, embeddedConfig);

        if (type === 'landing') {
            return {
                filterData: Array.isArray(config.filterData) ? config.filterData : (window.jadwalFilterData || []),
                events: Array.isArray(config.events) ? config.events : (window.jadwalEvents || []),
                eventsUrl: String(config.eventsUrl || '').trim(),
                refreshIntervalMs: Number(config.refreshIntervalMs || 0),
                reservation: config.reservation || null,
                minBookingDate: config.minBookingDate || null
            };
        }

        if (type === 'user') {
            return {
                filterData: Array.isArray(config.filterData) ? config.filterData : (window.jadwalFilterDataUser || []),
                events: Array.isArray(config.events) ? config.events : (window.jadwalUserEvents || []),
                eventsUrl: String(config.eventsUrl || '').trim(),
                refreshIntervalMs: Number(config.refreshIntervalMs || 0),
                reservation: config.reservation || null,
                minBookingDate: config.minBookingDate || null
            };
        }

        return {
            filterData: Array.isArray(config.filterData) ? config.filterData : [],
            events: Array.isArray(config.events) ? config.events : [],
            eventsUrl: String(config.eventsUrl || '').trim(),
            refreshIntervalMs: Number(config.refreshIntervalMs || 0),
            reservation: config.reservation || null,
            minBookingDate: config.minBookingDate || null
        };
    }

    function applyCalendarEvents(type, events) {
        const normalizedEvents = filterVisibleCalendarEvents(events);

        window.calendarRawEvents[type] = normalizedEvents;
        window.sigapCalendarData[type] = window.sigapCalendarData[type] || {};
        window.sigapCalendarData[type].events = normalizedEvents;

        if (type === 'landing') {
            window.jadwalEvents = normalizedEvents;
        } else if (type === 'user') {
            window.jadwalUserEvents = normalizedEvents;
        }

        renderCalendarEvents(type);
    }

    function fetchCalendarEvents(type) {
        const config = getCalendarConfig(type);
        const eventsUrl = String(config.eventsUrl || '').trim();

        if (!eventsUrl) {
            return Promise.resolve(window.calendarRawEvents[type] || []);
        }

        if (window.__sigapCalendar.refreshInFlight[type]) {
            return window.__sigapCalendar.refreshInFlight[type];
        }

        const request = fetch(eventsUrl, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            cache: 'no-store'
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error(`Calendar fetch failed with status ${response.status}`);
                }

                return response.json();
            })
            .then(function (data) {
                const events = Array.isArray(data) ? data : [];
                applyCalendarEvents(type, events);
                return events;
            })
            .catch(function (error) {
                console.warn(`Failed to refresh calendar events for ${type}`, error);
                return window.calendarRawEvents[type] || [];
            })
            .finally(function () {
                delete window.__sigapCalendar.refreshInFlight[type];
            });

        window.__sigapCalendar.refreshInFlight[type] = request;
        return request;
    }

    function clearCalendarAutoRefresh(type) {
        if (window.__sigapCalendar.refreshTimers[type]) {
            window.clearInterval(window.__sigapCalendar.refreshTimers[type]);
            delete window.__sigapCalendar.refreshTimers[type];
        }
    }

    function scheduleCalendarAutoRefresh(type) {
        const config = getCalendarConfig(type);
        const refreshIntervalMs = Number(config.refreshIntervalMs || 0);
        const eventsUrl = String(config.eventsUrl || '').trim();

        clearCalendarAutoRefresh(type);

        if (!eventsUrl || refreshIntervalMs <= 0) {
            return;
        }

        window.__sigapCalendar.refreshTimers[type] = window.setInterval(function () {
            if (document.hidden) {
                return;
            }

            fetchCalendarEvents(type);
        }, refreshIntervalMs);
    }

    function getCalendarScopeRoot(type) {
        return document.querySelector(`[data-calendar-scope="${type}"]`);
    }

    function getFilterElements(type) {
        const root = getCalendarScopeRoot(type);

        if (root) {
            return {
                region: root.querySelector('[data-calendar-role="region"]'),
                district: root.querySelector('[data-calendar-role="district"]'),
                building: root.querySelector('[data-calendar-role="building"]')
            };
        }

        if (type === 'landing') {
            return {
                region: document.getElementById('filterWilayah'),
                district: document.getElementById('filterKecamatan'),
                building: document.getElementById('filterGedung')
            };
        }

        if (type === 'user') {
            return {
                region: document.getElementById('filterWilayahUser'),
                district: document.getElementById('filterKecamatanUser'),
                building: document.getElementById('filterGedungUser')
            };
        }

        return {
            region: null,
            district: null,
            building: null
        };
    }

    function resetSelect(selectEl, placeholder) {
        if (!selectEl) return;
        selectEl.innerHTML = `<option value="">${placeholder}</option>`;
        selectEl.disabled = true;
    }

    function bindScopedChange(element, type, handler) {
        if (!element) return;

        const attrName = `data-calendar-bound-${type}`;
        if (element.getAttribute(attrName) === 'true') return;

        element.setAttribute(attrName, 'true');
        element.addEventListener('change', handler);
    }

    function getSelectedFilters(type) {
        const elements = getFilterElements(type);

        return {
            region: elements.region ? elements.region.value : '',
            districtId: elements.district ? elements.district.value : '',
            buildingId: elements.building ? elements.building.value : ''
        };
    }

    function filterEventsByScope(type, filters) {
        const config = getCalendarConfig(type);
        const filterData = config.filterData || [];
        const rawEvents = window.calendarRawEvents[type] || [];

        let districtName = '';

        if (filters.region && filters.districtId) {
            const region = filterData.find(function (item) {
                return String(item.region) === String(filters.region);
            });

            if (region) {
                const district = (region.districts || []).find(function (item) {
                    return String(item.id) === String(filters.districtId);
                });

                if (district) districtName = district.name;
            }
        }

        return rawEvents.filter(function (event) {
            if (filters.region && String(event.region) !== String(filters.region)) return false;
            if (districtName && String(event.district) !== String(districtName)) return false;
            if (filters.buildingId && String(event.building_id) !== String(filters.buildingId)) return false;
            return true;
        });
    }

    function renderCalendarEvents(type) {
        const calendar = window.calendarInstances[type];
        if (!calendar) return;

        const elements = getFilterElements(type);
        const hasFilters = elements.region || elements.district || elements.building;
        const events = hasFilters
            ? filterEventsByScope(type, getSelectedFilters(type))
            : (window.calendarRawEvents[type] || []);

        calendar.removeAllEvents();
        events.forEach(function (event) {
            calendar.addEvent(event);
        });

        requestAnimationFrame(function () {
            calendar.updateSize();
            applyDayCellStyles(calendar);
        });
    }

    function loadDistrictsByRegion(type, regionName) {
        const config = getCalendarConfig(type);
        const elements = getFilterElements(type);

        resetSelect(elements.district, 'Pilih Kecamatan...');
        resetSelect(elements.building, 'Pilih Gedung...');

        if (!regionName) {
            syncReservationState(type);
            if (type === 'user') syncUserReservationValidation(false);
            renderCalendarEvents(type);
            return;
        }

        const region = (config.filterData || []).find(function (item) {
            return String(item.region) === String(regionName);
        });

        if (!region) {
            syncReservationState(type);
            if (type === 'user') syncUserReservationValidation(false);
            renderCalendarEvents(type);
            return;
        }

        (region.districts || []).forEach(function (district) {
            const option = document.createElement('option');
            option.value = district.id;
            option.textContent = `${district.name} (${district.building_count})`;
            elements.district.appendChild(option);
        });

        elements.district.disabled = false;
        syncReservationState(type);
        if (type === 'user') syncUserReservationValidation(false);
        renderCalendarEvents(type);
    }

    function loadBuildingsByDistrict(type, regionName, districtId) {
        const config = getCalendarConfig(type);
        const elements = getFilterElements(type);

        resetSelect(elements.building, 'Pilih Gedung...');

        if (!regionName || !districtId) {
            syncReservationState(type);
            if (type === 'user') syncUserReservationValidation(false);
            renderCalendarEvents(type);
            return;
        }

        const region = (config.filterData || []).find(function (item) {
            return String(item.region) === String(regionName);
        });

        if (!region) {
            syncReservationState(type);
            if (type === 'user') syncUserReservationValidation(false);
            renderCalendarEvents(type);
            return;
        }

        const district = (region.districts || []).find(function (item) {
            return String(item.id) === String(districtId);
        });

        if (!district) {
            syncReservationState(type);
            if (type === 'user') syncUserReservationValidation(false);
            renderCalendarEvents(type);
            return;
        }

        (district.buildings || []).forEach(function (building) {
            const option = document.createElement('option');
            option.value = building.id;
            option.textContent = building.name;
            elements.building.appendChild(option);
        });

        elements.building.disabled = false;
        syncReservationState(type);
        if (type === 'user') syncUserReservationValidation(false);
        renderCalendarEvents(type);
    }

    function prefillFiltersFromBuilding(type, buildingId) {
        const config = getCalendarConfig(type);
        const elements = getFilterElements(type);

        if (!buildingId || !elements.region || !elements.district || !elements.building) {
            return false;
        }

        for (const region of (config.filterData || [])) {
            for (const district of (region.districts || [])) {
                const building = (district.buildings || []).find(function (item) {
                    return String(item.id) === String(buildingId);
                });

                if (building) {
                    elements.region.value = region.region;
                    loadDistrictsByRegion(type, region.region);
                    elements.district.value = String(district.id);
                    loadBuildingsByDistrict(type, region.region, String(district.id));
                    elements.building.value = String(building.id);
                    syncReservationState(type);
                    renderCalendarEvents(type);
                    return true;
                }
            }
        }

        return false;
    }

    function getMinBookingDate(type) {
        const config = getCalendarConfig(type);
        const value = config.minBookingDate;

        if (!value) return null;

        return normalizeDate(value);
    }

    function getUserReservationElements() {
        const config = getCalendarConfig('user');
        const reservation = config.reservation || {};

        return {
            form: reservation.formId ? document.getElementById(reservation.formId) : null,
            buildingInput: reservation.buildingInputId ? document.getElementById(reservation.buildingInputId) : null,
            buildingDisplay: reservation.buildingDisplayId ? document.getElementById(reservation.buildingDisplayId) : null,
            startInput: reservation.startInputId ? document.getElementById(reservation.startInputId) : null,
            endInput: reservation.endInputId ? document.getElementById(reservation.endInputId) : null,
            dateDisplay: reservation.dateDisplayId ? document.getElementById(reservation.dateDisplayId) : null,
            selectedDateText: reservation.selectedDateTextId ? document.getElementById(reservation.selectedDateTextId) : null,
            selectedBuildingText: reservation.selectedBuildingTextId ? document.getElementById(reservation.selectedBuildingTextId) : null,
            selectionStatus: reservation.selectionStatusId ? document.getElementById(reservation.selectionStatusId) : null,
            selectionHint: reservation.selectionHintId ? document.getElementById(reservation.selectionHintId) : null
        };
    }

    function populateRegionOptions(type) {
        const config = getCalendarConfig(type);
        const elements = getFilterElements(type);

        if (!elements.region || !(config.filterData || []).length) return;

        const hasRegionOptions = Array.from(elements.region.options || []).some(function (option) {
            return String(option.value || '').trim() !== '';
        });

        if (hasRegionOptions) return;

        const placeholder = elements.region.getAttribute('data-placeholder') || 'Pilih Wilayah...';
        elements.region.innerHTML = `<option value="">${placeholder}</option>`;

        (config.filterData || []).forEach(function (region) {
            const option = document.createElement('option');
            option.value = region.region;
            option.textContent = `Surabaya ${region.region} (${region.district_count})`;
            elements.region.appendChild(option);
        });

        elements.region.disabled = false;
    }

    function applyValidationState(field, shouldMark, isValid) {
        if (!field) return isValid;

        field.classList.remove('is-valid', 'is-invalid');

        if (!shouldMark) return isValid;

        field.classList.add(isValid ? 'is-valid' : 'is-invalid');
        return isValid;
    }

    function getUserReservationFields() {
        const reservationEls = getUserReservationElements();
        const filterElements = getFilterElements('user');

        return {
            form: reservationEls.form,
            region: filterElements.region,
            district: filterElements.district,
            building: filterElements.building,
            buildingInput: reservationEls.buildingInput,
            buildingDisplay: reservationEls.buildingDisplay,
            startInput: reservationEls.startInput,
            endInput: reservationEls.endInput,
            dateDisplay: reservationEls.dateDisplay,
            event: document.getElementById('reservation-event-id'),
            session: document.getElementById('reservation-session-id'),
            estPerson: document.getElementById('reservation-est-person'),
            idFile: document.getElementById('reservation-id-file')
        };
    }

    function isAllowedIdentityFile(fileInput) {
        if (!fileInput) return false;

        const fileName = fileInput.files && fileInput.files[0]
            ? fileInput.files[0].name
            : fileInput.value;

        if (!fileName) return false;

        return /\.(jpg|jpeg|png|pdf)$/i.test(String(fileName));
    }

    function syncUserReservationValidation(markInvalidEmpty) {
        const fields = getUserReservationFields();
        if (!fields.form) return { valid: true, issues: [] };

        const minBookingDate = getMinBookingDate('user');
        const regionValue = fields.region ? String(fields.region.value || '').trim() : '';
        const districtValue = fields.district && !fields.district.disabled
            ? String(fields.district.value || '').trim()
            : '';
        const buildingValue = fields.building && !fields.building.disabled
            ? String(fields.building.value || '').trim()
            : '';
        const dateValue = fields.startInput ? String(fields.startInput.value || '').trim() : '';
        const eventValue = fields.event ? String(fields.event.value || '').trim() : '';
        const sessionValue = fields.session ? String(fields.session.value || '').trim() : '';
        const estPersonValue = fields.estPerson ? String(fields.estPerson.value || '').trim() : '';
        const hasIdentityFile = !!(
            fields.idFile &&
            fields.idFile.files &&
            fields.idFile.files.length > 0
        );

        const regionValid = regionValue !== '';
        const districtValid = districtValue !== '';
        const buildingValid = buildingValue !== '';
        const dateValid = dateValue !== '' && (!minBookingDate || normalizeDate(dateValue) >= minBookingDate);
        const eventValid = eventValue !== '';
        const sessionValid = sessionValue !== '';
        const estPersonValid = estPersonValue !== '' && Number(estPersonValue) > 0;
        const idFileValid = !fields.idFile || (hasIdentityFile && isAllowedIdentityFile(fields.idFile));

        applyValidationState(fields.region, markInvalidEmpty || regionValue !== '', regionValid);
        applyValidationState(
            fields.district,
            markInvalidEmpty || regionValue !== '' || districtValue !== '',
            districtValid
        );
        applyValidationState(
            fields.building,
            markInvalidEmpty || districtValue !== '' || buildingValue !== '',
            buildingValid
        );
        applyValidationState(fields.dateDisplay, markInvalidEmpty || dateValue !== '', dateValid);
        applyValidationState(fields.buildingDisplay, markInvalidEmpty || buildingValue !== '', buildingValid);
        applyValidationState(fields.event, markInvalidEmpty || eventValue !== '', eventValid);
        applyValidationState(fields.session, markInvalidEmpty || sessionValue !== '', sessionValid);
        applyValidationState(fields.estPerson, markInvalidEmpty || estPersonValue !== '', estPersonValid);
        applyValidationState(fields.idFile, markInvalidEmpty || hasIdentityFile, idFileValid);

        const issues = [];

        if (!regionValid || !districtValid || !buildingValid) {
            issues.push('Lokasi reservasi belum lengkap');
        }

        if (!dateValid) {
            issues.push(
                dateValue === ''
                    ? 'Tanggal reservasi belum dipilih dari kalender'
                    : `Tanggal reservasi harus sama atau setelah ${formatTanggalIndonesia(formatDateYMDLocal(minBookingDate))}`
            );
        }

        if (!eventValid) {
            issues.push('Jenis acara belum dipilih');
        }

        if (!sessionValid) {
            issues.push('Sesi reservasi belum dipilih');
        }

        if (!estPersonValid) {
            issues.push('Estimasi peserta wajib diisi dengan angka lebih dari 0');
        }

        if (!idFileValid) {
            issues.push(
                hasIdentityFile
                    ? 'Format file identitas harus JPG, JPEG, PNG, atau PDF'
                    : 'File identitas belum diunggah'
            );
        }

        return {
            valid: issues.length === 0,
            issues: issues
        };
    }

    function initUserReservationForm() {
        if (window.__sigapReservationPageManagedByUserJs) {
            return;
        }

        const fields = getUserReservationFields();
        if (!fields.form) return;

        if (fields.form.dataset.userReservationBound === 'true') {
            syncUserReservationValidation(false);
            return;
        }

        fields.form.dataset.userReservationBound = 'true';

        if (
            fields.region &&
            fields.region.getAttribute('data-calendar-bound-user') !== 'true' &&
            fields.region.dataset.userLocationFallbackBound !== 'true'
        ) {
            fields.region.dataset.userLocationFallbackBound = 'true';
            fields.region.addEventListener('change', function () {
                loadDistrictsByRegion('user', this.value);
            });
        }

        if (
            fields.district &&
            fields.district.getAttribute('data-calendar-bound-user') !== 'true' &&
            fields.district.dataset.userLocationFallbackBound !== 'true'
        ) {
            fields.district.dataset.userLocationFallbackBound = 'true';
            fields.district.addEventListener('change', function () {
                loadBuildingsByDistrict('user', fields.region ? fields.region.value : '', this.value);
            });
        }

        if (
            fields.building &&
            fields.building.getAttribute('data-calendar-bound-user') !== 'true' &&
            fields.building.dataset.userLocationFallbackBound !== 'true'
        ) {
            fields.building.dataset.userLocationFallbackBound = 'true';
            fields.building.addEventListener('change', function () {
                syncReservationState('user');
                renderCalendarEvents('user');
            });
        }

        [
            fields.region,
            fields.district,
            fields.building,
            fields.event,
            fields.session,
            fields.idFile
        ].forEach(function (field) {
            if (!field) return;

            field.addEventListener('change', function () {
                syncUserReservationValidation(false);
            });
        });

        if (fields.estPerson) {
            fields.estPerson.addEventListener('input', function () {
                syncUserReservationValidation(false);
            });
        }

        fields.form.addEventListener('submit', function (event) {
            if (fields.form.dataset.sigapReservationBound === '1') {
                return;
            }

            const validation = syncUserReservationValidation(true);

            if (!validation.valid) {
                event.preventDefault();

                showAlert(
                    'warning',
                    '<b>PERIKSA KEMBALI</b>',
                    'Semua field wajib terisi. Cek kembali'
                );
                return;
            }

            event.preventDefault();

            showAlert(
                'success',
                '<b>FORM RESERVASI SUDAH SESUAI</b>',
                'Semua field wajib sudah lengkap. Pengajuan reservasi akan dikirim sekarang.',
                function () {
                    HTMLFormElement.prototype.submit.call(fields.form);
                }
            );
        });

        syncUserReservationValidation(false);
    }

    function updateUserSelectionState() {
        const reservationEls = getUserReservationElements();
        if (!reservationEls.form) return;

        const selectedDate = reservationEls.startInput ? reservationEls.startInput.value : '';
        const selectedBuilding = reservationEls.buildingInput ? reservationEls.buildingInput.value : '';

        if (reservationEls.selectedDateText) {
            reservationEls.selectedDateText.textContent = selectedDate
                ? formatTanggalIndonesia(selectedDate)
                : 'Belum dipilih';
        }

        if (reservationEls.dateDisplay) {
            reservationEls.dateDisplay.value = selectedDate
                ? formatTanggalIndonesia(selectedDate)
                : '';
        }

        if (reservationEls.selectionStatus) {
            if (selectedDate && selectedBuilding) {
                reservationEls.selectionStatus.textContent = 'Siap diajukan';
            } else if (!selectedBuilding) {
                reservationEls.selectionStatus.textContent = 'Menunggu pilihan gedung';
            } else {
                reservationEls.selectionStatus.textContent = 'Menunggu pilihan tanggal';
            }
        }

        if (reservationEls.selectionHint) {
            if (selectedDate && selectedBuilding) {
                reservationEls.selectionHint.textContent = 'Lengkapi jenis acara, sesi, estimasi peserta, lalu kirim pengajuan reservasi.';
            } else if (!selectedBuilding) {
                reservationEls.selectionHint.textContent = 'Pilih gedung dari filter lokasi agar form reservasi dapat diarahkan ke gedung yang benar.';
            } else {
                reservationEls.selectionHint.textContent = 'Klik tanggal kosong pada kalender untuk mengisi tanggal reservasi.';
            }
        }
    }

    function syncUserBuildingFromFilter() {
        const filterElements = getFilterElements('user');
        const reservationEls = getUserReservationElements();
        if (!reservationEls.form || !filterElements.building) return;

        const selectedOption = filterElements.building.options[filterElements.building.selectedIndex];
        const selectedBuildingId = filterElements.building.value || '';
        const selectedLabel = selectedOption && selectedOption.value ? selectedOption.textContent : '';

        if (reservationEls.buildingInput) reservationEls.buildingInput.value = selectedBuildingId;
        if (reservationEls.buildingDisplay) reservationEls.buildingDisplay.value = selectedLabel;
        if (reservationEls.selectedBuildingText) {
            reservationEls.selectedBuildingText.textContent = selectedLabel || 'Pilih gedung terlebih dahulu';
        }

        updateUserSelectionState();
        syncUserReservationValidation(false);
    }

    function setUserReservationDate(dateStr) {
        const reservationEls = getUserReservationElements();
        if (!reservationEls.form) return;

        if (reservationEls.startInput) {
            reservationEls.startInput.value = dateStr;
            reservationEls.startInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
        if (reservationEls.endInput) reservationEls.endInput.value = dateStr;
        if (reservationEls.dateDisplay) reservationEls.dateDisplay.value = formatTanggalIndonesia(dateStr);

        updateUserSelectionState();
        syncUserReservationValidation(false);

        reservationEls.form.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }

    function syncReservationState(type) {
        if (type !== 'user') return;
        syncUserBuildingFromFilter();
    }

    function initScopedFilters(type) {
        const config = getCalendarConfig(type);
        const elements = getFilterElements(type);
        populateRegionOptions(type);

        if (type === 'user' && window.__sigapReservationPageManagedByUserJs) {
            renderCalendarEvents(type);
            return;
        }

        if (!elements.region || !elements.district || !elements.building || !(config.filterData || []).length) {
            if (type === 'user') {
                updateUserSelectionState();
                initUserReservationForm();
                syncUserReservationValidation(false);
            }
            renderCalendarEvents(type);
            return;
        }

        resetSelect(elements.district, 'Pilih Kecamatan...');
        resetSelect(elements.building, 'Pilih Gedung...');

        bindScopedChange(elements.region, type, function () {
            loadDistrictsByRegion(type, this.value);
        });

        bindScopedChange(elements.district, type, function () {
            loadBuildingsByDistrict(type, elements.region.value, this.value);
        });

        bindScopedChange(elements.building, type, function () {
            syncReservationState(type);
            renderCalendarEvents(type);
        });

        if (type === 'user') {
            const reservationEls = getUserReservationElements();
            const selectedBuildingId = reservationEls.buildingInput ? reservationEls.buildingInput.value : '';

            if (!prefillFiltersFromBuilding(type, selectedBuildingId)) {
                syncUserBuildingFromFilter();
                updateUserSelectionState();
                renderCalendarEvents(type);
            }

            initUserReservationForm();
            syncUserReservationValidation(false);
            return;
        }

        renderCalendarEvents(type);
    }

    function applyDayCellStyles(calendar) {
        if (!calendar || !calendar.el) return;

        const today = getTodayLocal();
        const cells = calendar.el.querySelectorAll('.fc-daygrid-day');
        cells.forEach(function (cell) {
            const dateStr = cell.getAttribute('data-date');
            if (!dateStr) return;

            const dateObj = normalizeDate(dateStr);
            const dayFrame = cell.querySelector('.fc-daygrid-day-frame');

            cell.style.backgroundColor = '';
            cell.style.backgroundImage = 'none';
            cell.style.borderRadius = '0';
            cell.style.cursor = 'pointer';
            cell.style.opacity = '1';

            if (dayFrame) {
                dayFrame.style.setProperty('background', 'transparent', 'important');
                dayFrame.style.setProperty('background-color', 'transparent', 'important');
                dayFrame.style.setProperty('border-radius', '0', 'important');
                dayFrame.style.setProperty('box-shadow', 'none', 'important');
            }

            if (dateObj.getTime() === today.getTime()) {
                cell.style.backgroundColor = '#e5e7eb';
            } else if (isPastDate(dateObj)) {
                cell.style.backgroundColor = '#ffcccc';
                cell.style.cursor = 'not-allowed';
            } else {
                cell.style.backgroundColor = '#edfaef';
            }
        });
    }

    function refreshResponsiveHeaders() {
        Object.values(window.calendarInstances).forEach(function (calendar) {
            if (!calendar) return;
            calendar.setOption('dayHeaderContent', buildDayHeaderContent());
            calendar.updateSize();
        });
    }

    function getCalendarType(el) {
        if (!el) return '';
        if (el.dataset.calendarInstance) return el.dataset.calendarInstance;

        if (el.id === 'calendar-landing') return 'landing';
        if (el.id === 'calendar-user') return 'user';
        if (el.id === 'calendar-admin') return 'admin';

        return '';
    }

    function handleDateClick(type, info) {
        const clickedDate = normalizeDate(info.date);
        const clickedDateStr = info.dateStr;

        if (isPastDate(clickedDate)) {
            showAlert('error', '<b>TANGGAL LAMPAU</b>', 'Tanggal yang sudah lewat tidak dapat dipilih.');
            return;
        }

        if (type === 'landing') {
            showAlert(
                'info',
                '<b>INFORMASI RESERVASI</b>',
                'Mohon dapat melakukan reservasi minimal H-14 (empat belas hari) sebelum tanggal pelaksanaan<br><b><small class="text-danger">(S&K berlaku)</small></b>',
                function () {
                    openLandingModal(clickedDateStr);
                }
            );
            return;
        }

        if (type !== 'user') {
            return;
        }

        const minBookingDate = getMinBookingDate('user');
        const filterElements = getFilterElements('user');
        const hasBuilding = filterElements.building && filterElements.building.value;

        if (!hasBuilding) {
            showAlert('warning', '<b>PILIH GEDUNG DULU</b>', 'Silakan pilih wilayah, kecamatan, dan gedung sebelum menentukan tanggal reservasi');
            return;
        }

        if (minBookingDate && clickedDate < minBookingDate) {
            showAlert(
                'warning',
                '<b>MINIMAL H-14</b>',
                `Tanggal reservasi user harus sama atau setelah ${formatTanggalIndonesia(formatDateYMDLocal(minBookingDate))}.`
            );
            return;
        }

        setUserReservationDate(clickedDateStr);
    }

    function initCalendar(el, type) {
        if (!el || !type || typeof FullCalendar === 'undefined') return;

        const calendar = new FullCalendar.Calendar(el, {
            locale: 'id',
            initialView: 'dayGridMonth',
            displayEventTime: false,
            height: 'auto',
            contentHeight: 'auto',
            fixedWeekCount: false,
            expandRows: false,
            dayMaxEventRows: false,
            dayMaxEvents: false,
            eventDisplay: 'block',
            editable: type === 'admin',

            headerToolbar: {
                left: '',
                center: 'title',
                right: 'prev,next'
            },

            dayHeaderContent: buildDayHeaderContent(),
            events: [],

            eventClassNames: function (arg) {
                const props = arg.event.extendedProps || {};
                const statusMeta = getStatusMeta(props.status);

                return ['fc-sigap-event', statusMeta.className];
            },

            dateClick: function (info) {
                handleDateClick(type, info);
            },

            eventClick: function (info) {
                info.jsEvent.preventDefault();
                info.jsEvent.stopPropagation();

                if (type === 'landing' || type === 'user') {
                    openEventDetailModal(info.event);
                }
            },

            eventDidMount: function (info) {
                const props = info.event.extendedProps || {};
                const statusMeta = getStatusMeta(props.status);

                info.el.style.setProperty('--fc-sigap-bg', statusMeta.softBg);
                info.el.style.setProperty('--fc-sigap-text', '#111827');
                info.el.style.setProperty('--fc-sigap-accent', statusMeta.accent);
                info.el.style.background = 'transparent';
                info.el.style.border = '0';
                info.el.style.boxShadow = 'none';
            },

            dayCellDidMount: function () {
                requestAnimationFrame(function () {
                    applyDayCellStyles(calendar);
                });
            },

            datesSet: function () {
                requestAnimationFrame(function () {
                    calendar.updateSize();
                    applyDayCellStyles(calendar);
                });
            }
        });

        calendar.render();
        calendar.el.classList.add('fc-ready');

        window.calendarInstances[type] = calendar;
        window.calendarRawEvents[type] = getCalendarConfig(type).events || [];

        if (!(getFilterElements(type).region || getFilterElements(type).district || getFilterElements(type).building)) {
            renderCalendarEvents(type);
        }
    }

    function destroyCalendars() {
        Object.keys(window.__sigapCalendar.refreshTimers || {}).forEach(function (type) {
            clearCalendarAutoRefresh(type);
        });

        Object.values(window.calendarInstances).forEach(function (calendar) {
            if (!calendar) return;

            try {
                calendar.destroy();
            } catch (e) {}
        });

        window.calendarInstances = {};
        window.calendarRawEvents = {};
    }

    function prepareCalendarModals() {
        ['eventModalLanding', 'eventDetailModal'].forEach(function (id) {
            const modal = ensureModalOnBody(id);
            if (modal) bindModalLifecycle(modal);
        });

        cleanupModalArtifacts();
    }

    function bindGlobalCalendarEvents() {
        if (window.__sigapCalendar.resizeBound) return;

        window.__sigapCalendar.resizeBound = true;
        window.addEventListener('resize', refreshResponsiveHeaders);

        if (window.__sigapCalendar.refreshBindingReady) {
            return;
        }

        window.__sigapCalendar.refreshBindingReady = true;

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                return;
            }

            Object.keys(window.calendarInstances).forEach(function (type) {
                if (getCalendarConfig(type).eventsUrl) {
                    fetchCalendarEvents(type);
                }
            });
        });

        window.addEventListener('focus', function () {
            Object.keys(window.calendarInstances).forEach(function (type) {
                if (getCalendarConfig(type).eventsUrl) {
                    fetchCalendarEvents(type);
                }
            });
        });
    }

    function collectCalendarElements() {
        const elements = Array.from(document.querySelectorAll('[data-calendar-instance]'));

        ['calendar-landing', 'calendar-user', 'calendar-admin'].forEach(function (id) {
            const fallback = document.getElementById(id);
            if (fallback && elements.indexOf(fallback) === -1) {
                elements.push(fallback);
            }
        });

        return elements;
    }

    function initCalendarPage() {
        const calendarElements = collectCalendarElements().filter(Boolean);
        if (!calendarElements.length) return;

        destroyCalendars();
        bindGlobalCalendarEvents();
        prepareCalendarModals();

        calendarElements.forEach(function (element) {
            initCalendar(element, getCalendarType(element));
        });

        ['landing', 'user'].forEach(function (type) {
            if (window.calendarInstances[type]) {
                initScopedFilters(type);
            }
        });

        Object.keys(window.calendarInstances).forEach(function (type) {
            scheduleCalendarAutoRefresh(type);

            if (getCalendarConfig(type).eventsUrl) {
                fetchCalendarEvents(type);
            }
        });
    }

    window.SigapPageInits.calendar = initCalendarPage;
})();
