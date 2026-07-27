@extends('layouts.admin')

@section('content')
@php
    $roleContext = $roleContext ?? resolve_admin_role_context($admin ?? admin_user() ?? []);
    $pageMeta = resolve_admin_page_meta();
    $leafMenuItems = $leafMenuItems ?? admin_flatten_leaf_menu_items($menuBlueprint ?? admin_menu_blueprint());
    $accessOverview = $accessOverview ?? resolve_admin_access_overview();
    $roles = $roles ?? [];
    $accessSelections = $accessSelections ?? [];
    $canManageAccess = (bool) ($canManageAccess ?? false);
    $messages = $messages ?? ['success' => '', 'error' => ''];

    $roleMap = [];
    foreach ($roles as $role) {
        $roleId = (int) ($role['id'] ?? 0);
        if ($roleId <= 0) {
            continue;
        }

        $roleMap[$roleId] = $role;
    }

    $roleOrder = [1, 2, 3, 4];
    $groupedMenus = [];
    foreach ($leafMenuItems as $item) {
        $heading = (string) ($item['heading'] ?? 'MENU');
        $groupedMenus[$heading][] = $item;
    }
@endphp

<div class="container-fluid">
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Pengaturan Hak Akses</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            @foreach (($pageMeta['breadcrumbs'] ?? []) as $index => $crumb)
                                @php
                                    $isLast = $index === count($pageMeta['breadcrumbs'] ?? []) - 1;
                                @endphp
                                <li class="breadcrumb-item{{ $isLast ? ' active' : '' }}" @if ($isLast) aria-current="page" @endif>
                                    @if (!$isLast && !empty($crumb['href']))
                                        <a class="text-muted text-decoration-none" href="{{ $crumb['href'] }}">{{ $crumb['label'] }}</a>
                                    @else
                                        {{ $crumb['label'] }}
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </nav>
                </div>
                <div class="col-3">
                    <div class="text-center mb-n5">
                        <img src="{{ asset('assets/custom/images/breadcrumb/ChatBc.png') }}" class="img-fluid mb-n4" alt="Breadcrumb">
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($messages['success'] !== '')
        <div class="alert alert-success border-0 shadow-sm mb-4" role="alert">
            <div class="fw-semibold mb-1">Berhasil</div>
            <div class="small mb-0">{{ $messages['success'] }}</div>
        </div>
    @endif

    @if ($messages['error'] !== '')
        <div class="alert alert-danger border-0 shadow-sm mb-4" role="alert">
            <div class="fw-semibold mb-1">Periksa Kembali</div>
            <div class="small mb-0">{{ $messages['error'] }}</div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-xxl-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
                        <div>
                            <h5 class="card-title fw-semibold mb-1">Form Hak Akses Menu</h5>
                            <p class="card-subtitle text-muted mb-0">
                                Super Admin dapat mengatur menu apa saja yang muncul pada sidebar untuk Admin, Verifikator, dan Operator.
                            </p>
                        </div>

                        <span class="badge bg-primary-subtle text-primary px-3 py-2">
                            {{ count($leafMenuItems) }} menu tersedia
                        </span>
                    </div>

                    <div class="alert alert-primary border-0 shadow-sm mb-4" role="alert">
                        <div class="fw-semibold mb-1">Catatan Pengaturan</div>
                        <div class="small mb-0">
                            Role <b>Super Admin</b> selalu memiliki seluruh akses menu. Checkbox yang dapat diubah hanya untuk <b>Admin</b>, <b>Verifikator</b>, dan <b>Operator</b>.
                        </div>
                    </div>

                    <form action="{{ url('admin/pengaturan/akses') }}" method="POST">
                        {!! csrf_field() !!}

                        <div class="d-flex flex-column gap-4">
                            @foreach ($groupedMenus as $heading => $menus)
                                <div class="border rounded-3 overflow-hidden">
                                    <div class="px-4 py-3 bg-light-subtle border-bottom">
                                        <div class="fw-semibold text-primary">{{ $heading }}</div>
                                    </div>

                                    <div class="table-responsive admin-access-table-wrap">
                                        <table class="table align-middle mb-0 admin-access-table">
                                            <colgroup>
                                                <col class="admin-access-table__menu-col">
                                                @foreach ($roleOrder as $roleId)
                                                    @if (isset($roleMap[$roleId]))
                                                        <col class="admin-access-table__role-col">
                                                    @endif
                                                @endforeach
                                            </colgroup>
                                            <thead class="bg-body-tertiary">
                                                <tr>
                                                    <th class="ps-4 admin-access-table__menu-head">Menu</th>
                                                    @foreach ($roleOrder as $roleId)
                                                        @if (isset($roleMap[$roleId]))
                                                            @php
                                                                $roleLabel = $roleMap[$roleId]['role_name'] ?? ('Role ' . $roleId);
                                                            @endphp
                                                            <th class="text-center admin-access-table__role-head">{{ $roleMap[$roleId]['role_name'] ?? ('Role ' . $roleId) }}</th>
                                                        @endif
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($menus as $menu)
                                                    @php
                                                        $menuId = (int) ($menu['menu_id'] ?? 0);
                                                        $menuKey = (string) ($menu['key'] ?? '');
                                                    @endphp
                                                    <tr class="admin-access-table__row">
                                                        <td class="ps-4 admin-access-table__menu-cell">
                                                            <div class="admin-access-table__menu-meta">
                                                                <div class="fw-semibold admin-access-table__menu-title">{{ $menu['path_label'] ?? $menu['label'] ?? 'Menu' }}</div>
                                                                <div class="small text-muted admin-access-table__menu-key">{{ $menuKey }}</div>
                                                            </div>
                                                        </td>

                                                        @foreach ($roleOrder as $roleId)
                                                            @if (isset($roleMap[$roleId]))
                                                                @php
                                                                    $selectedKeys = array_map('strval', (array) ($accessSelections[$roleId] ?? []));
                                                                    $isChecked = $roleId === 1 || in_array($menuKey, $selectedKeys, true);
                                                                    $roleLabel = $roleMap[$roleId]['role_name'] ?? ('Role ' . $roleId);
                                                                @endphp
                                                                <td class="text-center admin-access-table__role-cell" data-role-label="{{ $roleLabel }}">
                                                                    <div class="form-check admin-access-table__check-wrap">
                                                                        <input
                                                                            class="form-check-input admin-access-table__check-input"
                                                                            type="checkbox"
                                                                            name="access[{{ $roleId }}][]"
                                                                            value="{{ $menuId }}"
                                                                            @if ($isChecked) checked @endif
                                                                            @if ($roleId === 1 || !$canManageAccess) disabled @endif
                                                                        >
                                                                    </div>
                                                                </td>
                                                            @endif
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if ($canManageAccess)
                            <div class="d-flex justify-content-end mt-4">
                                <button type="submit" class="btn btn-primary px-4">
                                    <b>SIMPAN HAK AKSES</b>
                                </button>
                            </div>
                        @else
                            <div class="alert alert-warning mt-4 mb-0" role="alert">
                                <div class="fw-semibold mb-1">Mode Lihat Saja</div>
                                <div class="small mb-0">
                                    Hanya Super Admin yang dapat mengubah checkbox hak akses pada halaman ini.
                                </div>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xxl-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title fw-semibold mb-1">Ringkasan Role</h5>
                    <p class="card-subtitle text-muted mb-4">
                        Jumlah menu aktif yang saat ini dimiliki tiap role.
                    </p>

                    <div class="d-flex flex-column gap-3">
                        @foreach ($roleOrder as $roleId)
                            @if (isset($roleMap[$roleId]))
                                @php
                                    $selectedKeys = array_map('strval', (array) ($accessSelections[$roleId] ?? []));
                                    $menuCount = $roleId === 1 ? count($leafMenuItems) : count($selectedKeys);
                                @endphp
                                <div class="border rounded-3 p-3">
                                    <div class="d-flex align-items-center justify-content-between gap-3">
                                        <div>
                                            <div class="fw-semibold">{{ $roleMap[$roleId]['role_name'] ?? ('Role ' . $roleId) }}</div>
                                            <div class="small text-muted">{{ $roleMap[$roleId]['description'] ?? '-' }}</div>
                                        </div>
                                        <span class="badge bg-success-subtle text-success px-3 py-2">{{ $menuCount }} menu</span>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title fw-semibold mb-1">Preview Sidebar</h5>
                    <p class="card-subtitle text-muted mb-4">
                        Ringkasan struktur menu yang saat ini aktif berdasarkan pengaturan hak akses.
                    </p>

                    <div class="d-flex flex-column gap-3">
                        @foreach ($accessOverview as $roleAccess)
                            <div class="border rounded-3 p-3">
                                <div class="d-flex align-items-center justify-content-between gap-3 mb-2">
                                    <div class="fw-semibold">{{ $roleAccess['role_label'] ?? 'Role' }}</div>
                                    <span class="badge bg-primary-subtle text-primary">{{ count($roleAccess['allowed_keys'] ?? []) }} aktif</span>
                                </div>
                                <div class="small text-muted mb-2">{{ $roleAccess['scope_label'] ?? '-' }}</div>
                                <div class="small">
                                    @php
                                        $labels = [];
                                        foreach (admin_flatten_leaf_menu_items($roleAccess['sidebar_sections'] ?? []) as $sidebarItem) {
                                            $labels[] = $sidebarItem['path_label'] ?? $sidebarItem['label'] ?? 'Menu';
                                        }
                                    @endphp
                                    {{ !empty($labels) ? implode(', ', $labels) : 'Belum ada menu aktif' }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
