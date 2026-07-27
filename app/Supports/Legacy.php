<?php

if (!defined('SIGAP_SESSION_IDLE_TIMEOUT')) {
    define('SIGAP_SESSION_IDLE_TIMEOUT', 60 * 15);
}

if (!defined('SIGAP_USER_SESSION_IDLE_TIMEOUT')) {
    define('SIGAP_USER_SESSION_IDLE_TIMEOUT', SIGAP_SESSION_IDLE_TIMEOUT);
}

if (!defined('SIGAP_ADMIN_SESSION_IDLE_TIMEOUT')) {
    define('SIGAP_ADMIN_SESSION_IDLE_TIMEOUT', SIGAP_SESSION_IDLE_TIMEOUT);
}

foreach ([
    __DIR__ . '/Status/ReservationStatus.php',
    __DIR__ . '/Auth/RoleAccess.php',
] as $supportFile) {
    if (is_file($supportFile)) {
        require_once $supportFile;
    }
}

if (!function_exists('legacy_normalize_asset_path')) {
    function legacy_normalize_asset_path(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));
        $path = ltrim($path, '/');

        if (str_starts_with($path, 'assets/')) {
            $path = substr($path, strlen('assets/'));
        }

        if (str_starts_with($path, 'uploads/')) {
            $path = 'upload/' . substr($path, strlen('uploads/'));
        }

        return ltrim($path, '/');
    }
}

if (!function_exists('legacy_normalize_upload_relative_path')) {
    function legacy_normalize_upload_relative_path(?string $path): string
    {
        $path = trim(str_replace('\\', '/', (string) $path));
        $path = ltrim($path, '/');

        foreach (['assets/upload/', 'assets/upload/', 'uploads/', 'upload/'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $path = substr($path, strlen($prefix));
                break;
            }
        }

        return ltrim($path, '/');
    }
}

if (!function_exists('legacy_asset_path')) {
    function legacy_asset_path(string $path = ''): string
    {
        $path = legacy_normalize_asset_path($path);

        return public_path('assets' . ($path !== '' ? '/' . $path : ''));
    }
}

if (!function_exists('legacy_upload_path')) {
    function legacy_upload_path(?string $path = ''): string
    {
        $path = legacy_normalize_upload_relative_path($path);

        return public_path('assets/upload' . ($path !== '' ? '/' . $path : ''));
    }
}

if (!function_exists('legacy_asset_candidates')) {
    function legacy_asset_candidates(string $path): array
    {
        $path = trim(str_replace('\\', '/', $path));
        $path = ltrim($path, '/');
        $withoutAssetsPrefix = str_starts_with($path, 'assets/')
            ? substr($path, strlen('assets/'))
            : $path;

        $candidates = [
            public_path('assets/' . legacy_normalize_asset_path($withoutAssetsPrefix)),
        ];

        $uploadRelativePath = legacy_normalize_upload_relative_path($path);
        if ($uploadRelativePath !== $path) {
            $candidates[] = legacy_upload_path($uploadRelativePath);
        }

        return array_values(array_unique($candidates));
    }
}

if (!function_exists('legacy_first_existing_asset_path')) {
    function legacy_first_existing_asset_path(string $path): ?string
    {
        foreach (legacy_asset_candidates($path) as $candidate) {
            $realPath = realpath($candidate);

            if ($realPath !== false && is_file($realPath)) {
                return $realPath;
            }
        }

        return null;
    }
}

if (!function_exists('legacy_delete_upload_file')) {
    function legacy_delete_upload_file(?string $relativePath): void
    {
        $relativePath = legacy_normalize_upload_relative_path($relativePath);
        if ($relativePath === '' || str_contains($relativePath, '..')) {
            return;
        }

        $basePath = legacy_upload_path();
        if (!is_dir($basePath)) {
            return;
        }

        $realBasePath = realpath($basePath);
        $targetPath = legacy_upload_path($relativePath);
        $targetDirectory = realpath(dirname($targetPath));

        if ($realBasePath === false || $targetDirectory === false) {
            return;
        }

        if (!str_starts_with($targetDirectory, $realBasePath)) {
            return;
        }

        if (is_file($targetPath)) {
            @unlink($targetPath);
        }
    }
}

if (!function_exists('verify_csrf')) {
    function verify_csrf(): void
    {
        $token = (string) (request()->input('_token') ?? request()->header('X-CSRF-TOKEN', ''));
        if ($token === '' || !hash_equals((string) csrf_token(), $token)) {
            abort(419, 'Sesi Anda telah habis. Silakan muat ulang halaman.');
        }
    }
}

if (!function_exists('verify_csrf_or_redirect')) {
    function verify_csrf_or_redirect(string $path, string $message = 'Sesi Anda telah habis. Silakan ulangi.'): void
    {
        try {
            verify_csrf();
        } catch (Throwable $exception) {
            session(['error' => $message]);
            throw new \Illuminate\Http\Exceptions\HttpResponseException(redirect($path));
        }
    }
}

if (!function_exists('set_authenticated_user_session')) {
    function set_authenticated_user_session(array $user): void
    {
        session([
            'user' => $user,
            'user_id' => (int) ($user['id'] ?? 0),
        ]);
        touch_user_session_activity();
    }
}

if (!function_exists('set_authenticated_admin_session')) {
    function set_authenticated_admin_session(array $admin): void
    {
        session([
            'admin' => $admin,
            'admin_id' => (int) ($admin['id'] ?? 0),
        ]);
        touch_admin_session_activity();
    }
}

if (!function_exists('user_user')) {
    function user_user(): ?array
    {
        $user = session('user');

        return is_array($user) ? $user : null;
    }
}

if (!function_exists('admin_user')) {
    function admin_user(): ?array
    {
        $admin = session('admin');

        return is_array($admin) ? $admin : null;
    }
}

if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in(): bool
    {
        return user_user() !== null;
    }
}

if (!function_exists('is_admin_logged_in')) {
    function is_admin_logged_in(): bool
    {
        return admin_user() !== null;
    }
}

if (!function_exists('destroy_user_auth_session')) {
    function destroy_user_auth_session(): void
    {
        session()->forget(['user', 'user_id', 'user_last_activity']);
    }
}

if (!function_exists('destroy_admin_auth_session')) {
    function destroy_admin_auth_session(): void
    {
        session()->forget(['admin', 'admin_id', 'admin_last_activity']);
    }
}

if (!function_exists('touch_user_session_activity')) {
    function touch_user_session_activity(): void
    {
        session(['user_last_activity' => time()]);
    }
}

if (!function_exists('touch_admin_session_activity')) {
    function touch_admin_session_activity(): void
    {
        session(['admin_last_activity' => time()]);
    }
}

if (!function_exists('user_session_idle_timeout_seconds')) {
    function user_session_idle_timeout_seconds(): int
    {
        return SIGAP_USER_SESSION_IDLE_TIMEOUT;
    }
}

if (!function_exists('admin_session_idle_timeout_seconds')) {
    function admin_session_idle_timeout_seconds(): int
    {
        return SIGAP_ADMIN_SESSION_IDLE_TIMEOUT;
    }
}

if (!function_exists('auth_session_expired_message')) {
    function auth_session_expired_message(string $guard = 'user'): string
    {
        return 'Sesi ' . ($guard === 'admin' ? 'admin' : 'Anda') . ' telah berakhir. Silakan masuk ulang.';
    }
}

if (!function_exists('auth_browser_session_storage_key')) {
    function auth_browser_session_storage_key(string $guard): string
    {
        return 'sigap_' . $guard . '_session_active';
    }
}

if (!function_exists('require_admin')) {
    function require_admin(): void
    {
        if (!is_admin_logged_in()) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(redirect('admin/login'));
        }

        touch_admin_session_activity();
    }
}

if (!function_exists('require_admin_menu_access')) {
    function require_admin_menu_access(string $accessKey): void
    {
        require_admin();
    }
}

if (!function_exists('admin_menu_blueprint')) {
    function admin_menu_blueprint(): array
    {
        return [];
    }
}

if (!function_exists('admin_flatten_leaf_menu_items')) {
    function admin_flatten_leaf_menu_items(array $items): array
    {
        return [];
    }
}

if (!function_exists('admin_role_access_keys')) {
    function admin_role_access_keys(int $roleId): array
    {
        return [];
    }
}

if (!function_exists('resolve_admin_access_overview')) {
    function resolve_admin_access_overview(): array
    {
        return [];
    }
}

if (!function_exists('resolve_admin_role_context')) {
    function resolve_admin_role_context(array $admin = []): array
    {
        return [
            'scope_type' => 'all',
            'district_name' => '',
            'dashboard_title' => 'Infografis Admin',
        ];
    }
}

if (!function_exists('reservation_status_normalize')) {
    function reservation_status_normalize(string $status): string
    {
        $status = strtoupper(trim($status));
        $status = str_replace(['-', '_'], ' ', $status);
        $status = preg_replace('/\s+/', ' ', $status) ?? $status;

        return $status;
    }
}

if (!function_exists('reservation_status_display_key')) {
    function reservation_status_display_key(?string $status): string
    {
        $status = reservation_status_normalize((string) $status);

        return match ($status) {
            '',
            'BARU',
            'RESERVASI',
            'RESERVASI BARU',
            'PENGAJUAN BARU',
            'MENUNGGU PERSETUJUAN' => 'RESERVASI BARU',
            'BERKAS TIDAK SESUAI',
            'REVISI RESERVASI',
            'BERKAS RESERVASI TIDAK SESUAI' => 'BERKAS RESERVASI TIDAK SESUAI',
            'KERJASAMA UMKM',
            'KERJA SAMA UMKM' => 'KERJASAMA UMKM',
            'VERIFIKASI',
            'PROSES VERIFIKASI' => 'PROSES VERIFIKASI',
            'REVISI KERJASAMA UMKM',
            'REVISI KERJA SAMA UMKM',
            'BERKAS VERIFIKASI TIDAK SESUAI' => 'BERKAS VERIFIKASI TIDAK SESUAI',
            'PEMBAYARAN',
            'MENUNGGU PEMBAYARAN' => 'MENUNGGU PEMBAYARAN',
            'PROSES PEMBAYARAN',
            'CEK PEMBAYARAN' => 'CEK PEMBAYARAN',
            'REVISI PEMBAYARAN',
            'BERKAS PEMBAYARAN TIDAK SESUAI' => 'BERKAS PEMBAYARAN TIDAK SESUAI',
            'LUNAS',
            'PEMBAYARAN LUNAS' => 'PEMBAYARAN LUNAS',
            'DITOLAK',
            'PERMOHONAN DITOLAK' => 'PERMOHONAN DITOLAK',
            'BATAL',
            'DIBATALKAN',
            'DIBATALKAN PEMOHON' => 'DIBATALKAN PEMOHON',
            'SELESAI',
            'ACARA SELESAI' => 'ACARA SELESAI',
            default => $status,
        };
    }
}

if (!function_exists('reservation_status_storage_value')) {
    function reservation_status_storage_value(?string $status): string
    {
        return reservation_status_display_key($status);
    }
}

if (!function_exists('reservation_status_filter_values')) {
    function reservation_status_filter_values(array $statuses): array
    {
        $normalizedStatuses = [];

        foreach ($statuses as $status) {
            $displayKey = reservation_status_display_key((string) $status);

            if ($displayKey !== '') {
                $normalizedStatuses[] = $displayKey;
            }
        }

        return array_values(array_unique($normalizedStatuses));
    }
}

if (!function_exists('reservation_status_uses_order_code')) {
    function reservation_status_uses_order_code(?string $status): bool
    {
        return in_array(reservation_status_display_key($status), [
            'MENUNGGU PEMBAYARAN',
            'CEK PEMBAYARAN',
            'BERKAS PEMBAYARAN TIDAK SESUAI',
            'PEMBAYARAN LUNAS',
            'ACARA SELESAI',
        ], true);
    }
}

if (!function_exists('reservation_status_label')) {
    function reservation_status_label(?string $status): string
    {
        $status = reservation_status_display_key($status);

        return mb_convert_case(strtolower($status), MB_CASE_TITLE, 'UTF-8');
    }
}

if (!function_exists('reservation_status_html_label')) {
    function reservation_status_html_label(?string $status): string
    {
        return e(reservation_status_label($status));
    }
}

if (!function_exists('reservation_status_tone')) {
    function reservation_status_tone(?string $status): string
    {
        return match (reservation_status_display_key($status)) {
            'RESERVASI BARU',
            'PROSES VERIFIKASI',
            'CEK PEMBAYARAN' => 'warning',
            'KERJASAMA UMKM' => 'info',
            'MENUNGGU PEMBAYARAN' => 'primary',
            'PEMBAYARAN LUNAS' => 'success',
            'PERMOHONAN DITOLAK',
            'DIBATALKAN PEMOHON' => 'danger',
            'BERKAS RESERVASI TIDAK SESUAI',
            'BERKAS VERIFIKASI TIDAK SESUAI',
            'BERKAS PEMBAYARAN TIDAK SESUAI',
            'ACARA SELESAI' => 'secondary',
            default => 'secondary',
        };
    }
}

if (!function_exists('reservation_status_class_lookup')) {
    function reservation_status_class_lookup(): array
    {
        $statuses = [
            'RESERVASI BARU',
            'BERKAS RESERVASI TIDAK SESUAI',
            'KERJASAMA UMKM',
            'PROSES VERIFIKASI',
            'BERKAS VERIFIKASI TIDAK SESUAI',
            'MENUNGGU PEMBAYARAN',
            'CEK PEMBAYARAN',
            'BERKAS PEMBAYARAN TIDAK SESUAI',
            'PEMBAYARAN LUNAS',
            'PERMOHONAN DITOLAK',
            'DIBATALKAN PEMOHON',
            'ACARA SELESAI',
        ];
        $lookup = [];

        foreach ($statuses as $status) {
            $lookup[$status] = reservation_status_tone($status);
        }

        $lookup['BERKAS TIDAK SESUAI'] = reservation_status_tone('BERKAS RESERVASI TIDAK SESUAI');

        return $lookup;
    }
}

if (!function_exists('reservation_status_matches')) {
    function reservation_status_matches(string $status, array $statuses): bool
    {
        $normalizedStatus = reservation_status_display_key($status);
        $normalizedStatuses = reservation_status_filter_values($statuses);

        return in_array($normalizedStatus, $normalizedStatuses, true);
    }
}
