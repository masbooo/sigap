@include('partials.admin.header')

<?php
$adminBrowserSessionGuardMode = consume_auth_browser_session_guard_mode('admin');
$adminBrowserSessionStorageKey = auth_browser_session_storage_key('admin');
$adminBrowserSessionLogoutUrl = base_url('admin/logout');
$adminSessionExpiredLogoutUrl = base_url('admin/logout?reason=expired');
$adminKeepAliveUrl = base_url('admin/session/keepalive');
$adminSharedActivityStorageKey = 'sigap.admin.last-activity';
?>

<?php if ($adminBrowserSessionGuardMode !== 'none'): ?>
<style>
    html.auth-session-guard-pending body {
        visibility: hidden;
    }
</style>
<script>
    (function () {
        var docEl = document.documentElement;
        var guardMode = <?php echo json_encode($adminBrowserSessionGuardMode); ?>;
        var storageKey = <?php echo json_encode($adminBrowserSessionStorageKey); ?>;
        var logoutUrl = <?php echo json_encode($adminBrowserSessionLogoutUrl); ?>;

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
    'scope' => 'admin',
    'keepAliveUrl' => $adminKeepAliveUrl,
    'logoutUrl' => $adminBrowserSessionLogoutUrl,
    'expiredLogoutUrl' => $adminSessionExpiredLogoutUrl,
    'browserSessionKey' => $adminBrowserSessionStorageKey,
    'sharedActivityKey' => $adminSharedActivityStorageKey,
    'idleTimeoutSeconds' => admin_session_idle_timeout_seconds(),
])

<div id="main-wrapper">
    @include('partials.admin.sidebar')

    <div class="page-wrapper">
        @include('partials.admin.navbar')

        <div class="body-wrapper" id="main-content">
            @yield('content')
        </div>
    </div>

    <div class="dark-transparent sidebartoggler"></div>
</div>

@include('partials.admin.footer')
