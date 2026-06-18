@include('partials.user.header')

<?php
$userBrowserSessionGuardMode = consume_auth_browser_session_guard_mode('user');
$userBrowserSessionStorageKey = auth_browser_session_storage_key('user');
$userBrowserSessionLogoutUrl = base_url('logout');
$userSessionExpiredLogoutUrl = base_url('logout?reason=expired');
$userKeepAliveUrl = base_url('user/session/keepalive');
$userSharedActivityStorageKey = 'sigap.user.last-activity';
?>

<?php if ($userBrowserSessionGuardMode !== 'none'): ?>
<style>
    html.auth-session-guard-pending body {
        visibility: hidden;
    }
</style>
<script>
    (function () {
        var docEl = document.documentElement;
        var guardMode = <?php echo json_encode($userBrowserSessionGuardMode); ?>;
        var storageKey = <?php echo json_encode($userBrowserSessionStorageKey); ?>;
        var logoutUrl = <?php echo json_encode($userBrowserSessionLogoutUrl); ?>;

        docEl.classList.add('auth-session-guard-pending');

        function allowAccess() {
            docEl.classList.remove('auth-session-guard-pending');
        }

        try {
            if (guardMode === 'bootstrap') {
                window.sessionStorage.setItem(storageKey, String(Date.now()));
                allowAccess();
                return;
            }

            if (window.sessionStorage.getItem(storageKey)) {
                allowAccess();
                return;
            }
        } catch (error) {
        }

        window.location.replace(logoutUrl);
    })();
</script>
<?php endif; ?>

@include('partials.auth.session-idle-guard', [
    'scope' => 'user',
    'keepAliveUrl' => $userKeepAliveUrl,
    'logoutUrl' => $userBrowserSessionLogoutUrl,
    'expiredLogoutUrl' => $userSessionExpiredLogoutUrl,
    'browserSessionKey' => $userBrowserSessionStorageKey,
    'sharedActivityKey' => $userSharedActivityStorageKey,
    'idleTimeoutSeconds' => user_session_idle_timeout_seconds(),
])

<div id="main-wrapper">
    @include('partials.user.sidebar')

    <div class="page-wrapper">
        @include('partials.user.navbar')

        <div class="body-wrapper" id="main-content">
            @yield('content')
        </div>
    </div>

    <div class="dark-transparent sidebartoggler"></div>
</div>

@include('partials.user.footer')
