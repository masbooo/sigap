<?php

class Kontak
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getGroupedContacts(): array
    {
        $sql = "
            SELECT
                k.id,
                k.district,
                k.region,
                k.address,
                k.phone,
                k.lat,
                k.lng,
                COUNT(g.id) AS building_count
            FROM kecamatan k
            INNER JOIN gedung g
                ON g.district_id = k.id
               AND g.status = 'AKTIF'
            WHERE k.lat IS NOT NULL
              AND k.lng IS NOT NULL
            GROUP BY
                k.id,
                k.district,
                k.region,
                k.address,
                k.phone,
                k.lat,
                k.lng
            ORDER BY
                FIELD(k.region, 'Pusat', 'Timur', 'Selatan', 'Barat', 'Utara'),
                k.district ASC
        ";

        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

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