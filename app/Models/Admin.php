<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use PDO;

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
