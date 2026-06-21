<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDO;
use Throwable;

class User
{
    protected PDO $db;
    protected array $columnExistsCache = [];
    private static bool $identitySchemaEnsured = false;

    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
        $this->ensureIdentityPathSchema();
    }

    public function findForLogin(string $username): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM user
            WHERE username = :username
            LIMIT 1
        ");

        $stmt->execute([
            ':username' => $username
        ]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM user
            WHERE username = :username
              AND status = 'AKTIF'
            LIMIT 1
        ");

        $stmt->execute([
            ':username' => $username
        ]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM user
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function usernameExists(string $username): bool
    {
        $stmt = $this->db->prepare("
            SELECT id
            FROM user
            WHERE username = :username
            LIMIT 1
        ");

        $stmt->execute([
            ':username' => $username
        ]);

        return (bool) $stmt->fetch();
    }

    public function findByNik(string $nik): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM user
            WHERE nik = :nik
            LIMIT 1
        ");

        $stmt->execute([
            ':nik' => $nik
        ]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function findActiveByNikAndPhone(string $nik, string $phone): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM user
            WHERE nik = :nik
              AND phone = :phone
              AND status = 'AKTIF'
            LIMIT 1
        ");

        $stmt->execute([
            ':nik' => $nik,
            ':phone' => $phone
        ]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function nikExistsForOther(string $nik, int $excludeId): bool
    {
        $stmt = $this->db->prepare("
            SELECT id
            FROM user
            WHERE nik = :nik
              AND id <> :id
            LIMIT 1
        ");

        $stmt->execute([
            ':nik' => $nik,
            ':id' => $excludeId
        ]);

        return (bool) $stmt->fetch();
    }

    public function createSimple(array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO user (
                username,
                password,
                nik,
                name,
                address,
                subdistrict_id,
                district_id,
                phone,
                status,
                created_at
            ) VALUES (
                :username,
                :password,
                :nik,
                :name,
                :address,
                :subdistrict_id,
                :district_id,
                :phone,
                :status,
                NOW()
            )
        ");

        return $stmt->execute([
            ':username' => $data['username'],
            ':password' => $data['password'],
            ':nik' => $data['nik'],
            ':name' => $data['name'],
            ':address' => $data['address'],
            ':subdistrict_id' => $data['subdistrict_id'],
            ':district_id' => $data['district_id'],
            ':phone' => $data['phone'],
            ':status' => $data['status']
        ]);
    }

    public function activateWithBiodata(int $id, array $data): bool
    {
        $assignments = [
            'nik = :nik',
            'name = :name',
            'gender = :gender',
            'address = :address',
            'district_id = :district_id',
            'subdistrict_id = :subdistrict_id',
            'phone = :phone',
            "status = 'AKTIF'",
            'updated_at = NOW()',
        ];

        $params = [
            ':nik' => $data['nik'],
            ':name' => $data['name'],
            ':gender' => $data['gender'],
            ':address' => $data['address'],
            ':district_id' => $data['district_id'],
            ':subdistrict_id' => $data['subdistrict_id'],
            ':phone' => $data['phone'],
            ':id' => $id
        ];

        if (array_key_exists('id_path', $data) && $this->hasColumn('user', 'id_path')) {
            $assignments[] = 'id_path = :id_path';
            $params[':id_path'] = $this->normalizeRelativeUploadPath($data['id_path'] ?? null);
        }

        $stmt = $this->db->prepare("
            UPDATE user
            SET " . implode(",\n                ", $assignments) . "
            WHERE id = :id
        ");

        return $stmt->execute($params);
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
        $stmt = $this->db->query("
            SELECT id, username, name, district_id, subdistrict_id, status, last_login
            FROM user
            WHERE status = 'AKTIF'
            ORDER BY name ASC, username ASC
        ");

        return $stmt->fetchAll() ?: [];
    }

    public function updateLastLogin(int $id): void
    {
        $stmt = $this->db->prepare("
            UPDATE user
            SET last_login = NOW()
            WHERE id = :id
        ");

        $stmt->execute([
            ':id' => $id
        ]);
    }

    public function updatePassword(int $id, string $hashedPassword): bool
    {
        $stmt = $this->db->prepare("
            UPDATE user
            SET
                password = :password,
                updated_at = NOW()
            WHERE id = :id
        ");

        return $stmt->execute([
            ':password' => $hashedPassword,
            ':id' => $id
        ]);
    }

    public function updateProfilePhotoPath(int $id, ?string $relativePath): bool
    {
        $normalizedPath = $this->normalizeRelativeUploadPath($relativePath);

        $stmt = $this->db->prepare("
            UPDATE user
            SET
                pic_path = :pic_path,
                updated_at = NOW()
            WHERE id = :id
        ");

        return $stmt->execute([
            ':pic_path' => $normalizedPath,
            ':id' => $id,
        ]);
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
