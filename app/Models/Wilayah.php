<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use PDO;

class Wilayah
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
    }

    public function getRegions(): array
    {
        $stmt = $this->db->prepare("
            SELECT id, region
            FROM wilayah
            ORDER BY region ASC
        ");

        $stmt->execute();
        $rows = $stmt->fetchAll();

        return $rows ?: [];
    }

    public function getDistricts(): array
    {
        $stmt = $this->db->prepare("
            SELECT id, district, region, address, phone, lat, lng
            FROM kecamatan
            ORDER BY district ASC
        ");

        $stmt->execute();
        $rows = $stmt->fetchAll();

        return $rows ?: [];
    }

    public function getVillages(): array
    {
        $stmt = $this->db->prepare("
            SELECT id, district_id, subdistrict
            FROM kelurahan
            ORDER BY subdistrict ASC
        ");

        $stmt->execute();
        $rows = $stmt->fetchAll();

        return $rows ?: [];
    }

    public function getDistrictVillageMap(): array
    {
        $districts = $this->getDistricts();
        $villages  = $this->getVillages();

        $map = [];

        foreach ($districts as $district) {
            $map[(string) $district['id']] = [];
        }

        foreach ($villages as $village) {
            $districtId = (string) ($village['district_id'] ?? '');

            if ($districtId === '') {
                continue;
            }

            if (!isset($map[$districtId])) {
                $map[$districtId] = [];
            }

            $map[$districtId][] = [
                'id'   => (int) $village['id'],
                'name' => $village['subdistrict'],
            ];
        }

        return $map;
    }

    public function districtExists(int $districtId): bool
    {
        $stmt = $this->db->prepare("
            SELECT id
            FROM kecamatan
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $districtId
        ]);

        return (bool) $stmt->fetch();
    }

    public function findDistrictById(int $districtId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, district, region, address, phone, lat, lng
            FROM kecamatan
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $districtId,
        ]);

        $district = $stmt->fetch();

        return $district ?: null;
    }

    public function villageBelongsToDistrict(int $villageId, int $districtId): bool
    {
        $stmt = $this->db->prepare("
            SELECT id
            FROM kelurahan
            WHERE id = :village_id
              AND district_id = :district_id
            LIMIT 1
        ");

        $stmt->execute([
            ':village_id' => $villageId,
            ':district_id' => $districtId
        ]);

        return (bool) $stmt->fetch();
    }
}
