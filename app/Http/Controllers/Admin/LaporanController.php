<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class LaporanController extends Controller
{
    public function rating()
    {
        require_admin_menu_access('laporan.rating.gedung');
        $this->redirect('/admin/laporan/rating/gedung');
    }

    public function gedung()
    {
        require_admin_menu_access('laporan.rating.gedung');
        csrf_token();

        $admin = admin_user() ?? [];
        $roleContext = resolve_admin_role_context($admin);
        $districtId = (int) ($admin['district_id'] ?? 0);

        $gedungModel = $this->model('Gedung');
        $reportItems = $gedungModel->getRatingReportItems();

        if (($roleContext['scope_type'] ?? 'all') === 'district' && $districtId > 0) {
            $reportItems = $this->filterGedungByDistrict($reportItems, $districtId);
        }

        $reportItems = $this->normalizeGedungItems($reportItems);

        $this->view('admin.laporan.rating', [
            'title' => 'Laporan Rating Gedung - SIGAP',
            'admin' => $admin,
            'roleContext' => $roleContext,
            'reportType' => 'gedung',
            'reportLabel' => 'Gedung',
            'reportItems' => $reportItems,
            'summaryCards' => $this->buildSummaryCards($reportItems, 'Gedung'),
        ]);
    }

    public function umkm()
    {
        require_admin_menu_access('laporan.rating.umkm');
        csrf_token();

        $admin = admin_user() ?? [];
        $roleContext = resolve_admin_role_context($admin);
        $districtName = trim((string) ($roleContext['district_name'] ?? ''));

        $umkmModel = $this->model('Umkm');
        $reportItems = $umkmModel->getRatingReportItems();

        if (($roleContext['scope_type'] ?? 'all') === 'district') {
            $reportItems = $this->filterUmkmByDistrict($reportItems, $districtName);
        }

        $reportItems = $this->normalizeUmkmItems($reportItems);

        $this->view('admin.laporan.rating', [
            'title' => 'Laporan Rating UMKM - SIGAP',
            'admin' => $admin,
            'roleContext' => $roleContext,
            'reportType' => 'umkm',
            'reportLabel' => 'UMKM',
            'reportItems' => $reportItems,
            'summaryCards' => $this->buildSummaryCards($reportItems, 'UMKM'),
        ]);
    }

    private function filterGedungByDistrict(array $items, int $districtId): array
    {
        return array_values(array_filter($items, function (array $item) use ($districtId): bool {
            return (int) ($item['district_id'] ?? 0) === $districtId;
        }));
    }

    private function filterUmkmByDistrict(array $items, string $districtName): array
    {
        if (trim($districtName) === '') {
            return [];
        }

        return array_values(array_filter($items, function (array $item) use ($districtName): bool {
            return in_array($districtName, (array) ($item['gsg_districts'] ?? []), true);
        }));
    }

    private function normalizeGedungItems(array $items): array
    {
        usort($items, function (array $left, array $right): int {
            $leftRating = (float) ($left['rating'] ?? -1);
            $rightRating = (float) ($right['rating'] ?? -1);

            if ($leftRating === $rightRating) {
                $leftReviews = (int) ($left['review_count'] ?? 0);
                $rightReviews = (int) ($right['review_count'] ?? 0);

                if ($leftReviews === $rightReviews) {
                    return strnatcasecmp((string) ($left['building_name'] ?? ''), (string) ($right['building_name'] ?? ''));
                }

                return $rightReviews <=> $leftReviews;
            }

            return $rightRating <=> $leftRating;
        });

        foreach ($items as &$item) {
            $rating = isset($item['rating']) ? (float) $item['rating'] : null;
            $reviewCount = max(0, (int) ($item['review_count'] ?? 0));
            $item['name_label'] = trim((string) ($item['building_name'] ?? '-')) !== '' ? (string) $item['building_name'] : '-';
            $item['name_subtitle'] = trim((string) ($item['address'] ?? '')) !== '' ? (string) $item['address'] : '-';
            $item['location_label'] = $this->buildGedungLocationLabel($item);
            $item['location_subtitle'] = trim((string) ($item['subdistrict'] ?? '')) !== '' ? (string) $item['subdistrict'] : '-';
            $item['capacity_label'] = ((int) ($item['capacity'] ?? 0) > 0)
                ? number_format((int) $item['capacity'], 0, ',', '.') . ' orang'
                : '-';
            $item['rating_label'] = $rating !== null ? number_format($rating, 1, ',', '.') : '-';
            $item['rating_tone'] = $this->resolveRatingTone($rating);
            $item['review_count_label'] = number_format($reviewCount, 0, ',', '.');
        }
        unset($item);

        return $items;
    }

    private function normalizeUmkmItems(array $items): array
    {
        usort($items, function (array $left, array $right): int {
            $leftRating = (float) ($left['rating'] ?? -1);
            $rightRating = (float) ($right['rating'] ?? -1);

            if ($leftRating === $rightRating) {
                $leftReviews = (int) ($left['review_count'] ?? 0);
                $rightReviews = (int) ($right['review_count'] ?? 0);

                if ($leftReviews === $rightReviews) {
                    return strnatcasecmp((string) ($left['umkm_name'] ?? ''), (string) ($right['umkm_name'] ?? ''));
                }

                return $rightReviews <=> $leftReviews;
            }

            return $rightRating <=> $leftRating;
        });

        foreach ($items as &$item) {
            $rating = isset($item['rating']) ? (float) $item['rating'] : null;
            $reviewCount = max(0, (int) ($item['review_count'] ?? 0));
            $buildingNames = array_values((array) ($item['building_names'] ?? []));

            $item['name_label'] = trim((string) ($item['umkm_name'] ?? '-')) !== '' ? (string) $item['umkm_name'] : '-';
            $item['name_subtitle'] = trim((string) ($item['owner'] ?? '')) !== '' ? (string) $item['owner'] : '-';
            $item['product_label'] = trim((string) ($item['product_label'] ?? '')) !== '' ? (string) $item['product_label'] : '-';
            $item['building_count'] = count($buildingNames);
            $item['building_summary'] = $this->buildBuildingSummary($buildingNames);
            $item['product_subtitle'] = $item['building_summary'];
            $item['location_label'] = $this->buildUmkmLocationLabel($item);
            $item['location_subtitle'] = trim((string) ($item['home_subdistrict'] ?? '')) !== '' ? (string) $item['home_subdistrict'] : '-';
            $item['rating_label'] = $rating !== null ? number_format($rating, 1, ',', '.') : '-';
            $item['rating_tone'] = $this->resolveRatingTone($rating);
            $item['review_count_label'] = number_format($reviewCount, 0, ',', '.');
        }
        unset($item);

        return $items;
    }

    private function buildSummaryCards(array $items, string $entityLabel): array
    {
        $ratingValues = [];
        $reviewTotal = 0;

        foreach ($items as $item) {
            $rating = $item['rating'] ?? null;
            if ($rating !== null && (float) $rating > 0) {
                $ratingValues[] = (float) $rating;
            }

            $reviewTotal += max(0, (int) ($item['review_count'] ?? 0));
        }

        $averageRating = !empty($ratingValues)
            ? array_sum($ratingValues) / count($ratingValues)
            : null;

        $highRatedCount = count(array_filter($items, function (array $item): bool {
            return (float) ($item['rating'] ?? 0) >= 4.0;
        }));

        return [
            [
                'label' => 'Total ' . $entityLabel,
                'value' => number_format(count($items), 0, ',', '.'),
                'tone' => 'primary',
                'icon' => 'ti ti-building-store',
            ],
            [
                'label' => 'Rata-rata Rating',
                'value' => $averageRating !== null ? number_format($averageRating, 1, ',', '.') . ' / 5' : '-',
                'tone' => 'success',
                'icon' => 'ti ti-star',
            ],
            [
                'label' => 'Rating >= 4.0',
                'value' => number_format($highRatedCount, 0, ',', '.'),
                'tone' => 'warning',
                'icon' => 'ti ti-star',
            ],
            [
                'label' => 'Total Ulasan',
                'value' => number_format($reviewTotal, 0, ',', '.'),
                'tone' => 'info',
                'icon' => 'ti ti-message-2',
            ],
        ];
    }

    private function buildGedungLocationLabel(array $item): string
    {
        $region = trim((string) ($item['home_region'] ?? ''));
        $district = trim((string) ($item['district'] ?? ''));

        if ($region !== '' && $district !== '') {
            return $region . ' - ' . $district;
        }

        if ($district !== '') {
            return $district;
        }

        if ($region !== '') {
            return $region;
        }

        return '-';
    }

    private function buildUmkmLocationLabel(array $item): string
    {
        $region = trim((string) ($item['home_region'] ?? ''));
        $district = trim((string) ($item['home_district'] ?? ''));

        if ($region !== '' && $district !== '') {
            return $region . ' - ' . $district;
        }

        if ($district !== '') {
            return $district;
        }

        if ($region !== '') {
            return $region;
        }

        $gsgDistricts = array_values((array) ($item['gsg_districts'] ?? []));

        return !empty($gsgDistricts) ? implode(', ', array_slice($gsgDistricts, 0, 2)) : '-';
    }

    private function buildBuildingSummary(array $buildingNames): string
    {
        $buildingNames = array_values(array_filter(array_map('trim', $buildingNames)));

        if (empty($buildingNames)) {
            return '-';
        }

        $preview = array_slice($buildingNames, 0, 2);
        $summary = implode(', ', $preview);
        $remaining = count($buildingNames) - count($preview);

        if ($remaining > 0) {
            $summary .= ' + ' . $remaining . ' lainnya';
        }

        return $summary;
    }

    private function resolveRatingTone(?float $rating): string
    {
        if ($rating === null) {
            return 'secondary';
        }

        if ($rating >= 4.5) {
            return 'success';
        }

        if ($rating >= 4.0) {
            return 'primary';
        }

        if ($rating >= 3.0) {
            return 'warning';
        }

        return 'danger';
    }
}
