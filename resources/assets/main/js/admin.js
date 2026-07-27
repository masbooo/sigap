(function () {
    var ADMIN_RESERVATION_THEME_CLASSES = [
        'admin-reservation-theme-warning',
        'admin-reservation-theme-info',
        'admin-reservation-theme-danger',
        'admin-reservation-theme-success',
        'admin-reservation-theme-primary',
        'admin-reservation-theme-secondary'
    ];
    var activeAdminActionMenu = null;

    function getSidebarScrollElement() {
        return document.querySelector('.left-sidebar .scroll-sidebar');
    }

    function getSimpleBarInstance(element) {
        if (!element || typeof window.SimpleBar === 'undefined' || !window.SimpleBar.instances) {
            return null;
        }

        try {
            return window.SimpleBar.instances.get(element) || null;
        } catch (error) {
            console.warn('SimpleBar instance lookup failed', error);
            return null;
        }
    }

    function ensureSimpleBar(element) {
        if (!element || typeof window.SimpleBar === 'undefined') {
            return null;
        }

        var instance = getSimpleBarInstance(element);

        if (!instance && element.hasAttribute('data-simplebar') && typeof window.SimpleBar === 'function') {
            try {
                instance = new window.SimpleBar(element);
            } catch (error) {
                console.warn('SimpleBar init failed', error);
            }
        }

        return instance || getSimpleBarInstance(element);
    }

    function refreshAdminSidebarScroll() {
        var sidebarScroll = getSidebarScrollElement();
        if (!sidebarScroll) return;

        var instance = ensureSimpleBar(sidebarScroll);
        if (!instance || typeof instance.recalculate !== 'function') return;

        requestAnimationFrame(function () {
            instance.recalculate();
        });
    }

    function bindSidebarScrollRefresh() {
        var sidebarScroll = getSidebarScrollElement();
        if (!sidebarScroll || sidebarScroll.dataset.scrollRefreshBound === 'true') {
            return;
        }

        sidebarScroll.dataset.scrollRefreshBound = 'true';
        sidebarScroll.addEventListener('shown.bs.collapse', refreshAdminSidebarScroll);
        sidebarScroll.addEventListener('hidden.bs.collapse', refreshAdminSidebarScroll);
    }

    function getReservationConfigElement() {
        var scope = document.getElementById('main-content') || document;
        var scopedConfig = scope.querySelector('#admin-gedung-reservation-config');

        if (scopedConfig) {
            return scopedConfig;
        }

        var allConfigs = document.querySelectorAll('#admin-gedung-reservation-config');
        return allConfigs.length ? allConfigs[allConfigs.length - 1] : null;
    }

    function getLatestElementById(id, scope) {
        var root = scope && scope.querySelector ? scope : document;

        if (root === document || root === document.body) {
            var mainContent = document.getElementById('main-content');

            if (mainContent) {
                var scopedElements = mainContent.querySelectorAll('[id="' + id + '"]');

                if (scopedElements.length) {
                    return scopedElements[scopedElements.length - 1];
                }
            }
        }

        var elements = root.querySelectorAll('[id="' + id + '"]');

        return elements.length ? elements[elements.length - 1] : null;
    }

    function buildAdminSwalButtonClass(tone) {
        var normalizedTone = String(tone || 'primary').trim().toLowerCase() || 'primary';

        return 'btn reservation-modal-action-btn btn-' + normalizedTone;
    }

    function buildAdminSwalOptions(options, tones) {
        var toneMap = tones || {};
        var mergedOptions = Object.assign({}, options || {});
        var customClass = Object.assign({}, mergedOptions.customClass || {});

        customClass.actions = [customClass.actions, 'reservation-modal-swal-actions'].filter(Boolean).join(' ');
        customClass.confirmButton = [customClass.confirmButton, buildAdminSwalButtonClass(toneMap.confirm || 'primary')].filter(Boolean).join(' ');
        customClass.cancelButton = [customClass.cancelButton, buildAdminSwalButtonClass(toneMap.cancel || 'secondary')].filter(Boolean).join(' ');
        customClass.denyButton = [customClass.denyButton, buildAdminSwalButtonClass(toneMap.deny || 'success')].filter(Boolean).join(' ');

        mergedOptions.buttonsStyling = false;
        mergedOptions.customClass = customClass;

        return mergedOptions;
    }

    function getAdminReservationConfig() {
        var configEl = getReservationConfigElement();
        if (!configEl) {
            return null;
        }

        try {
            return JSON.parse(configEl.textContent || '{}');
        } catch (error) {
            console.warn('Failed to parse admin reservation config', error);
            return null;
        }
    }

    function getAdminReservationMap() {
        var config = getAdminReservationConfig();
        var reservations = config && Array.isArray(config.reservations) ? config.reservations : [];
        var map = {};

        reservations.forEach(function (reservation) {
            map[String(reservation.id || '')] = reservation;
        });

        return map;
    }

    function getAdminReservationTableElement() {
        return document.getElementById('zero_config');
    }

    function getAdminReservationEmptyMessage(tableEl) {
        if (!tableEl) {
            return '';
        }

        return String(tableEl.getAttribute('data-empty-message') || '').trim();
    }

    function getAdminReservationScrollXMode(tableEl) {
        if (!tableEl) {
            return 'auto';
        }

        var rawValue = String(tableEl.getAttribute('data-scroll-x') || '').trim().toLowerCase();
        if (!rawValue) {
            return 'auto';
        }

        if (rawValue === 'false' || rawValue === '0' || rawValue === 'no' || rawValue === 'off') {
            return 'false';
        }

        if (rawValue === 'true' || rawValue === '1' || rawValue === 'yes' || rawValue === 'on') {
            return 'true';
        }

        return 'auto';
    }

    function getAdminReservationScrollX(tableEl) {
        return getAdminReservationScrollXMode(tableEl) !== 'false';
    }

    function getAdminReservationDataTable(tableEl) {
        if (!tableEl || typeof window.jQuery === 'undefined' || !window.jQuery.fn || !window.jQuery.fn.DataTable) {
            return null;
        }

        if (!window.jQuery.fn.dataTable.isDataTable(tableEl)) {
            return null;
        }

        return window.jQuery(tableEl).DataTable();
    }

    function normalizeAdminReservationEmptyState(tableEl) {
        if (!tableEl || !tableEl.tBodies || !tableEl.tBodies.length) {
            return '';
        }

        var tbody = tableEl.tBodies[0];
        var headerRow = tableEl.tHead && tableEl.tHead.rows && tableEl.tHead.rows.length
            ? tableEl.tHead.rows[0]
            : null;

        if (!tbody || !headerRow || tbody.rows.length !== 1) {
            return '';
        }

        var row = tbody.rows[0];
        if (!row || row.cells.length !== 1) {
            return '';
        }

        var onlyCell = row.cells[0];
        var colspan = Number(onlyCell.getAttribute('colspan') || 0);
        var columnCount = headerRow.cells.length;

        if (!colspan || colspan !== columnCount) {
            return '';
        }

        var emptyMessage = String(onlyCell.textContent || '').trim();
        tbody.removeChild(row);

        return emptyMessage;
    }

    function initializeAdminReservationTable() {
        var tableEl = getAdminReservationTableElement();
        if (!tableEl || typeof window.jQuery === 'undefined' || !window.jQuery.fn || !window.jQuery.fn.DataTable) {
            return null;
        }

        var $table = window.jQuery(tableEl);

        if (window.jQuery.fn.dataTable.isDataTable(tableEl)) {
            bindAdminReservationTableWidthSync(tableEl);
            scheduleAdminReservationTableWidthSync(tableEl, true);
            return $table.DataTable();
        }

        var normalizedEmptyMessage = normalizeAdminReservationEmptyState(tableEl);
        var emptyMessage = normalizedEmptyMessage || getAdminReservationEmptyMessage(tableEl);
        var dataTable = $table.DataTable({
            scrollX: getAdminReservationScrollX(tableEl),
            pagingType: 'full_numbers',
            language: {
                lengthMenu: 'Tampilkan _MENU_ data',
                search: 'Cari:',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                infoEmpty: 'Menampilkan 0 sampai 0 dari 0 data',
                infoFiltered: '(difilter dari _MAX_ total data)',
                zeroRecords: 'Data tidak ditemukan',
                emptyTable: emptyMessage || 'Tidak ada data yang tersedia dalam tabel ini',
                paginate: {
                    first: '<i class="ti ti-chevrons-left"></i>',
                    previous: '<i class="ti ti-chevron-left"></i>',
                    next: '<i class="ti ti-chevron-right"></i>',
                    last: '<i class="ti ti-chevrons-right"></i>'
                }
            }
        });

        bindAdminReservationTableWidthSync(tableEl);
        scheduleAdminReservationTableWidthSync(tableEl, true);

        return dataTable;
    }

    function refreshAdminReservationTableLayout(tableEl, shouldAdjustColumns) {
        if (!tableEl) {
            return;
        }

        if (shouldAdjustColumns) {
            var dataTable = getAdminReservationDataTable(tableEl);

            if (dataTable && dataTable.columns && typeof dataTable.columns.adjust === 'function') {
                dataTable.columns.adjust();
            }
        }

        syncAdminReservationTableWidth(tableEl);
    }

    function syncAdminReservationTableWidth(tableEl) {
        if (!tableEl) {
            return;
        }

        var wrapper = tableEl.closest('.dataTables_wrapper');
        if (!wrapper) {
            tableEl.style.width = '100%';
            return;
        }

        var scrollHead = wrapper.querySelector('.dataTables_scrollHead');
        var scrollHeadInner = wrapper.querySelector('.dataTables_scrollHeadInner');
        var scrollBody = wrapper.querySelector('.dataTables_scrollBody');
        var headTable = scrollHeadInner ? scrollHeadInner.querySelector('table') : null;
        var bodyTable = scrollBody ? scrollBody.querySelector('table') : null;

        if (!scrollHead || !scrollHeadInner || !scrollBody || !headTable || !bodyTable) {
            wrapper.classList.remove('sigap-table-has-overflow');
            tableEl.style.width = '100%';
            return;
        }

        scrollHead.style.width = '100%';
        scrollBody.style.width = '100%';
        scrollHeadInner.style.width = '100%';
        headTable.style.width = '100%';
        bodyTable.style.width = '100%';
        tableEl.style.width = '100%';

        var availableWidth = Math.max(scrollHead.clientWidth || 0, scrollBody.clientWidth || 0, wrapper.clientWidth || 0);
        var naturalWidth = Math.max(
            scrollBody.scrollWidth || 0,
            headTable.scrollWidth || 0,
            bodyTable.scrollWidth || 0,
            headTable.offsetWidth || 0,
            bodyTable.offsetWidth || 0
        );

        if (!availableWidth && !naturalWidth) {
            return;
        }

        var targetWidth = Math.max(availableWidth, naturalWidth);
        var hasOverflow = targetWidth > availableWidth + 1;

        wrapper.classList.toggle('sigap-table-has-overflow', hasOverflow);

        if (!hasOverflow) {
            scrollBody.scrollLeft = 0;
            return;
        }

        var targetWidthStyle = Math.round(targetWidth) + 'px';
        scrollHeadInner.style.width = targetWidthStyle;
        headTable.style.width = targetWidthStyle;
        bodyTable.style.width = targetWidthStyle;
        tableEl.style.width = targetWidthStyle;
    }

    function scheduleAdminReservationTableWidthSync(tableEl, shouldAdjustColumns) {
        if (!tableEl) {
            return;
        }

        if (shouldAdjustColumns) {
            tableEl.__sigapNeedsColumnAdjust = true;
        }

        if (tableEl.__sigapWidthSyncFrame) {
            return;
        }

        tableEl.__sigapWidthSyncFrame = window.requestAnimationFrame(function () {
            tableEl.__sigapWidthSyncFrame = null;

            var needsColumnAdjust = tableEl.__sigapNeedsColumnAdjust === true;
            tableEl.__sigapNeedsColumnAdjust = false;

            refreshAdminReservationTableLayout(tableEl, needsColumnAdjust);
        });
    }

    function bindAdminReservationTableWidthSync(tableEl) {
        if (!tableEl || tableEl.dataset.widthSyncBound === 'true') {
            return;
        }

        tableEl.dataset.widthSyncBound = 'true';

        if (typeof window.jQuery !== 'undefined') {
            window.jQuery(tableEl).on('draw.dt column-sizing.dt order.dt search.dt page.dt length.dt', function () {
                scheduleAdminReservationTableWidthSync(tableEl);
            });
        }

        if (typeof window.ResizeObserver === 'function') {
            var observedContainer = tableEl.closest('.datatables, .table-responsive, .card, .container-fluid')
                || tableEl.parentElement
                || tableEl;

            var resizeObserver = new window.ResizeObserver(function () {
                scheduleAdminReservationTableWidthSync(tableEl, true);
            });

            resizeObserver.observe(observedContainer);
            tableEl.__sigapWidthSyncObserver = resizeObserver;
        }
    }

    function closeAdminActionMenu() {
        if (!activeAdminActionMenu) {
            return;
        }

        var dropdown = activeAdminActionMenu.dropdown;
        var toggle = activeAdminActionMenu.toggle;
        var menu = activeAdminActionMenu.menu;

        if (dropdown && menu && menu.parentNode !== dropdown) {
            dropdown.appendChild(menu);
        }

        if (menu) {
            menu.hidden = true;
            menu.style.position = '';
            menu.style.top = '';
            menu.style.left = '';
            menu.style.visibility = '';
        }

        if (dropdown) {
            dropdown.classList.remove('is-open');
        }

        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }

        activeAdminActionMenu = null;
    }

    function positionAdminActionMenu(dropdown, toggle, menu) {
        var spacing = 10;

        document.body.appendChild(menu);
        menu.hidden = false;
        menu.style.position = 'fixed';
        menu.style.top = '0';
        menu.style.left = '0';
        menu.style.visibility = 'hidden';

        var toggleRect = toggle.getBoundingClientRect();
        var menuWidth = menu.offsetWidth;
        var menuHeight = menu.offsetHeight;
        var maxLeft = Math.max(spacing, window.innerWidth - menuWidth - spacing);
        var left = Math.min(Math.max(spacing, toggleRect.right - menuWidth), maxLeft);
        var top = toggleRect.bottom + spacing;

        if (top + menuHeight > window.innerHeight - spacing) {
            var topAbove = toggleRect.top - menuHeight - spacing;
            top = topAbove >= spacing
                ? topAbove
                : Math.max(spacing, window.innerHeight - menuHeight - spacing);
        }

        menu.style.top = Math.round(top) + 'px';
        menu.style.left = Math.round(left) + 'px';
        menu.style.visibility = 'visible';

        activeAdminActionMenu = {
            dropdown: dropdown,
            toggle: toggle,
            menu: menu
        };
    }

    function toggleAdminActionMenu(dropdown) {
        if (!dropdown) {
            return;
        }

        var toggle = dropdown.querySelector('.admin-table-action-toggle');
        var menu = dropdown.querySelector('.admin-table-action-menu');

        if (!toggle || !menu) {
            return;
        }

        if (activeAdminActionMenu && activeAdminActionMenu.dropdown === dropdown) {
            closeAdminActionMenu();
            return;
        }

        closeAdminActionMenu();
        dropdown.classList.add('is-open');
        toggle.setAttribute('aria-expanded', 'true');
        positionAdminActionMenu(dropdown, toggle, menu);
    }

    function formatReservationCurrency(amount) {
        return 'Rp ' + Number(amount || 0).toLocaleString('id-ID');
    }

    function formatReservationDate(dateValue) {
        var value = String(dateValue || '').trim();
        if (!value) return '-';

        var date = new Date(value.slice(0, 10) + 'T00:00:00');
        if (isNaN(date.getTime())) return value;

        return date.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    }

    function getNormalizedReservationStatusKey(status) {
        var normalized = String(status || '').trim().toUpperCase();

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

    function shouldUseReservationOrderCode(status) {
        return ['MENUNGGU PEMBAYARAN', 'CEK PEMBAYARAN', 'BERKAS PEMBAYARAN TIDAK SESUAI', 'PEMBAYARAN LUNAS', 'ACARA SELESAI']
            .indexOf(getNormalizedReservationStatusKey(status)) !== -1;
    }

    function getReservationStatusMeta(status) {
        var normalized = getNormalizedReservationStatusKey(status);
        var map = {
            'RESERVASI BARU': {
                text: 'Reservasi<br>Baru',
                badgeClass: 'text-bg-warning',
                themeClass: 'admin-reservation-theme-warning'
            },
            'BERKAS RESERVASI TIDAK SESUAI': {
                text: 'Berkas Reservasi<br>Tidak Sesuai',
                badgeClass: 'text-bg-dark',
                themeClass: 'admin-reservation-theme-secondary'
            },
            'KERJASAMA UMKM': {
                text: 'Kerjasama<br>UMKM',
                badgeClass: 'text-bg-info',
                themeClass: 'admin-reservation-theme-info'
            },
            'PROSES VERIFIKASI': {
                text: 'Proses<br>Verifikasi',
                badgeClass: 'text-bg-warning',
                themeClass: 'admin-reservation-theme-warning'
            },
            'BERKAS VERIFIKASI TIDAK SESUAI': {
                text: 'Berkas Verifikasi<br>Tidak Sesuai',
                badgeClass: 'text-bg-dark',
                themeClass: 'admin-reservation-theme-secondary'
            },
            'BERKAS TIDAK SESUAI': {
                text: 'Berkas Tidak<br>Sesuai',
                badgeClass: 'text-bg-dark',
                themeClass: 'admin-reservation-theme-secondary'
            },
            'MENUNGGU PEMBAYARAN': {
                text: 'Menunggu<br>Pembayaran',
                badgeClass: 'text-bg-primary',
                themeClass: 'admin-reservation-theme-info'
            },
            'CEK PEMBAYARAN': {
                text: 'Cek<br>Pembayaran',
                badgeClass: 'text-bg-warning',
                themeClass: 'admin-reservation-theme-warning'
            },
            'BERKAS PEMBAYARAN TIDAK SESUAI': {
                text: 'Berkas Pembayaran<br>Tidak Sesuai',
                badgeClass: 'text-bg-dark',
                themeClass: 'admin-reservation-theme-secondary'
            },
            'PERMOHONAN DITOLAK': {
                text: 'Permohonan<br>Ditolak',
                badgeClass: 'text-bg-danger',
                themeClass: 'admin-reservation-theme-danger'
            },
            'PEMBAYARAN LUNAS': {
                text: 'Pembayaran<br>Lunas',
                badgeClass: 'text-bg-success',
                themeClass: 'admin-reservation-theme-success'
            },
            'DIBATALKAN PEMOHON': {
                text: 'Dibatalkan<br>Pemohon',
                badgeClass: 'text-bg-danger',
                themeClass: 'admin-reservation-theme-danger'
            },
            'ACARA SELESAI': {
                text: 'Acara<br>Selesai',
                badgeClass: 'text-bg-dark',
                themeClass: 'admin-reservation-theme-secondary'
            }
        };

        return map[normalized] || {
            text: normalized || 'STATUS',
            badgeClass: 'bg-secondary-subtle text-secondary',
            themeClass: 'admin-reservation-theme-secondary'
        };
    }

    function getReservationDocumentStage(item) {
        var normalizedStatus = getNormalizedReservationStatusKey(item && item.status ? item.status : '');
        var notes = String(item && item.notes ? item.notes : '').trim().toUpperCase();
        var orderCode = String(item && item.order_id ? item.order_id : '').trim();
        var hasUmkmFile = String(item && item.umkm_file_url ? item.umkm_file_url : '').trim() !== '';
        var hasPaymentFile = String(item && item.payment_file_url ? item.payment_file_url : '').trim() !== '';

        if (['CEK PEMBAYARAN', 'BERKAS PEMBAYARAN TIDAK SESUAI', 'PEMBAYARAN LUNAS', 'ACARA SELESAI'].indexOf(normalizedStatus) !== -1) {
            return 'PAYMENT';
        }

        if (['KERJASAMA UMKM', 'PROSES VERIFIKASI', 'BERKAS VERIFIKASI TIDAK SESUAI', 'MENUNGGU PEMBAYARAN'].indexOf(normalizedStatus) !== -1) {
            return 'UMKM';
        }

        if (normalizedStatus === 'BERKAS TIDAK SESUAI') {
            if (hasPaymentFile) {
                return 'PAYMENT';
            }

            if (hasUmkmFile || orderCode !== '') {
                return 'UMKM';
            }
        }

        if (normalizedStatus === 'DIBATALKAN PEMOHON') {
            if (
                hasPaymentFile ||
                /STATUS TERAKHIR:\s*(CEK PEMBAYARAN|BERKAS PEMBAYARAN TIDAK SESUAI|PEMBAYARAN LUNAS|ACARA SELESAI)/.test(notes)
            ) {
                return 'PAYMENT';
            }

            if (
                hasUmkmFile ||
                orderCode !== '' ||
                /STATUS TERAKHIR:\s*(KERJASAMA UMKM|PROSES VERIFIKASI|BERKAS VERIFIKASI TIDAK SESUAI|MENUNGGU PEMBAYARAN)/.test(notes)
            ) {
                return 'UMKM';
            }
        }

        return 'RESERVATION';
    }

    function getReservationDetailCodeLabel(item) {
        var status = String(item && item.status ? item.status : '').trim().toUpperCase();
        var requestCode = String(item && item.request_id ? item.request_id : '').trim();
        var orderCode = String(item && item.order_id ? item.order_id : '').trim();
        var useOrderCode = shouldUseReservationOrderCode(status);
        var code = useOrderCode ? orderCode : requestCode;

        if (!code) {
            code = requestCode || orderCode || String(item && item.id ? item.id : '-').trim() || '-';
        }

        return 'Kode : ' + code;
    }

    function applyAdminReservationModalTheme(modalEl, statusMeta) {
        if (!modalEl) {
            return;
        }

        ADMIN_RESERVATION_THEME_CLASSES.forEach(function (className) {
            modalEl.classList.remove(className);
        });

        if (statusMeta && statusMeta.themeClass) {
            modalEl.classList.add(statusMeta.themeClass);
        }
    }

    function showAdminReservationAlert(icon, title, text) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: icon,
                title: title,
                text: text,
                confirmButtonText: 'OK',
                timer: 3000,
                timerProgressBar: true
            });
            return;
        }

        window.alert(text);
    }

    function showAdminReservationFlash() {
        var configEl = getReservationConfigElement();
        var config = getAdminReservationConfig();
        var messages = config && config.messages ? config.messages : null;

        if (!configEl || !messages || configEl.dataset.flashShown === '1') {
            return;
        }

        configEl.dataset.flashShown = '1';

        if (messages.success) {
            showAdminReservationAlert('success', '<b>BERHASIL</b>', String(messages.success));
            return;
        }

        if (messages.error) {
            showAdminReservationAlert('warning', '<b>PERIKSA KEMBALI</b>', String(messages.error));
        }
    }

    function setElementText(root, id, value) {
        var element = root ? root.querySelector('[id="' + id + '"]') : null;
        if (!element) return;
        element.textContent = value;
    }

    function normalizeReservationFileType(fileType) {
        return String(fileType || '').trim().toLowerCase();
    }

    function getReservationFileTypeLabel(fileType) {
        var normalized = normalizeReservationFileType(fileType);
        return normalized ? normalized.toUpperCase() : 'FILE';
    }

    function getReservationFileTypeFromUrl(fileUrl) {
        var normalizedUrl = String(fileUrl || '').trim();
        if (!normalizedUrl) {
            return '';
        }

        var cleanUrl = normalizedUrl.split('#')[0].split('?')[0];
        var dotIndex = cleanUrl.lastIndexOf('.');
        if (dotIndex === -1) {
            return '';
        }

        return normalizeReservationFileType(cleanUrl.slice(dotIndex + 1));
    }

    function isReservationImageType(fileType) {
        return ['jpg', 'jpeg', 'png', 'webp'].indexOf(normalizeReservationFileType(fileType)) !== -1;
    }

    function createReservationDocumentLink(fileUrl, label, galleryTitle) {
        var fileType = getReservationFileTypeFromUrl(fileUrl);
        var link = document.createElement('a');
        link.href = fileUrl;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        link.className = 'btn btn-sm btn-primary px-3';
        link.textContent = label;
        link.setAttribute('data-gallery-trigger', 'reservation-file');
        link.setAttribute('data-file-type', fileType);
        link.setAttribute('data-gallery-title', galleryTitle || label || 'Preview file');

        return link;
    }

    function setDocumentSlot(root, id, fileUrl, availableLabel, missingLabel, galleryTitle) {
        var container = root ? root.querySelector('[id="' + id + '"]') : null;
        if (!container) {
            return;
        }

        container.innerHTML = '';

        var normalizedUrl = String(fileUrl || '').trim();
        if (normalizedUrl) {
            container.appendChild(
                createReservationDocumentLink(
                    normalizedUrl,
                    availableLabel || 'Lihat Berkas',
                    galleryTitle || availableLabel || 'Preview file'
                )
            );
            return;
        }

        var placeholder = document.createElement('span');
        placeholder.className = 'text-muted small';
        placeholder.textContent = missingLabel || 'Belum tersedia';
        container.appendChild(placeholder);
    }

    function setDocumentSlotVisibility(root, wrapperId, shouldShow) {
        var wrapper = root ? root.querySelector('[id="' + wrapperId + '"]') : null;
        if (!wrapper) {
            return;
        }

        wrapper.hidden = !shouldShow;
    }

    function setIdentityFileContent(modalEl, item) {
        var fileUrl = String(item.identity_file_url || '').trim();
        var applicationFileUrl = String(item.application_file_url || '').trim();
        var umkmFileUrl = String(item.umkm_file_url || '').trim();
        var paymentFileUrl = String(item.payment_file_url || '').trim();
        var queryRoot = modalEl || document;
        var documentStage = getReservationDocumentStage(item);
        var showUmkmFile = documentStage !== 'RESERVATION' || umkmFileUrl !== '';
        var showPaymentFile = documentStage === 'PAYMENT' || paymentFileUrl !== '';

        setDocumentSlot(
            queryRoot,
            'adminReservationDetailKtpFile',
            fileUrl,
            'Lihat KTP',
            'Belum diunggah',
            'KTP Pemohon'
        );
        setDocumentSlot(
            queryRoot,
            'adminReservationDetailApplicationFile',
            applicationFileUrl,
            'Lihat Permohonan',
            'Belum tersedia di sistem',
            'File Permohonan'
        );
        setDocumentSlot(
            queryRoot,
            'adminReservationDetailUmkmFile',
            umkmFileUrl,
            'Lihat Kerjasama UMKM',
            'Belum tersedia di sistem',
            'Bukti Kerjasama UMKM'
        );
        setDocumentSlot(
            queryRoot,
            'adminReservationDetailPaymentFile',
            paymentFileUrl,
            'Lihat Bukti Bayar',
            'Belum tersedia di sistem',
            'Bukti Pembayaran'
        );
        setDocumentSlotVisibility(queryRoot, 'adminReservationDetailUmkmFileWrapper', showUmkmFile);
        setDocumentSlotVisibility(queryRoot, 'adminReservationDetailPaymentFileWrapper', showPaymentFile);
    }

    function showAdminReservationDetail(reservationId, reservationNumber) {
        var item = getAdminReservationMap()[String(reservationId || '')];
        var modalEl = getLatestElementById('adminReservationDetailModal', document.body || document);

        if (!item || !modalEl) {
            return false;
        }

        if (modalEl.parentElement !== document.body) {
            document.body.appendChild(modalEl);
        }

        var statusMeta = getReservationStatusMeta(item.status);
        var requesterName = String(item.user_name || '').trim() || String(item.username || '-').trim() || '-';
        var startDate = String(item.start_date || '').trim();
        var endDate = String(item.end_date || '').trim();
        var dateLabel = startDate && endDate && startDate !== endDate
            ? formatReservationDate(startDate) + ' s/d ' + formatReservationDate(endDate)
            : formatReservationDate(startDate || endDate);
        var statusBadge = getLatestElementById('adminReservationDetailStatusBadge', modalEl);
        var reservationContext = String(modalEl.getAttribute('data-reservation-context') || '').trim().toLowerCase();
        var umkmName = String(item.umkm_name || '-').trim() || '-';
        var umkmOwner = String(item.umkm_owner || '-').trim() || '-';
        var umkmAddress = String(item.umkm_address || '-').trim() || '-';
        var umkmCategory = String(item.umkm_type || '-').trim() || '-';

        setElementText(modalEl, 'adminReservationDetailCode', getReservationDetailCodeLabel(item));
        setElementText(modalEl, 'adminReservationDetailRequester', requesterName);
        setElementText(modalEl, 'adminReservationDetailUserAddress', String(item.user_address || '-'));
        setElementText(modalEl, 'adminReservationDetailPhone', String(item.user_phone || '-'));
        setElementText(modalEl, 'adminReservationDetailNik', String(item.user_nik || '-'));
        setElementText(modalEl, 'adminReservationDetailBuilding', String(item.building_name || '-'));
        setElementText(
            modalEl,
            'adminReservationDetailBuildingAddress',
            String(item.building_address || item.location_label || '-')
        );
        setElementText(modalEl, 'adminReservationDetailDate', dateLabel || '-');
        setElementText(modalEl, 'adminReservationDetailSession', String(item.session_display_name || item.session_name || '-'));
        setElementText(modalEl, 'adminReservationDetailEvent', String(item.event_name || '-'));
        setElementText(
            modalEl,
            'adminReservationDetailEstPerson',
            item.est_person ? String(item.est_person) + ' orang' : '-'
        );
        setElementText(modalEl, 'adminReservationDetailTotalPrice', formatReservationCurrency(item.total_price || 0));
        setElementText(
            modalEl,
            'adminReservationDetailNotes',
            String(item.notes || '').trim() !== '' ? String(item.notes) : '-'
        );
        setElementText(modalEl, 'adminReservationDetailUmkm', umkmName);
        setElementText(modalEl, 'adminReservationDetailOwner', reservationContext === 'umkm' ? umkmOwner : '-');
        setElementText(modalEl, 'adminReservationDetailAddress', reservationContext === 'umkm' ? umkmAddress : '-');
        setElementText(modalEl, 'adminReservationDetailCategory', reservationContext === 'umkm' ? umkmCategory : '-');

        if (statusBadge) {
            statusBadge.className = 'badge admin-reservation-detail-status-badge ' + statusMeta.badgeClass;
            statusBadge.innerHTML = statusMeta.text;
        }

        applyAdminReservationModalTheme(modalEl, statusMeta);
        setIdentityFileContent(modalEl, item);

        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }

        return true;
    }

    function buildAdminReservationPopupItems(trigger) {
        var scope = trigger.closest('.modal') || document;
        var links = scope.querySelectorAll('[data-gallery-trigger="reservation-file"]');
        var items = [];
        var seen = {};
        var index = 0;

        Array.prototype.forEach.call(links, function (link) {
            var fileUrl = String(link.getAttribute('href') || '').trim();
            if (fileUrl === '' || fileUrl === '#' || link.hidden) {
                return;
            }

            var fileType = normalizeReservationFileType(link.getAttribute('data-file-type'));
            var title = String(link.getAttribute('data-gallery-title') || 'Preview file').trim();
            var key = fileUrl + '|' + fileType + '|' + title;

            if (Object.prototype.hasOwnProperty.call(seen, key)) {
                if (link === trigger) {
                    index = seen[key];
                }

                return;
            }

            items.push({
                src: fileUrl,
                type: isReservationImageType(fileType) ? 'image' : 'iframe',
                title: title,
                fileType: fileType
            });
            seen[key] = items.length - 1;

            if (link === trigger) {
                index = seen[key];
            }
        });

        return {
            items: items,
            index: index
        };
    }

    function handleAdminReservationDelete(deleteForm) {
        if (!deleteForm) return;

        if (deleteForm.dataset.confirmedSubmit === '1') {
            return;
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: '<b>HAPUS RESERVASI</b>',
                html: '<b class="text-danger">Reservasi yang dihapus tidak dapat dikembalikan.</b><br><br>Lanjutkan penghapusan data?',
                showCancelButton: true,
                confirmButtonText: 'HAPUS',
                cancelButtonText: 'BATAL',
                reverseButtons: true
            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }

                deleteForm.dataset.confirmedSubmit = '1';
                HTMLFormElement.prototype.submit.call(deleteForm);
            });
            return;
        }

        if (window.confirm('Reservasi yang dihapus tidak dapat dikembalikan. Lanjutkan?')) {
            deleteForm.dataset.confirmedSubmit = '1';
            HTMLFormElement.prototype.submit.call(deleteForm);
        }
    }

    function getReservationFormLabel(form) {
        return String(form.getAttribute('data-reservation-label') || 'reservasi ini').trim() || 'reservasi ini';
    }

    function formatReservationRejectTemplate(template, reservationLabel) {
        return String(template || '').split('__RESERVATION__').join(reservationLabel);
    }

    function handleAdminReservationApprove(approveForm) {
        if (!approveForm) return;

        if (approveForm.dataset.confirmedSubmit === '1') {
            return;
        }

        var reservationLabel = getReservationFormLabel(approveForm);
        var title = approveForm.getAttribute('data-approve-title') || '<b>PROSES VERIFIKASI</b>';
        var html = approveForm.getAttribute('data-approve-text')
            || ('Pengajuan <b>' + reservationLabel + '</b> telah sesuai<br><br>Lanjut verifikasi?');
        var confirmButtonText = approveForm.getAttribute('data-approve-confirm') || 'PROSES';

        if (typeof Swal !== 'undefined') {
            Swal.fire(buildAdminSwalOptions({
                icon: 'question',
                title: '<b>' + title + '</b>',
                html: html,
                showCancelButton: true,
                confirmButtonText: confirmButtonText,
                cancelButtonText: 'KEMBALI',
                reverseButtons: true
            }, {
                confirm: 'success',
                cancel: 'danger'
            })).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }

                approveForm.dataset.confirmedSubmit = '1';
                HTMLFormElement.prototype.submit.call(approveForm);
            });
            return;
        }

        if (window.confirm('Lanjutkan approval untuk ' + reservationLabel + '?')) {
            approveForm.dataset.confirmedSubmit = '1';
            HTMLFormElement.prototype.submit.call(approveForm);
        }
    }

    function handleAdminReservationReject(rejectForm) {
        if (!rejectForm) return;

        if (rejectForm.dataset.confirmedSubmit === '1') {
            return;
        }

        var reservationLabel = getReservationFormLabel(rejectForm);
        var title = rejectForm.getAttribute('data-reject-title') || 'Tolak Reservasi';
        var helperText = rejectForm.getAttribute('data-reject-helper')
            || 'Tuliskan catatan penolakan terlebih dahulu sebelum melanjutkan.';
        var headlineHtml = formatReservationRejectTemplate(
            rejectForm.getAttribute('data-reject-text'),
            reservationLabel
        ) || ('Permohonan <b class="text-danger">' + reservationLabel + '</b> akan ditolak');
        var placeholder = rejectForm.getAttribute('data-reject-placeholder')
            || 'Tulis alasan penolakan';
        var confirmButtonText = rejectForm.getAttribute('data-reject-confirm') || 'LANJUT';
        var inputLabel = rejectForm.getAttribute('data-reject-input-label') || 'Keterangan';
        var requiredMessage = rejectForm.getAttribute('data-reject-required-message') || 'Catatan penolakan wajib diisi';
        var fallbackPrompt = formatReservationRejectTemplate(
            rejectForm.getAttribute('data-reject-fallback-prompt'),
            reservationLabel
        ) || ('Tuliskan catatan penolakan untuk ' + reservationLabel + ':');
        var fallbackConfirmText = formatReservationRejectTemplate(
            rejectForm.getAttribute('data-reject-confirm-text'),
            reservationLabel
        ) || ('Lanjutkan penolakan untuk ' + reservationLabel + '?');
        var noteField = rejectForm.querySelector('input[name=\"rejection_note\"]');

        if (typeof Swal !== 'undefined') {
            Swal.fire(buildAdminSwalOptions({
                icon: 'warning',
                title: '<b>' + title + '</b>',
                html: headlineHtml + '<br><br>' + helperText,
                input: 'textarea',
                inputLabel: inputLabel,
                inputPlaceholder: placeholder,
                inputAttributes: {
                    'aria-label': inputLabel
                },
                showCancelButton: true,
                confirmButtonText: confirmButtonText,
                cancelButtonText: 'KEMBALI',
                reverseButtons: true,
                inputValidator: function (value) {
                    if (String(value || '').trim() === '') {
                        return requiredMessage;
                    }

                    return null;
                }
            }, {
                confirm: 'success',
                cancel: 'danger'
            })).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }

                if (noteField) {
                    noteField.value = String(result.value || '').trim();
                }

                rejectForm.dataset.confirmedSubmit = '1';
                HTMLFormElement.prototype.submit.call(rejectForm);
            });
            return;
        }

        var fallbackNote = window.prompt(fallbackPrompt, '');
        if (String(fallbackNote || '').trim() === '') {
            window.alert(requiredMessage);
            return;
        }

        if (noteField) {
            noteField.value = String(fallbackNote).trim();
        }

        if (window.confirm(fallbackConfirmText)) {
            rejectForm.dataset.confirmedSubmit = '1';
            HTMLFormElement.prototype.submit.call(rejectForm);
        }
    }

    function initializeAdminPage() {
        bindSidebarScrollRefresh();
        refreshAdminSidebarScroll();
        initializeAdminReservationTable();
        scheduleAdminReservationTableWidthSync(getAdminReservationTableElement(), true);
        showAdminReservationFlash();
    }

    document.addEventListener('DOMContentLoaded', initializeAdminPage);

    window.addEventListener('load', refreshAdminSidebarScroll);
    window.addEventListener('resize', refreshAdminSidebarScroll);
    window.addEventListener('resize', closeAdminActionMenu);
    window.addEventListener('resize', function () {
        scheduleAdminReservationTableWidthSync(getAdminReservationTableElement(), true);
    });
    document.addEventListener('scroll', closeAdminActionMenu, true);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeAdminActionMenu();
        }
    });

    document.addEventListener('click', function (event) {
        var galleryTrigger = event.target.closest('[data-gallery-trigger="reservation-file"]');
        if (!galleryTrigger) {
            return;
        }

        var fileUrl = String(galleryTrigger.getAttribute('href') || '').trim();
        if (fileUrl === '' || fileUrl === '#') {
            return;
        }

        event.preventDefault();

        if (!window.jQuery || !window.jQuery.magnificPopup) {
            window.open(fileUrl, '_blank', 'noopener');
            return;
        }

        var popupData = buildAdminReservationPopupItems(galleryTrigger);
        if (!popupData.items.length) {
            window.open(fileUrl, '_blank', 'noopener');
            return;
        }

        window.jQuery.magnificPopup.open({
            items: popupData.items,
            type: popupData.items[popupData.index].type,
            mainClass: popupData.items[popupData.index].type === 'image' ? 'mfp-img-mobile' : 'mfp-fade',
            closeOnContentClick: popupData.items[popupData.index].type === 'image',
            closeBtnInside: false,
            fixedContentPos: false,
            gallery: {
                enabled: popupData.items.length > 1,
                navigateByImgClick: true,
                preload: [0, 1]
            },
            image: {
                verticalFit: true,
                titleSrc: function (item) {
                    var title = item.data && item.data.title ? item.data.title : 'Preview file';
                    var type = getReservationFileTypeLabel(item.data && item.data.fileType ? item.data.fileType : item.type);

                    return title + '<small>' + type + '</small>';
                }
            },
            iframe: {
                markup: '<div class="mfp-iframe-scaler"><div class="mfp-close"></div><iframe class="mfp-iframe" src="//about:blank" frameborder="0" allowfullscreen></iframe><div class="mfp-bottom-bar"><div class="mfp-title"></div></div></div>',
                srcAction: 'iframe_src'
            },
            callbacks: {
                markupParse: function (template, values, item) {
                    var title = item.data && item.data.title ? item.data.title : 'Preview file';
                    var type = getReservationFileTypeLabel(item.data && item.data.fileType ? item.data.fileType : item.type);
                    values.title = title + '<small>' + type + '</small>';
                }
            }
        }, popupData.index);
    });

    document.addEventListener('click', function (event) {
        var sidebarToggle = event.target.closest('.sidebartoggler');
        if (sidebarToggle) {
            window.setTimeout(refreshAdminSidebarScroll, 200);
            window.setTimeout(function () {
                scheduleAdminReservationTableWidthSync(getAdminReservationTableElement(), true);
            }, 220);
        }

        var actionToggle = event.target.closest('.admin-table-action-toggle');
        if (actionToggle) {
            event.preventDefault();
            toggleAdminActionMenu(actionToggle.closest('.admin-table-action-dropdown'));
            return;
        }

        var actionItem = event.target.closest('.admin-table-action-item');
        if (actionItem && activeAdminActionMenu && activeAdminActionMenu.menu.contains(actionItem)) {
            window.setTimeout(closeAdminActionMenu, 0);
        }

        if (
            activeAdminActionMenu
            && !activeAdminActionMenu.menu.contains(event.target)
            && !activeAdminActionMenu.dropdown.contains(event.target)
        ) {
            closeAdminActionMenu();
        }

        var detailButton = event.target.closest('.js-admin-reservation-detail-button');
        if (detailButton) {
            event.preventDefault();
            closeAdminActionMenu();
            showAdminReservationDetail(
                detailButton.getAttribute('data-reservation-id'),
                detailButton.getAttribute('data-reservation-number')
            );
        }
    });

    document.addEventListener('submit', function (event) {
        var deleteForm = event.target.closest('.js-admin-reservation-delete-form');
        if (deleteForm) {
            closeAdminActionMenu();
            if (deleteForm.dataset.confirmedSubmit === '1') {
                return;
            }

            event.preventDefault();
            handleAdminReservationDelete(deleteForm);
            return;
        }

        var approveForm = event.target.closest('.js-admin-reservation-approve-form');
        if (approveForm) {
            closeAdminActionMenu();
            if (approveForm.dataset.confirmedSubmit === '1') {
                return;
            }

            event.preventDefault();
            handleAdminReservationApprove(approveForm);
            return;
        }

        var rejectForm = event.target.closest('.js-admin-reservation-reject-form');
        if (!rejectForm) return;

        closeAdminActionMenu();
        if (rejectForm.dataset.confirmedSubmit === '1') {
            return;
        }

        event.preventDefault();
        handleAdminReservationReject(rejectForm);
    });

    window.refreshAdminSidebarScroll = refreshAdminSidebarScroll;
    window.showAdminReservationDetail = showAdminReservationDetail;
    window.initAdminPage = initializeAdminPage;
    window.initPage = initializeAdminPage;
})();
