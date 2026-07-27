<?php

namespace App\Repositories;

use Throwable;

class AdminRepository extends LegacyRepository
{
    protected $table = 'admin';

    private const DEFAULT_SEEDED_PASSWORD = '123456';

    public function findForLogin(string $username): ?array
    {
        return $this->row(
            $this->query()
                ->where('username', $username)
                ->first()
        );
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
        return $this->query()
            ->where('id', $id)
            ->update([
                'password' => $hashedPassword,
                'updated_at' => now(),
            ]) > 0;
    }

    public function countActive(): int
    {
        return $this->query()
            ->where('status', 'AKTIF')
            ->count();
    }

    public function getActiveAdmins(): array
    {
        return $this->rows(
            $this->query()
                ->select('id', 'username', 'name', 'role_id', 'district_id', 'status', 'last_login')
                ->where('status', 'AKTIF')
                ->orderBy('role_id')
                ->orderBy('username')
                ->get()
        );
    }

    public function findById(int $id): ?array
    {
        return $this->row(
            $this->query()
                ->where('id', $id)
                ->first()
        );
    }

    public function getAccountsByRoleIds(array $roleIds): array
    {
        $roleIds = array_values(array_unique(array_filter(array_map('intval', $roleIds))));

        if (empty($roleIds)) {
            return [];
        }

        return $this->rows(
            $this->query()
                ->from('admin as a')
                ->select([
                    'a.id',
                    'a.username',
                    'a.name',
                    'a.role_id',
                    'a.district_id',
                    'a.status',
                    'a.last_login',
                    'p.role_name',
                    'k.district AS district_name',
                    'k.region AS district_region',
                ])
                ->leftJoin('peran as p', 'p.id', '=', 'a.role_id')
                ->leftJoin('kecamatan as k', 'k.id', '=', 'a.district_id')
                ->whereIn('a.role_id', $roleIds)
                ->orderBy('a.role_id')
                ->orderBy('a.name')
                ->orderBy('a.username')
                ->get()
        );
    }

    public function usernameExistsForOther(string $username, int $excludeId = 0): bool
    {
        return $this->query()
            ->where('username', $username)
            ->where('id', '<>', $excludeId)
            ->exists();
    }

    public function createManagedAccount(array $data): bool
    {
        return $this->query()->insert([
            'username' => $data['username'],
            'password' => $data['password'],
            'name' => $data['name'],
            'role_id' => $data['role_id'],
            'district_id' => $data['district_id'] ?: null,
            'status' => $data['status'],
            'created_at' => now(),
        ]);
    }

    public function updateManagedAccount(int $id, array $data): bool
    {
        $payload = [
            'username' => $data['username'],
            'name' => $data['name'],
            'role_id' => $data['role_id'],
            'district_id' => $data['district_id'] ?: null,
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

    public function updateLastLogin(int $id): void
    {
        $this->query()
            ->where('id', $id)
            ->update(['last_login' => now()]);
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
