<?php

namespace App\Repositories;

class WilayahRepository extends LegacyRepository
{
    protected $table = 'wilayah';

    public function getRegions(): array
    {
        return $this->rows(
            $this->query()
                ->select('id', 'region')
                ->orderBy('region')
                ->get()
        );
    }

    public function getDistricts(): array
    {
        return $this->rows(
            $this->table('kecamatan')
                ->select('id', 'district', 'region', 'address', 'phone', 'lat', 'lng')
                ->orderBy('district')
                ->get()
        );
    }

    public function getVillages(): array
    {
        return $this->rows(
            $this->table('kelurahan')
                ->select('id', 'district_id', 'subdistrict')
                ->orderBy('subdistrict')
                ->get()
        );
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
        return $this->table('kecamatan')
            ->where('id', $districtId)
            ->exists();
    }

    public function findDistrictById(int $districtId): ?array
    {
        return $this->row(
            $this->table('kecamatan')
                ->select('id', 'district', 'region', 'address', 'phone', 'lat', 'lng')
                ->where('id', $districtId)
                ->first()
        );
    }

    public function villageBelongsToDistrict(int $villageId, int $districtId): bool
    {
        return $this->table('kelurahan')
            ->where('id', $villageId)
            ->where('district_id', $districtId)
            ->exists();
    }
}
