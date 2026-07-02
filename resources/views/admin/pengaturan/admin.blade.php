@extends('layouts.admin')

@section('content')
@php
    $pageMeta = resolve_admin_page_meta();
    $messages = $messages ?? ['success' => '', 'error' => ''];
    $statuses = $statuses ?? ['AKTIF', 'TIDAK AKTIF'];
    $roles = $roles ?? [];
    $districtScopedRoleIds = array_map('intval', $districtScopedRoleIds ?? [3]);
    $currentAdminId = (int) (($admin ?? admin_user() ?? [])['id'] ?? 0);
    $accountsByRole = [];

    foreach (($adminAccounts ?? []) as $account) {
        $accountsByRole[(int) ($account['role_id'] ?? 0)][] = $account;
    }

    $statusTone = static function (string $status): string {
        return strtoupper(trim($status)) === 'AKTIF'
            ? 'bg-success-subtle text-success'
            : 'bg-secondary-subtle text-secondary';
    };

    $displayName = static function (array $account): string {
        $name = trim((string) ($account['name'] ?? ''));

        return $name !== '' ? $name : (trim((string) ($account['username'] ?? '')) ?: '-');
    };

    $accountSubtitle = static function (array $account): string {
        $district = trim((string) ($account['district_name'] ?? ''));
        $region = trim((string) ($account['district_region'] ?? ''));
        $roleName = trim((string) ($account['role_name'] ?? 'Admin'));

        if ($district !== '' && $region !== '') {
            return $roleName . ' - ' . $district . ', Surabaya ' . $region;
        }

        if ($district !== '') {
            return $roleName . ' - ' . $district;
        }

        return $roleName;
    };

    $roleTone = static function (int $roleId): string {
        return $roleId === 2 ? 'bg-primary-subtle text-primary' : 'bg-warning-subtle text-warning';
    };

    $roleIcon = static function (int $roleId): string {
        return $roleId === 2 ? 'ti ti-user-shield' : 'ti ti-users-group';
    };
@endphp

<div class="container-fluid">
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Pengaturan Admin</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            @foreach (($pageMeta['breadcrumbs'] ?? []) as $index => $crumb)
                                @php $isLast = $index === count($pageMeta['breadcrumbs'] ?? []) - 1; @endphp
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
                        <img src="{{ base_url('assets/custom/images/breadcrumb/ChatBc.png') }}" class="img-fluid mb-n4" alt="Breadcrumb">
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (($messages['success'] ?? '') !== '')
        <div class="alert alert-success border-0 shadow-sm mb-4" role="alert">
            <div class="fw-semibold mb-1">Berhasil</div>
            <div class="small mb-0">{{ $messages['success'] }}</div>
        </div>
    @endif

    @if (($messages['error'] ?? '') !== '')
        <div class="alert alert-danger border-0 shadow-sm mb-4" role="alert">
            <div class="fw-semibold mb-1">Periksa Kembali</div>
            <div class="small mb-0">{{ $messages['error'] }}</div>
        </div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
                <div>
                    <h5 class="card-title fw-semibold mb-1">Input Admin</h5>
                    <p class="card-subtitle text-muted mb-0">Tambahkan akun admin dari tabel admin untuk role_id 2 dan 3.</p>
                </div>

                <span class="badge bg-primary-subtle text-primary px-3 py-2">{{ count($adminAccounts ?? []) }} akun</span>
            </div>

            <form
                action="{{ base_url('admin/pengaturan/admin') }}"
                method="POST"
                data-admin-account-form
                data-scoped-roles="{{ implode(',', $districtScopedRoleIds) }}"
            >
                {!! csrf_field() !!}

                <div class="row g-3 align-items-end">
                    <div class="col-md-6 col-xl-2">
                        <label class="form-label" for="admin-account-role">Role</label>
                        <select class="form-select" id="admin-account-role" name="role_id" data-role-select required>
                            @foreach ($roles as $roleId => $role)
                                <option value="{{ (int) $roleId }}">{{ $role['role_name'] ?? ('Role ' . $roleId) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 col-xl-2">
                        <label class="form-label" for="admin-account-name">Nama</label>
                        <input type="text" class="form-control" id="admin-account-name" name="name" maxlength="100" required>
                    </div>

                    <div class="col-md-6 col-xl-2">
                        <label class="form-label" for="admin-account-username">Username</label>
                        <input type="text" class="form-control" id="admin-account-username" name="username" maxlength="50" required>
                    </div>

                    <div class="col-md-6 col-xl-2">
                        <label class="form-label" for="admin-account-password">Password</label>
                        <input type="password" class="form-control" id="admin-account-password" name="password" minlength="6" required>
                    </div>

                    <div class="col-md-6 col-xl-2 admin-account-district-field d-none" data-district-field>
                        <label class="form-label" for="admin-account-district">Kecamatan</label>
                        <select class="form-select" id="admin-account-district" name="district_id" data-district-select>
                            <option value="">Pilih Kecamatan</option>
                            @foreach (($districts ?? []) as $district)
                                <option value="{{ (int) ($district['id'] ?? 0) }}">{{ $district['district'] ?? '-' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 col-xl-2">
                        <label class="form-label" for="admin-account-status">Status</label>
                        <select class="form-select" id="admin-account-status" name="status" required>
                            @foreach ($statuses as $statusOption)
                                <option value="{{ $statusOption }}" @if ($statusOption === 'AKTIF') selected @endif>
                                    {{ ucwords(strtolower($statusOption)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 col-xl-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti ti-plus me-1"></i> SIMPAN
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">
        @foreach ($roles as $roleId => $role)
            @php
                $roleId = (int) $roleId;
                $roleAccounts = $accountsByRole[$roleId] ?? [];
                $roleLabel = $role['role_name'] ?? ('Role ' . $roleId);
            @endphp
            <div class="col-12">
                <div class="card shadow-sm h-100 admin-user-card">
                    <div class="px-4 py-3 bg-light-subtle border-bottom rounded-top">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="rounded-2 d-inline-flex align-items-center justify-content-center admin-user-card-icon {{ $roleTone($roleId) }}">
                                    <i class="{{ $roleIcon($roleId) }}"></i>
                                </span>
                                <div>
                                    <div class="fw-semibold text-primary">{{ $roleLabel }}</div>
                                    <div class="small text-muted">role_id {{ $roleId }}</div>
                                </div>
                            </div>
                            <span class="badge bg-primary-subtle text-primary">{{ count($roleAccounts) }} akun</span>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0 admin-user-table">
                            <thead class="bg-body-tertiary">
                                <tr>
                                    <th class="ps-4">No</th>
                                    <th>Nama</th>
                                    <th>Username</th>
                                    <th>Status</th>
                                    <th class="text-center pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($roleAccounts as $index => $account)
                                    @php
                                        $accountId = (int) ($account['id'] ?? 0);
                                        $accountStatus = strtoupper(trim((string) ($account['status'] ?? '-')));
                                        $modalId = 'edit-admin-account-' . $accountId;
                                        $isCurrentAdmin = $accountId === $currentAdminId;
                                    @endphp
                                    <tr>
                                        <td class="ps-4">{{ $index + 1 }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $displayName($account) }}</div>
                                            <div class="small text-muted">{{ $accountSubtitle($account) }}</div>
                                        </td>
                                        <td>{{ $account['username'] ?? '-' }}</td>
                                        <td><span class="badge {{ $statusTone($accountStatus) }}">{{ ucwords(strtolower($accountStatus)) }}</span></td>
                                        <td class="text-center pe-4 admin-table-action-cell">
                                            <div class="admin-table-action-dropdown">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-light-secondary border-0 admin-table-action-toggle"
                                                    aria-label="Buka menu aksi"
                                                    aria-expanded="false"
                                                >
                                                    <i class="ti ti-dots fs-5" aria-hidden="true"></i>
                                                </button>

                                                <div class="admin-table-action-menu" hidden>
                                                    <button type="button" class="admin-table-action-item" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
                                                        <span class="admin-table-action-icon text-primary bg-primary-subtle">
                                                            <i class="ti ti-edit fs-5"></i>
                                                        </span>
                                                        <span class="admin-table-action-label">Rubah</span>
                                                    </button>

                                                    @if ($isCurrentAdmin)
                                                        <button type="button" class="admin-table-action-item is-disabled" disabled>
                                                            <span class="admin-table-action-icon text-secondary bg-secondary-subtle">
                                                                <i class="ti ti-trash fs-5"></i>
                                                            </span>
                                                            <span class="admin-table-action-label">Hapus</span>
                                                        </button>
                                                    @else
                                                        <form
                                                            action="{{ base_url('admin/pengaturan/admin/hapus') }}"
                                                            method="POST"
                                                            class="admin-table-action-form admin-user-action-form"
                                                            onsubmit="return confirm('Hapus akun {{ $displayName($account) }}?')"
                                                        >
                                                            {!! csrf_field() !!}
                                                            <input type="hidden" name="id" value="{{ $accountId }}">
                                                            <button type="submit" class="admin-table-action-item">
                                                                <span class="admin-table-action-icon text-danger bg-danger-subtle">
                                                                    <i class="ti ti-trash fs-5"></i>
                                                                </span>
                                                                <span class="admin-table-action-label">Hapus</span>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Belum ada data {{ strtolower($roleLabel) }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @foreach (($adminAccounts ?? []) as $account)
        @php
            $accountId = (int) ($account['id'] ?? 0);
            $roleId = (int) ($account['role_id'] ?? 0);
            $accountStatus = strtoupper(trim((string) ($account['status'] ?? '-')));
            $modalId = 'edit-admin-account-' . $accountId;
        @endphp
        <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}-label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <form
                        action="{{ base_url('admin/pengaturan/admin/update') }}"
                        method="POST"
                        data-admin-account-form
                        data-scoped-roles="{{ implode(',', $districtScopedRoleIds) }}"
                    >
                        {!! csrf_field() !!}
                        <input type="hidden" name="id" value="{{ $accountId }}">

                        <div class="modal-header border-0 pb-0">
                            <div>
                                <h5 class="modal-title fw-semibold" id="{{ $modalId }}-label">Rubah Admin</h5>
                                <p class="text-muted mb-0 small">{{ $account['username'] ?? '-' }}</p>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label" for="{{ $modalId }}-role">Role</label>
                                    <select class="form-select" id="{{ $modalId }}-role" name="role_id" data-role-select required>
                                        @foreach ($roles as $optionRoleId => $role)
                                            <option value="{{ (int) $optionRoleId }}" @if ($roleId === (int) $optionRoleId) selected @endif>
                                                {{ $role['role_name'] ?? ('Role ' . $optionRoleId) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label" for="{{ $modalId }}-name">Nama</label>
                                    <input type="text" class="form-control" id="{{ $modalId }}-name" name="name" value="{{ $displayName($account) }}" maxlength="100" required>
                                </div>

                                <div class="col-12">
                                    <label class="form-label" for="{{ $modalId }}-username">Username</label>
                                    <input type="text" class="form-control" id="{{ $modalId }}-username" name="username" value="{{ $account['username'] ?? '' }}" maxlength="50" required>
                                </div>

                                <div class="col-12">
                                    <label class="form-label" for="{{ $modalId }}-password">Password Baru</label>
                                    <input type="password" class="form-control" id="{{ $modalId }}-password" name="password" minlength="6" placeholder="Kosongkan jika tidak diganti">
                                </div>

                                <div class="col-12 admin-account-district-field d-none" data-district-field>
                                    <label class="form-label" for="{{ $modalId }}-district">Kecamatan</label>
                                    <select class="form-select" id="{{ $modalId }}-district" name="district_id" data-district-select>
                                        <option value="">Pilih Kecamatan</option>
                                        @foreach (($districts ?? []) as $district)
                                            <option
                                                value="{{ (int) ($district['id'] ?? 0) }}"
                                                @if ((string) ($account['district_id'] ?? '') === (string) ($district['id'] ?? '')) selected @endif
                                            >
                                                {{ $district['district'] ?? '-' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label" for="{{ $modalId }}-status">Status</label>
                                    <select class="form-select" id="{{ $modalId }}-status" name="status" required>
                                        @foreach ($statuses as $statusOption)
                                            <option value="{{ $statusOption }}" @if ($accountStatus === $statusOption) selected @endif>
                                                {{ ucwords(strtolower($statusOption)) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>

<script>
    (function () {
        function bindAdminAccountForms(root) {
            var scope = root && root.querySelector ? root : document;

            scope.querySelectorAll('[data-admin-account-form]').forEach(function (form) {
                if (form.dataset.boundAdminAccountForm === 'true') {
                    return;
                }

                form.dataset.boundAdminAccountForm = 'true';

                var roleSelect = form.querySelector('[data-role-select]');
                var districtField = form.querySelector('[data-district-field]');
                var districtSelect = form.querySelector('[data-district-select]');
                var scopedRoles = String(form.getAttribute('data-scoped-roles') || '')
                    .split(',')
                    .map(function (roleId) { return roleId.trim(); })
                    .filter(Boolean);

                function syncDistrictField() {
                    var needsDistrict = roleSelect && scopedRoles.indexOf(String(roleSelect.value)) !== -1;

                    if (districtField) {
                        districtField.classList.toggle('d-none', !needsDistrict);
                    }

                    if (districtSelect) {
                        districtSelect.required = !!needsDistrict;
                        if (!needsDistrict) {
                            districtSelect.value = '';
                        }
                    }
                }

                if (roleSelect) {
                    roleSelect.addEventListener('change', syncDistrictField);
                }

                syncDistrictField();
            });
        }

        bindAdminAccountForms(document);
    })();
</script>
@endsection
