(function () {
    function reloadCaptcha() {
        var captchaImg = document.getElementById('captcha-img');
        if (!captchaImg) return;

        var baseSrc = captchaImg.getAttribute('src') || '';
        var cleanSrc = baseSrc.split('?')[0];
        captchaImg.src = cleanSrc + '?t=' + Date.now();
    }

    function bindCaptchaReload() {
        var button = document.getElementById('reload-captcha-btn');
        if (!button) return;

        button.addEventListener('click', function () {
            reloadCaptcha();
        });
    }

    function readAuthFlashMessage(type) {
        var flashEl = document.querySelector('[data-auth-flash="' + type + '"]');
        var dataMessage = flashEl ? String(flashEl.getAttribute('data-message') || '').trim() : '';

        if (dataMessage !== '') {
            return dataMessage;
        }

        return type === 'error'
            ? String(window.__loginErrorMessage || '').trim()
            : String(window.__loginSuccessMessage || '').trim();
    }

    function markAuthFlashMessageHandled(type) {
        var flashEl = document.querySelector('[data-auth-flash="' + type + '"]');

        if (flashEl) {
            flashEl.removeAttribute('data-message');
        }

        if (type === 'error') {
            window.__loginErrorMessage = '';
            return;
        }

        window.__loginSuccessMessage = '';
    }

    function showError() {
        var errorMessage = readAuthFlashMessage('error');
        if (!errorMessage) return;

        markAuthFlashMessageHandled('error');

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: '<b>GAGAL</b>',
                text: errorMessage,
                confirmButtonText: 'OK',
                timer: 3000,
                timerProgressBar: true
            });
        } else {
            alert(errorMessage);
        }
    }

    function showSuccess() {
        var successMessage = readAuthFlashMessage('success');
        if (!successMessage) return;

        markAuthFlashMessageHandled('success');

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: '<b>BERHASIL</b>',
                text: successMessage,
                confirmButtonText: 'OK',
                timer: 3000,
                timerProgressBar: true
            });
        } else {
            alert(successMessage);
        }
    }

    function handleAuthenticatedSessionRedirect() {
        var config = window.__authSessionRedirectConfig || {};

        if (!config || config.active !== true) {
            return;
        }

        var storageKey = String(config.storageKey || '').trim();
        var dashboardUrl = String(config.dashboardUrl || '').trim();
        var logoutUrl = String(config.logoutUrl || '').trim();
        var hasBrowserSessionMarker = false;

        try {
            hasBrowserSessionMarker = storageKey !== '' && !!window.sessionStorage.getItem(storageKey);
        } catch (error) {
            hasBrowserSessionMarker = false;
        }

        if (hasBrowserSessionMarker && dashboardUrl !== '') {
            window.location.replace(dashboardUrl);
            return;
        }

        if (logoutUrl !== '') {
            window.location.replace(logoutUrl);
        }
    }

    function debounce(fn, delay) {
        var timer = null;
        return function () {
            var context = this;
            var args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () {
                fn.apply(context, args);
            }, delay);
        };
    }

    function getFieldContainer(input) {
        return input.closest('.password-field, .mb-3, .mb-4, .col-md-6') || input.parentElement;
    }

    function resetFieldState(input) {
        if (!input) return;

        input.classList.remove('is-invalid', 'is-valid');

        var container = getFieldContainer(input);
        if (!container) return;

        var invalidEl = container.querySelector('.invalid-feedback');
        var validEl = container.querySelector('.valid-feedback');

        if (invalidEl) invalidEl.style.display = 'none';
        if (validEl) validEl.style.display = 'none';
    }

    function setInputState(input, type, invalidMessage, validMessage) {
        if (!input) return false;

        resetFieldState(input);

        var container = getFieldContainer(input);
        var invalidEl = container ? container.querySelector('.invalid-feedback') : null;
        var validEl = container ? container.querySelector('.valid-feedback') : null;

        if (invalidEl && typeof invalidMessage === 'string') {
            invalidEl.textContent = invalidMessage;
        }

        if (validEl && typeof validMessage === 'string') {
            validEl.textContent = validMessage;
        }

        if (type === 'invalid') {
            input.classList.add('is-invalid');
            if (invalidEl) invalidEl.style.display = 'block';
            return false;
        }

        if (type === 'valid') {
            input.classList.add('is-valid');
            if (validEl) validEl.style.display = 'block';
            return true;
        }

        return false;
    }

    function bindPasswordToggle() {
        document.querySelectorAll('.password-toggle-icon').forEach(function (toggle) {
            function doToggle() {
                var targetId = toggle.getAttribute('data-target');
                var input = document.getElementById(targetId);
                var icon = toggle.querySelector('i');

                if (!input || !icon) return;

                if (input.type === 'password') {
                    input.type = 'text';
                    icon.className = 'ti ti-eye-off';
                    toggle.setAttribute('aria-label', 'Sembunyikan password');
                } else {
                    input.type = 'password';
                    icon.className = 'ti ti-eye';
                    toggle.setAttribute('aria-label', 'Tampilkan password');
                }
            }

            toggle.addEventListener('click', doToggle);

            toggle.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    doToggle();
                }
            });
        });
    }

    function validateRequiredField(input, emptyMessage, validMessage) {
        if (!input) return false;

        if (input.value.trim() === '') {
            return setInputState(input, 'invalid', emptyMessage, validMessage);
        }

        return setInputState(input, 'valid', emptyMessage, validMessage);
    }

    function bindRequiredFieldValidation(input, emptyMessage, validMessage) {
        if (!input) return;

        function handleValidation() {
            if (input.value.trim() === '') {
                validateRequiredField(input, emptyMessage, validMessage);
                return;
            }

            validateRequiredField(input, emptyMessage, validMessage);
        }

        ['input', 'change', 'blur'].forEach(function (eventName) {
            input.addEventListener(eventName, handleValidation);
        });

        if (input.value.trim() !== '') {
            validateRequiredField(input, emptyMessage, validMessage);
        } else {
            resetFieldState(input);
        }
    }

    function validateRegisterUsernameFormat(input) {
        if (!input) return false;

        var value = input.value.trim();

        if (value === '') {
            return setInputState(
                input,
                'invalid',
                'Username belum diisi',
                'Username belum digunakan (tersedia)'
            );
        }

        if (!/^[A-Za-z0-9]{6,30}$/.test(value)) {
            return setInputState(
                input,
                'invalid',
                'Username minimal 6 karakter dan hanya boleh terdiri dari huruf dan angka',
                'Username belum digunakan (tersedia)'
            );
        }

        return true;
    }

    function validateRegisterPassword(input) {
        if (!input) return false;

        var value = input.value.trim();

        if (value === '') {
            return setInputState(input, 'invalid', 'Password belum diisi', 'Password sesuai');
        }

        if (!/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/.test(value)) {
            return setInputState(
                input,
                'invalid',
                'Password minimal 8 karakter dan harus terdiri dari huruf dan angka',
                'Password sesuai'
            );
        }

        return setInputState(
            input,
            'valid',
            'Password minimal 8 karakter dan harus terdiri dari huruf dan angka',
            'Password sesuai'
        );
    }

    function validatePasswordConfirmation(passwordInput, confirmationInput) {
        if (!confirmationInput) return false;

        if (confirmationInput.value.trim() === '') {
            return setInputState(
                confirmationInput,
                'invalid',
                'Ulangi Password belum diisi',
                'Ulangi Password sesuai'
            );
        }

        if (!passwordInput || confirmationInput.value !== passwordInput.value) {
            return setInputState(
                confirmationInput,
                'invalid',
                'Ulangi Password tidak sesuai',
                'Ulangi Password sesuai'
            );
        }

        return setInputState(
            confirmationInput,
            'valid',
            'Ulangi Password tidak sesuai',
            'Ulangi Password sesuai'
        );
    }

    function validateNikField(input) {
        if (!input) return false;

        var value = input.value.trim();

        if (value === '') {
            return setInputState(input, 'invalid', 'NIK belum diisi', 'NIK sesuai');
        }

        if (!/^[0-9]{16}$/.test(value)) {
            return setInputState(input, 'invalid', 'NIK harus 16 digit angka', 'NIK sesuai');
        }

        return setInputState(input, 'valid', 'NIK harus 16 digit angka', 'NIK sesuai');
    }

    function validatePhoneField(input) {
        if (!input) return false;

        var value = input.value.trim();

        if (value === '') {
            return setInputState(input, 'invalid', 'Telp / HP belum diisi', 'Telp / HP sesuai');
        }

        if (!/^[0-9]{10,15}$/.test(value)) {
            return setInputState(input, 'invalid', 'Telp / HP harus 10-15 digit angka', 'Telp / HP sesuai');
        }

        return setInputState(input, 'valid', 'Telp / HP harus 10-15 digit angka', 'Telp / HP sesuai');
    }

    function bindLoginValidation() {
        var form = document.getElementById('loginForm');
        if (!form) return;

        var usernameInput = document.getElementById('loginUsername');
        var passwordInput = document.getElementById('loginPassword');
        var captchaInput = document.getElementById('loginCaptcha');

        bindRequiredFieldValidation(usernameInput, 'Username belum diisi', 'Username sudah diisi');
        bindRequiredFieldValidation(passwordInput, 'Password belum diisi', 'Password sudah diisi');
        bindRequiredFieldValidation(captchaInput, 'Captcha belum diisi', 'Captcha sudah diisi');

        form.addEventListener('submit', function (e) {
            var validUsername = validateRequiredField(usernameInput, 'Username belum diisi', 'Username sudah diisi');
            var validPassword = validateRequiredField(passwordInput, 'Password belum diisi', 'Password sudah diisi');
            var validCaptcha = validateRequiredField(captchaInput, 'Captcha belum diisi', 'Captcha sudah diisi');

            if (!validUsername || !validPassword || !validCaptcha) {
                e.preventDefault();

                if (!validUsername && usernameInput) {
                    usernameInput.focus();
                    return;
                }

                if (!validPassword && passwordInput) {
                    passwordInput.focus();
                    return;
                }

                if (!validCaptcha && captchaInput) {
                    captchaInput.focus();
                }
            }
        });
    }

    function bindRegisterValidation() {
        var form = document.getElementById('registerForm');
        if (!form) return;

        var usernameInput = document.getElementById('registerUsername');
        var passwordInput = document.getElementById('registerPassword');
        var confirmationInput = document.getElementById('registerPasswordConfirmation');
        var captchaInput = document.getElementById('registerCaptcha');

        var checkUsernameUrl = String(form.getAttribute('data-check-username-url') || window.__checkUsernameUrl || '/cek-username').trim();
        var lastUsernameChecked = '';
        var lastUsernameAvailable = null;

        async function validateUsernameAvailability() {
            if (!usernameInput) return false;

            var formatValid = validateRegisterUsernameFormat(usernameInput);
            if (!formatValid) {
                lastUsernameChecked = usernameInput.value.trim();
                lastUsernameAvailable = null;
                return false;
            }

            var username = usernameInput.value.trim();

            try {
                var response = await fetch(checkUsernameUrl + '?username=' + encodeURIComponent(username), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                var data = await response.json();

                lastUsernameChecked = username;
                lastUsernameAvailable = !!data.available;

                if (data.available) {
                    return setInputState(
                        usernameInput,
                        'valid',
                        'Username sudah digunakan',
                        data.message || 'Username belum digunakan (tersedia)'
                    );
                }

                return setInputState(
                    usernameInput,
                    'invalid',
                    data.message || 'Username sudah digunakan',
                    'Username belum digunakan (tersedia)'
                );
            } catch (e) {
                lastUsernameChecked = username;
                lastUsernameAvailable = null;
                return setInputState(
                    usernameInput,
                    'invalid',
                    'Gagal memeriksa username',
                    'Username belum digunakan (tersedia)'
                );
            }
        }

        var debouncedUsernameValidation = debounce(function () {
            validateUsernameAvailability();
        }, 400);

        if (usernameInput) {
            usernameInput.addEventListener('input', function () {
                lastUsernameChecked = '';
                lastUsernameAvailable = null;
                resetFieldState(usernameInput);

                var formatValid = validateRegisterUsernameFormat(usernameInput);
                if (formatValid) {
                    debouncedUsernameValidation();
                }
            });

            usernameInput.addEventListener('blur', function () {
                validateUsernameAvailability();
            });
        }

        if (passwordInput) {
            passwordInput.addEventListener('input', function () {
                validateRegisterPassword(passwordInput);
                validatePasswordConfirmation(passwordInput, confirmationInput);
            });
        }

        if (confirmationInput) {
            confirmationInput.addEventListener('input', function () {
                validatePasswordConfirmation(passwordInput, confirmationInput);
            });
        }

        if (captchaInput) {
            captchaInput.addEventListener('input', function () {
                validateRequiredField(captchaInput, 'Captcha belum diisi', 'Captcha sudah diisi');
            });
        }

        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            var username = usernameInput ? usernameInput.value.trim() : '';
            var validUsername = false;

            if (!validateRegisterUsernameFormat(usernameInput)) {
                validUsername = false;
            } else if (username !== lastUsernameChecked || lastUsernameAvailable === null) {
                validUsername = await validateUsernameAvailability();
            } else {
                validUsername = !!lastUsernameAvailable;
                if (!validUsername) {
                    setInputState(
                        usernameInput,
                        'invalid',
                        'Username sudah digunakan',
                        'Username belum digunakan (tersedia)'
                    );
                }
            }

            var validPassword = validateRegisterPassword(passwordInput);
            var validConfirmation = validatePasswordConfirmation(passwordInput, confirmationInput);
            var validCaptcha = validateRequiredField(captchaInput, 'Captcha belum diisi', 'Captcha sudah diisi');

            if (validUsername && validPassword && validConfirmation && validCaptcha) {
                form.submit();
            }
        });
    }

    function bindForgotPasswordValidation() {
        var verifyForm = document.getElementById('forgotPasswordVerifyForm');
        var resetForm = document.getElementById('forgotPasswordResetForm');

        if (verifyForm) {
            var nikInput = document.getElementById('forgotNik');
            var phoneInput = document.getElementById('forgotPhone');
            var captchaInput = document.getElementById('forgotCaptcha');

            if (nikInput) {
                nikInput.addEventListener('input', function () {
                    validateNikField(nikInput);
                });
            }

            if (phoneInput) {
                phoneInput.addEventListener('input', function () {
                    validatePhoneField(phoneInput);
                });
            }

            if (captchaInput) {
                captchaInput.addEventListener('input', function () {
                    validateRequiredField(captchaInput, 'Captcha belum diisi', 'Captcha sudah diisi');
                });
            }

            verifyForm.addEventListener('submit', function (e) {
                var validNik = validateNikField(nikInput);
                var validPhone = validatePhoneField(phoneInput);
                var validCaptcha = validateRequiredField(captchaInput, 'Captcha belum diisi', 'Captcha sudah diisi');

                if (!validNik || !validPhone || !validCaptcha) {
                    e.preventDefault();
                }
            });
        }

        if (resetForm) {
            var passwordInput = document.getElementById('forgotPassword');
            var confirmationInput = document.getElementById('forgotPasswordConfirmation');

            if (passwordInput) {
                passwordInput.addEventListener('input', function () {
                    validateRegisterPassword(passwordInput);
                    validatePasswordConfirmation(passwordInput, confirmationInput);
                });
            }

            if (confirmationInput) {
                confirmationInput.addEventListener('input', function () {
                    validatePasswordConfirmation(passwordInput, confirmationInput);
                });
            }

            resetForm.addEventListener('submit', function (e) {
                var validPassword = validateRegisterPassword(passwordInput);
                var validConfirmation = validatePasswordConfirmation(passwordInput, confirmationInput);

                if (!validPassword || !validConfirmation) {
                    e.preventDefault();
                }
            });
        }
    }

    window.addEventListener('DOMContentLoaded', function () {
        handleAuthenticatedSessionRedirect();
        bindCaptchaReload();
        bindPasswordToggle();
        bindLoginValidation();
        bindRegisterValidation();
        bindForgotPasswordValidation();
        showError();
        showSuccess();
    });
})();
