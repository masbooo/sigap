function initRequiredBiodataModal() {
    var modalEl =
        document.getElementById('requiredBiodataModal') ||
        document.getElementById('vertical-center-scroll-modal');

    if (!modalEl) return;

    if (modalEl.parentElement !== document.body) {
        document.body.appendChild(modalEl);
    }

    var nikInput = document.getElementById('requiredNIK');
    var nameInput = document.getElementById('requiredName');
    var genderInputs = document.querySelectorAll('input[name="gender"]');
    var genderGroup = document.getElementById('requiredGenderGroup');
    var genderInvalidFeedback = document.getElementById('requiredGenderInvalidFeedback');
    var genderValidFeedback = document.getElementById('requiredGenderValidFeedback');
    var addressInput = document.getElementById('requiredAddress');
    var districtInput = document.getElementById('requiredDistrict');
    var villageInput = document.getElementById('requiredVillage');
    var phoneInput = document.getElementById('requiredPhone');
    var identityInput = document.getElementById('requiredIdentityFile');
    var identityPreview = document.getElementById('required-identity-file-preview');
    var form = document.getElementById('requiredBiodataForm');
    var identityObjectUrl = '';

    var districtVillageMap = window.__districtVillageMap || {};

    function onlyDigits(value, maxLength) {
        var cleaned = String(value || '').replace(/\D/g, '');
        if (maxLength && cleaned.length > maxLength) {
            cleaned = cleaned.slice(0, maxLength);
        }
        return cleaned;
    }

    function onlyLetters(value, maxLength) {
        var cleaned = String(value || '')
            .replace(/[^A-Za-z\s]/g, '')
            .replace(/\s{2,}/g, ' ')
            .replace(/^\s+/, '');

        if (maxLength && cleaned.length > maxLength) {
            cleaned = cleaned.slice(0, maxLength);
        }

        return cleaned;
    }

    function resetFieldState(el) {
        if (!el) return;
        el.classList.remove('is-invalid', 'is-valid');
    }

    function setFieldState(el, isValid) {
        if (!el) return false;

        el.classList.remove('is-invalid', 'is-valid');

        if (isValid) {
            el.classList.add('is-valid');
            return true;
        }

        el.classList.add('is-invalid');
        return false;
    }

    function fillVillageOptions(selectedDistrictId, selectedVillageId) {
        if (!villageInput) return;

        villageInput.innerHTML = '<option value="">Pilih Kelurahan</option>';

        var villages = districtVillageMap[String(selectedDistrictId)] || [];

        if (!selectedDistrictId || !villages.length) {
            villageInput.disabled = true;
            resetFieldState(villageInput);
            return;
        }

        villages.forEach(function (village) {
            var option = document.createElement('option');
            option.value = String(village.id);
            option.textContent = village.name;

            if (selectedVillageId && String(selectedVillageId) === String(village.id)) {
                option.selected = true;
            }

            villageInput.appendChild(option);
        });

        villageInput.disabled = false;
    }

    function validateNik() {
        if (!nikInput) return true;
        nikInput.value = onlyDigits(nikInput.value, 16);
        return setFieldState(nikInput, nikInput.value.trim().length === 16);
    }

    function validateName() {
        if (!nameInput) return true;

        nameInput.value = onlyLetters(nameInput.value, 100);

        var value = nameInput.value.trim();
        var isValid = value !== '' && /^[A-Za-z\s]+$/.test(value);

        return setFieldState(nameInput, isValid);
    }

    function validateGender() {
        if (!genderInputs || !genderInputs.length || !genderGroup) return true;

        var selectedValue = '';

        genderInputs.forEach(function (input) {
            input.classList.remove('is-invalid', 'is-valid');

            if (input.checked) {
                selectedValue = String(input.value || '').trim().toUpperCase();
            }
        });

        var isValid = selectedValue === 'L' || selectedValue === 'P';

        if (genderInvalidFeedback) {
            genderInvalidFeedback.classList.toggle('d-none', isValid);
            genderInvalidFeedback.classList.toggle('d-block', !isValid);
        }

        if (genderValidFeedback) {
            genderValidFeedback.classList.toggle('d-none', !isValid);
            genderValidFeedback.classList.toggle('d-block', isValid);
        }

        genderInputs.forEach(function (input) {
            input.classList.add(isValid ? 'is-valid' : 'is-invalid');
        });

        return isValid;
    }

    function validateAddress() {
        if (!addressInput) return true;
        return setFieldState(addressInput, addressInput.value.trim() !== '');
    }

    function validateDistrict() {
        if (!districtInput) return true;
        return setFieldState(districtInput, districtInput.value.trim() !== '');
    }

    function validateVillage() {
        if (!villageInput) return true;

        if (villageInput.disabled) {
            resetFieldState(villageInput);
            return false;
        }

        return setFieldState(villageInput, villageInput.value.trim() !== '');
    }

    function validatePhone() {
        if (!phoneInput) return true;
        phoneInput.value = onlyDigits(phoneInput.value, 15);
        var len = phoneInput.value.trim().length;
        return setFieldState(phoneInput, len >= 10 && len <= 15);
    }

    function getIdentityFileExtension(fileName) {
        var normalizedName = String(fileName || '').trim();
        var dotIndex = normalizedName.lastIndexOf('.');

        if (dotIndex === -1) {
            return '';
        }

        return normalizedName.slice(dotIndex + 1).toLowerCase();
    }

    function getIdentityFileTypeLabel(extension) {
        return extension ? String(extension).toUpperCase() : 'FILE';
    }

    function isIdentityPreviewImage(extension) {
        return ['jpg', 'jpeg', 'png'].indexOf(String(extension || '').toLowerCase()) !== -1;
    }

    function revokeIdentityPreviewObjectUrl() {
        if (!identityObjectUrl) {
            return;
        }

        URL.revokeObjectURL(identityObjectUrl);
        identityObjectUrl = '';
    }

    function syncIdentityPreview() {
        if (!identityInput || !identityPreview) {
            return;
        }

        var media = identityPreview.querySelector('[data-upload-preview-media]');
        var image = identityPreview.querySelector('[data-upload-preview-image]');
        var icon = identityPreview.querySelector('[data-upload-preview-icon]');
        var status = identityPreview.querySelector('[data-upload-preview-status]');
        var name = identityPreview.querySelector('[data-upload-preview-name]');
        var check = identityPreview.querySelector('.reservation-upload-preview-check');
        var emptyStatus = String(identityPreview.getAttribute('data-empty-status') || 'Belum ada file');
        var emptyName = String(identityPreview.getAttribute('data-empty-name') || 'Unggah JPG, JPEG, PNG, atau PDF');
        var existingUrl = String(identityInput.getAttribute('data-existing-file-url') || '').trim();
        var existingName = String(identityInput.getAttribute('data-existing-file-name') || '').trim();
        var existingExtension = String(identityInput.getAttribute('data-existing-file-extension') || '').trim().toLowerCase();
        var selectedFile = identityInput.files && identityInput.files[0] ? identityInput.files[0] : null;
        var previewUrl = '';
        var previewName = emptyName;
        var previewExtension = '';
        var previewStatus = emptyStatus;
        var isReady = false;

        revokeIdentityPreviewObjectUrl();

        if (selectedFile) {
            previewExtension = getIdentityFileExtension(selectedFile.name);
            previewName = String(selectedFile.name || '').trim() || emptyName;
            previewStatus = 'File siap diunggah';
            previewUrl = URL.createObjectURL(selectedFile);
            identityObjectUrl = previewUrl;
            isReady = true;
        } else if (existingUrl !== '') {
            previewExtension = existingExtension;
            previewName = existingName || emptyName;
            previewStatus = 'File sudah diunggah';
            previewUrl = existingUrl;
            isReady = true;
        }

        identityPreview.classList.toggle('is-ready', isReady);
        identityPreview.dataset.fileType = previewExtension;

        if (media) {
            if (previewUrl !== '') {
                media.href = previewUrl;
                media.hidden = false;
                media.removeAttribute('aria-disabled');
                media.removeAttribute('tabindex');
                media.setAttribute('target', '_blank');
                media.setAttribute('rel', 'noopener noreferrer');
                media.setAttribute('data-gallery-trigger', 'required-identity-file');
                media.setAttribute('data-gallery-title', 'File KTP');
            } else {
                media.removeAttribute('href');
                media.removeAttribute('target');
                media.removeAttribute('rel');
                media.removeAttribute('data-gallery-trigger');
                media.removeAttribute('data-gallery-title');
                media.setAttribute('aria-disabled', 'true');
                media.setAttribute('tabindex', '-1');
            }
        }

        if (image) {
            image.hidden = true;
            image.removeAttribute('src');

            if (previewUrl !== '' && isIdentityPreviewImage(previewExtension)) {
                image.src = previewUrl;
                image.hidden = false;
            }
        }

        if (icon) {
            icon.hidden = previewUrl !== '' && isIdentityPreviewImage(previewExtension);
            icon.textContent = getIdentityFileTypeLabel(previewExtension);
        }

        if (status) {
            status.textContent = previewStatus;
        }

        if (name) {
            name.hidden = false;
            name.textContent = isReady ? previewName : emptyName;
        }

        if (check) {
            check.hidden = !isReady;
        }
    }

    function validateIdentityFile() {
        if (!identityInput) return true;

        var existingUrl = String(identityInput.getAttribute('data-existing-file-url') || '').trim();
        var selectedFile = identityInput.files && identityInput.files[0] ? identityInput.files[0] : null;
        var isRequired = String(identityInput.getAttribute('data-required-upload') || '0') === '1';
        var isValid = false;

        if (selectedFile) {
            var extension = getIdentityFileExtension(selectedFile.name);
            isValid =
                ['jpg', 'jpeg', 'png', 'pdf'].indexOf(extension) !== -1 &&
                Number(selectedFile.size || 0) > 0 &&
                Number(selectedFile.size || 0) <= 1048576;
        } else {
            isValid = !isRequired || existingUrl !== '';
        }

        return setFieldState(identityInput, isValid);
    }

    if (districtInput) {
        var selectedDistrictId = districtInput.getAttribute('data-selected') || districtInput.value || '';
        if (selectedDistrictId !== '') {
            districtInput.value = String(selectedDistrictId);
        }
    }

    if (districtInput && villageInput) {
        fillVillageOptions(
            districtInput.value,
            villageInput.getAttribute('data-selected') || villageInput.value || ''
        );

        if (districtInput.value) {
            validateDistrict();
        }

        if (villageInput.value) {
            validateVillage();
        }

        districtInput.addEventListener('change', function () {
            resetFieldState(districtInput);
            resetFieldState(villageInput);

            villageInput.setAttribute('data-selected', '');
            fillVillageOptions(districtInput.value, '');

            validateDistrict();
            validateVillage();
        });

        villageInput.addEventListener('change', function () {
            validateVillage();
        });
    }

    if (nikInput) {
        if (nikInput.value.trim() !== '') validateNik();

        nikInput.addEventListener('input', function () {
            validateNik();
        });
    }

    if (nameInput) {
        if (nameInput.value.trim() !== '') validateName();

        nameInput.addEventListener('input', function () {
            validateName();
        });

        nameInput.addEventListener('paste', function () {
            setTimeout(function () {
                validateName();
            }, 0);
        });
    }

    if (genderInputs && genderInputs.length) {
        if (Array.from(genderInputs).some(function (input) { return input.checked; })) {
            validateGender();
        }

        genderInputs.forEach(function (input) {
            input.addEventListener('change', function () {
                validateGender();
            });
        });
    }

    if (addressInput) {
        if (addressInput.value.trim() !== '') validateAddress();

        addressInput.addEventListener('input', function () {
            validateAddress();
        });
    }

    if (phoneInput) {
        if (phoneInput.value.trim() !== '') validatePhone();

        phoneInput.addEventListener('input', function () {
            validatePhone();
        });
    }

    if (identityInput) {
        syncIdentityPreview();
        if (String(identityInput.getAttribute('data-existing-file-url') || '').trim() !== '') {
            validateIdentityFile();
        }

        identityInput.addEventListener('change', function () {
            syncIdentityPreview();
            validateIdentityFile();
        });
    }

    if (form && !form.dataset.bound) {
        form.dataset.bound = '1';

        form.addEventListener('submit', function (e) {
            var nikValid = validateNik();
            var nameValid = validateName();
            var genderValid = validateGender();
            var addressValid = validateAddress();
            var districtValid = validateDistrict();
            var villageValid = validateVillage();
            var phoneValid = validatePhone();
            var identityValid = validateIdentityFile();

            var valid =
                nikValid &&
                nameValid &&
                genderValid &&
                addressValid &&
                districtValid &&
                villageValid &&
                phoneValid &&
                identityValid;

            if (!valid) {
                e.preventDefault();

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: '<b>PERIKSA KEMBALI</b>',
                        text: 'Pastikan semua field terisi dengan benar',
                        confirmButtonText: 'OK',
                timer: 3000,
                timerProgressBar: true
                    });
                } else {
                    alert('Pastikan semua field terisi dengan benar');
                }
            }
        });
    }

    setTimeout(function () {
        if (typeof bootstrap !== 'undefined') {
            var existingModal = bootstrap.Modal.getInstance(modalEl);
            if (!existingModal) {
                var modal = new bootstrap.Modal(modalEl, {
                    backdrop: 'static',
                    keyboard: false
                });
                modal.show();
            }
        }
    }, 150);

    modalEl.addEventListener('hidden.bs.modal', function () {
        revokeIdentityPreviewObjectUrl();
    });
}

function initUserDashboardFlashMessages() {
    var errorMessage = window.__loginErrorMessage;
    var successMessage = window.__loginSuccessMessage;

    if (errorMessage) {
        window.__loginErrorMessage = '';

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: '<b>PERIKSA KEMBALI</b>',
                text: String(errorMessage),
                confirmButtonText: 'OK',
                timer: 3000,
                timerProgressBar: true
            });
        }
    }

    if (successMessage) {
        window.__loginSuccessMessage = '';

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: '<b>BERHASIL</b>',
                text: String(successMessage),
                confirmButtonText: 'OK',
                timer: 3000,
                timerProgressBar: true
            });
        }
    }
}

window.__sigapReservationPageManagedByUserJs = true;

function getUserReservationConfig() {
    var scope = document.getElementById('main-content') || document;
    var configEl = scope.querySelector('#user-reservation-config');

    if (!configEl) {
        var configs = document.querySelectorAll('#user-reservation-config');
        configEl = configs.length ? configs[configs.length - 1] : null;
    }

    if (!configEl) return null;
    if (configEl.__sigapParsedConfig) return configEl.__sigapParsedConfig;

    try {
        configEl.__sigapParsedConfig = JSON.parse(configEl.textContent || '{}');
    } catch (error) {
        console.warn('User reservation config parse failed', error);
        configEl.__sigapParsedConfig = null;
    }

    return configEl.__sigapParsedConfig;
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

function initUserReservationPanelLoader() {
    var embeddedConfig = getUserReservationConfig() || {};
    var reservation = embeddedConfig.reservation || {};
    var panelUrl = String(reservation.panelUrl || '').trim();
    var panelContainer = reservation.panelContainerId
        ? document.getElementById(reservation.panelContainerId)
        : document.getElementById('user-reservation-panel-container');
    var openButton = reservation.openButtonId
        ? document.getElementById(reservation.openButtonId)
        : document.getElementById('user-reservation-open-button');
    var shouldAutoLoadPanel = reservation.autoLoadPanel === true
        || String(reservation.autoLoadPanel || '0') === '1';

    if (!panelContainer || panelUrl === '') {
        return;
    }

    function clearLegacyReservationHash() {
        if (
            String(window.location.hash || '') !== '#userReservationForm'
            || typeof window.history === 'undefined'
            || typeof window.history.replaceState !== 'function'
        ) {
            return;
        }

        window.history.replaceState(
            window.history.state,
            document.title,
            window.location.pathname + window.location.search
        );
    }

    function setOpenButtonExpanded(isExpanded) {
        if (!openButton) {
            return;
        }

        openButton.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
    }

    function setOpenButtonLoadingState(isLoading) {
        if (!openButton || (openButton.disabled && openButton.dataset.sigapReservationPanelLoading !== '1')) {
            return;
        }

        if (isLoading) {
            openButton.dataset.sigapReservationPanelLoading = '1';
            openButton.dataset.sigapReservationPanelLabel = openButton.innerHTML;
            openButton.disabled = true;
            openButton.innerHTML = '<b>MEMUAT...</b>';
            return;
        }

        if (openButton.dataset.sigapReservationPanelLoading !== '1') {
            return;
        }

        openButton.disabled = false;
        openButton.innerHTML = openButton.dataset.sigapReservationPanelLabel || '<b>RESERVASI SEKARANG</b>';
        openButton.removeAttribute('data-sigap-reservation-panel-loading');
        openButton.removeAttribute('data-sigap-reservation-panel-label');
    }

    function getReservationPanelScrollTarget(scrollTarget) {
        var preferredTarget = String(scrollTarget || 'form').trim().toLowerCase();

        if (preferredTarget === 'calendar') {
            return document.getElementById('user-reservation-panel-root')
                || document.getElementById('calendar-user')
                || panelContainer;
        }

        return document.getElementById('user-reservation-detail-row')
            || document.getElementById('userReservationForm')
            || document.getElementById('user-reservation-panel-root')
            || panelContainer;
    }

    function getReservationPanelScrollOffset(scrollTarget) {
        var preferredTarget = String(scrollTarget || 'form').trim().toLowerCase();
        var header =
            document.querySelector('.topbar')
            || document.querySelector('.header-fp')
            || document.querySelector('header')
            || document.querySelector('.sticky-top')
            || document.querySelector('.fixed-top');
        var headerHeight = header ? header.offsetHeight : 90;
        var spacing = preferredTarget === 'calendar' ? 8 : 0;

        return headerHeight + spacing;
    }

    function scrollReservationPanelIntoView(scrollTarget) {
        var target = getReservationPanelScrollTarget(scrollTarget);

        if (target && typeof window.scrollTo === 'function') {
            var offsetTop = target.getBoundingClientRect().top + window.pageYOffset - getReservationPanelScrollOffset(scrollTarget);

            window.scrollTo({
                top: Math.max(offsetTop, 0),
                behavior: 'smooth',
            });
        }
    }

    function showPanelLoadingState() {
        panelContainer.classList.remove('d-none');
        panelContainer.innerHTML = [
            '<div class="card shadow-sm">',
            '    <div class="card-body py-5 text-center text-muted">',
            '        Memuat form reservasi...',
            '    </div>',
            '</div>'
        ].join('');
        setOpenButtonExpanded(true);
    }

    function showPanelLoadError() {
        var message = 'Bagian reservasi gagal dimuat. Silakan coba lagi.';

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: '<b>GAGAL MEMUAT</b>',
                text: message,
                confirmButtonText: 'OK',
                timer: 3000,
                timerProgressBar: true
            });
            return;
        }

        alert(message);
    }

    function loadReservationPanel(scrollTarget) {
        var preferredScrollTarget = String(scrollTarget || '').trim().toLowerCase();
        var shouldScroll = preferredScrollTarget !== '';

        if (panelContainer.dataset.panelLoaded === '1') {
            panelContainer.classList.remove('d-none');
            setOpenButtonExpanded(true);

            if (shouldScroll) {
                clearLegacyReservationHash();
                scrollReservationPanelIntoView(preferredScrollTarget);
            }

            return Promise.resolve(panelContainer);
        }

        if (panelContainer.dataset.panelLoading === '1') {
            if (shouldScroll) {
                panelContainer.dataset.panelPendingScrollTarget = preferredScrollTarget;
            }

            return Promise.resolve(null);
        }

        if (shouldScroll) {
            panelContainer.dataset.panelPendingScrollTarget = preferredScrollTarget;
        }

        panelContainer.dataset.panelLoading = '1';
        setOpenButtonLoadingState(true);
        showPanelLoadingState();

        return fetch(panelUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
            .then(function (response) {
                if (response.redirected && response.url) {
                    window.location.href = response.url;
                    return null;
                }

                if (!response.ok) {
                    throw new Error('Request failed');
                }

                return response.text();
            })
            .then(function (html) {
                if (html === null) {
                    return null;
                }

                panelContainer.innerHTML = html;
                panelContainer.classList.remove('d-none');
                panelContainer.dataset.panelLoaded = '1';
                panelContainer.removeAttribute('data-panel-loading');
                setOpenButtonLoadingState(false);
                setOpenButtonExpanded(true);

                if (
                    panelContainer.querySelector('[data-calendar-instance], #calendar-user') &&
                    window.SigapPageInits &&
                    typeof window.SigapPageInits.calendar === 'function'
                ) {
                    try {
                        window.SigapPageInits.calendar();
                    } catch (calendarError) {
                        console.warn('Reservation calendar init failed', calendarError);
                    }
                }

                if (typeof window.initPage === 'function') {
                    window.initPage();
                } else {
                    window.setTimeout(initUserReservationPage, 0);
                }

                if (String(panelContainer.dataset.panelPendingScrollTarget || '').trim() !== '') {
                    var pendingScrollTarget = String(panelContainer.dataset.panelPendingScrollTarget || '').trim().toLowerCase();
                    panelContainer.removeAttribute('data-panel-pending-scroll-target');
                    window.setTimeout(function () {
                        clearLegacyReservationHash();
                        scrollReservationPanelIntoView(pendingScrollTarget);
                    }, 120);
                }

                return panelContainer;
            })
            .catch(function (error) {
                console.warn('Reservation panel load failed', error);
                panelContainer.innerHTML = '';
                panelContainer.classList.add('d-none');
                panelContainer.removeAttribute('data-panel-loading');
                panelContainer.removeAttribute('data-panel-pending-scroll-target');
                setOpenButtonLoadingState(false);
                setOpenButtonExpanded(false);
                showPanelLoadError();
                return null;
            });
    }

    if (openButton && openButton.dataset.sigapReservationPanelBound !== '1') {
        openButton.dataset.sigapReservationPanelBound = '1';
        openButton.addEventListener('click', function () {
            var reservationForm = document.getElementById('userReservationForm');
            var reservationMode = reservationForm
                ? String(reservationForm.dataset.reservationMode || '').trim().toLowerCase()
                : '';

            loadReservationPanel(reservationMode === 'edit' ? 'form' : 'calendar');
        });
    }

    if (shouldAutoLoadPanel && panelContainer.dataset.panelAutoLoadHandled !== '1') {
        panelContainer.dataset.panelAutoLoadHandled = '1';
        loadReservationPanel('form');
    } else {
        clearLegacyReservationHash();
    }
}

function formatReservationDate(dateStr) {
    if (!dateStr) return '';

    var date = new Date(String(dateStr).slice(0, 10) + 'T00:00:00');
    if (isNaN(date.getTime())) return String(dateStr);

    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    });
}

function formatReservationDateTime(dateStr) {
    if (!dateStr) return '-';

    var normalizedDate = String(dateStr).replace(' ', 'T');
    var date = new Date(normalizedDate);
    if (isNaN(date.getTime())) return String(dateStr);

    return date.toLocaleString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function formatReservationCurrency(amount) {
    return 'Rp ' + Number(amount || 0).toLocaleString('id-ID');
}

function getUserDataTableElement() {
    return document.getElementById('zero_config');
}

function getUserDataTableEmptyMessage(tableEl) {
    if (!tableEl) {
        return '';
    }

    return String(tableEl.getAttribute('data-empty-message') || '').trim();
}

function getUserDataTableScrollXMode(tableEl) {
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

function getUserDataTableMode(tableEl) {
    if (!tableEl || tableEl.id !== 'zero_config') {
        return 'desktop';
    }

    var viewportWidth = Math.max(
        window.innerWidth || 0,
        document.documentElement ? document.documentElement.clientWidth || 0 : 0
    );

    if (viewportWidth <= 767.98) {
        return 'mobile';
    }

    if (viewportWidth <= 991.98) {
        return 'tablet';
    }

    return 'desktop';
}

function isUserDataTableCompactMode(mode) {
    return mode === 'tablet' || mode === 'mobile';
}

function getUserDataTableColumnVisibility(mode) {
    if (mode === 'mobile') {
        return [true, true, false, false, false, false, false, false, true];
    }

    if (mode === 'tablet') {
        return [true, true, true, false, true, false, false, false, true];
    }

    return [true, true, true, true, true, true, true, true, true];
}

function getUserDataTableScrollX(tableEl, mode) {
    if (isUserDataTableCompactMode(mode)) {
        return false;
    }

    return getUserDataTableScrollXMode(tableEl) !== 'false';
}

function getUserDataTableApi(tableEl) {
    if (!tableEl || typeof window.jQuery === 'undefined' || !window.jQuery.fn || !window.jQuery.fn.DataTable) {
        return null;
    }

    if (!window.jQuery.fn.dataTable.isDataTable(tableEl)) {
        return null;
    }

    return window.jQuery(tableEl).DataTable();
}

function normalizeUserDataTableEmptyState(tableEl) {
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

function escapeUserDataTableHtml(value) {
    return String(value == null ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function getUserDataTableRowId(rowNode) {
    if (!rowNode) {
        return '';
    }

    return String(rowNode.getAttribute('data-reservation-id') || rowNode.id || '').trim();
}

function normalizeUserDataTableExpandedRows(rowIds) {
    if (!Array.isArray(rowIds)) {
        return [];
    }

    var uniqueRowIds = [];

    rowIds.forEach(function (rowId) {
        var normalizedRowId = String(rowId || '').trim();

        if (!normalizedRowId || uniqueRowIds.indexOf(normalizedRowId) !== -1) {
            return;
        }

        uniqueRowIds.push(normalizedRowId);
    });

    return uniqueRowIds;
}

function getUserDataTableExpandedRows(tableEl) {
    return normalizeUserDataTableExpandedRows(tableEl ? tableEl.__sigapExpandedRowIds : []);
}

function setUserDataTableExpandedRows(tableEl, rowIds) {
    if (!tableEl) {
        return [];
    }

    tableEl.__sigapExpandedRowIds = normalizeUserDataTableExpandedRows(rowIds);
    return tableEl.__sigapExpandedRowIds.slice();
}

function addUserDataTableExpandedRow(tableEl, rowId) {
    var expandedRows = getUserDataTableExpandedRows(tableEl);
    var normalizedRowId = String(rowId || '').trim();

    if (!normalizedRowId || expandedRows.indexOf(normalizedRowId) !== -1) {
        return expandedRows;
    }

    expandedRows.push(normalizedRowId);
    return setUserDataTableExpandedRows(tableEl, expandedRows);
}

function removeUserDataTableExpandedRow(tableEl, rowId) {
    var normalizedRowId = String(rowId || '').trim();
    var expandedRows = getUserDataTableExpandedRows(tableEl).filter(function (storedRowId) {
        return storedRowId !== normalizedRowId;
    });

    return setUserDataTableExpandedRows(tableEl, expandedRows);
}

function getUserDataTableRowDatasetValue(rowNode, key) {
    if (!rowNode || !rowNode.dataset) {
        return '';
    }

    return String(rowNode.dataset[key] || '').trim();
}

function buildUserDataTableChildField(label, value, secondaryValue) {
    var safeLabel = escapeUserDataTableHtml(label || '-');
    var safeValue = escapeUserDataTableHtml(value || '-');
    var safeSecondaryValue = escapeUserDataTableHtml(secondaryValue || '');

    return ''
        + '<div class="sigap-user-dt-child-item">'
        + '<div class="sigap-user-dt-child-label">' + safeLabel + '</div>'
        + '<div class="sigap-user-dt-child-separator">:</div>'
        + '<div class="sigap-user-dt-child-value">'
        + '<span class="sigap-user-dt-child-primary">' + safeValue + '</span>'
        + (safeSecondaryValue !== ''
            ? '<span class="sigap-user-dt-child-secondary">' + safeSecondaryValue + '</span>'
            : '')
        + '</div>'
        + '</div>';
}

function getUserDataTableChildFields(rowNode, mode) {
    var code = getUserDataTableRowDatasetValue(rowNode, 'childCode') || '-';
    var building = getUserDataTableRowDatasetValue(rowNode, 'childBuilding') || '-';
    var buildingLocation = getUserDataTableRowDatasetValue(rowNode, 'childBuildingLocation');
    var submittedAt = getUserDataTableRowDatasetValue(rowNode, 'childSubmittedAt') || '-';
    var eventDate = getUserDataTableRowDatasetValue(rowNode, 'childEventDate') || '-';
    var eventName = getUserDataTableRowDatasetValue(rowNode, 'childEventName') || '-';
    var price = getUserDataTableRowDatasetValue(rowNode, 'childPrice') || '-';

    if (buildingLocation === '-') {
        buildingLocation = '';
    }

    if (mode === 'tablet') {
        return [
            { label: 'Gedung', value: building, secondary: buildingLocation },
            { label: 'Tanggal Acara', value: eventDate },
            { label: 'Tarif Sewa', value: price }
        ];
    }

    if (mode === 'mobile') {
        return [
            { label: 'Kode', value: code },
            { label: 'Gedung', value: building, secondary: buildingLocation },
            { label: 'Tanggal Ajuan', value: submittedAt },
            { label: 'Tanggal Acara', value: eventDate },
            { label: 'Acara', value: eventName },
            { label: 'Tarif Sewa', value: price }
        ];
    }

    return [];
}

function formatUserDataTableChildRow(rowNode, mode) {
    var childFields = getUserDataTableChildFields(rowNode, mode);

    if (!childFields.length) {
        return '';
    }

    return ''
        + '<div class="sigap-user-dt-child-card">'
        + '<div class="sigap-user-dt-child-grid">'
        + childFields.map(function (field) {
            return buildUserDataTableChildField(field.label, field.value, field.secondary);
        }).join('')
        + '</div>'
        + '</div>';
}

function setUserDataTableRowExpandedState(rowNode, isExpanded) {
    if (!rowNode) {
        return;
    }

    rowNode.classList.toggle('is-user-dt-child-open', isExpanded);

    if (rowNode.hasAttribute('data-user-dt-compact-row')) {
        rowNode.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
    } else {
        rowNode.removeAttribute('aria-expanded');
    }
}

function openUserDataTableChildRow(tableEl, dataTable, rowApi, mode, shouldPersistState) {
    if (!tableEl || !dataTable || !rowApi || !isUserDataTableCompactMode(mode)) {
        return;
    }

    var rowNode = rowApi.node();
    var rowId = getUserDataTableRowId(rowNode);
    var childMarkup = formatUserDataTableChildRow(rowNode, mode);

    if (!rowNode || !rowId || childMarkup === '') {
        return;
    }

    rowApi.child(childMarkup, 'sigap-user-dt-child-row').show();
    setUserDataTableRowExpandedState(rowNode, true);

    if (shouldPersistState !== false) {
        addUserDataTableExpandedRow(tableEl, rowId);

        if (dataTable.state && typeof dataTable.state.save === 'function') {
            dataTable.state.save();
        }
    }
}

function closeUserDataTableChildRow(tableEl, dataTable, rowApi, shouldPersistState) {
    if (!tableEl || !dataTable || !rowApi) {
        return;
    }

    var rowNode = rowApi.node();
    var rowId = getUserDataTableRowId(rowNode);

    if (rowApi.child && typeof rowApi.child.isShown === 'function' && rowApi.child.isShown()) {
        rowApi.child.hide();
    }

    setUserDataTableRowExpandedState(rowNode, false);

    if (shouldPersistState !== false && rowId !== '') {
        removeUserDataTableExpandedRow(tableEl, rowId);

        if (dataTable.state && typeof dataTable.state.save === 'function') {
            dataTable.state.save();
        }
    }
}

function syncUserDataTableRowAccessibility(dataTable, mode) {
    if (!dataTable) {
        return;
    }

    var isCompactMode = isUserDataTableCompactMode(mode);

    dataTable.rows({ page: 'current' }).every(function () {
        var rowNode = this.node();

        if (!rowNode) {
            return;
        }

        rowNode.classList.toggle('is-user-dt-compact-row', isCompactMode);

        if (isCompactMode) {
            rowNode.setAttribute('tabindex', '0');
            rowNode.setAttribute('data-user-dt-compact-row', 'true');
            rowNode.setAttribute(
                'aria-expanded',
                this.child && typeof this.child.isShown === 'function' && this.child.isShown() ? 'true' : 'false'
            );
        } else {
            rowNode.removeAttribute('tabindex');
            rowNode.removeAttribute('aria-expanded');
            rowNode.removeAttribute('data-user-dt-compact-row');
            rowNode.classList.remove('is-user-dt-child-open');
        }
    });
}

function restoreUserDataTableExpandedRows(tableEl, dataTable, mode) {
    if (!tableEl || !dataTable) {
        return;
    }

    if (!isUserDataTableCompactMode(mode)) {
        dataTable.rows().every(function () {
            closeUserDataTableChildRow(tableEl, dataTable, this, false);
        });

        return;
    }

    var expandedRows = getUserDataTableExpandedRows(tableEl);

    dataTable.rows({ page: 'current' }).every(function () {
        var rowNode = this.node();
        var rowId = getUserDataTableRowId(rowNode);

        if (!rowId) {
            return;
        }

        if (expandedRows.indexOf(rowId) !== -1) {
            openUserDataTableChildRow(tableEl, dataTable, this, mode, false);
        } else {
            closeUserDataTableChildRow(tableEl, dataTable, this, false);
        }
    });
}

function applyUserDataTableModeClasses(tableEl, mode) {
    if (!tableEl) {
        return;
    }

    var wrapper = tableEl.closest('.dataTables_wrapper');
    var container = tableEl.closest('.datatables');
    var modeClasses = [
        'sigap-user-dt-mode-desktop',
        'sigap-user-dt-mode-tablet',
        'sigap-user-dt-mode-mobile',
        'sigap-user-dt-is-compact'
    ];

    [tableEl, wrapper, container].forEach(function (element) {
        if (!element || !element.classList) {
            return;
        }

        modeClasses.forEach(function (className) {
            element.classList.remove(className);
        });

        element.classList.add('sigap-user-dt-mode-' + mode);
        element.classList.toggle('sigap-user-dt-is-compact', isUserDataTableCompactMode(mode));
    });
}

function bindUserDataTableCompactRows(tableEl, dataTable, mode) {
    if (!tableEl || !dataTable || typeof window.jQuery === 'undefined') {
        return;
    }

    var $table = window.jQuery(tableEl);
    var isCompactMode = isUserDataTableCompactMode(mode);

    $table.off('.sigapCompactRows');

    if (!isCompactMode) {
        syncUserDataTableRowAccessibility(dataTable, mode);
        restoreUserDataTableExpandedRows(tableEl, dataTable, mode);
        return;
    }

    function shouldIgnoreCompactToggle(target) {
        if (!target || !target.closest) {
            return false;
        }

        return Boolean(
            target.closest('.admin-table-action-cell')
            || target.closest('.admin-table-action-toggle')
            || target.closest('.admin-table-action-menu')
            || target.closest('.admin-table-action-item')
            || target.closest('.admin-table-action-form')
            || target.closest('a, button, input, select, textarea, label, form')
        );
    }

    function toggleCompactRow(rowNode) {
        if (!rowNode) {
            return;
        }

        var rowApi = dataTable.row(rowNode);

        if (!rowApi || !rowApi.node()) {
            return;
        }

        if (rowApi.child && typeof rowApi.child.isShown === 'function' && rowApi.child.isShown()) {
            closeUserDataTableChildRow(tableEl, dataTable, rowApi, true);
            return;
        }

        openUserDataTableChildRow(tableEl, dataTable, rowApi, mode, true);
    }

    $table.on('click.sigapCompactRows', 'tbody tr', function (event) {
        if (this.classList.contains('child') || shouldIgnoreCompactToggle(event.target)) {
            return;
        }

        toggleCompactRow(this);
    });

    $table.on('keydown.sigapCompactRows', 'tbody tr', function (event) {
        if (event.key !== 'Enter' && event.key !== ' ') {
            return;
        }

        if (this.classList.contains('child') || shouldIgnoreCompactToggle(event.target)) {
            return;
        }

        event.preventDefault();
        toggleCompactRow(this);
    });

    $table.on('draw.dt.sigapCompactRows', function () {
        syncUserDataTableRowAccessibility(dataTable, mode);
        restoreUserDataTableExpandedRows(tableEl, dataTable, mode);
    });

    syncUserDataTableRowAccessibility(dataTable, mode);
    restoreUserDataTableExpandedRows(tableEl, dataTable, mode);
}

function syncUserDataTableForViewport(tableEl, shouldAdjustColumns) {
    if (!tableEl) {
        return;
    }

    var currentMode = getUserDataTableMode(tableEl);
    var activeMode = String(tableEl.__sigapDataTableMode || '').trim();

    if (activeMode && activeMode !== currentMode) {
        initializeUserDataTable();
        return;
    }

    scheduleUserDataTableWidthSync(tableEl, shouldAdjustColumns);
}

function bindUserDataTableWidthSync(tableEl) {
    if (!tableEl || tableEl.dataset.widthSyncBound === 'true') {
        return;
    }

    tableEl.dataset.widthSyncBound = 'true';

    if (typeof window.jQuery !== 'undefined') {
        window.jQuery(tableEl).on('draw.dt column-sizing.dt order.dt search.dt page.dt length.dt', function () {
            scheduleUserDataTableWidthSync(tableEl);
        });
    }

    if (typeof window.ResizeObserver === 'function') {
        var observedContainer = tableEl.closest('.datatables, .table-responsive, .card, .container-fluid')
            || tableEl.parentElement
            || tableEl;

        var resizeObserver = new window.ResizeObserver(function () {
            scheduleUserDataTableWidthSync(tableEl, true);
        });

        resizeObserver.observe(observedContainer);
        tableEl.__sigapWidthSyncObserver = resizeObserver;
    }
}

function refreshUserDataTableLayout(tableEl, shouldAdjustColumns) {
    if (!tableEl) {
        return;
    }

    if (shouldAdjustColumns) {
        var dataTable = getUserDataTableApi(tableEl);

        if (dataTable && dataTable.columns && typeof dataTable.columns.adjust === 'function') {
            dataTable.columns.adjust();
        }
    }

    syncUserDataTableWidth(tableEl);
}

function syncUserDataTableWidth(tableEl) {
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

function scheduleUserDataTableWidthSync(tableEl, shouldAdjustColumns) {
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

        refreshUserDataTableLayout(tableEl, needsColumnAdjust);
    });
}

function initializeUserDataTable() {
    var tableEl = getUserDataTableElement();
    if (!tableEl || typeof window.jQuery === 'undefined' || !window.jQuery.fn || !window.jQuery.fn.DataTable) {
        return null;
    }

    var $table = window.jQuery(tableEl);
    var mode = getUserDataTableMode(tableEl);
    var previousMode = String(tableEl.__sigapDataTableMode || '').trim();

    if (window.jQuery.fn.dataTable.isDataTable(tableEl) && previousMode && previousMode !== mode) {
        var existingTable = $table.DataTable();

        $table.off('.sigapCompactRows');
        restoreUserDataTableExpandedRows(tableEl, existingTable, 'desktop');
        existingTable.destroy();
    }

    if (window.jQuery.fn.dataTable.isDataTable(tableEl)) {
        var currentTable = $table.DataTable();

        tableEl.__sigapDataTableMode = mode;
        applyUserDataTableModeClasses(tableEl, mode);
        bindUserDataTableCompactRows(tableEl, currentTable, mode);
        bindUserDataTableWidthSync(tableEl);
        syncUserDataTableForViewport(tableEl, true);

        return currentTable;
    }

    if (!tableEl.__sigapNormalizedEmptyMessage) {
        tableEl.__sigapNormalizedEmptyMessage = normalizeUserDataTableEmptyState(tableEl);
    }

    var emptyMessage = tableEl.__sigapNormalizedEmptyMessage || getUserDataTableEmptyMessage(tableEl);
    var columnVisibility = getUserDataTableColumnVisibility(mode);
    var dataTable = $table.DataTable({
        stateSave: true,
        autoWidth: false,
        scrollX: getUserDataTableScrollX(tableEl, mode),
        pagingType: 'full_numbers',
        columnDefs: [
            {
                targets: 8,
                orderable: false,
                searchable: false
            }
        ].concat(columnVisibility.map(function (isVisible, index) {
            return {
                targets: index,
                visible: isVisible
            };
        })),
        stateLoadParams: function (settings, data) {
            tableEl.__sigapExpandedRowIds = normalizeUserDataTableExpandedRows(
                data && Array.isArray(data.sigapExpandedRows) ? data.sigapExpandedRows : []
            );

            if (!data || !Array.isArray(data.columns)) {
                return;
            }

            data.columns.forEach(function (columnState, index) {
                if (!columnState) {
                    return;
                }

                columnState.visible = columnVisibility[index] !== false;
            });
        },
        stateSaveParams: function (settings, data) {
            data.sigapExpandedRows = getUserDataTableExpandedRows(tableEl);
            data.sigapViewportMode = mode;

            if (!Array.isArray(data.columns)) {
                return;
            }

            data.columns.forEach(function (columnState, index) {
                if (!columnState) {
                    return;
                }

                columnState.visible = columnVisibility[index] !== false;
            });
        },
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

    tableEl.__sigapDataTableMode = mode;
    applyUserDataTableModeClasses(tableEl, mode);
    bindUserDataTableCompactRows(tableEl, dataTable, mode);
    bindUserDataTableWidthSync(tableEl);
    syncUserDataTableForViewport(tableEl, true);

    return dataTable;
}

function initUserReservationPage() {
    var form = document.getElementById('userReservationForm');
    var configEl = document.getElementById('user-reservation-config');
    var isEditMode = form
        ? String(form.dataset.reservationMode || '').toLowerCase() === 'edit'
        : false;
    var reservationStatusKey = form
        ? String(form.dataset.reservationStatusKey || 'RESERVASI BARU').trim().toUpperCase()
        : 'RESERVASI BARU';
    var isFormInitiallyVisible = form
        ? String(form.dataset.initialFormVisible || '0') === '1'
        : false;
    var requiresRequestFile = form
        ? String(form.dataset.requiresRequestFile || '0') !== '0'
        : false;
    var requiresFreshRequestFile = form
        ? String(form.dataset.requiresFreshRequestFile || '0') === '1'
        : false;
    var requiresIdFile = form
        ? String(form.dataset.requiresIdFile || '1') !== '0'
        : false;
    var historyModalThemeClasses = [
        'admin-reservation-theme-warning',
        'admin-reservation-theme-info',
        'admin-reservation-theme-danger',
        'admin-reservation-theme-success',
        'admin-reservation-theme-primary',
        'admin-reservation-theme-secondary'
    ];

    var embeddedConfig = getUserReservationConfig() || {};
    window.sigapCalendarData = window.sigapCalendarData || {};
    window.sigapCalendarData.user = Object.assign(
        {},
        window.sigapCalendarData.user || {},
        embeddedConfig
    );

    var config = window.sigapCalendarData.user || {};
    var reservation = config.reservation || {};
    var filterData = Array.isArray(config.filterData) ? config.filterData : [];
    var historyReservations = Array.isArray(config.historyReservations) ? config.historyReservations : [];
    var minBookingDate = config.minBookingDate ? new Date(String(config.minBookingDate).slice(0, 10) + 'T00:00:00') : null;
    var historyReservationMap = {};

    historyReservations.forEach(function (item) {
        if (!item || typeof item !== 'object') return;
        historyReservationMap[String(item.id || '')] = item;
    });

    var elements = {
        form: form,
        region: document.getElementById('filterWilayahUser'),
        district: document.getElementById('filterKecamatanUser'),
        building: document.getElementById('filterGedungUser'),
        buildingInput: reservation.buildingInputId ? document.getElementById(reservation.buildingInputId) : document.getElementById('reservation-building-id'),
        buildingDisplay: reservation.buildingDisplayId ? document.getElementById(reservation.buildingDisplayId) : document.getElementById('reservation-building-display'),
        startInput: reservation.startInputId ? document.getElementById(reservation.startInputId) : document.getElementById('reservation-start-date'),
        dateDisplay: reservation.dateDisplayId ? document.getElementById(reservation.dateDisplayId) : document.getElementById('reservation-date-display'),
        selectedDateText: reservation.selectedDateTextId ? document.getElementById(reservation.selectedDateTextId) : document.getElementById('reservation-selected-date-text'),
        selectedBuildingText: reservation.selectedBuildingTextId ? document.getElementById(reservation.selectedBuildingTextId) : document.getElementById('reservation-selected-building-text'),
        selectionStatus: reservation.selectionStatusId ? document.getElementById(reservation.selectionStatusId) : document.getElementById('reservation-selection-status'),
        selectionHint: reservation.selectionHintId ? document.getElementById(reservation.selectionHintId) : document.getElementById('reservation-selection-hint'),
        event: document.getElementById('reservation-event-id'),
        session: document.getElementById('reservation-session-id'),
        umkm: reservation.umkmSelectId ? document.getElementById(reservation.umkmSelectId) : document.getElementById('reservation-umkm-id'),
        startTime: reservation.startTimeId ? document.getElementById(reservation.startTimeId) : document.getElementById('reservation-start-time'),
        endTime: reservation.endTimeId ? document.getElementById(reservation.endTimeId) : document.getElementById('reservation-end-time'),
        customTimeGroup: reservation.customTimeGroupId ? document.getElementById(reservation.customTimeGroupId) : document.getElementById('reservation-custom-time-group'),
        estPerson: document.getElementById('reservation-est-person'),
        estPersonFeedback: document.getElementById('reservation-est-person-feedback'),
        requestFile: reservation.requestFileId ? document.getElementById(reservation.requestFileId) : document.getElementById('reservation-request-file'),
        requestFilePreview: document.getElementById('reservation-request-file-preview'),
        idFile: document.getElementById('reservation-id-file'),
        idFilePreview: document.getElementById('reservation-id-file-preview'),
        openButton: reservation.openButtonId ? document.getElementById(reservation.openButtonId) : document.getElementById('user-reservation-open-button'),
        printButton: reservation.printButtonId ? document.getElementById(reservation.printButtonId) : document.getElementById('reservation-print-button'),
        detailRow: reservation.detailRowId ? document.getElementById(reservation.detailRowId) : document.getElementById('user-reservation-detail-row'),
        formColumn: reservation.formColumnId ? document.getElementById(reservation.formColumnId) : document.getElementById('user-reservation-form-column'),
        summaryColumn: reservation.summaryColumnId ? document.getElementById(reservation.summaryColumnId) : document.getElementById('user-reservation-summary-column'),
        historyDetailModal: getLatestElementById('reservationHistoryDetailModal', document.getElementById('main-content') || document.body || document),
        paymentVaModal: getLatestElementById('reservationPaymentVaModal', document.getElementById('main-content') || document.body || document),
        paymentQrisModal: getLatestElementById('reservationPaymentQrisModal', document.getElementById('main-content') || document.body || document),
        submitButton: form ? form.querySelector('button[type="submit"]') : null
    };

    function buildReservationSwalButtonClass(tone) {
        var normalizedTone = String(tone || 'primary').trim().toLowerCase() || 'primary';

        return 'btn reservation-modal-action-btn btn-' + normalizedTone;
    }

    function buildReservationSwalOptions(options, tones) {
        var toneMap = tones || {};
        var mergedOptions = Object.assign({}, options || {});
        var customClass = Object.assign({}, mergedOptions.customClass || {});

        customClass.actions = [customClass.actions, 'reservation-modal-swal-actions'].filter(Boolean).join(' ');
        customClass.confirmButton = [customClass.confirmButton, buildReservationSwalButtonClass(toneMap.confirm || 'primary')].filter(Boolean).join(' ');
        customClass.cancelButton = [customClass.cancelButton, buildReservationSwalButtonClass(toneMap.cancel || 'secondary')].filter(Boolean).join(' ');
        customClass.denyButton = [customClass.denyButton, buildReservationSwalButtonClass(toneMap.deny || 'success')].filter(Boolean).join(' ');

        mergedOptions.buttonsStyling = false;
        mergedOptions.customClass = customClass;

        return mergedOptions;
    }

    function getReservationAlertTone(icon) {
        switch (String(icon || '').trim().toLowerCase()) {
            case 'success':
                return 'success';
            case 'warning':
                return 'warning';
            case 'error':
                return 'danger';
            default:
                return 'primary';
        }
    }

    function showReservationAlert(icon, title, text) {
        if (typeof Swal !== 'undefined') {
            Swal.fire(buildReservationSwalOptions({
                icon: icon,
                title: title,
                html: text,
                confirmButtonText: 'OK',
                timer: 3000,
                timerProgressBar: true
            }, {
                confirm: getReservationAlertTone(icon)
            }));
        } else {
            alert(text || title);
        }
    }

    function showReservationSubmitConfirmation(onConfirmed) {
        var isUmkmFollowUpMode = reservationStatusKey === 'KERJASAMA UMKM' || reservationStatusKey === 'BERKAS VERIFIKASI TIDAK SESUAI';
        var confirmationTitle = isUmkmFollowUpMode
            ? '<b>KONFIRMASI BUKTI UMKM</b>'
            : (isEditMode ? '<b>KONFIRMASI PERUBAHAN</b>' : '<b>KONFIRMASI RESERVASI</b>');
        var confirmationText = isUmkmFollowUpMode
            ? 'Bukti Kerjasama UMKM yang diunggah sudah sesuai?'
            : (isEditMode
                ? 'Apakah data reservasi yang dirubah sudah sesuai?'
                : 'Apakah data reservasi yang diisi sudah sesuai?');
        var readyTitle = isUmkmFollowUpMode
            ? '<b>BUKTI KERJASAMA UMKM DIKIRIM</b>'
            : (isEditMode ? '<b>PERUBAHAN RESERVASI DIKIRIM</b>' : '<b>RESERVASI DIKIRIM</b>');
        var readyText = isUmkmFollowUpMode
            ? 'Bukti Kerjasama UMKM sedang dikirim. Mohon tunggu sebentar.'
            : (isEditMode ? 'Perubahan reservasi sedang dikirim. Mohon tunggu sebentar.' : 'Pengajuan reservasi sedang dikirim. Mohon tunggu sebentar.');

        if (typeof Swal !== 'undefined') {
            Swal.fire(buildReservationSwalOptions({
                icon: 'info',
                title: confirmationTitle,
                text: confirmationText,
                showCancelButton: true,
                confirmButtonText: 'SESUAI',
                cancelButtonText: 'KEMBALI',
                reverseButtons: true
            }, {
                confirm: 'success',
                cancel: 'danger'
            })).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }

                Swal.fire(buildReservationSwalOptions({
                    timer: 900,
                    timerProgressBar: true,
                    icon: 'success',
                    title: readyTitle,
                    text: readyText,
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }, {
                    confirm: 'success'
                })).then(function () {
                    onConfirmed();
                });
            });
            return;
        }

        if (!window.confirm(confirmationText)) {
            return;
        }

        alert(readyText);
        onConfirmed();
    }

    function getNormalizedHistoryStatusKey(status) {
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

    function shouldUseHistoryOrderCode(status) {
        return ['MENUNGGU PEMBAYARAN', 'CEK PEMBAYARAN', 'BERKAS PEMBAYARAN TIDAK SESUAI', 'PEMBAYARAN LUNAS', 'ACARA SELESAI']
            .indexOf(getNormalizedHistoryStatusKey(status)) !== -1;
    }

    function getHistoryStatusMeta(status) {
        var normalizedStatus = getNormalizedHistoryStatusKey(status);
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

        return map[normalizedStatus] || {
            text: normalizedStatus || 'STATUS',
            badgeClass: 'bg-secondary-subtle text-secondary',
            themeClass: 'admin-reservation-theme-secondary'
        };
    }

    function getHistoryReservationDocumentStage(item) {
        var normalizedStatus = getNormalizedHistoryStatusKey(item && item.status ? item.status : '');
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

    function applyHistoryReservationModalTheme(modalEl, statusMeta) {
        if (!modalEl) {
            return;
        }

        historyModalThemeClasses.forEach(function (className) {
            modalEl.classList.remove(className);
        });

        if (statusMeta && statusMeta.themeClass) {
            modalEl.classList.add(statusMeta.themeClass);
        }
    }

    function getReservationLocationText(item) {
        var district = String(item && item.district ? item.district : '').trim();
        var region = String(item && item.region ? item.region : '').trim();

        if (district && region) {
            return district + ' - Surabaya ' + region;
        }

        if (district) {
            return district;
        }

        if (region) {
            return 'Surabaya ' + region;
        }

        return '-';
    }

    function getReservationPriceBreakdown(item) {
        var hourCount = Number(item && item.hour_count ? item.hour_count : 0);
        var hourPrice = Number(item && item.perhour_price ? item.perhour_price : 0);

        if (hourCount <= 0) {
            return '-';
        }

        return hourCount + ' jam x ' + formatReservationCurrency(hourPrice);
    }

    function normalizeHistoryReservationFileType(fileType) {
        return String(fileType || '').trim().toLowerCase();
    }

    function getHistoryReservationFileTypeFromUrl(fileUrl) {
        var normalizedUrl = String(fileUrl || '').trim();
        if (!normalizedUrl) {
            return '';
        }

        var cleanUrl = normalizedUrl.split('#')[0].split('?')[0];
        var dotIndex = cleanUrl.lastIndexOf('.');
        if (dotIndex === -1) {
            return '';
        }

        return normalizeHistoryReservationFileType(cleanUrl.slice(dotIndex + 1));
    }

    function createHistoryReservationDocumentLink(fileUrl, label, galleryTitle) {
        var fileType = getHistoryReservationFileTypeFromUrl(fileUrl);
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

    function setHistoryReservationDocumentSlot(modalEl, id, fileUrl, availableLabel, missingLabel, galleryTitle) {
        var container = modalEl ? modalEl.querySelector('[id="' + id + '"]') : null;
        if (!container) {
            return;
        }

        container.innerHTML = '';

        var normalizedUrl = String(fileUrl || '').trim();
        if (normalizedUrl) {
            container.appendChild(
                createHistoryReservationDocumentLink(
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

    function setHistoryReservationDocumentSlotVisibility(modalEl, wrapperId, shouldShow) {
        var wrapper = modalEl ? modalEl.querySelector('[id="' + wrapperId + '"]') : null;
        if (!wrapper) {
            return;
        }

        wrapper.hidden = !shouldShow;
    }

    function getReservationDetailCodeLabel(item) {
        var status = String(item && item.status ? item.status : '').trim().toUpperCase();
        var requestCode = String(item && item.request_id ? item.request_id : '').trim();
        var orderCode = String(item && item.order_id ? item.order_id : '').trim();
        var useOrderCode = shouldUseHistoryOrderCode(status);
        var code = useOrderCode ? orderCode : requestCode;

        if (!code) {
            code = requestCode || orderCode || String(item && item.id ? item.id : '-').trim() || '-';
        }

        return 'Kode : ' + code;
    }

    function showHistoryDetailModal(reservationId) {
        var item = historyReservationMap[String(reservationId || '')];
        var modalEl = getLatestElementById('reservationHistoryDetailModal', document.body || document);
        elements.historyDetailModal = modalEl;
        if (!item || !modalEl) return;

        if (modalEl.parentElement !== document.body) {
            document.body.appendChild(modalEl);
        }

        var statusMeta = getHistoryStatusMeta(item.status);
        var startDate = String(item.start_date || '').trim();
        var endDate = String(item.end_date || '').trim();
        var requesterName = String(item.user_name || '').trim() || String(item.username || '-').trim() || '-';
        var dateLabel = startDate && endDate && startDate !== endDate
            ? formatReservationDate(startDate) + ' s/d ' + formatReservationDate(endDate)
            : formatReservationDate(startDate || endDate);

        function setModalText(id, value) {
            var element = modalEl.querySelector('[id="' + id + '"]');
            if (element) {
                element.textContent = value;
            }
        }

        var detailFields = {
            statusBadge: modalEl.querySelector('[id="historyDetailStatusBadge"]')
        };

        setModalText('historyDetailReservationCode', getReservationDetailCodeLabel(item));
        setModalText('historyDetailRequester', requesterName);
        setModalText('historyDetailUserAddress', String(item.user_address || '-').trim() || '-');
        setModalText('historyDetailPhone', String(item.user_phone || '-').trim() || '-');
        setModalText('historyDetailNik', String(item.user_nik || '-').trim() || '-');
        setModalText('historyDetailBuilding', String(item.building_name || '-').trim() || '-');
        setModalText(
            'historyDetailBuildingAddress',
            String(item.building_address || getReservationLocationText(item) || '-').trim() || '-'
        );
        setModalText('historyDetailDate', dateLabel || '-');
        setModalText('historyDetailSession', item.session_display_name || item.session_name || '-');
        setModalText('historyDetailEvent', item.event_name || '-');
        setModalText('historyDetailEstPerson', item.est_person ? String(item.est_person) + ' orang' : '-');
        setModalText('historyDetailTotalPrice', formatReservationCurrency(item.total_price || 0));
        setModalText('historyDetailUmkm', String(item.umkm_name || '-').trim() || '-');
        setModalText('historyDetailNotes', String(item.notes || '').trim() !== '' ? String(item.notes) : '-');
        setHistoryReservationDocumentSlot(
            modalEl,
            'historyDetailKtpFile',
            item.identity_file_url,
            'Lihat KTP',
            'Belum diunggah',
            'KTP Pemohon'
        );
        setHistoryReservationDocumentSlot(
            modalEl,
            'historyDetailApplicationFile',
            item.application_file_url,
            'Lihat Permohonan',
            'Belum tersedia di sistem',
            'File Permohonan'
        );
        setHistoryReservationDocumentSlot(
            modalEl,
            'historyDetailUmkmFile',
            item.umkm_file_url,
            'Lihat Kerjasama UMKM',
            'Belum tersedia di sistem',
            'Bukti Kerjasama UMKM'
        );
        setHistoryReservationDocumentSlot(
            modalEl,
            'historyDetailPaymentFile',
            item.payment_file_url,
            'Lihat Bukti Bayar',
            'Belum tersedia di sistem',
            'Bukti Pembayaran'
        );
        var documentStage = getHistoryReservationDocumentStage(item);
        setHistoryReservationDocumentSlotVisibility(
            modalEl,
            'historyDetailUmkmFileWrapper',
            documentStage !== 'RESERVATION' || String(item.umkm_file_url || '').trim() !== ''
        );
        setHistoryReservationDocumentSlotVisibility(
            modalEl,
            'historyDetailPaymentFileWrapper',
            documentStage === 'PAYMENT' || String(item.payment_file_url || '').trim() !== ''
        );

        if (detailFields.statusBadge) {
            detailFields.statusBadge.className = 'badge admin-reservation-detail-status-badge ' + statusMeta.badgeClass;
            detailFields.statusBadge.innerHTML = statusMeta.text;
        }

        applyHistoryReservationModalTheme(modalEl, statusMeta);

        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    }

    function resetSelect(selectEl, placeholder) {
        if (!selectEl) return;

        selectEl.innerHTML = '<option value="">' + placeholder + '</option>';
        selectEl.disabled = true;
    }

    function setFieldState(field, isValid, shouldMark) {
        if (!field) return;

        field.classList.remove('is-valid', 'is-invalid');

        if (!shouldMark) return;

        field.classList.add(isValid ? 'is-valid' : 'is-invalid');
    }

    function normalizeReservationTimeValue(value) {
        var normalized = String(value || '').trim();

        if (!normalized) {
            return '';
        }

        return normalized.length === 5 ? normalized + ':00' : normalized;
    }

    function getReservationTimeMinutes(value) {
        var normalized = normalizeReservationTimeValue(value);
        var parts = normalized.split(':');

        if (parts.length < 2) {
            return NaN;
        }

        var hours = Number(parts[0]);
        var minutes = Number(parts[1]);

        if (!Number.isFinite(hours) || !Number.isFinite(minutes)) {
            return NaN;
        }

        return (hours * 60) + minutes;
    }

    function isValidReservationTimeRange(startValue, endValue) {
        var startMinutes = getReservationTimeMinutes(startValue);
        var endMinutes = getReservationTimeMinutes(endValue);

        if (!Number.isFinite(startMinutes) || !Number.isFinite(endMinutes)) {
            return false;
        }

        return endMinutes > startMinutes;
    }

    function getSelectedSessionMeta() {
        if (!elements.session) {
            return {
                value: '',
                startTime: '',
                endTime: '',
                isCustom: false
            };
        }

        var option = elements.session.options[elements.session.selectedIndex];
        if (!option) {
            return {
                value: '',
                startTime: '',
                endTime: '',
                isCustom: false
            };
        }

        return {
            value: String(option.value || '').trim(),
            startTime: String(option.getAttribute('data-start-time') || '').trim(),
            endTime: String(option.getAttribute('data-end-time') || '').trim(),
            isCustom: String(option.getAttribute('data-is-custom') || '0') === '1'
        };
    }

    function toggleCustomTimeFields(isVisible) {
        if (!elements.customTimeGroup) {
            return;
        }

        elements.customTimeGroup.classList.toggle('d-none', !isVisible);
    }

    function syncSessionTimeFields() {
        var sessionMeta = getSelectedSessionMeta();
        var hasSelection = sessionMeta.value !== '';
        var isCustom = hasSelection && sessionMeta.isCustom;

        toggleCustomTimeFields(isCustom);

        if (!elements.startTime || !elements.endTime) {
            return;
        }

        if (!hasSelection) {
            elements.startTime.value = '';
            elements.endTime.value = '';
            return;
        }

        if (!isCustom) {
            elements.startTime.value = sessionMeta.startTime;
            elements.endTime.value = sessionMeta.endTime;
        }
    }

    function setReservationFormVisibility(isVisible) {
        if (elements.detailRow) {
            elements.detailRow.classList.toggle('d-none', !isVisible);
        }

        if (elements.openButton) {
            elements.openButton.setAttribute('aria-expanded', isVisible ? 'true' : 'false');
        }
    }

    function openReservationFormPanel(shouldScroll) {
        setReservationFormVisibility(true);

        if (shouldScroll && elements.detailRow && typeof elements.detailRow.scrollIntoView === 'function') {
            elements.detailRow.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    }

    function filterUmkmOptions() {
        if (!elements.umkm) {
            return;
        }

        var buildingValue = elements.buildingInput ? String(elements.buildingInput.value || '').trim() : '';
        var selectedValue = String(elements.umkm.value || '').trim();
        var hasVisibleSelectedOption = false;

        Array.from(elements.umkm.options || []).forEach(function (option, index) {
            if (index === 0) {
                option.hidden = false;
                option.disabled = false;
                return;
            }

            var allowedBuildingIds = String(option.getAttribute('data-building-ids') || '')
                .split(',')
                .map(function (value) {
                    return String(value || '').trim();
                })
                .filter(Boolean);
            var optionValue = String(option.value || '').trim();
            var isSelectedOption = selectedValue !== '' && optionValue === selectedValue;
            var isVisible = buildingValue === ''
                ? isSelectedOption
                : allowedBuildingIds.indexOf(buildingValue) !== -1;

            option.hidden = !isVisible;
            option.disabled = !isVisible;

            if (isVisible && isSelectedOption) {
                hasVisibleSelectedOption = true;
            }
        });

        elements.umkm.disabled = buildingValue === '';

        if (buildingValue !== '' && !hasVisibleSelectedOption && selectedValue !== '') {
            elements.umkm.value = '';
        }
    }

    function hasAvailableUmkmOptions() {
        if (!elements.umkm) {
            return false;
        }

        return Array.from(elements.umkm.options || []).some(function (option, index) {
            if (index === 0) {
                return false;
            }

            return !option.hidden && !option.disabled && String(option.value || '').trim() !== '';
        });
    }

    function getUploadPreviewTarget(input) {
        if (!input) {
            return null;
        }

        var targetId = String(input.getAttribute('data-preview-target') || '').trim();
        return targetId !== '' ? document.getElementById(targetId) : null;
    }

    function getUploadFileExtension(filename) {
        var normalized = String(filename || '').trim().toLowerCase();
        var match = normalized.match(/\.([a-z0-9]+)$/i);
        return match ? String(match[1] || '').toLowerCase() : '';
    }

    function isUploadPreviewImage(extension) {
        return ['jpg', 'jpeg', 'png'].indexOf(String(extension || '').toLowerCase()) !== -1;
    }

    function getUploadTypeLabel(extension) {
        var normalized = String(extension || '').trim().toLowerCase();
        if (!normalized) {
            return 'FILE';
        }

        return normalized.toUpperCase();
    }

    function getExistingUploadMeta(input) {
        if (!input) {
            return {
                url: '',
                name: '',
                extension: ''
            };
        }

        return {
            url: String(input.getAttribute('data-existing-file-url') || '').trim(),
            name: String(input.getAttribute('data-existing-file-name') || '').trim(),
            extension: String(input.getAttribute('data-existing-file-extension') || '').trim().toLowerCase()
        };
    }

    function clearUploadPreviewObjectUrl(preview) {
        if (!preview || typeof URL === 'undefined' || typeof URL.revokeObjectURL !== 'function') {
            return;
        }

        var currentObjectUrl = String(preview.dataset.objectUrl || '').trim();
        if (currentObjectUrl === '') {
            return;
        }

        URL.revokeObjectURL(currentObjectUrl);
        preview.dataset.objectUrl = '';
    }

    function buildUploadPreviewState(input) {
        var emptyName = 'Unggah JPG, JPEG, PNG, atau PDF';
        var emptyStatus = 'Belum ada file';
        var file = input && input.files && input.files[0] ? input.files[0] : null;

        if (file) {
            var currentExtension = getUploadFileExtension(file.name);
            var previewUrl = '';

            if (typeof URL !== 'undefined' && typeof URL.createObjectURL === 'function') {
                previewUrl = URL.createObjectURL(file);
            }

            return {
                ready: true,
                name: file.name,
                status: 'File siap diunggah',
                url: previewUrl,
                extension: currentExtension,
                typeLabel: getUploadTypeLabel(currentExtension),
                isImage: isUploadPreviewImage(currentExtension),
                isObjectUrl: previewUrl !== ''
            };
        }

        var existingFile = getExistingUploadMeta(input);
        if (existingFile.url !== '') {
            return {
                ready: true,
                name: existingFile.name || emptyName,
                status: 'File sudah diunggah',
                url: existingFile.url,
                extension: existingFile.extension,
                typeLabel: getUploadTypeLabel(existingFile.extension),
                isImage: isUploadPreviewImage(existingFile.extension),
                isObjectUrl: false
            };
        }

        return {
            ready: false,
            name: emptyName,
            status: emptyStatus,
            url: '',
            extension: '',
            typeLabel: 'FILE',
            isImage: false,
            isObjectUrl: false
        };
    }

    function syncUploadPreview(input) {
        var preview = getUploadPreviewTarget(input);
        if (!preview) {
            return;
        }

        var previewState = buildUploadPreviewState(input);
        var media = preview.querySelector('[data-upload-preview-media]');
        var image = preview.querySelector('[data-upload-preview-image]');
        var icon = preview.querySelector('[data-upload-preview-icon]');
        var status = preview.querySelector('[data-upload-preview-status]');
        var name = preview.querySelector('[data-upload-preview-name]');
        var link = preview.querySelector('[data-upload-preview-link]');
        var check = preview.querySelector('.reservation-upload-preview-check');

        clearUploadPreviewObjectUrl(preview);

        preview.classList.toggle('is-ready', previewState.ready);
        preview.dataset.fileType = String(previewState.extension || '').toLowerCase();

        if (media) {
            media.classList.remove('has-image');
            media.classList.toggle('image-popup-vertical-fit', previewState.ready && previewState.isImage);
            if (previewState.url !== '') {
                media.href = previewState.url;
                media.dataset.galleryTrigger = 'reservation-file';
                media.dataset.galleryTitle = preview.dataset.galleryTitle || previewState.name || preview.dataset.emptyName || 'Preview file';
                media.dataset.fileType = String(previewState.extension || '').toLowerCase();
                media.hidden = false;
                media.removeAttribute('aria-disabled');
                media.removeAttribute('tabindex');
            } else {
                media.removeAttribute('href');
                media.removeAttribute('data-gallery-trigger');
                media.removeAttribute('data-gallery-title');
                media.removeAttribute('data-file-type');
                media.setAttribute('aria-disabled', 'true');
                media.setAttribute('tabindex', '-1');
            }
        }

        if (image) {
            image.hidden = true;
            image.removeAttribute('src');
        }

        if (icon) {
            icon.hidden = false;
            icon.textContent = previewState.typeLabel;
        }

        if (status) {
            status.textContent = previewState.status || preview.dataset.emptyStatus || 'Belum ada file';
        }

        if (name) {
            name.hidden = true;
            name.textContent = previewState.name || preview.dataset.emptyName || 'Unggah JPG, JPEG, PNG, atau PDF';
        }

        if (link) {
            if (previewState.url !== '') {
                link.href = previewState.url;
                link.dataset.fileType = String(previewState.extension || '').toLowerCase();
                link.hidden = false;
            } else {
                link.hidden = true;
                link.removeAttribute('href');
                delete link.dataset.fileType;
            }
        }

        if (check) {
            check.hidden = !previewState.ready;
        }

        if (previewState.isObjectUrl) {
            preview.dataset.objectUrl = previewState.url;
        }
    }

    function syncAllUploadPreviews() {
        [elements.requestFile, elements.idFile].forEach(function (input) {
            if (!input) {
                return;
            }

            syncUploadPreview(input);
        });
    }

    function getSelectedOptionText(selectEl, placeholder) {
        if (!selectEl) {
            return placeholder;
        }

        var option = selectEl.options[selectEl.selectedIndex];
        var value = option ? String(option.value || '').trim() : '';

        return value !== '' ? String(option.textContent || '').trim() : placeholder;
    }

    function appendReservationPrintField(formEl, name, value) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = String(value == null ? '' : value);
        formEl.appendChild(input);
    }

    function printReservationApplication() {
        var printUrl = reservation.printUrl ? String(reservation.printUrl).trim() : '';
        var csrfInput = elements.form ? elements.form.querySelector('input[name="_token"]') : null;
        var buildingValue = elements.buildingInput ? String(elements.buildingInput.value || '').trim() : '';
        var dateValue = elements.startInput ? String(elements.startInput.value || '').trim() : '';
        var eventValue = elements.event ? String(elements.event.value || '').trim() : '';
        var sessionMeta = getSelectedSessionMeta();
        var sessionValue = sessionMeta.value;
        var startTimeValue = elements.startTime ? String(elements.startTime.value || '').trim() : '';
        var endTimeValue = elements.endTime ? String(elements.endTime.value || '').trim() : '';
        var estPersonValue = elements.estPerson ? String(elements.estPerson.value || '').trim() : '';
        var selectedBuildingCapacity = updateEstPersonCapacityHint();
        var estPersonNumber = Number(estPersonValue);
        var isDateValid = dateValue !== '';

        if (isDateValid && minBookingDate) {
            var selectedDate = new Date(String(dateValue).slice(0, 10) + 'T00:00:00');
            isDateValid = !isNaN(selectedDate.getTime()) && selectedDate >= minBookingDate;
        }

        var isPrintReady =
            printUrl !== '' &&
            csrfInput &&
            buildingValue !== '' &&
            isDateValid &&
            eventValue !== '' &&
            sessionValue !== '' &&
            startTimeValue !== '' &&
            endTimeValue !== '' &&
            isValidReservationTimeRange(startTimeValue, endTimeValue) &&
            estPersonValue !== '' &&
            Number.isFinite(estPersonNumber) &&
            estPersonNumber > 0 &&
            (selectedBuildingCapacity <= 0 || estPersonNumber <= selectedBuildingCapacity);

        validateReservationForm(false);

        if (!isPrintReady) {
            showReservationAlert(
                'warning',
                '<b>BELUM BISA CETAK</b>',
                'Lengkapi dulu data formulirnya sebelum mencetak permohonan'
            );
            return;
        }

        var printForm = document.createElement('form');
        printForm.method = 'post';
        printForm.action = printUrl;
        printForm.target = '_blank';
        printForm.style.display = 'none';

        appendReservationPrintField(printForm, '_token', csrfInput.value);
        appendReservationPrintField(printForm, 'building_id', buildingValue);
        appendReservationPrintField(printForm, 'event_id', eventValue);
        appendReservationPrintField(printForm, 'umkm_id', elements.umkm ? String(elements.umkm.value || '').trim() : '');
        appendReservationPrintField(printForm, 'session_option', sessionValue);
        appendReservationPrintField(printForm, 'start_date', dateValue);
        appendReservationPrintField(printForm, 'end_date', dateValue);
        appendReservationPrintField(printForm, 'start_time', startTimeValue);
        appendReservationPrintField(printForm, 'end_time', endTimeValue);
        appendReservationPrintField(printForm, 'est_person', estPersonValue);

        document.body.appendChild(printForm);
        printForm.submit();
        document.body.removeChild(printForm);
    }

    function findRegion(regionValue) {
        for (var i = 0; i < filterData.length; i += 1) {
            if (String(filterData[i].region) === String(regionValue)) {
                return filterData[i];
            }
        }

        return null;
    }

    function findDistrict(regionValue, districtId) {
        var region = findRegion(regionValue);
        if (!region) return null;

        var districts = Array.isArray(region.districts) ? region.districts : [];
        for (var i = 0; i < districts.length; i += 1) {
            if (String(districts[i].id) === String(districtId)) {
                return districts[i];
            }
        }

        return null;
    }

    function getPathByBuilding(buildingId) {
        if (!buildingId) return null;

        for (var i = 0; i < filterData.length; i += 1) {
            var region = filterData[i];
            var districts = Array.isArray(region.districts) ? region.districts : [];

            for (var j = 0; j < districts.length; j += 1) {
                var district = districts[j];
                var buildings = Array.isArray(district.buildings) ? district.buildings : [];

                for (var k = 0; k < buildings.length; k += 1) {
                    if (String(buildings[k].id) === String(buildingId)) {
                        return {
                            region: region.region,
                            districtId: String(district.id),
                            buildingId: String(buildings[k].id)
                        };
                    }
                }
            }
        }

        return null;
    }

    function getSelectedDistrictName() {
        var district = findDistrict(
            elements.region ? elements.region.value : '',
            elements.district ? elements.district.value : ''
        );

        return district ? district.name || '' : '';
    }

    function getSelectedBuildingData() {
        var buildingId = elements.buildingInput ? String(elements.buildingInput.value || '').trim() : '';
        if (!buildingId) return null;

        for (var i = 0; i < filterData.length; i += 1) {
            var region = filterData[i];
            var districts = Array.isArray(region.districts) ? region.districts : [];

            for (var j = 0; j < districts.length; j += 1) {
                var district = districts[j];
                var buildings = Array.isArray(district.buildings) ? district.buildings : [];

                for (var k = 0; k < buildings.length; k += 1) {
                    if (String(buildings[k].id) === buildingId) {
                        return buildings[k];
                    }
                }
            }
        }

        return null;
    }

    function updateEstPersonCapacityHint() {
        var selectedBuilding = getSelectedBuildingData();
        var capacity = selectedBuilding ? Number(selectedBuilding.capacity || 0) : 0;

        if (elements.estPerson) {
            if (capacity > 0) {
                elements.estPerson.setAttribute('max', String(capacity));
            } else {
                elements.estPerson.removeAttribute('max');
            }
        }

        if (elements.estPersonFeedback) {
            elements.estPersonFeedback.textContent = capacity > 0
                ? 'Estimasi orang tidak boleh 0, maksimum ' + capacity.toLocaleString('id-ID') + ' orang'
                : 'Estimasi orang tidak boleh 0 dan wajib menyesuaikan kapasitas gedung';
        }

        return capacity;
    }

    function renderFilteredUserCalendar() {
        var calendar = window.calendarInstances && window.calendarInstances.user;
        if (!calendar) return;

        var regionValue = elements.region ? String(elements.region.value || '').trim() : '';
        var districtName = getSelectedDistrictName();
        var buildingValue = elements.building && !elements.building.disabled
            ? String(elements.building.value || '').trim()
            : '';
        var rawEvents = Array.isArray(window.calendarRawEvents && window.calendarRawEvents.user)
            ? window.calendarRawEvents.user
            : (Array.isArray(config.events) ? config.events : []);

        calendar.removeAllEvents();

        rawEvents.forEach(function (event) {
            if (regionValue && String(event.region) !== regionValue) return;
            if (districtName && String(event.district) !== districtName) return;
            if (buildingValue && String(event.building_id) !== buildingValue) return;
            calendar.addEvent(event);
        });

        if (typeof calendar.updateSize === 'function') {
            calendar.updateSize();
        }
    }

    function syncSelectionSummary() {
        var selectedDate = elements.startInput ? String(elements.startInput.value || '').trim() : '';
        var selectedBuildingLabel = elements.buildingDisplay ? String(elements.buildingDisplay.value || '').trim() : '';
        var hasBuilding = elements.buildingInput ? String(elements.buildingInput.value || '').trim() !== '' : false;

        if (elements.dateDisplay) {
            elements.dateDisplay.value = selectedDate ? formatReservationDate(selectedDate) : '';
        }

        if (elements.selectedDateText) {
            elements.selectedDateText.textContent = selectedDate ? formatReservationDate(selectedDate) : 'Belum dipilih';
        }

        if (elements.selectedBuildingText) {
            elements.selectedBuildingText.textContent = selectedBuildingLabel || 'Pilih gedung terlebih dahulu';
        }

        if (elements.selectionStatus) {
            if (selectedDate && hasBuilding) {
                elements.selectionStatus.textContent = isEditMode ? 'Siap diperbarui' : 'Siap diajukan';
            } else if (!hasBuilding) {
                elements.selectionStatus.textContent = 'Menunggu pilihan gedung';
            } else {
                elements.selectionStatus.textContent = 'Menunggu pilihan tanggal';
            }
        }

        if (elements.selectionHint) {
            if (selectedDate && hasBuilding) {
                elements.selectionHint.textContent = isEditMode
                    ? 'Lengkapi perubahan jenis acara, sesi, estimasi peserta, lalu simpan pembaruan reservasi.'
                    : 'Lengkapi jenis acara, sesi, estimasi peserta, lalu kirim pengajuan reservasi.';
            } else if (!hasBuilding) {
                elements.selectionHint.textContent = 'Pilih gedung dari filter lokasi agar form reservasi dapat diarahkan ke gedung yang benar.';
            } else {
                elements.selectionHint.textContent = 'Klik tanggal kosong pada kalender untuk mengisi tanggal reservasi.';
            }
        }
    }

    function syncBuildingSelection() {
        if (!elements.building) return;

        var selectedOption = elements.building.options[elements.building.selectedIndex];
        var buildingValue = String(elements.building.value || '').trim();
        var buildingLabel = selectedOption && selectedOption.value ? selectedOption.textContent : '';

        if (elements.buildingInput) {
            elements.buildingInput.value = buildingValue;
        }

        if (elements.buildingDisplay) {
            elements.buildingDisplay.value = buildingLabel;
        }

        updateEstPersonCapacityHint();
        filterUmkmOptions();
        syncSelectionSummary();
    }

    function populateDistrictOptions(regionValue, selectedDistrictId) {
        resetSelect(elements.district, 'Pilih Kecamatan...');
        resetSelect(elements.building, 'Pilih Gedung...');

        if (elements.buildingInput) {
            elements.buildingInput.value = '';
        }

        if (elements.buildingDisplay) {
            elements.buildingDisplay.value = '';
        }

        updateEstPersonCapacityHint();
        filterUmkmOptions();

        var region = findRegion(regionValue);
        if (!region || !elements.district) {
            syncSelectionSummary();
            renderFilteredUserCalendar();
            return;
        }

        (region.districts || []).forEach(function (district) {
            var option = document.createElement('option');
            option.value = district.id;
            option.textContent = district.name + ' (' + district.building_count + ')';
            elements.district.appendChild(option);
        });

        elements.district.disabled = false;

        if (selectedDistrictId) {
            elements.district.value = String(selectedDistrictId);
        }

        syncSelectionSummary();
        renderFilteredUserCalendar();
    }

    function populateBuildingOptions(regionValue, districtId, selectedBuildingId) {
        resetSelect(elements.building, 'Pilih Gedung...');

        if (elements.buildingInput) {
            elements.buildingInput.value = '';
        }

        if (elements.buildingDisplay) {
            elements.buildingDisplay.value = '';
        }

        filterUmkmOptions();

        var district = findDistrict(regionValue, districtId);
        if (!district || !elements.building) {
            syncSelectionSummary();
            renderFilteredUserCalendar();
            return;
        }

        (district.buildings || []).forEach(function (building) {
            var option = document.createElement('option');
            option.value = building.id;
            option.textContent = building.name;
            option.dataset.capacity = String(building.capacity || 0);
            elements.building.appendChild(option);
        });

        elements.building.disabled = false;

        if (selectedBuildingId) {
            elements.building.value = String(selectedBuildingId);
        }

        syncBuildingSelection();
        renderFilteredUserCalendar();
    }

    function restoreLocationSelection() {
        if (!elements.region || !elements.district || !elements.building) return;

        var selectedBuildingId = elements.buildingInput ? String(elements.buildingInput.value || '').trim() : '';
        var selectionPath = getPathByBuilding(selectedBuildingId);

        if (!selectionPath) {
            syncBuildingSelection();
            renderFilteredUserCalendar();
            return;
        }

        elements.region.value = selectionPath.region;
        populateDistrictOptions(selectionPath.region, selectionPath.districtId);
        populateBuildingOptions(selectionPath.region, selectionPath.districtId, selectionPath.buildingId);
    }

    function validateReservationForm(markFields) {
        var regionValue = elements.region ? String(elements.region.value || '').trim() : '';
        var districtValue = elements.district && !elements.district.disabled ? String(elements.district.value || '').trim() : '';
        var buildingValue = elements.buildingInput ? String(elements.buildingInput.value || '').trim() : '';
        var dateValue = elements.startInput ? String(elements.startInput.value || '').trim() : '';
        var eventValue = elements.event ? String(elements.event.value || '').trim() : '';
        var sessionMeta = getSelectedSessionMeta();
        var sessionValue = sessionMeta.value;
        var startTimeValue = elements.startTime ? String(elements.startTime.value || '').trim() : '';
        var endTimeValue = elements.endTime ? String(elements.endTime.value || '').trim() : '';
        var requiresCustomTime = sessionMeta.isCustom && sessionValue !== '';
        var estPersonValue = elements.estPerson ? String(elements.estPerson.value || '').trim() : '';
        var umkmValue = elements.umkm ? String(elements.umkm.value || '').trim() : '';
        var umkmRequired = hasAvailableUmkmOptions();
        var selectedBuildingCapacity = updateEstPersonCapacityHint();
        var requestFileName = elements.requestFile && elements.requestFile.files && elements.requestFile.files[0]
            ? elements.requestFile.files[0].name
            : '';
        var existingRequestFileUrl = getExistingUploadMeta(elements.requestFile).url;
        var fileName = elements.idFile && elements.idFile.files && elements.idFile.files[0]
            ? elements.idFile.files[0].name
            : '';
        var existingIdFileUrl = getExistingUploadMeta(elements.idFile).url;

        var regionValid = regionValue !== '';
        var districtValid = districtValue !== '';
        var buildingValid = buildingValue !== '';
        var dateValid = dateValue !== '';
        var eventValid = eventValue !== '';
        var sessionValid = sessionValue !== '';
        var timeValid = sessionValid &&
            startTimeValue !== '' &&
            endTimeValue !== '' &&
            isValidReservationTimeRange(startTimeValue, endTimeValue);
        var estPersonValid =
            estPersonValue !== '' &&
            Number(estPersonValue) > 0 &&
            (selectedBuildingCapacity <= 0 || Number(estPersonValue) <= selectedBuildingCapacity);
        var umkmValid = !umkmRequired || umkmValue !== '';
        var requestFileHasValidExtension = requestFileName !== '' && /\.(jpg|jpeg|png|pdf)$/i.test(requestFileName);
        var requestFileValid = requiresFreshRequestFile
            ? requestFileHasValidExtension
            : ((existingRequestFileUrl !== '' && requestFileName === '')
                || (!requiresRequestFile && requestFileName === '')
                || requestFileHasValidExtension);
        var fileValid = (existingIdFileUrl !== '' && fileName === '')
            || (!requiresIdFile && fileName === '')
            || (fileName !== '' && /\.(jpg|jpeg|png|pdf)$/i.test(fileName));

        if (dateValid && minBookingDate && reservationStatusKey === 'RESERVASI BARU') {
            var selectedDate = new Date(String(dateValue).slice(0, 10) + 'T00:00:00');
            dateValid = !isNaN(selectedDate.getTime()) && selectedDate >= minBookingDate;
        }

        setFieldState(elements.region, regionValid, markFields || regionValue !== '');
        setFieldState(elements.district, districtValid, markFields || regionValue !== '' || districtValue !== '');
        setFieldState(elements.building, buildingValid, markFields || districtValue !== '' || buildingValue !== '');
        setFieldState(elements.dateDisplay, dateValid, markFields || dateValue !== '');
        setFieldState(elements.buildingDisplay, buildingValid, markFields || buildingValue !== '');
        setFieldState(elements.event, eventValid, markFields || eventValue !== '');
        setFieldState(elements.session, sessionValid, markFields || sessionValue !== '');
        setFieldState(elements.startTime, timeValid, requiresCustomTime && (markFields || startTimeValue !== '' || endTimeValue !== ''));
        setFieldState(elements.endTime, timeValid, requiresCustomTime && (markFields || startTimeValue !== '' || endTimeValue !== ''));
        setFieldState(elements.estPerson, estPersonValid, markFields || estPersonValue !== '');
        setFieldState(elements.umkm, umkmValid, umkmRequired && (markFields || umkmValue !== ''));
        setFieldState(elements.requestFile, requestFileValid, markFields || requestFileName !== '' || existingRequestFileUrl !== '');
        setFieldState(elements.idFile, fileValid, markFields || fileName !== '' || existingIdFileUrl !== '');

        var isValid =
            regionValid &&
            districtValid &&
            buildingValid &&
            dateValid &&
            eventValid &&
            sessionValid &&
            timeValid &&
            estPersonValid &&
            umkmValid &&
            requestFileValid &&
            fileValid;

        return {
            valid: isValid
        };
    }

    function showFlashMessages() {
        var flashStateHost = configEl || document.body;

        if (flashStateHost.dataset.sigapReservationFlashShown === '1') return;
        flashStateHost.dataset.sigapReservationFlashShown = '1';

        var messages = config.messages || {};

        if (messages.success) {
            showReservationAlert('success', '<b>BERHASIL</b>', String(messages.success));
            return;
        }

        if (messages.error) {
            showReservationAlert('warning', '<b>PERIKSA KEMBALI</b>', String(messages.error));
        }
    }

    function getReservationCodeValue(item) {
        var status = String(item && item.status ? item.status : '').trim().toUpperCase();
        var requestCode = String(item && item.request_id ? item.request_id : '').trim();
        var orderCode = String(item && item.order_id ? item.order_id : '').trim();
        var useOrderCode = shouldUseHistoryOrderCode(status);
        var code = useOrderCode ? orderCode : requestCode;

        if (!code) {
            code = requestCode || orderCode || String(item && item.id ? item.id : '-').trim() || '-';
        }

        return code;
    }

    function buildReservationPaymentDocumentFilename(method, bookingCode) {
        var normalizedMethod = normalizeReservationPaymentMethod(method);
        var sanitizedBookingCode = sanitizeReservationPaymentFilenamePart(bookingCode);

        if (normalizedMethod === 'qris') {
            return 'qris-pembayaran-' + sanitizedBookingCode + '.svg';
        }

        return 'va-pembayaran-' + sanitizedBookingCode + '.pdf';
    }

    function buildReservationPaymentQrisImageFilename(bookingCode) {
        var sanitizedBookingCode = sanitizeReservationPaymentFilenamePart(bookingCode);

        return 'qris-pembayaran-' + sanitizedBookingCode + '.jpg';
    }

    function buildReservationPaymentDocumentBaseUrl(baseUrl, method, bookingCode) {
        var normalizedBaseUrl = String(baseUrl || '').trim();
        var normalizedMethod = normalizeReservationPaymentMethod(method);
        var documentFilename = buildReservationPaymentDocumentFilename(normalizedMethod, bookingCode);

        if (!normalizedBaseUrl || !normalizedMethod || !documentFilename) {
            return '';
        }

        normalizedBaseUrl = normalizedBaseUrl.replace(/\/+$/, '');

        if (normalizedBaseUrl.toLowerCase().slice(-(documentFilename.length + 1)) === '/' + documentFilename.toLowerCase()) {
            return normalizedBaseUrl;
        }

        return normalizedBaseUrl + '/' + encodeURIComponent(documentFilename);
    }

    function buildReservationPaymentPreviewUrl(baseUrl, method, bookingCode) {
        var documentBaseUrl = buildReservationPaymentDocumentBaseUrl(baseUrl, method, bookingCode);
        var normalizedMethod = normalizeReservationPaymentMethod(method);

        if (!documentBaseUrl || !normalizedMethod) {
            return '';
        }

        return documentBaseUrl
            + (documentBaseUrl.indexOf('?') === -1 ? '?' : '&')
            + 'method=' + encodeURIComponent(normalizedMethod);
    }

    function buildReservationPaymentDownloadUrl(baseUrl, method, bookingCode) {
        var previewUrl = buildReservationPaymentPreviewUrl(baseUrl, method, bookingCode);

        if (!previewUrl) {
            return '';
        }

        return previewUrl + '&download=1';
    }

    function openReservationPaymentPreview(previewUrl) {
        var normalizedUrl = String(previewUrl || '').trim();
        if (!normalizedUrl) {
            showReservationAlert(
                'warning',
                '<b>DOKUMEN TIDAK TERSEDIA</b>',
                'Dokumen pembayaran belum dapat dibuka'
            );
            return;
        }

        var previewWindow = window.open(normalizedUrl, '_blank', 'noopener');
        if (!previewWindow) {
            window.location.href = normalizedUrl;
        }
    }

    function formatReservationCompactCurrency(amount) {
        return 'Rp' + Number(amount || 0).toLocaleString('id-ID');
    }

    function sanitizeReservationPaymentFilenamePart(value) {
        var normalizedValue = String(value || '')
            .trim()
            .replace(/[^A-Za-z0-9-]+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');

        return normalizedValue || 'reservasi';
    }

    function formatReservationDateTimeFromObject(date) {
        if (!(date instanceof Date) || isNaN(date.getTime())) {
            return '-';
        }

        var monthNames = [
            'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember'
        ];

        var day = String(date.getDate()).padStart(2, '0');
        var month = monthNames[date.getMonth()] || '';
        var year = String(date.getFullYear());
        var hours = String(date.getHours()).padStart(2, '0');
        var minutes = String(date.getMinutes()).padStart(2, '0');
        var seconds = String(date.getSeconds()).padStart(2, '0');

        return day + ' ' + month + ' ' + year + ' ' + hours + ':' + minutes + ':' + seconds;
    }

    function formatReservationCountdown(totalSeconds) {
        var normalizedSeconds = Math.max(0, Math.floor(Number(totalSeconds || 0)));
        var hours = String(Math.floor(normalizedSeconds / 3600)).padStart(2, '0');
        var minutes = String(Math.floor((normalizedSeconds % 3600) / 60)).padStart(2, '0');
        var seconds = String(normalizedSeconds % 60).padStart(2, '0');

        return hours + ' : ' + minutes + ' : ' + seconds;
    }

    function parseReservationDateTime(value) {
        var normalizedValue = String(value || '').trim();
        var parts;
        var parsedDate;

        if (!normalizedValue) {
            return null;
        }

        parts = normalizedValue.match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::(\d{2}))?$/);
        if (parts) {
            parsedDate = new Date(
                Number(parts[1]),
                Number(parts[2]) - 1,
                Number(parts[3]),
                Number(parts[4]),
                Number(parts[5]),
                Number(parts[6] || 0)
            );
            return isNaN(parsedDate.getTime()) ? null : parsedDate;
        }

        parsedDate = new Date(normalizedValue);
        return isNaN(parsedDate.getTime()) ? null : parsedDate;
    }

    function normalizeReservationPaymentMethod(method) {
        var normalizedMethod = String(method || '').trim().toLowerCase();
        return normalizedMethod === 'va' || normalizedMethod === 'qris' ? normalizedMethod : '';
    }

    function buildReservationPaymentMethodLabel(method) {
        var normalizedMethod = normalizeReservationPaymentMethod(method);

        if (normalizedMethod === 'va') {
            return 'Virtual Account (VA)';
        }

        if (normalizedMethod === 'qris') {
            return 'QRIS';
        }

        return '';
    }

    function getReservationPaymentProcessUrl() {
        return String(reservation.paymentProcessUrl || '').trim();
    }

    function getReservationPaymentRevisionUrl() {
        return String(reservation.paymentRevisionUrl || '').trim();
    }

    function getReservationPaymentCsrfToken() {
        var token = String(reservation.csrfToken || '').trim();
        var fallbackInput = document.querySelector('input[name="_token"]');

        if (token) {
            return token;
        }

        return fallbackInput ? String(fallbackInput.value || '').trim() : '';
    }

    function syncReservationPaymentState(item) {
        var statusKey;
        var paymentMethod;
        var paymentExpiry;
        var paymentIsAvailable;
        var paymentIsActive;

        if (!item || typeof item !== 'object') {
            return null;
        }

        statusKey = getNormalizedHistoryStatusKey(item.status || '');
        paymentMethod = normalizeReservationPaymentMethod(item.payment_method_key);
        paymentExpiry = parseReservationDateTime(item.payment_expired_at);
        paymentIsAvailable = statusKey === 'MENUNGGU PEMBAYARAN';
        paymentIsActive = paymentIsAvailable
            && paymentMethod !== ''
            && (!(paymentExpiry instanceof Date) || paymentExpiry.getTime() > Date.now());

        item.payment_is_available = paymentIsAvailable;
        item.payment_is_active = paymentIsActive;
        item.payment_action_mode = paymentIsAvailable ? (paymentIsActive ? 'selected' : 'process') : '';
        item.payment_action_label = paymentIsAvailable ? (paymentIsActive ? 'Bayar' : 'Proses') : '';

        if (!paymentIsActive) {
            item.payment_method_key = '';
            item.payment_method_label = '';
            item.payment_provider = '';
            item.payment_code_value = '';
            item.payment_qris_url = '';
            item.payment_expired_at = '';
            item.payment_expiry_label = '';
            item.payment_preview_url = '';
            item.payment_download_url = '';
            return item;
        }

        item.payment_method_key = paymentMethod;
        item.payment_method_label = String(item.payment_method_label || '').trim() || buildReservationPaymentMethodLabel(paymentMethod);

        if (!String(item.payment_expiry_label || '').trim() && paymentExpiry instanceof Date) {
            item.payment_expiry_label = formatReservationDateTimeFromObject(paymentExpiry) + ' WIB';
        }

        return item;
    }

    function mergeReservationPaymentState(reservationId, paymentState) {
        var key = String(reservationId || '').trim();
        var item = historyReservationMap[key] || { id: key };

        if (paymentState && typeof paymentState === 'object') {
            Object.keys(paymentState).forEach(function (paymentKey) {
                item[paymentKey] = paymentState[paymentKey];
            });
        }

        historyReservationMap[key] = syncReservationPaymentState(item) || item;
        return historyReservationMap[key];
    }

    function getReservationPaymentActionButton(reservationId) {
        return document.querySelector('.js-user-reservation-payment-print-button[data-reservation-id="' + String(reservationId || '').trim() + '"]');
    }

    function getReservationPaymentRevisionButton(reservationId) {
        return document.querySelector('.js-user-reservation-payment-revise-button[data-reservation-id="' + String(reservationId || '').trim() + '"]');
    }

    function setReservationPaymentButtonBusy(button, isBusy) {
        if (!button) {
            return;
        }

        button.disabled = !!isBusy;
        button.classList.toggle('is-disabled', !!isBusy);
    }

    function buildReservationPaymentUrls(button, item, method) {
        var normalizedMethod = normalizeReservationPaymentMethod(method);
        var printUrl = String(button && button.getAttribute('data-print-url') ? button.getAttribute('data-print-url') : '').trim();
        var bookingCode = item ? getReservationCodeValue(item) : String(button && button.getAttribute('data-reservation-code') ? button.getAttribute('data-reservation-code') : '-').trim();
        var previewUrl = item ? String(item.payment_preview_url || '').trim() : '';
        var downloadUrl = item ? String(item.payment_download_url || '').trim() : '';

        if (!previewUrl) {
            previewUrl = buildReservationPaymentPreviewUrl(printUrl, normalizedMethod, bookingCode);
        }

        if (!downloadUrl) {
            downloadUrl = buildReservationPaymentDownloadUrl(printUrl, normalizedMethod, bookingCode);
        }

        return {
            previewUrl: previewUrl,
            downloadUrl: downloadUrl
        };
    }

    function createReservationPaymentRevisionButton(reservationId) {
        var button = document.createElement('button');
        var icon = document.createElement('span');
        var iconGlyph = document.createElement('i');
        var label = document.createElement('span');

        button.type = 'button';
        button.className = 'admin-table-action-item js-user-reservation-payment-revise-button';
        button.setAttribute('data-reservation-id', String(reservationId || '').trim());

        icon.className = 'admin-table-action-icon text-info bg-info-subtle';
        iconGlyph.className = 'ti ti-refresh fs-5';
        icon.appendChild(iconGlyph);

        label.className = 'admin-table-action-label';
        label.textContent = 'Revisi';

        button.appendChild(icon);
        button.appendChild(label);

        bindReservationPaymentRevisionButton(button);
        return button;
    }

    function ensureReservationPaymentRevisionButton(actionButton, item) {
        var reservationId = String(item && item.id ? item.id : actionButton && actionButton.getAttribute('data-reservation-id') ? actionButton.getAttribute('data-reservation-id') : '').trim();
        var menu = actionButton ? actionButton.closest('.admin-table-action-menu') : null;
        var revisionButton = getReservationPaymentRevisionButton(reservationId);

        if (!menu || !reservationId) {
            if (revisionButton) {
                revisionButton.remove();
            }
            return;
        }

        if (!item || !item.payment_is_active) {
            if (revisionButton) {
                revisionButton.remove();
            }
            return;
        }

        if (!revisionButton) {
            revisionButton = createReservationPaymentRevisionButton(reservationId);
            actionButton.insertAdjacentElement('afterend', revisionButton);
        }

        revisionButton.setAttribute('data-payment-method', String(item.payment_method_key || '').trim());
        revisionButton.setAttribute('data-payment-method-label', String(item.payment_method_label || '').trim());
    }

    function renderReservationPaymentActionButtonState(button, item) {
        var labelEl;
        var iconWrap;
        var isActive;

        if (!button || !item) {
            return;
        }

        labelEl = button.querySelector('.js-user-reservation-payment-action-label');
        iconWrap = button.querySelector('.js-user-reservation-payment-action-icon');
        isActive = !!item.payment_is_active;

        button.setAttribute('data-payment-action-mode', String(item.payment_action_mode || '').trim());
        button.setAttribute('data-payment-method', String(item.payment_method_key || '').trim());
        button.setAttribute('data-payment-method-label', String(item.payment_method_label || '').trim());
        button.setAttribute('data-payment-preview-url', String(item.payment_preview_url || '').trim());
        button.setAttribute('data-payment-download-url', String(item.payment_download_url || '').trim());
        button.setAttribute('data-payment-qris-url', String(item.payment_qris_url || '').trim());
        button.setAttribute('data-payment-code', String(item.payment_code_value || '').trim());
        button.setAttribute('data-payment-expired-at', String(item.payment_expired_at || '').trim());
        button.setAttribute('data-payment-expiry-label', String(item.payment_expiry_label || '').trim());

        if (labelEl) {
            labelEl.textContent = String(item.payment_action_label || 'Proses').trim() || 'Proses';
        }

        if (iconWrap) {
            iconWrap.classList.remove('text-success', 'bg-success-subtle', 'text-warning', 'bg-warning-subtle');
            iconWrap.classList.add(isActive ? 'text-success' : 'text-warning');
            iconWrap.classList.add(isActive ? 'bg-success-subtle' : 'bg-warning-subtle');
        }

        ensureReservationPaymentRevisionButton(button, item);
    }

    function applyReservationPaymentStateToRow(reservationId) {
        var key = String(reservationId || '').trim();
        var item = historyReservationMap[key] || null;
        var actionButton = getReservationPaymentActionButton(key);
        var revisionButton = getReservationPaymentRevisionButton(key);

        if (!item) {
            return;
        }

        syncReservationPaymentState(item);

        if (actionButton) {
            renderReservationPaymentActionButtonState(actionButton, item);
        } else if (revisionButton) {
            revisionButton.remove();
        }
    }

    function postReservationPaymentAction(url, payload) {
        var normalizedUrl = String(url || '').trim();
        var body = new URLSearchParams();
        var csrfToken = getReservationPaymentCsrfToken();

        if (!normalizedUrl) {
            return Promise.reject(new Error('Aksi pembayaran belum tersedia'));
        }

        Object.keys(payload || {}).forEach(function (key) {
            body.append(key, String(payload[key] == null ? '' : payload[key]));
        });

        if (csrfToken) {
            body.append('_token', csrfToken);
        }

        return fetch(normalizedUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: body.toString()
        }).then(function (response) {
            if (response.redirected && response.url) {
                window.location.href = response.url;
                return null;
            }

            return response.text().then(function (text) {
                var data = null;
                var error;

                try {
                    data = JSON.parse(text || '{}');
                } catch (parseError) {
                    data = null;
                }

                if (!response.ok || !data || data.success !== true) {
                    error = new Error(data && data.message ? String(data.message) : 'Permintaan pembayaran gagal diproses');
                    error.responseData = data;
                    throw error;
                }

                return data;
            });
        });
    }

    function clearReservationPaymentQrisCountdown(modalEl) {
        if (!modalEl) {
            return;
        }

        if (modalEl.__paymentQrisCountdownTimer) {
            window.clearInterval(modalEl.__paymentQrisCountdownTimer);
            modalEl.__paymentQrisCountdownTimer = null;
        }
    }

    function triggerReservationPaymentDownloadLink(url, filename) {
        var anchor;

        if (!url) {
            return;
        }

        anchor = document.createElement('a');
        anchor.href = url;
        anchor.style.display = 'none';

        if (filename) {
            anchor.setAttribute('download', filename);
        }

        document.body.appendChild(anchor);
        anchor.click();
        document.body.removeChild(anchor);
    }

    function revokeReservationPaymentQrisPreparedDownload(modalEl) {
        var preparedUrl = modalEl ? String(modalEl.__paymentQrisPreparedDownloadUrl || '').trim() : '';

        if (!preparedUrl || typeof URL === 'undefined' || typeof URL.revokeObjectURL !== 'function') {
            if (modalEl) {
                modalEl.__paymentQrisPreparedDownloadUrl = '';
            }
            return;
        }

        URL.revokeObjectURL(preparedUrl);
        modalEl.__paymentQrisPreparedDownloadUrl = '';
    }

    function loadReservationPaymentQrisDownloadImage(sourceUrl) {
        var normalizedUrl = String(sourceUrl || '').trim();

        return new Promise(function (resolve, reject) {
            var image = new Image();
            var cleanupUrl = '';

            if (!normalizedUrl) {
                reject(new Error('missing-source'));
                return;
            }

            image.onload = function () {
                resolve({
                    image: image,
                    cleanupUrl: cleanupUrl
                });
            };

            image.onerror = function () {
                if (cleanupUrl && typeof URL !== 'undefined' && typeof URL.revokeObjectURL === 'function') {
                    URL.revokeObjectURL(cleanupUrl);
                }
                reject(new Error('image-load-failed'));
            };

            if (/^(data:|blob:)/i.test(normalizedUrl)) {
                image.src = normalizedUrl;
                return;
            }

            if (typeof fetch !== 'function') {
                image.src = normalizedUrl;
                return;
            }

            fetch(normalizedUrl, {
                credentials: 'same-origin',
                cache: 'no-store'
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('image-fetch-failed');
                }

                return response.blob();
            }).then(function (blob) {
                if (typeof URL === 'undefined' || typeof URL.createObjectURL !== 'function') {
                    image.src = normalizedUrl;
                    return;
                }

                cleanupUrl = URL.createObjectURL(blob);
                image.src = cleanupUrl;
            }).catch(function () {
                image.src = normalizedUrl;
            });
        });
    }

    function buildReservationPaymentQrisJpegDownloadUrl(image, quality) {
        return new Promise(function (resolve, reject) {
            var canvas;
            var context;
            var width = Number(image && (image.naturalWidth || image.width) ? (image.naturalWidth || image.width) : 0);
            var height = Number(image && (image.naturalHeight || image.height) ? (image.naturalHeight || image.height) : 0);

            if (!width || !height) {
                reject(new Error('invalid-image-size'));
                return;
            }

            canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            context = canvas.getContext('2d');

            if (!context) {
                reject(new Error('canvas-context-unavailable'));
                return;
            }

            try {
                context.fillStyle = '#ffffff';
                context.fillRect(0, 0, width, height);
                context.drawImage(image, 0, 0, width, height);
            } catch (error) {
                reject(error);
                return;
            }

            if (typeof canvas.toBlob === 'function' && typeof URL !== 'undefined' && typeof URL.createObjectURL === 'function') {
                canvas.toBlob(function (blob) {
                    if (!blob) {
                        reject(new Error('image-blob-empty'));
                        return;
                    }

                    resolve({
                        url: URL.createObjectURL(blob),
                        revoke: true
                    });
                }, 'image/jpeg', quality);
                return;
            }

            try {
                resolve({
                    url: canvas.toDataURL('image/jpeg', quality),
                    revoke: false
                });
            } catch (error) {
                reject(error);
            }
        });
    }

    function prepareReservationPaymentQrisDownload(modalEl) {
        var downloadButton = modalEl ? modalEl.querySelector('#reservationPaymentQrisDownloadButton') : null;
        var imageEl = modalEl ? modalEl.querySelector('#reservationPaymentQrisImage') : null;
        var sourceUrl = imageEl ? String(imageEl.currentSrc || imageEl.getAttribute('src') || '').trim() : '';
        var bookingCode = modalEl ? String(modalEl.__paymentQrisBookingCode || '').trim() : '';
        var filename = buildReservationPaymentQrisImageFilename(bookingCode || 'qris');
        var requestToken;

        if (!modalEl || !downloadButton || !sourceUrl) {
            return Promise.reject(new Error('download-source-unavailable'));
        }

        requestToken = Number(modalEl.__paymentQrisDownloadToken || 0) + 1;
        modalEl.__paymentQrisDownloadToken = requestToken;
        revokeReservationPaymentQrisPreparedDownload(modalEl);

        downloadButton.setAttribute('href', '#');
        downloadButton.setAttribute('download', filename);
        downloadButton.dataset.downloadReady = '0';

        return loadReservationPaymentQrisDownloadImage(sourceUrl).then(function (result) {
            return buildReservationPaymentQrisJpegDownloadUrl(result.image, 0.92).then(function (downloadResult) {
                if (result.cleanupUrl && typeof URL !== 'undefined' && typeof URL.revokeObjectURL === 'function') {
                    URL.revokeObjectURL(result.cleanupUrl);
                }

                return downloadResult;
            }, function (error) {
                if (result.cleanupUrl && typeof URL !== 'undefined' && typeof URL.revokeObjectURL === 'function') {
                    URL.revokeObjectURL(result.cleanupUrl);
                }

                throw error;
            });
        }).then(function (downloadResult) {
            if (!modalEl || Number(modalEl.__paymentQrisDownloadToken || 0) !== requestToken) {
                if (downloadResult && downloadResult.revoke && downloadResult.url && typeof URL !== 'undefined' && typeof URL.revokeObjectURL === 'function') {
                    URL.revokeObjectURL(downloadResult.url);
                }

                return '';
            }

            if (downloadResult && downloadResult.revoke) {
                modalEl.__paymentQrisPreparedDownloadUrl = String(downloadResult.url || '').trim();
            }

            downloadButton.setAttribute('href', String(downloadResult && downloadResult.url ? downloadResult.url : '').trim() || '#');
            downloadButton.setAttribute('download', filename);
            downloadButton.dataset.downloadReady = '1';

            return String(downloadResult && downloadResult.url ? downloadResult.url : '').trim();
        });
    }

    function downloadReservationPaymentQrisImage(modalEl) {
        var downloadButton = modalEl ? modalEl.querySelector('#reservationPaymentQrisDownloadButton') : null;
        var preparedUrl = downloadButton ? String(downloadButton.getAttribute('href') || '').trim() : '';
        var bookingCode = modalEl ? String(modalEl.__paymentQrisBookingCode || '').trim() : '';
        var filename = buildReservationPaymentQrisImageFilename(bookingCode || 'qris');

        if (downloadButton && downloadButton.dataset.downloadReady === '1' && preparedUrl && preparedUrl !== '#') {
            triggerReservationPaymentDownloadLink(preparedUrl, filename);
            return Promise.resolve(preparedUrl);
        }

        return prepareReservationPaymentQrisDownload(modalEl).then(function (downloadUrl) {
            if (!downloadUrl) {
                throw new Error('download-url-unavailable');
            }

            triggerReservationPaymentDownloadLink(downloadUrl, filename);
            return downloadUrl;
        }).catch(function () {
            showReservationAlert(
                'error',
                '<b>UNDUH GAGAL</b>',
                'Kode QR tidak berhasil diunduh dalam format JPG'
            );

            return '';
        });
    }

    function updateReservationPaymentQrisCountdown(modalEl) {
        var countdownEl = modalEl ? modalEl.querySelector('#reservationPaymentQrisCountdown') : null;
        var reservationId = modalEl ? String(modalEl.__paymentQrisReservationId || '').trim() : '';
        var item = reservationId !== '' ? historyReservationMap[reservationId] || null : null;
        var expiresAt = modalEl && modalEl.__paymentQrisExpiresAt instanceof Date
            ? modalEl.__paymentQrisExpiresAt
            : null;
        var remainingSeconds;

        if (!countdownEl) {
            return;
        }

        if (!(expiresAt instanceof Date) || isNaN(expiresAt.getTime())) {
            countdownEl.textContent = '';
            countdownEl.classList.remove('is-expired');
            return;
        }

        remainingSeconds = Math.max(0, Math.ceil((expiresAt.getTime() - Date.now()) / 1000));
        countdownEl.textContent = '(' + formatReservationCountdown(remainingSeconds) + ')';
        countdownEl.classList.toggle('is-expired', remainingSeconds <= 0);

        if (remainingSeconds > 0) {
            return;
        }

        clearReservationPaymentQrisCountdown(modalEl);

        if (item) {
            syncReservationPaymentState(item);
            applyReservationPaymentStateToRow(reservationId);
        }
    }

    function showReservationPaymentVaModal(button, previewUrl, downloadUrl) {
        var modalEl = getLatestElementById('reservationPaymentVaModal', document.body || document);
        var iframe = modalEl ? modalEl.querySelector('#reservationPaymentVaFrame') : null;
        var codeLabel = modalEl ? modalEl.querySelector('#reservationPaymentVaCode') : null;
        var openButton = modalEl ? modalEl.querySelector('#reservationPaymentVaOpenButton') : null;
        var downloadButton = modalEl ? modalEl.querySelector('#reservationPaymentVaDownloadButton') : null;
        var reservationId = String(button && button.getAttribute('data-reservation-id') ? button.getAttribute('data-reservation-id') : '').trim();
        var item = historyReservationMap[reservationId] || null;
        var bookingCode = item ? getReservationCodeValue(item) : String(button && button.getAttribute('data-reservation-code') ? button.getAttribute('data-reservation-code') : '-').trim();
        var normalizedUrl = String(previewUrl || '').trim();
        var normalizedDownloadUrl = String(downloadUrl || '').trim();
        var pdfFilename = buildReservationPaymentDocumentFilename('va', bookingCode);

        if (!modalEl || !iframe || !normalizedUrl || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            openReservationPaymentPreview(normalizedUrl);
            return;
        }

        if (modalEl.parentElement !== document.body) {
            document.body.appendChild(modalEl);
        }

        if (modalEl.dataset.paymentVaBound !== '1') {
            modalEl.dataset.paymentVaBound = '1';

            modalEl.addEventListener('hidden.bs.modal', function () {
                if (iframe) {
                    iframe.setAttribute('src', 'about:blank');
                }

                if (openButton) {
                    openButton.setAttribute('href', '#');
                }

                if (downloadButton) {
                    downloadButton.setAttribute('href', '#');
                }
            });
        }

        if (codeLabel) {
            codeLabel.textContent = 'Kode : ' + (bookingCode || '-');
        }

        if (openButton) {
            openButton.setAttribute('href', normalizedUrl);
            openButton.setAttribute('title', pdfFilename);
        }

        if (downloadButton) {
            downloadButton.setAttribute('href', normalizedDownloadUrl || normalizedUrl);
            downloadButton.setAttribute('download', pdfFilename);
            downloadButton.setAttribute('title', pdfFilename);
        }

        iframe.setAttribute('src', normalizedUrl);
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    function showReservationPaymentQrisModal(button, previewUrl, downloadUrl) {
        var modalEl = getLatestElementById('reservationPaymentQrisModal', document.body || document);
        var reservationId = String(button && button.getAttribute('data-reservation-id') ? button.getAttribute('data-reservation-id') : '').trim();
        var item = historyReservationMap[reservationId] || null;
        var bookingCodeEl = modalEl ? modalEl.querySelector('#reservationPaymentQrisBookingCode') : null;
        var totalEl = modalEl ? modalEl.querySelector('#reservationPaymentQrisTotal') : null;
        var expiryEl = modalEl ? modalEl.querySelector('#reservationPaymentQrisExpiry') : null;
        var countdownEl = modalEl ? modalEl.querySelector('#reservationPaymentQrisCountdown') : null;
        var paymentCodeEl = modalEl ? modalEl.querySelector('#reservationPaymentQrisPaymentCode') : null;
        var imageEl = modalEl ? modalEl.querySelector('#reservationPaymentQrisImage') : null;
        var downloadButton = modalEl ? modalEl.querySelector('#reservationPaymentQrisDownloadButton') : null;
        var bookingCode = item ? getReservationCodeValue(item) : String(button && button.getAttribute('data-reservation-code') ? button.getAttribute('data-reservation-code') : '-').trim();
        var qrisImageUrl = item ? String(item.payment_qris_url || '').trim() : '';
        var qrisCode = item ? String(item.payment_code_value || '').trim() : '';
        var qrisDownloadUrl = String(downloadUrl || previewUrl || qrisImageUrl).trim();
        var expiryDate = item ? parseReservationDateTime(item.payment_expired_at) : null;

        if (!modalEl || !item || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            openReservationPaymentPreview(previewUrl);
            return;
        }

        if (modalEl.parentElement !== document.body) {
            document.body.appendChild(modalEl);
        }

        if (modalEl.dataset.paymentQrisBound !== '1') {
            modalEl.dataset.paymentQrisBound = '1';

            modalEl.addEventListener('hidden.bs.modal', function () {
                clearReservationPaymentQrisCountdown(modalEl);
                modalEl.__paymentQrisDownloadToken = Number(modalEl.__paymentQrisDownloadToken || 0) + 1;
                revokeReservationPaymentQrisPreparedDownload(modalEl);
                modalEl.__paymentQrisReservationId = '';
                modalEl.__paymentQrisExpiresAt = null;
                modalEl.__paymentQrisBookingCode = '';

                if (countdownEl) {
                    countdownEl.textContent = '(00 : 15 : 00)';
                    countdownEl.classList.remove('is-expired');
                }
            });

        }

        if (downloadButton) {
            downloadButton.onclick = function (event) {
                event.preventDefault();
                downloadReservationPaymentQrisImage(modalEl);
            };
        }

        if (bookingCodeEl) {
            bookingCodeEl.textContent = 'Kode : ' + (bookingCode || '-');
        }

        if (totalEl) {
            totalEl.textContent = formatReservationCompactCurrency(item.total_price || 0);
        }

        if (expiryEl) {
            expiryEl.textContent = String(item.payment_expiry_label || '').trim() || '-';
        }

        if (countdownEl) {
            countdownEl.textContent = '(00 : 15 : 00)';
            countdownEl.classList.remove('is-expired');
        }

        if (paymentCodeEl) {
            paymentCodeEl.textContent = qrisCode || '-';
        }

        if (imageEl) {
            if (!imageEl.dataset.fallbackSrc) {
                imageEl.dataset.fallbackSrc = String(imageEl.getAttribute('src') || '').trim();
            }

            imageEl.onerror = function () {
                var fallbackSrc = String(imageEl.dataset.fallbackSrc || '').trim();
                if (fallbackSrc && imageEl.getAttribute('src') !== fallbackSrc) {
                    imageEl.setAttribute('src', fallbackSrc);
                }
            };

            imageEl.setAttribute('src', qrisImageUrl || String(imageEl.dataset.fallbackSrc || '').trim());
        }

        if (downloadButton) {
            downloadButton.setAttribute('href', '#');
            downloadButton.setAttribute('download', buildReservationPaymentQrisImageFilename(bookingCode));
            downloadButton.dataset.downloadReady = '0';
        }

        clearReservationPaymentQrisCountdown(modalEl);
        revokeReservationPaymentQrisPreparedDownload(modalEl);
        modalEl.__paymentQrisReservationId = reservationId;
        modalEl.__paymentQrisBookingCode = bookingCode;
        modalEl.__paymentQrisExpiresAt = expiryDate instanceof Date && !isNaN(expiryDate.getTime())
            ? expiryDate
            : null;
        updateReservationPaymentQrisCountdown(modalEl);

        if (modalEl.__paymentQrisExpiresAt instanceof Date && modalEl.__paymentQrisExpiresAt.getTime() > Date.now()) {
            modalEl.__paymentQrisCountdownTimer = window.setInterval(function () {
                updateReservationPaymentQrisCountdown(modalEl);
            }, 1000);
        }

        prepareReservationPaymentQrisDownload(modalEl).catch(function () {
            return '';
        });

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    function openReservationPaymentSelectedMethod(button, selectedItem) {
        var reservationId = String(button && button.getAttribute('data-reservation-id') ? button.getAttribute('data-reservation-id') : '').trim();
        var item = selectedItem || historyReservationMap[reservationId] || null;
        var method = item ? normalizeReservationPaymentMethod(item.payment_method_key) : '';
        var urls = buildReservationPaymentUrls(button, item, method);

        if (!item || !method) {
            showReservationAlert(
                'warning',
                '<b>METODE BELUM DIPILIH</b>',
                'Silakan pilih metode pembayaran terlebih dahulu'
            );
            return;
        }

        if (method === 'va') {
            showReservationPaymentVaModal(button, urls.previewUrl, urls.downloadUrl);
            return;
        }

        if (method === 'qris') {
            showReservationPaymentQrisModal(button, urls.previewUrl, urls.downloadUrl);
        }
    }

    function requestReservationPaymentMethod(button, method) {
        var reservationId = String(button && button.getAttribute('data-reservation-id') ? button.getAttribute('data-reservation-id') : '').trim();
        var processUrl = getReservationPaymentProcessUrl();

        setReservationPaymentButtonBusy(button, true);

        return postReservationPaymentAction(processUrl, {
            reservation_id: reservationId,
            method: normalizeReservationPaymentMethod(method)
        }).then(function (responseData) {
            var updatedItem = mergeReservationPaymentState(reservationId, responseData && responseData.payment ? responseData.payment : {});
            applyReservationPaymentStateToRow(reservationId);
            setReservationPaymentButtonBusy(button, false);
            openReservationPaymentSelectedMethod(button, updatedItem);
            return updatedItem;
        }).catch(function (error) {
            setReservationPaymentButtonBusy(button, false);
            applyReservationPaymentStateToRow(reservationId);
            showReservationAlert(
                'warning',
                '<b>PROSES PEMBAYARAN GAGAL</b>',
                error && error.message ? String(error.message) : 'Metode pembayaran gagal diproses'
            );
        });
    }

    function showReservationPaymentMethodPicker(button) {
        if (!button) {
            return;
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire(buildReservationSwalOptions({
                icon: 'info',
                title: '<b>METODE PEMBAYARAN</b>',
                text: 'Pilih metode pembayaran yang diinginkan',
                showDenyButton: true,
                confirmButtonText: 'VIRTUAL ACCOUNT (VA)',
                denyButtonText: 'KODE QR (QRIS)',
                reverseButtons: true
            }, {
                confirm: 'primary',
                deny: 'success'
            })).then(function (result) {
                if (result.isConfirmed) {
                    requestReservationPaymentMethod(button, 'va');
                    return;
                }

                if (result.isDenied) {
                    requestReservationPaymentMethod(button, 'qris');
                }
            });
            return;
        }

        var selectedMethod = String(
            window.prompt('Pilih metode pembayaran yang diinginkan', 'VA') || ''
        ).trim().toLowerCase();

        if (selectedMethod === 'va' || selectedMethod === 'qris') {
            requestReservationPaymentMethod(button, selectedMethod);
        }
    }

    function handleReservationPaymentActionClick(button) {
        var reservationId = String(button && button.getAttribute('data-reservation-id') ? button.getAttribute('data-reservation-id') : '').trim();
        var item = historyReservationMap[reservationId] || null;

        if (!item) {
            showReservationAlert(
                'warning',
                '<b>DATA TIDAK DITEMUKAN</b>',
                'Riwayat reservasi tidak ditemukan'
            );
            return;
        }

        syncReservationPaymentState(item);
        applyReservationPaymentStateToRow(reservationId);

        if (item.payment_is_active) {
            openReservationPaymentSelectedMethod(button);
            return;
        }

        showReservationPaymentMethodPicker(button);
    }

    function handleReservationPaymentRevisionClick(button) {
        var reservationId = String(button && button.getAttribute('data-reservation-id') ? button.getAttribute('data-reservation-id') : '').trim();
        var item = historyReservationMap[reservationId] || null;
        var revisionUrl = getReservationPaymentRevisionUrl();
        var methodLabel = item ? String(item.payment_method_label || '').trim() || buildReservationPaymentMethodLabel(item.payment_method_key) : '';
        var confirmationHtml = 'Metode pembayaran <b class="text-danger">' + (methodLabel || 'yang dipilih') + '</b> akan dibatalkan<br><br>Lanjut revisi transaksi?';

        if (!item) {
            return;
        }

        syncReservationPaymentState(item);
        applyReservationPaymentStateToRow(reservationId);

        if (!item.payment_is_active) {
            showReservationAlert(
                'info',
                '<b>METODE SUDAH TIDAK AKTIF</b>',
                'Metode pembayaran sebelumnya sudah habis masa berlakunya. Silakan pilih Proses untuk membuat kode baru'
            );
            return;
        }

        function submitRevision() {
            var actionButton = getReservationPaymentActionButton(reservationId);

            setReservationPaymentButtonBusy(actionButton, true);
            setReservationPaymentButtonBusy(button, true);

            postReservationPaymentAction(revisionUrl, {
                reservation_id: reservationId
            }).then(function (responseData) {
                mergeReservationPaymentState(reservationId, responseData && responseData.payment ? responseData.payment : {});
                applyReservationPaymentStateToRow(reservationId);
                setReservationPaymentButtonBusy(actionButton, false);
                setReservationPaymentButtonBusy(button, false);
            }).catch(function (error) {
                setReservationPaymentButtonBusy(actionButton, false);
                setReservationPaymentButtonBusy(button, false);
                applyReservationPaymentStateToRow(reservationId);
                showReservationAlert(
                    'warning',
                    '<b>REVISI GAGAL</b>',
                    error && error.message ? String(error.message) : 'Metode pembayaran gagal direvisi'
                );
            });
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire(buildReservationSwalOptions({
                icon: 'warning',
                title: '<b>REVISI METODE PEMBAYARAN</b>',
                html: confirmationHtml,
                showCancelButton: true,
                confirmButtonText: 'YA',
                cancelButtonText: 'TIDAK',
                reverseButtons: true
            }, {
                confirm: 'success',
                cancel: 'danger'
            })).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }

                submitRevision();
            });
            return;
        }

        if (window.confirm('Metode pembayaran yang dipilih akan dibatalkan. Lanjutkan revisi?')) {
            submitRevision();
        }
    }

    function bindReservationPaymentActionButton(button) {
        if (!button || button.dataset.boundPaymentPrintClick === '1') return;

        button.dataset.boundPaymentPrintClick = '1';
        button.addEventListener('click', function () {
            handleReservationPaymentActionClick(this);
        });
    }

    function bindReservationPaymentRevisionButton(button) {
        if (!button || button.dataset.boundPaymentRevisionClick === '1') return;

        button.dataset.boundPaymentRevisionClick = '1';
        button.addEventListener('click', function () {
            handleReservationPaymentRevisionClick(this);
        });
    }

    historyReservations.forEach(function (item) {
        if (!item || typeof item !== 'object') {
            return;
        }

        mergeReservationPaymentState(item.id, item);
    });

    document.querySelectorAll('.js-user-reservation-detail-button').forEach(function (button) {
        if (button.dataset.boundDetailClick === '1') return;

        button.dataset.boundDetailClick = '1';
        button.addEventListener('click', function () {
            showHistoryDetailModal(this.getAttribute('data-reservation-id'));
        });
    });

    document.querySelectorAll('.js-user-reservation-payment-print-button').forEach(function (button) {
        bindReservationPaymentActionButton(button);
    });

    document.querySelectorAll('.js-user-reservation-payment-revise-button').forEach(function (button) {
        bindReservationPaymentRevisionButton(button);
    });

    Object.keys(historyReservationMap).forEach(function (reservationId) {
        applyReservationPaymentStateToRow(reservationId);
    });

    document.querySelectorAll('.js-user-reservation-action-form').forEach(function (actionForm) {
        if (actionForm.dataset.boundReservationActionSubmit === '1') return;

        actionForm.dataset.boundReservationActionSubmit = '1';
        actionForm.addEventListener('submit', function (event) {
            if (actionForm.dataset.confirmedSubmit === '1') {
                return;
            }

            event.preventDefault();

            var confirmTitle = String(actionForm.dataset.confirmTitle || '<b>HAPUS RESERVASI</b>');
            var confirmHtml = String(actionForm.dataset.confirmHtml || '<b class="text-danger">Reservasi yang telah dihapus tidak dapat dikembalikan</b><br><br>Lanjutkan?');
            var confirmButton = String(actionForm.dataset.confirmButton || 'LANJUT');
            var fallbackMessage = String(actionForm.dataset.confirmFallback || 'Lanjutkan?');

            if (typeof Swal !== 'undefined') {
                Swal.fire(buildReservationSwalOptions({
                    icon: 'warning',
                    title: confirmTitle,
                    html: confirmHtml,
                    showCancelButton: true,
                    confirmButtonText: confirmButton,
                    cancelButtonText: 'KEMBALI',
                    reverseButtons: true
                }, {
                    confirm: 'success',
                    cancel: 'danger'
                })).then(function (result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    actionForm.dataset.confirmedSubmit = '1';
                    HTMLFormElement.prototype.submit.call(actionForm);
                });
                return;
            }

            if (window.confirm(fallbackMessage)) {
                actionForm.dataset.confirmedSubmit = '1';
                HTMLFormElement.prototype.submit.call(actionForm);
            }
        });
    });

    showFlashMessages();

    if (!form) {
        return;
    }

    if (form.dataset.sigapReservationBound !== '1') {
        form.dataset.sigapReservationBound = '1';

        if (elements.region) {
            elements.region.addEventListener('change', function () {
                populateDistrictOptions(this.value, '');
                validateReservationForm(false);
            });
        }

        if (elements.district) {
            elements.district.addEventListener('change', function () {
                populateBuildingOptions(elements.region ? elements.region.value : '', this.value, '');
                validateReservationForm(false);
            });
        }

        if (elements.building) {
            elements.building.addEventListener('change', function () {
                syncBuildingSelection();
                renderFilteredUserCalendar();
                validateReservationForm(false);
            });
        }

        if (elements.startInput) {
            elements.startInput.addEventListener('change', function () {
                if (String(elements.startInput.value || '').trim() !== '') {
                    openReservationFormPanel(true);
                }

                syncSelectionSummary();
                validateReservationForm(false);
            });
        }

        [elements.event, elements.umkm, elements.requestFile, elements.idFile].forEach(function (field) {
            if (!field) return;

            field.addEventListener('change', function () {
                if (field === elements.requestFile || field === elements.idFile) {
                    syncUploadPreview(field);
                }

                validateReservationForm(false);
            });
        });

        if (elements.session) {
            elements.session.addEventListener('change', function () {
                syncSessionTimeFields();
                validateReservationForm(false);
            });
        }

        [elements.startTime, elements.endTime].forEach(function (field) {
            if (!field) return;

            field.addEventListener('change', function () {
                validateReservationForm(false);
            });
        });

        if (elements.estPerson) {
            elements.estPerson.addEventListener('input', function () {
                validateReservationForm(false);
            });
        }

        if (elements.printButton) {
            elements.printButton.addEventListener('click', function () {
                printReservationApplication();
            });
        }

        form.addEventListener('submit', function (event) {
            var validation = validateReservationForm(true);

            if (!validation.valid) {
                event.preventDefault();
                showReservationAlert(
                    'warning',
                    '<b>PERIKSA KEMBALI</b>',
                    'Semua field wajib terisi. Cek kembali'
                );
                return;
            }

            event.preventDefault();

            showReservationSubmitConfirmation(function () {
                if (elements.submitButton) {
                    elements.submitButton.disabled = true;
                    elements.submitButton.textContent = 'Memproses...';
                }

                form.dataset.sigapReservationSubmitting = '1';
                HTMLFormElement.prototype.submit.call(form);
            });
        });
    }

    setReservationFormVisibility(isFormInitiallyVisible);
    restoreLocationSelection();
    syncSessionTimeFields();
    syncSelectionSummary();
    syncAllUploadPreviews();
    validateReservationForm(false);
}

function initUserRatingPage() {
    var page = document.getElementById('user-rating-page');

    if (!page || page.dataset.ratingBound === '1') {
        return;
    }

    page.dataset.ratingBound = '1';

    function showRatingAlert(icon, title, text) {
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

        alert(text || title);
    }

    function syncWidget(widget, value) {
        if (!widget) return;

        var rating = Math.max(0, Math.min(5, parseInt(value, 10) || 0));
        var input = widget.querySelector('input[data-rating-input]');
        var label = widget.querySelector('[data-rating-label]');
        var buttons = widget.querySelectorAll('[data-rating-value]');

        if (input) {
            input.value = rating > 0 ? String(rating) : '';
        }

        buttons.forEach(function (button) {
            var buttonValue = parseInt(button.getAttribute('data-rating-value') || '0', 10);
            var isActive = buttonValue <= rating;

            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        if (label) {
            label.textContent = rating > 0 ? (rating + '/5') : 'Pilih rating';
        }
    }

    page.querySelectorAll('[data-user-rating-widget]').forEach(function (widget) {
        if (widget.dataset.widgetBound === '1') {
            return;
        }

        widget.dataset.widgetBound = '1';

        var initialValue = parseInt(widget.getAttribute('data-initial-rating') || '0', 10) || 0;
        var buttons = widget.querySelectorAll('[data-rating-value]');

        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                syncWidget(widget, button.getAttribute('data-rating-value'));
            });
        });

        syncWidget(widget, initialValue);
    });

    page.querySelectorAll('form[data-user-rating-form]').forEach(function (form) {
        if (form.dataset.ratingFormBound === '1') {
            return;
        }

        form.dataset.ratingFormBound = '1';

        form.addEventListener('submit', function (event) {
            var widget = form.querySelector('[data-user-rating-widget]');
            var input = widget ? widget.querySelector('input[data-rating-input]') : null;
            var ratingValue = parseInt(input ? input.value : '0', 10) || 0;

            if (ratingValue < 1 || ratingValue > 5) {
                event.preventDefault();
                showRatingAlert(
                    'warning',
                    '<b>PERIKSA KEMBALI</b>',
                    'Silakan pilih rating bintang terlebih dahulu'
                );
                return;
            }
        });
    });

    var hash = String(window.location.hash || '');
    if (hash.indexOf('#rating-') === 0) {
        var target = document.querySelector(hash);
        if (target && typeof target.scrollIntoView === 'function') {
            window.setTimeout(function () {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }, 180);
        }
    }
}

window.SigapPageInits = window.SigapPageInits || {};
window.SigapPageInits.rating = initUserRatingPage;

(function () {
    if (window.__sigapUserReservationActionBound) return;
    window.__sigapUserReservationActionBound = true;

    var activeUserReservationActionMenu = null;

    function closeUserReservationActionMenu() {
        if (!activeUserReservationActionMenu) {
            return;
        }

        var currentMenu = activeUserReservationActionMenu;
        currentMenu.dropdown.classList.remove('is-open');
        currentMenu.toggle.setAttribute('aria-expanded', 'false');
        currentMenu.menu.hidden = true;
        currentMenu.menu.style.position = '';
        currentMenu.menu.style.top = '';
        currentMenu.menu.style.left = '';
        currentMenu.menu.style.visibility = '';
        currentMenu.dropdown.appendChild(currentMenu.menu);
        activeUserReservationActionMenu = null;
    }

    function positionUserReservationActionMenu(dropdown, toggle, menu) {
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

        activeUserReservationActionMenu = {
            dropdown: dropdown,
            toggle: toggle,
            menu: menu
        };
    }

    function toggleUserReservationActionMenu(dropdown) {
        if (!dropdown) {
            return;
        }

        var toggle = dropdown.querySelector('.admin-table-action-toggle');
        var menu = dropdown.querySelector('.admin-table-action-menu');

        if (!toggle || !menu) {
            return;
        }

        if (activeUserReservationActionMenu && activeUserReservationActionMenu.dropdown === dropdown) {
            closeUserReservationActionMenu();
            return;
        }

        closeUserReservationActionMenu();
        dropdown.classList.add('is-open');
        toggle.setAttribute('aria-expanded', 'true');
        positionUserReservationActionMenu(dropdown, toggle, menu);
    }

    document.addEventListener('click', function (event) {
        var actionToggle = event.target.closest('.admin-table-action-toggle');
        if (actionToggle) {
            event.preventDefault();
            toggleUserReservationActionMenu(actionToggle.closest('.admin-table-action-dropdown'));
            return;
        }

        var actionItem = event.target.closest('.admin-table-action-item');
        if (
            actionItem &&
            activeUserReservationActionMenu &&
            activeUserReservationActionMenu.menu.contains(actionItem) &&
            actionItem.getAttribute('aria-disabled') !== 'true'
        ) {
            window.setTimeout(closeUserReservationActionMenu, 0);
        }

        if (
            activeUserReservationActionMenu &&
            !activeUserReservationActionMenu.menu.contains(event.target) &&
            !activeUserReservationActionMenu.dropdown.contains(event.target)
        ) {
            closeUserReservationActionMenu();
        }
    });

    document.addEventListener('submit', function (event) {
        if (event.target.closest('.js-user-reservation-action-form')) {
            closeUserReservationActionMenu();
        }
    });

    document.addEventListener('click', function (event) {
        var uploadTrigger = event.target.closest('.js-user-payment-upload-trigger');
        if (!uploadTrigger) {
            return;
        }

        event.preventDefault();

        var uploadForm = uploadTrigger.closest('.js-user-payment-upload-form');
        var fileInput = uploadForm ? uploadForm.querySelector('.js-user-payment-file-input') : null;

        if (!fileInput) {
            return;
        }

        fileInput.click();
    });

    document.addEventListener('change', function (event) {
        var fileInput = event.target.closest('.js-user-payment-file-input');
        if (!fileInput || !fileInput.files || !fileInput.files.length) {
            return;
        }

        var uploadForm = fileInput.closest('.js-user-payment-upload-form');
        if (!uploadForm) {
            return;
        }

        closeUserReservationActionMenu();
        HTMLFormElement.prototype.submit.call(uploadForm);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeUserReservationActionMenu();
        }
    });

    window.addEventListener('resize', closeUserReservationActionMenu);
    document.addEventListener('scroll', closeUserReservationActionMenu, true);
})();

(function () {
    if (window.__sigapTopbarDropdownBound) return;
    window.__sigapTopbarDropdownBound = true;

    function getDropdownElements(dropdown) {
        if (!dropdown) return {};
        return {
            trigger: dropdown.querySelector(':scope > .nav-link'),
            menu: dropdown.querySelector(':scope > .dropdown-menu')
        };
    }

    function closeDropdown(dropdown) {
        if (!dropdown) return;

        const { trigger, menu } = getDropdownElements(dropdown);

        dropdown.classList.remove('dropdown-open', 'show');
        if (menu) menu.classList.remove('show');
        if (trigger) trigger.setAttribute('aria-expanded', 'false');
    }

    function openDropdown(dropdown) {
        if (!dropdown) return;

        const { trigger, menu } = getDropdownElements(dropdown);

        dropdown.classList.add('dropdown-open', 'show');
        if (menu) menu.classList.add('show');
        if (trigger) trigger.setAttribute('aria-expanded', 'true');
    }

    function closeAllDropdowns(exceptDropdown) {
        document
            .querySelectorAll('.topbar .nav-item.dropdown')
            .forEach(function (dropdown) {
                if (exceptDropdown && dropdown === exceptDropdown) return;
                closeDropdown(dropdown);
            });
    }

    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('.topbar .nav-item.dropdown > .nav-link');
        const clickedInsideMenu = event.target.closest('.topbar .nav-item.dropdown > .dropdown-menu');

        if (trigger) {
            event.preventDefault();
            event.stopPropagation();

            const dropdown = trigger.closest('.nav-item.dropdown');
            const isOpen = dropdown.classList.contains('dropdown-open') || dropdown.classList.contains('show');

            closeAllDropdowns(dropdown);

            if (isOpen) {
                closeDropdown(dropdown);
            } else {
                openDropdown(dropdown);
            }

            return;
        }

        if (clickedInsideMenu) {
            return;
        }

        closeAllDropdowns();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeAllDropdowns();
        }
    });
})();

(function () {
    var oldInitPage = window.initPage;

    window.initPage = function () {
        if (typeof oldInitPage === 'function') {
            oldInitPage();
        }
        initUserDashboardFlashMessages();
        initRequiredBiodataModal();
        initializeUserDataTable();
        initUserReservationPanelLoader();
        initUserRatingPage();
        window.setTimeout(initUserReservationPage, 0);
    };

    document.addEventListener('DOMContentLoaded', function () {
        initUserDashboardFlashMessages();
        initRequiredBiodataModal();
        initializeUserDataTable();
        initUserReservationPanelLoader();
        initUserRatingPage();
        window.setTimeout(initUserReservationPage, 0);
    });
})();

(function () {
    if (window.__sigapSidebarControllerBound) return;
    window.__sigapSidebarControllerBound = true;

    var MOBILE_BREAKPOINT = 1199;
    var DESKTOP_STATE_KEY = 'sigap.user.sidebar.desktopCollapsed';
    var resizeTimer = null;
    var layoutRefreshTimer = null;
    var previousIsMobile = null;

    function getMainWrapper() {
        return document.getElementById('main-wrapper');
    }

    function isCompactViewport() {
        return window.innerWidth <= MOBILE_BREAKPOINT;
    }

    function readStoredDesktopState() {
        try {
            return window.sessionStorage.getItem(DESKTOP_STATE_KEY) === '1';
        } catch (error) {
            return false;
        }
    }

    function storeDesktopState(isCollapsed) {
        try {
            window.sessionStorage.setItem(DESKTOP_STATE_KEY, isCollapsed ? '1' : '0');
        } catch (error) {
            return;
        }
    }

    function setTogglerExpanded(isExpanded) {
        document.querySelectorAll('.sidebartoggler').forEach(function (toggle) {
            toggle.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
        });
    }

    function refreshOwlCarousels() {
        var jq = window.jQuery || window.$;
        if (!jq || !jq.fn || !jq.fn.owlCarousel) return;

        jq('.owl-carousel.owl-loaded').trigger('refresh.owl.carousel');
    }

    function refreshCalendars() {
        if (!window.calendarInstances) return;

        Object.keys(window.calendarInstances).forEach(function (key) {
            var instance = window.calendarInstances[key];
            if (!instance || typeof instance.updateSize !== 'function') return;

            try {
                instance.updateSize();
                if (typeof instance.render === 'function') {
                    instance.render();
                }
            } catch (error) {
                console.warn('Calendar resize failed', error);
            }
        });
    }

    function scheduleLayoutRefresh() {
        window.clearTimeout(layoutRefreshTimer);
        layoutRefreshTimer = window.setTimeout(function () {
            refreshOwlCarousels();
            refreshCalendars();
        }, 380);
    }

    function setDesktopSidebarCollapsed(isCollapsed) {
        document.body.setAttribute('data-sidebartype', isCollapsed ? 'mini-sidebar' : 'full');
        storeDesktopState(isCollapsed);
        setTogglerExpanded(!isCollapsed);
    }

    function closeMobileSidebar() {
        var mainWrapper = getMainWrapper();
        if (!mainWrapper) return;

        mainWrapper.classList.remove('show-sidebar');
        setTogglerExpanded(false);
    }

    function toggleMobileSidebar() {
        var mainWrapper = getMainWrapper();
        if (!mainWrapper) return;

        mainWrapper.classList.toggle('show-sidebar');
        setTogglerExpanded(mainWrapper.classList.contains('show-sidebar'));
    }

    function syncSidebarMode() {
        var mainWrapper = getMainWrapper();
        if (!mainWrapper) return;

        var isMobile = isCompactViewport();

        if (isMobile) {
            document.body.setAttribute('data-sidebartype', 'full');

            if (previousIsMobile === false) {
                closeMobileSidebar();
            } else {
                setTogglerExpanded(mainWrapper.classList.contains('show-sidebar'));
            }
        } else {
            mainWrapper.classList.remove('show-sidebar');
            setDesktopSidebarCollapsed(readStoredDesktopState());
        }

        previousIsMobile = isMobile;
    }

    function handleSidebarToggle(event) {
        event.preventDefault();
        event.stopImmediatePropagation();

        if (isCompactViewport()) {
            toggleMobileSidebar();
        } else {
            var isCollapsed = document.body.getAttribute('data-sidebartype') === 'mini-sidebar';
            setDesktopSidebarCollapsed(!isCollapsed);
        }

        scheduleLayoutRefresh();
    }

    function bindSidebarTogglers() {
        document.querySelectorAll('.sidebartoggler').forEach(function (toggle) {
            if (toggle.dataset.sigapSidebarBound === '1') return;

            toggle.dataset.sigapSidebarBound = '1';
            toggle.setAttribute('role', 'button');
            toggle.setAttribute('aria-controls', 'main-wrapper');
            toggle.addEventListener('click', handleSidebarToggle, true);
        });
    }

    document.addEventListener('click', function (event) {
        var sidebarLink = event.target.closest('.left-sidebar a');
        if (!sidebarLink) return;
        if (sidebarLink.classList.contains('sidebartoggler')) return;
        if (sidebarLink.hasAttribute('data-bs-toggle')) return;
        if (!isCompactViewport()) return;

        closeMobileSidebar();
    });

    document.addEventListener('DOMContentLoaded', function () {
        bindSidebarTogglers();
        syncSidebarMode();
        scheduleLayoutRefresh();
        syncUserDataTableForViewport(getUserDataTableElement(), true);
    });

    window.addEventListener('resize', function () {
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(function () {
            var lastIsMobile = previousIsMobile;

            bindSidebarTogglers();
            syncSidebarMode();
            syncUserDataTableForViewport(getUserDataTableElement(), true);

            if (lastIsMobile !== null && lastIsMobile !== isCompactViewport()) {
                scheduleLayoutRefresh();
            }
        }, 120);
    });
})();
