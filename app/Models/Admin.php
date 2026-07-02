<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use PDO;
use Throwable;

class Admin
{
    protected PDO $db;
    private const DEFAULT_SEEDED_PASSWORD = '123456';

    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
    }

    public function findForLogin(string $username): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM admin
            WHERE username = :username
            LIMIT 1
        ");

        $stmt->execute([
            ':username' => $username
        ]);

        $admin = $stmt->fetch();

        return $admin ?: null;
    }

    public function verifyPassword(array $admin, string $password): bool
    {
        $adminId = (int) ($admin['id'] ?? 0);
        $storedHash = (string) ($admin['password'] ?? '');

        if ($adminId <= 0 || $password === '') {
            return false;
        }

        if ($storedHash !== '' && password_verify($password, $storedHash)) {
            if (password_needs_rehash($storedHash, PASSWORD_BCRYPT)) {
                $this->updatePasswordHash($adminId, password_hash($password, PASSWORD_BCRYPT));
            }

            return true;
        }

        if (!$this->canUseSeededDefaultPassword($admin, $password)) {
            return false;
        }

        return $this->updatePasswordHash(
            $adminId,
            password_hash(self::DEFAULT_SEEDED_PASSWORD, PASSWORD_BCRYPT)
        );
    }

    public function updatePasswordHash(int $id, string $hashedPassword): bool
    {
        $stmt = $this->db->prepare("
            UPDATE admin
            SET
                password = :password,
                updated_at = NOW()
            WHERE id = :id
        ");

        return $stmt->execute([
            ':password' => $hashedPassword,
            ':id' => $id,
        ]);
    }

    public function countActive(): int
    {
        $stmt = $this->db->query("
            SELECT COUNT(*) AS total
            FROM admin
            WHERE status = 'AKTIF'
        ");

        return (int) ($stmt->fetch()['total'] ?? 0);
    }

    public function getActiveAdmins(): array
    {
        $stmt = $this->db->query("
            SELECT id, username, name, role_id, district_id, status, last_login
            FROM admin
            WHERE status = 'AKTIF'
            ORDER BY role_id ASC, username ASC
        ");

        return $stmt->fetchAll() ?: [];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM admin
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $id,
        ]);

        $admin = $stmt->fetch();

        return $admin ?: null;
    }

    public function getAccountsByRoleIds(array $roleIds): array
    {
        $roleIds = array_values(array_unique(array_filter(array_map('intval', $roleIds))));

        if (empty($roleIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($roleIds), '?'));
        $stmt = $this->db->prepare("
            SELECT
                a.id,
                a.username,
                a.name,
                a.role_id,
                a.district_id,
                a.status,
                a.last_login,
                p.role_name,
                k.district AS district_name,
                k.region AS district_region
            FROM admin a
            LEFT JOIN peran p
                ON p.id = a.role_id
            LEFT JOIN kecamatan k
                ON k.id = a.district_id
            WHERE a.role_id IN ({$placeholders})
            ORDER BY a.role_id ASC, a.name ASC, a.username ASC
        ");

        $stmt->execute($roleIds);

        return $stmt->fetchAll() ?: [];
    }

    public function usernameExistsForOther(string $username, int $excludeId = 0): bool
    {
        $stmt = $this->db->prepare("
            SELECT id
            FROM admin
            WHERE username = :username
              AND id <> :id
            LIMIT 1
        ");

        $stmt->execute([
            ':username' => $username,
            ':id' => $excludeId,
        ]);

        return (bool) $stmt->fetch();
    }

    public function createManagedAccount(array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO admin (
                username,
                password,
                name,
                role_id,
                district_id,
                status,
                created_at
            ) VALUES (
                :username,
                :password,
                :name,
                :role_id,
                :district_id,
                :status,
                NOW()
            )
        ");

        return $stmt->execute([
            ':username' => $data['username'],
            ':password' => $data['password'],
            ':name' => $data['name'],
            ':role_id' => $data['role_id'],
            ':district_id' => $data['district_id'] ?: null,
            ':status' => $data['status'],
        ]);
    }

    public function updateManagedAccount(int $id, array $data): bool
    {
        $assignments = [
            'username = :username',
            'name = :name',
            'role_id = :role_id',
            'district_id = :district_id',
            'status = :status',
            'updated_at = NOW()',
        ];

        $params = [
            ':username' => $data['username'],
            ':name' => $data['name'],
            ':role_id' => $data['role_id'],
            ':district_id' => $data['district_id'] ?: null,
            ':status' => $data['status'],
            ':id' => $id,
        ];

        if (!empty($data['password'])) {
            $assignments[] = 'password = :password';
            $params[':password'] = $data['password'];
        }

        $stmt = $this->db->prepare("
            UPDATE admin
            SET " . implode(",\n                ", $assignments) . "
            WHERE id = :id
        ");

        return $stmt->execute($params);
    }

    public function deleteAccount(int $id): bool
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM admin
                WHERE id = :id
            ");

            return $stmt->execute([
                ':id' => $id,
            ]);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function updateLastLogin(int $id): void
    {
        $stmt = $this->db->prepare("
            UPDATE admin
            SET last_login = NOW()
            WHERE id = :id
        ");

        $stmt->execute([
            ':id' => $id
        ]);
    }

    private function canUseSeededDefaultPassword(array $admin, string $password): bool
    {
        if ($password !== self::DEFAULT_SEEDED_PASSWORD) {
            return false;
        }

        $lastLogin = trim((string) ($admin['last_login'] ?? ''));

        return $lastLogin === '' || strpos($lastLogin, '0000-00-00') === 0;
    }
}
