<script>
    (function () {
        var scope = <?php echo json_encode($scope ?? 'user'); ?>;
        var keepAliveUrl = <?php echo json_encode($keepAliveUrl ?? ''); ?>;
        var logoutUrl = <?php echo json_encode($logoutUrl ?? ''); ?>;
        var expiredLogoutUrl = <?php echo json_encode($expiredLogoutUrl ?? ($logoutUrl ?? '')); ?>;
        var browserSessionKey = <?php echo json_encode($browserSessionKey ?? ''); ?>;
        var sharedActivityKey = <?php echo json_encode($sharedActivityKey ?? ''); ?>;
        var idleTimeoutMs = Math.max(300000, Number(<?php echo json_encode((int) ($idleTimeoutSeconds ?? 900) * 1000); ?>) || 900000);
        var keepAliveThrottleMs = 60000;
        var passiveEvents = ['pointerdown', 'keydown', 'touchstart', 'scroll', 'mousemove', 'click'];
        var lastLocalActivityAt = Date.now();
        var lastUiPulseAt = 0;
        var keepAliveInFlight = false;

        function canUseStorage(storageType) {
            try {
                var storage = window[storageType];
                if (!storage) {
                    return false;
                }

                var testKey = '__sigap_' + scope + '_' + storageType + '__';
                storage.setItem(testKey, '1');
                storage.removeItem(testKey);
                return true;
            } catch (error) {
                return false;
            }
        }

        var hasLocalStorage = canUseStorage('localStorage');
        var hasSessionStorage = canUseStorage('sessionStorage');

        function readNumber(storageType, key) {
            if (!key) {
                return 0;
            }

            try {
                var storage = window[storageType];
                var value = storage ? Number(storage.getItem(key) || 0) : 0;
                return isFinite(value) && value > 0 ? value : 0;
            } catch (error) {
                return 0;
            }
        }

        function writeNumber(storageType, key, value) {
            if (!key) {
                return;
            }

            try {
                var storage = window[storageType];
                if (storage) {
                    storage.setItem(key, String(value));
                }
            } catch (error) {
            }
        }

        function getSharedActivityAt() {
            return hasLocalStorage ? readNumber('localStorage', sharedActivityKey) : 0;
        }

        function setSharedActivityAt(timestamp) {
            if (hasLocalStorage) {
                writeNumber('localStorage', sharedActivityKey, timestamp);
            }
        }

        function markBrowserSession(timestamp) {
            if (hasSessionStorage) {
                writeNumber('sessionStorage', browserSessionKey, timestamp);
            }
        }

        function referenceActivityAt() {
            return Math.max(lastLocalActivityAt, getSharedActivityAt());
        }

        function isGloballyIdle(now) {
            return (now - referenceActivityAt()) >= idleTimeoutMs;
        }

        function redirectToLogout(forceExpiredReason) {
            var targetUrl = forceExpiredReason ? expiredLogoutUrl : logoutUrl;

            if (!targetUrl) {
                window.location.reload();
                return;
            }

            window.location.replace(targetUrl);
        }

        function sendKeepAlive() {
            var now = Date.now();

            if (keepAliveInFlight || !keepAliveUrl || isGloballyIdle(now)) {
                return;
            }

            var sharedLastPingAt = hasLocalStorage ? readNumber('localStorage', sharedActivityKey + '.ping') : 0;
            if (sharedLastPingAt > 0 && (now - sharedLastPingAt) < keepAliveThrottleMs) {
                return;
            }

            keepAliveInFlight = true;
            if (hasLocalStorage) {
                writeNumber('localStorage', sharedActivityKey + '.ping', now);
            }

            fetch(keepAliveUrl, {
                method: 'POST',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(function (response) {
                keepAliveInFlight = false;

                if (response.status === 401 || response.status === 403 || response.status === 440) {
                    redirectToLogout(true);
                    return;
                }

                if (!response.ok) {
                    return;
                }

                var ts = Date.now();
                markBrowserSession(ts);
                setSharedActivityAt(Math.max(referenceActivityAt(), ts));
            }).catch(function () {
                keepAliveInFlight = false;
            });
        }

        function handleActivity(event) {
            var now = Date.now();
            var eventType = event && event.type ? String(event.type) : '';

            if ((eventType === 'mousemove' || eventType === 'scroll') && (now - lastUiPulseAt) < 1000) {
                return;
            }

            lastUiPulseAt = now;

            if (isGloballyIdle(now)) {
                redirectToLogout(true);
                return;
            }

            lastLocalActivityAt = now;
            setSharedActivityAt(now);
            markBrowserSession(now);
            sendKeepAlive();
        }

        passiveEvents.forEach(function (eventName) {
            window.addEventListener(eventName, handleActivity, {
                passive: true
            });
        });

        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') {
                handleActivity({
                    type: 'visibilitychange'
                });
            }
        });

        window.addEventListener('focus', function () {
            handleActivity({
                type: 'focus'
            });
        });

        window.addEventListener('storage', function (event) {
            if (!event || event.key !== sharedActivityKey) {
                return;
            }

            var syncedActivityAt = Number(event.newValue || 0);
            if (isFinite(syncedActivityAt) && syncedActivityAt > lastLocalActivityAt) {
                lastLocalActivityAt = syncedActivityAt;
            }
        });

        lastLocalActivityAt = Math.max(lastLocalActivityAt, getSharedActivityAt());
        setSharedActivityAt(lastLocalActivityAt);
        markBrowserSession(lastLocalActivityAt);
    })();
</script>
