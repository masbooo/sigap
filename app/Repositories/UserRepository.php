<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Schema;
use Throwable;

class UserRepository extends LegacyRepository
{
    protected $table = 'user';

    protected array $columnExistsCache = [];
    private static bool $identitySchemaEnsured = false;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->ensureIdentityPathSchema();
    }

    public function findForLogin(string $username): ?array
    {
        return $this->findWhere('username', $username);
    }

    public function findByUsername(string $username): ?array
    {
        return $this->row(
            $this->query()
                ->where('username', $username)
                ->where('status', 'AKTIF')
                ->first()
        );
    }

    public function findById(int $id): ?array
    {
        return $this->findWhere('id', $id);
    }

    public function usernameExists(string $username): bool
    {
        return $this->query()
            ->where('username', $username)
            ->exists();
    }

    public function usernameExistsForOther(string $username, int $excludeId = 0): bool
    {
        return $this->query()
            ->where('username', $username)
            ->where('id', '<>', $excludeId)
            ->exists();
    }

    public function findByNik(string $nik): ?array
    {
        return $this->findWhere('nik', $nik);
    }

    public function findActiveByNikAndPhone(string $nik, string $phone): ?array
    {
        return $this->row(
            $this->query()
                ->where('nik', $nik)
                ->where('phone', $phone)
                ->where('status', 'AKTIF')
                ->first()
        );
    }

    public function nikExistsForOther(string $nik, int $excludeId): bool
    {
        return $this->query()
            ->where('nik', $nik)
            ->where('id', '<>', $excludeId)
            ->exists();
    }

    public function createSimple(array $data): bool
    {
        return $this->query()->insert([
            'username' => $data['username'],
            'password' => $data['password'],
            'nik' => $data['nik'],
            'name' => $data['name'],
            'address' => $data['address'],
            'subdistrict_id' => $data['subdistrict_id'],
            'district_id' => $data['district_id'],
            'phone' => $data['phone'],
            'status' => $data['status'],
            'created_at' => now(),
        ]);
    }

    public function activateWithBiodata(int $id, array $data): bool
    {
        $payload = [
            'nik' => $data['nik'],
            'name' => $data['name'],
            'gender' => $data['gender'],
            'address' => $data['address'],
            'district_id' => $data['district_id'],
            'subdistrict_id' => $data['subdistrict_id'],
            'phone' => $data['phone'],
            'status' => 'AKTIF',
            'updated_at' => now(),
        ];

        if (array_key_exists('id_path', $data) && $this->hasColumn('user', 'id_path')) {
            $payload['id_path'] = $this->normalizeRelativeUploadPath($data['id_path'] ?? null);
        }

        return $this->query()
            ->where('id', $id)
            ->update($payload) > 0;
    }

    public function isProfileIncomplete(array $user): bool
    {
        $nik = trim((string) ($user['nik'] ?? ''));
        $name = trim((string) ($user['name'] ?? ''));
        $gender = strtoupper(trim((string) ($user['gender'] ?? '')));
        $address = trim((string) ($user['address'] ?? ''));
        $phone = trim((string) ($user['phone'] ?? ''));
        $districtId = (int) ($user['district_id'] ?? 0);
        $subdistrictId = (int) ($user['subdistrict_id'] ?? 0);
        $identityPath = trim((string) ($user['id_path'] ?? ''));

        return (
            $nik === '' ||
            $name === '' ||
            !in_array($gender, ['L', 'P'], true) ||
            $address === '' ||
            $phone === '' ||
            $districtId <= 0 ||
            $subdistrictId <= 0 ||
            ($this->hasColumn('user', 'id_path') && $identityPath === '')
        );
    }

    public function hasPendingProfileStatus(array $user): bool
    {
        $status = strtoupper(trim((string) ($user['status'] ?? '')));

        return $status === 'PROSES';
    }

    public function shouldShowProfileCompletionModal(array $user): bool
    {
        return $this->hasPendingProfileStatus($user);
    }

    public function getActiveUsers(): array
    {
        return $this->rows(
            $this->query()
                ->select('id', 'username', 'name', 'district_id', 'subdistrict_id', 'status', 'last_login')
                ->where('status', 'AKTIF')
                ->orderBy('name')
                ->orderBy('username')
                ->get()
        );
    }

    public function getManagedUsers(): array
    {
        return $this->rows(
            $this->query()
                ->from('user as u')
                ->select([
                    'u.id',
                    'u.username',
                    'u.name',
                    'u.status',
                    'u.phone',
                    'u.nik',
                    'u.district_id',
                    'u.subdistrict_id',
                    'u.last_login',
                    'k.district AS district_name',
                    'k.region AS district_region',
                    'kl.subdistrict AS subdistrict_name',
                ])
                ->leftJoin('kecamatan as k', 'k.id', '=', 'u.district_id')
                ->leftJoin('kelurahan as kl', 'kl.id', '=', 'u.subdistrict_id')
                ->orderBy('u.name')
                ->orderBy('u.username')
                ->get()
        );
    }

    public function createManagedAccount(array $data): bool
    {
        return $this->query()->insert([
            'username' => $data['username'],
            'password' => $data['password'],
            'nik' => $data['nik'] ?? null,
            'name' => $data['name'],
            'address' => $data['address'] ?? '',
            'subdistrict_id' => $data['subdistrict_id'] ?? null,
            'district_id' => $data['district_id'] ?? null,
            'phone' => $data['phone'] ?? '',
            'status' => $data['status'],
            'created_at' => now(),
        ]);
    }

    public function updateManagedAccount(int $id, array $data): bool
    {
        $payload = [
            'username' => $data['username'],
            'name' => $data['name'],
            'phone' => $data['phone'] ?? '',
            'status' => $data['status'],
            'updated_at' => now(),
        ];

        if (!empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        return $this->query()
            ->where('id', $id)
            ->update($payload) > 0;
    }

    public function deleteAccount(int $id): bool
    {
        try {
            return $this->query()
                ->where('id', $id)
                ->delete() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function updateStatus(int $id, string $status): bool
    {
        return $this->query()
            ->where('id', $id)
            ->update([
                'status' => $status,
                'updated_at' => now(),
            ]) > 0;
    }

    public function updateLastLogin(int $id): void
    {
        $this->query()
            ->where('id', $id)
            ->update(['last_login' => now()]);
    }

    public function updatePassword(int $id, string $hashedPassword): bool
    {
        return $this->query()
            ->where('id', $id)
            ->update([
                'password' => $hashedPassword,
                'updated_at' => now(),
            ]) > 0;
    }

    public function updateProfilePhotoPath(int $id, ?string $relativePath): bool
    {
        $normalizedPath = $this->normalizeRelativeUploadPath($relativePath);

        return $this->query()
            ->where('id', $id)
            ->update([
                'pic_path' => $normalizedPath,
                'updated_at' => now(),
            ]) > 0;
    }

    private function findWhere(string $column, mixed $value): ?array
    {
        return $this->row(
            $this->query()
                ->where($column, $value)
                ->first()
        );
    }

    private function ensureIdentityPathSchema(): void
    {
        if (self::$identitySchemaEnsured) {
            return;
        }

        self::$identitySchemaEnsured = true;

        try {
            if ($this->hasColumn('user', 'id_path')) {
                return;
            }

            $afterColumn = $this->hasColumn('user', 'pic_path')
                ? 'pic_path'
                : ($this->hasColumn('user', 'phone') ? 'phone' : 'status');

            $this->db->exec("ALTER TABLE user ADD COLUMN id_path VARCHAR(255) NULL DEFAULT NULL AFTER {$afterColumn}");
            $this->columnExistsCache['user.id_path'] = true;
        } catch (Throwable $e) {
            // Keep the app usable even if schema changes are not allowed.
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        $cacheKey = $table . '.' . $column;
        if (array_key_exists($cacheKey, $this->columnExistsCache)) {
            return $this->columnExistsCache[$cacheKey];
        }

        $exists = Schema::hasColumn($table, $column);
        $this->columnExistsCache[$cacheKey] = $exists;

        return $exists;
    }

    private function normalizeRelativeUploadPath(?string $relativePath): ?string
    {
        $normalizedPath = trim(str_replace('\\', '/', (string) $relativePath));

        if (str_starts_with($normalizedPath, 'user/identity/')) {
            $normalizedPath = 'user/identitas/' . substr($normalizedPath, strlen('user/identity/'));
        }

        return $normalizedPath !== '' ? ltrim($normalizedPath, '/') : null;
    }
}
