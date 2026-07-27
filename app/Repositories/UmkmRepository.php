<?php

namespace App\Repositories;

class UmkmRepository extends LegacyRepository
{
    protected $table = 'umkm';

    protected array $regionOrder = ['Pusat', 'Timur', 'Selatan', 'Barat', 'Utara'];

    protected int $itemsPerPage = 6;

    public function getRatingReportItems(): array
    {
        return $this->getActiveLinkedItems();
    }

    public function getReservationOptions(): array
    {
        return $this->hydrateReservationOptions($this->getActiveReservationRows());
    }

    public function getRandomHeroThumbnails(int $limit = 3): array
    {
        $limit = max(1, $limit);
        $rows = $this->rows(
            $this->query()
                ->select('umkm_name', 'pic_path')
                ->where('status', 'AKTIF')
                ->whereNotNull('pic_path')
                ->whereRaw("TRIM(pic_path) <> ''")
                ->get()
        );
        $thumbnails = [];

        foreach ($rows as $row) {
            $picPath = $this->normalizeUploadPath((string) ($row['pic_path'] ?? ''));

            if ($picPath === '' || isset($thumbnails[$picPath])) {
                continue;
            }

            $thumbnailUrl = $this->resolveUploadThumbnailUrl($picPath);

            if ($thumbnailUrl === null) {
                continue;
            }

            $thumbnails[$picPath] = [
                'src' => $thumbnailUrl,
                'alt' => trim((string) ($row['umkm_name'] ?? 'UMKM')),
            ];
        }

        if (empty($thumbnails)) {
            return [];
        }

        $thumbnails = array_values($thumbnails);
        shuffle($thumbnails);

        return array_slice($thumbnails, 0, $limit);
    }

    public function findReservationOptionById(int $umkmId): ?array
    {
        if ($umkmId <= 0) {
            return null;
        }

        $options = $this->hydrateReservationOptions($this->getActiveReservationRows($umkmId));

        return $options[0] ?? null;
    }

    public function getPageData(array $rawFilters = []): array
    {
        $items = $this->getActiveLinkedItems();
        $options = $this->buildFilterOptions($items);
        $filters = $this->normalizeFilters($rawFilters, $options);
        $filteredItems = $this->applyFilters($items, $filters);
        $pagination = $this->paginateItems($filteredItems, $rawFilters);

        return [
            'items' => $pagination['items'],
            'allItemsCount' => count($items),
            'filteredItemsCount' => count($filteredItems),
            'filters' => $filters,
            'filterOptions' => $options,
            'pagination' => $pagination['meta'],
        ];
    }

    protected function getActiveLinkedItems(): array
    {
        $rows = $this->rows(
            $this->activeLinkedQuery()
                ->select([
                    'u.id',
                    'u.product_id',
                    'u.category',
                    'u.umkm_name',
                    'u.owner',
                    'u.address',
                    'u.phone',
                    'u.description',
                    'u.pic_path',
                    'u.rating_avg',
                    'u.review_count',
                    'u.dedupe_key',
                    'p.type AS product_type',
                    'kd.district AS home_district',
                    'kd.region AS home_region',
                    'kl.subdistrict AS home_subdistrict',
                    'g.id AS gedung_id',
                    'g.building_name',
                    'kg.district AS gsg_district',
                    'kg.region AS gsg_region',
                ])
                ->leftJoin('kecamatan as kd', 'kd.id', '=', 'u.district_id')
                ->leftJoin('kelurahan as kl', 'kl.id', '=', 'u.subdistrict_id')
                ->leftJoin('kecamatan as kg', 'kg.id', '=', 'g.district_id')
                ->orderBy('u.umkm_name')
                ->orderBy('g.building_name')
                ->get()
        );

        $items = [];

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);

            if ($id <= 0) {
                continue;
            }

            if (!isset($items[$id])) {
                $items[$id] = [
                    'id' => $id,
                    'product_id' => (int) ($row['product_id'] ?? 0),
                    'category' => (string) ($row['category'] ?? ''),
                    'product_type' => (string) ($row['product_type'] ?? 'UMKM'),
                    'product_label' => $this->buildProductLabel((string) ($row['product_type'] ?? 'UMKM')),
                    'umkm_name' => trim((string) ($row['umkm_name'] ?? 'UMKM Tanpa Nama')),
                    'owner' => trim((string) ($row['owner'] ?? '-')),
                    'address' => trim((string) ($row['address'] ?? '')),
                    'phone' => trim((string) ($row['phone'] ?? '')),
                    'description' => trim((string) ($row['description'] ?? '')),
                    'pic_path' => $this->normalizeUploadPath((string) ($row['pic_path'] ?? '')),
                    'home_district' => trim((string) ($row['home_district'] ?? '')),
                    'home_region' => trim((string) ($row['home_region'] ?? '')),
                    'home_subdistrict' => trim((string) ($row['home_subdistrict'] ?? '')),
                    'rating' => $this->normalizeRatingValue($row['rating_avg'] ?? null),
                    'review_count' => max(0, (int) ($row['review_count'] ?? 0)),
                    'gsg_locations' => [],
                    'gsg_regions' => [],
                    'gsg_districts' => [],
                    'building_names' => [],
                    'building_count' => 0,
                ];
            }

            $buildingId = (int) ($row['gedung_id'] ?? 0);
            $buildingName = trim((string) ($row['building_name'] ?? ''));
            $gsgDistrict = trim((string) ($row['gsg_district'] ?? ''));
            $gsgRegion = trim((string) ($row['gsg_region'] ?? ''));

            if ($buildingId > 0 && $buildingName !== '') {
                $items[$id]['building_names'][$buildingId] = $buildingName;
            }

            if ($gsgDistrict !== '' && $gsgRegion !== '') {
                $locationKey = $gsgRegion . '|' . $gsgDistrict;

                $items[$id]['gsg_locations'][$locationKey] = [
                    'region' => $gsgRegion,
                    'district' => $gsgDistrict,
                ];

                $items[$id]['gsg_regions'][$gsgRegion] = $gsgRegion;
                $items[$id]['gsg_districts'][$gsgDistrict] = $gsgDistrict;
            }
        }

        foreach ($items as &$item) {
            $item['gsg_locations'] = array_values($item['gsg_locations']);
            $item['gsg_regions'] = $this->sortRegions(array_values($item['gsg_regions']));
            sort($item['gsg_districts'], SORT_NATURAL | SORT_FLAG_CASE);
            $item['gsg_districts'] = array_values($item['gsg_districts']);
            sort($item['building_names'], SORT_NATURAL | SORT_FLAG_CASE);
            $item['building_names'] = array_values($item['building_names']);
            $item['building_count'] = count($item['building_names']);
        }
        unset($item);

        uasort($items, function (array $left, array $right): int {
            return strnatcasecmp($left['umkm_name'], $right['umkm_name']);
        });

        return array_values($items);
    }

    protected function getActiveReservationRows(?int $umkmId = null): array
    {
        $query = $this->activeLinkedQuery(false)
            ->select([
                'u.id',
                'u.umkm_name',
                'p.type AS product_type',
                'g.id AS building_id',
                'g.building_name',
            ]);

        if ($umkmId !== null && $umkmId > 0) {
            $query->where('u.id', $umkmId);
        }

        return $this->rows(
            $query
                ->orderBy('u.umkm_name')
                ->orderBy('g.building_name')
                ->get()
        );
    }

    protected function activeLinkedQuery(bool $activeProductOnly = true)
    {
        $query = $this->query()
            ->from('umkm as u')
            ->join('gedung_umkm as gu', 'gu.umkm_id', '=', 'u.id')
            ->join('gedung as g', function ($join): void {
                $join->on('g.id', '=', 'gu.gedung_id')
                    ->where('g.status', '=', 'AKTIF');
            })
            ->where('u.status', 'AKTIF');

        if ($activeProductOnly) {
            return $query->leftJoin('produk as p', function ($join): void {
                $join->on('p.id', '=', 'u.product_id')
                    ->where('p.status', '=', 'AKTIF');
            });
        }

        return $query->leftJoin('produk as p', 'p.id', '=', 'u.product_id');
    }

    protected function hydrateReservationOptions(array $rows): array
    {
        $options = [];

        foreach ($rows as $row) {
            $umkmId = (int) ($row['id'] ?? 0);
            if ($umkmId <= 0) {
                continue;
            }

            if (!isset($options[$umkmId])) {
                $options[$umkmId] = [
                    'id' => $umkmId,
                    'product_label' => $this->buildProductLabel((string) ($row['product_type'] ?? 'UMKM')),
                    'umkm_name' => trim((string) ($row['umkm_name'] ?? 'UMKM')),
                    'building_ids' => [],
                    'building_names' => [],
                ];
            }

            $buildingId = (int) ($row['building_id'] ?? 0);
            $buildingName = trim((string) ($row['building_name'] ?? ''));

            if ($buildingId > 0) {
                $options[$umkmId]['building_ids'][$buildingId] = $buildingId;
            }

            if ($buildingName !== '') {
                $options[$umkmId]['building_names'][$buildingName] = $buildingName;
            }
        }

        foreach ($options as &$option) {
            $option['building_ids'] = array_values($option['building_ids']);
            sort($option['building_ids'], SORT_NUMERIC);

            $option['building_names'] = array_values($option['building_names']);
            sort($option['building_names'], SORT_NATURAL | SORT_FLAG_CASE);
        }
        unset($option);

        return array_values($options);
    }

    protected function buildFilterOptions(array $items): array
    {
        $productTypes = [];
        $regionDistrictMap = [];

        foreach ($items as $item) {
            $productType = trim((string) ($item['product_type'] ?? ''));

            if ($productType !== '') {
                $productTypes[$productType] = [
                    'value' => $productType,
                    'label' => $item['product_label'] ?? $productType,
                ];
            }

            foreach ($item['gsg_locations'] as $location) {
                $region = trim((string) ($location['region'] ?? ''));
                $district = trim((string) ($location['district'] ?? ''));

                if ($region === '' || $district === '') {
                    continue;
                }

                if (!isset($regionDistrictMap[$region])) {
                    $regionDistrictMap[$region] = [];
                }

                $regionDistrictMap[$region][$district] = $district;
            }
        }

        $sortedRegions = [];

        foreach ($this->regionOrder as $region) {
            if (!isset($regionDistrictMap[$region])) {
                continue;
            }

            $districts = array_values($regionDistrictMap[$region]);
            sort($districts, SORT_NATURAL | SORT_FLAG_CASE);

            $sortedRegions[$region] = $districts;
        }

        uasort($productTypes, function (array $left, array $right): int {
            return strnatcasecmp($left['label'], $right['label']);
        });

        $allDistricts = [];

        foreach ($sortedRegions as $districts) {
            foreach ($districts as $district) {
                $allDistricts[$district] = $district;
            }
        }

        sort($allDistricts, SORT_NATURAL | SORT_FLAG_CASE);

        return [
            'productTypes' => array_values($productTypes),
            'regions' => array_keys($sortedRegions),
            'regionDistrictMap' => $sortedRegions,
            'allDistricts' => array_values($allDistricts),
            'ratingOptions' => [
                ['value' => '', 'label' => 'Semua Rating'],
                ['value' => '4', 'label' => '>= 4.0 bintang'],
                ['value' => '4.5', 'label' => '>= 4.5 bintang'],
                ['value' => '5', 'label' => '5.0 bintang'],
            ],
        ];
    }

    protected function normalizeFilters(array $rawFilters, array $options): array
    {
        $product = trim((string) ($rawFilters['product'] ?? ''));
        $region = trim((string) ($rawFilters['region'] ?? ''));
        $district = trim((string) ($rawFilters['district'] ?? ''));
        $ratingMinRaw = trim((string) ($rawFilters['rating_min'] ?? ''));

        $allowedProducts = array_column($options['productTypes'], 'value');

        if (!in_array($product, $allowedProducts, true)) {
            $product = '';
        }

        if (!isset($options['regionDistrictMap'][$region])) {
            $region = '';
        }

        $allowedDistricts = $region !== ''
            ? ($options['regionDistrictMap'][$region] ?? [])
            : ($options['allDistricts'] ?? []);

        if (!in_array($district, $allowedDistricts, true)) {
            $district = '';
        }

        $allowedRatings = ['', '4', '4.5', '5'];

        if (!in_array($ratingMinRaw, $allowedRatings, true)) {
            $ratingMinRaw = '';
        }

        return [
            'product' => $product,
            'region' => $region,
            'district' => $district,
            'rating_min' => $ratingMinRaw,
        ];
    }

    protected function applyFilters(array $items, array $filters): array
    {
        $product = $filters['product'];
        $region = $filters['region'];
        $district = $filters['district'];
        $ratingMin = $filters['rating_min'] !== '' ? (float) $filters['rating_min'] : 0.0;

        return array_values(array_filter($items, function (array $item) use ($product, $region, $district, $ratingMin): bool {
            if ($product !== '' && (string) ($item['product_type'] ?? '') !== $product) {
                return false;
            }

            if ($ratingMin > 0 && (float) ($item['rating'] ?? 0) < $ratingMin) {
                return false;
            }

            if ($region !== '' && !in_array($region, $item['gsg_regions'] ?? [], true)) {
                return false;
            }

            if ($district !== '' && !in_array($district, $item['gsg_districts'] ?? [], true)) {
                return false;
            }

            return true;
        }));
    }

    protected function paginateItems(array $items, array $rawFilters): array
    {
        $totalItems = count($items);
        $perPage = max(1, $this->itemsPerPage);
        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        $requestedPage = (int) ($rawFilters['page'] ?? 1);
        $currentPage = max(1, min($requestedPage, $totalPages));
        $offset = ($currentPage - 1) * $perPage;
        $pageItems = $totalItems > 0
            ? array_slice($items, $offset, $perPage)
            : [];
        $currentCount = count($pageItems);

        return [
            'items' => $pageItems,
            'meta' => [
                'perPage' => $perPage,
                'currentPage' => $currentPage,
                'totalPages' => $totalPages,
                'totalItems' => $totalItems,
                'from' => $totalItems > 0 ? $offset + 1 : 0,
                'to' => $totalItems > 0 ? $offset + $currentCount : 0,
                'hasPreviousPage' => $currentPage > 1,
                'hasNextPage' => $currentPage < $totalPages,
                'previousPage' => $currentPage > 1 ? $currentPage - 1 : 1,
                'nextPage' => $currentPage < $totalPages ? $currentPage + 1 : $totalPages,
                'firstPage' => 1,
                'lastPage' => $totalPages,
            ],
        ];
    }

    protected function buildProductLabel(string $productType): string
    {
        if (stripos($productType, 'rias') !== false) {
            return 'Rias';
        }

        if (stripos($productType, 'katering') !== false) {
            return 'Katering';
        }

        return trim($productType) !== '' ? $productType : 'UMKM';
    }

    protected function normalizeUploadPath(string $path): string
    {
        $normalized = str_replace('\\', '/', trim($path));
        $normalized = ltrim($normalized, '/');

        return $normalized;
    }

    protected function resolveUploadThumbnailUrl(string $relativePath): ?string
    {
        $relativePath = trim(str_replace('\\', '/', $relativePath));

        if ($relativePath === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $relativePath)) {
            return $relativePath;
        }

        $normalizedPath = ltrim($relativePath, '/');
        $candidates = [];

        if (strpos($normalizedPath, 'assets/uploads/') === 0) {
            $candidates[] = $normalizedPath;
        }

        if (strpos($normalizedPath, 'uploads/') === 0) {
            $candidates[] = 'assets/' . $normalizedPath;
        }

        $candidates[] = 'assets/uploads/' . $normalizedPath;
        $candidates[] = 'assets/custom/' . $normalizedPath;
        $candidates = array_values(array_unique($candidates));

        foreach ($candidates as $candidate) {
            if (legacy_first_existing_asset_path($candidate) !== null) {
                return asset($candidate);
            }
        }

        return null;
    }

    protected function normalizeRatingValue($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return round((float) $value, 1);
    }

    protected function sortRegions(array $regions): array
    {
        $orderIndex = array_flip($this->regionOrder);

        usort($regions, function (string $left, string $right) use ($orderIndex): int {
            $leftIndex = $orderIndex[$left] ?? 999;
            $rightIndex = $orderIndex[$right] ?? 999;

            if ($leftIndex !== $rightIndex) {
                return $leftIndex <=> $rightIndex;
            }

            return strnatcasecmp($left, $right);
        });

        return $regions;
    }
}
