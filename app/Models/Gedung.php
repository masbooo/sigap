<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDO;

class Gedung
{
    protected PDO $db;
    protected array $columnExistsCache = [];

    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
    }

    public function getRatingReportItems(): array
    {
        $ratingSql = $this->getColumnExpressionSql('gedung', 'g', 'rating', '0', 'rating_avg');
        $reviewCountSql = $this->getColumnExpressionSql('gedung', 'g', 'review_count', '0');

        $sql = "
            SELECT
                g.id,
                g.building_name,
                g.address,
                g.capacity,
                g.district_id,
                g.subdistrict_id,
                {$ratingSql},
                {$reviewCountSql},
                fg.image_path AS building_photo,
                k.district,
                k.region AS home_region,
                kl.subdistrict
            FROM gedung g
            LEFT JOIN (
                SELECT
                    building_id,
                    MIN(image_path) AS image_path
                FROM foto_gedung
                WHERE is_thumbnail = 1
                GROUP BY building_id
            ) fg
                ON fg.building_id = g.id
            LEFT JOIN kecamatan k
                ON k.id = g.district_id
            LEFT JOIN kelurahan kl
                ON kl.id = g.subdistrict_id
            WHERE g.status = 'AKTIF'
            ORDER BY g.building_name ASC
        ";

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll() ?: [];
    }

    public function getGroupedByRegion(): array
    {
        $perHourPriceSql = $this->getAliasedColumnSql('gedung', 'g', 'perhour_price', 'overtime_price');
        $sql = "
            SELECT
                g.id,
                g.building_name,
                g.description,
                g.address,
                g.district_id,
                g.subdistrict_id,
                g.building_area,
                g.capacity,
                g.session_price,
                {$perHourPriceSql},
                g.status,
                fg.image_path AS building_photo,
                k.district,
                k.region AS district_region,
                kl.subdistrict
            FROM gedung g
            LEFT JOIN (
                SELECT
                    building_id,
                    MIN(image_path) AS image_path
                FROM foto_gedung
                WHERE is_thumbnail = 1
                GROUP BY building_id
            ) fg
                ON fg.building_id = g.id
            LEFT JOIN kecamatan k
                ON k.id = g.district_id
            LEFT JOIN kelurahan kl
                ON kl.id = g.subdistrict_id
            WHERE g.status = 'AKTIF'
            ORDER BY
                FIELD(k.region, 'Pusat', 'Timur', 'Selatan', 'Barat', 'Utara'),
                k.district ASC,
                g.building_name ASC
        ";

        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll();

        $grouped = [
            'Pusat' => ['label' => 'Surabaya Pusat', 'districts' => [], 'buildings' => []],
            'Timur' => ['label' => 'Surabaya Timur', 'districts' => [], 'buildings' => []],
            'Selatan' => ['label' => 'Surabaya Selatan', 'districts' => [], 'buildings' => []],
            'Barat' => ['label' => 'Surabaya Barat', 'districts' => [], 'buildings' => []],
            'Utara' => ['label' => 'Surabaya Utara', 'districts' => [], 'buildings' => []],
        ];

        foreach ($rows as $row) {
            $region = $row['district_region'] ?: 'Pusat';
            $district = $row['district'] ?: 'Tidak Diketahui';
            $subdistrict = $row['subdistrict'] ?: '-';

            if (!isset($grouped[$region])) {
                $grouped[$region] = [
                    'label' => 'Surabaya ' . $region,
                    'districts' => [],
                    'buildings' => [],
                ];
            }

            $row['region'] = $region;
            $row['district'] = $district;
            $row['subdistrict'] = $subdistrict;

            $grouped[$region]['buildings'][] = $row;

            if (!isset($grouped[$region]['districts'][$district])) {
                $grouped[$region]['districts'][$district] = 0;
            }

            $grouped[$region]['districts'][$district]++;
        }

        foreach ($grouped as $region => $item) {
            ksort($grouped[$region]['districts']);
        }

        return $grouped;
    }

    public function getAllActive(): array
    {
        $perHourPriceSql = $this->getAliasedColumnSql('gedung', 'g', 'perhour_price', 'overtime_price');
        $sql = "
            SELECT
                g.id,
                g.building_name,
                g.description,
                g.address,
                g.district_id,
                g.subdistrict_id,
                g.building_area,
                g.capacity,
                g.session_price,
                {$perHourPriceSql},
                g.status,
                fg.image_path AS building_photo,
                k.district,
                k.region AS district_region,
                kl.subdistrict
            FROM gedung g
            LEFT JOIN (
                SELECT
                    building_id,
                    MIN(image_path) AS image_path
                FROM foto_gedung
                WHERE is_thumbnail = 1
                GROUP BY building_id
            ) fg
                ON fg.building_id = g.id
            LEFT JOIN kecamatan k
                ON k.id = g.district_id
            LEFT JOIN kelurahan kl
                ON kl.id = g.subdistrict_id
            WHERE g.status = 'AKTIF'
            ORDER BY g.building_name ASC
        ";

        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $row['region'] = $row['district_region'] ?: '-';
            $row['district'] = $row['district'] ?: '-';
            $row['subdistrict'] = $row['subdistrict'] ?: '-';
        }

        return $rows;
    }

    private function getAliasedColumnSql(string $table, string $tableAlias, string $preferredColumn, ?string $legacyColumn = null): string
    {
        $columnName = $this->resolveColumnName($table, $preferredColumn, $legacyColumn);
        $expression = ($tableAlias !== '' ? $tableAlias . '.' : '') . $columnName;

        if ($columnName !== $preferredColumn) {
            $expression .= ' AS ' . $preferredColumn;
        }

        return $expression;
    }

    private function getColumnExpressionSql(string $table, string $tableAlias, string $preferredColumn, string $defaultExpression, ?string $legacyColumn = null): string
    {
        if ($this->hasColumn($table, $preferredColumn)) {
            return ($tableAlias !== '' ? $tableAlias . '.' : '') . $preferredColumn . ' AS ' . $preferredColumn;
        }

        if ($legacyColumn !== null && $this->hasColumn($table, $legacyColumn)) {
            $expression = ($tableAlias !== '' ? $tableAlias . '.' : '') . $legacyColumn;

            if ($legacyColumn !== $preferredColumn) {
                $expression .= ' AS ' . $preferredColumn;
            }

            return $expression;
        }

        return $defaultExpression . ' AS ' . $preferredColumn;
    }

    private function resolveColumnName(string $table, string $preferredColumn, ?string $legacyColumn = null): string
    {
        if ($this->hasColumn($table, $preferredColumn)) {
            return $preferredColumn;
        }

        if ($legacyColumn !== null && $this->hasColumn($table, $legacyColumn)) {
            return $legacyColumn;
        }

        return $preferredColumn;
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
}
