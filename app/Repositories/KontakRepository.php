<?php

namespace App\Repositories;

class KontakRepository extends LegacyRepository
{
    protected $table = 'kecamatan';

    public function getGroupedContacts(): array
    {
        $rows = $this->rows(
            $this->query()
                ->from('kecamatan as k')
                ->select([
                    'k.id',
                    'k.district',
                    'k.region',
                    'k.address',
                    'k.phone',
                    'k.lat',
                    'k.lng',
                ])
                ->selectRaw('COUNT(g.id) AS building_count')
                ->join('gedung as g', function ($join): void {
                    $join->on('g.district_id', '=', 'k.id')
                        ->where('g.status', '=', 'AKTIF');
                })
                ->whereNotNull('k.lat')
                ->whereNotNull('k.lng')
                ->groupBy('k.id', 'k.district', 'k.region', 'k.address', 'k.phone', 'k.lat', 'k.lng')
                ->orderByRaw("FIELD(k.region, 'Pusat', 'Timur', 'Selatan', 'Barat', 'Utara')")
                ->orderBy('k.district')
                ->get()
        );

        $regionOrder = ['Pusat', 'Timur', 'Selatan', 'Barat', 'Utara'];
        $result = [];

        foreach ($regionOrder as $region) {
            $result[$region] = [
                'label' => 'Surabaya ' . $region,
                'region' => $region,
                'district_count' => 0,
                'contacts' => [],
            ];
        }

        foreach ($rows as $row) {
            $region = $row['region'] ?? 'Pusat';

            if (!isset($result[$region])) {
                $result[$region] = [
                    'label' => 'Surabaya ' . $region,
                    'region' => $region,
                    'district_count' => 0,
                    'contacts' => [],
                ];
            }

            $result[$region]['contacts'][] = [
                'id' => (int) $row['id'],
                'district' => $row['district'],
                'region' => $region,
                'address' => $row['address'] ?? '-',
                'phone' => $row['phone'] ?? '-',
                'lat' => (float) $row['lat'],
                'lng' => (float) $row['lng'],
                'building_count' => (int) $row['building_count'],
            ];

            $result[$region]['district_count']++;
        }

        return $result;
    }

    public function getAllContactsFlat(): array
    {
        $grouped = $this->getGroupedContacts();
        $flat = [];

        foreach ($grouped as $data) {
            foreach ($data['contacts'] as $item) {
                $flat[] = $item;
            }
        }

        return $flat;
    }
}
