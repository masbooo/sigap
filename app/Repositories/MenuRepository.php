<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class MenuRepository extends LegacyRepository
{
    protected $table = 'menu';

    private static bool $bootstrapped = false;

    public function getHydratedBlueprint(array $masterBlueprint, array $defaultRoleAccessMap = []): array
    {
        $this->bootstrap($masterBlueprint, $defaultRoleAccessMap);

        $menuRows = $this->getAllMenusIndexedBySignature();

        foreach ($masterBlueprint as &$section) {
            $sectionHeading = (string) ($section['heading'] ?? '');

            if (empty($section['items']) || !is_array($section['items'])) {
                continue;
            }

            foreach ($section['items'] as &$item) {
                $itemLabel = (string) ($item['label'] ?? '');
                $children = (array) ($item['children'] ?? []);

                if (!empty($children)) {
                    foreach ($item['children'] as &$child) {
                        $grandChildren = (array) ($child['children'] ?? []);

                        if (!empty($grandChildren)) {
                            foreach ($child['children'] as &$grandChild) {
                                $this->applyMenuRowToLeaf(
                                    $grandChild,
                                    $menuRows,
                                    $sectionHeading,
                                    (string) ($child['label'] ?? ''),
                                    (string) ($grandChild['label'] ?? '')
                                );
                            }
                            unset($grandChild);
                        } else {
                            $this->applyMenuRowToLeaf(
                                $child,
                                $menuRows,
                                $sectionHeading,
                                $itemLabel,
                                (string) ($child['label'] ?? '')
                            );
                        }
                    }
                    unset($child);
                } else {
                    $this->applyMenuRowToLeaf(
                        $item,
                        $menuRows,
                        $sectionHeading,
                        (string) ($item['label'] ?? ''),
                        null
                    );
                }
            }
            unset($item);
        }
        unset($section);

        return $masterBlueprint;
    }

    public function getRoles(): array
    {
        return $this->rows(
            $this->table('peran')
                ->select('id', 'role_name', 'description')
                ->orderBy('id')
                ->get()
        );
    }

    public function getRoleAccessMenuIds(int $roleId): array
    {
        return $this->table('menu_peran')
            ->where('peran_id', $roleId)
            ->where('is_allowed', 1)
            ->orderBy('menu_id')
            ->pluck('menu_id')
            ->map(fn ($menuId): int => (int) $menuId)
            ->all();
    }

    public function hasRoleAccessRows(int $roleId): bool
    {
        return $this->table('menu_peran')
            ->where('peran_id', $roleId)
            ->exists();
    }

    public function saveRoleAccessMenuIds(int $roleId, array $allowedMenuIds, ?array $allMenuIds = null): void
    {
        $allowedMenuIds = array_values(array_unique(array_map('intval', $allowedMenuIds)));
        $allowedLookup = array_fill_keys($allowedMenuIds, true);

        if ($allMenuIds === null) {
            $allMenuIds = $this->query()
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($menuId): int => (int) $menuId)
                ->all();
        } else {
            $allMenuIds = array_values(array_unique(array_map('intval', $allMenuIds)));
        }

        DB::transaction(function () use ($roleId, $allMenuIds, $allowedLookup): void {
            $this->table('menu_peran')
                ->where('peran_id', $roleId)
                ->delete();

            if (empty($allMenuIds)) {
                return;
            }

            $rows = array_map(
                fn (int $menuId): array => [
                    'peran_id' => $roleId,
                    'menu_id' => $menuId,
                    'is_allowed' => isset($allowedLookup[$menuId]) ? 1 : 0,
                ],
                $allMenuIds
            );

            $this->table('menu_peran')->insert($rows);
        });
    }

    private function bootstrap(array $masterBlueprint, array $defaultRoleAccessMap): void
    {
        if (self::$bootstrapped) {
            return;
        }

        $this->ensureAccessTable();
        $this->syncMenuTable($masterBlueprint);
        $this->seedRoleAccess($masterBlueprint, $defaultRoleAccessMap);

        self::$bootstrapped = true;
    }

    private function ensureAccessTable(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS menu_peran (
                id INT(11) NOT NULL AUTO_INCREMENT,
                peran_id INT(11) NOT NULL,
                menu_id INT(11) NOT NULL,
                is_allowed TINYINT(1) NOT NULL DEFAULT 1,
                updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_peran_menu (peran_id, menu_id),
                KEY idx_menu_peran_peran (peran_id),
                KEY idx_menu_peran_menu (menu_id),
                CONSTRAINT fk_menu_peran_peran FOREIGN KEY (peran_id) REFERENCES peran(id) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT fk_menu_peran_menu FOREIGN KEY (menu_id) REFERENCES menu(id) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    private function syncMenuTable(array $masterBlueprint): void
    {
        $flatMenus = $this->flattenBlueprint($masterBlueprint);
        $existingRows = $this->getAllMenusIndexedBySignature();
        $existingRowsByRoute = $this->getAllMenusIndexedByRoute();

        foreach ($flatMenus as $flatMenu) {
            $signature = $flatMenu['signature'];
            $existing = $existingRows[$signature] ?? null;

            if ($existing === null) {
                $routeSignature = $this->buildRouteSignature(
                    $flatMenu['heading_menu'],
                    $flatMenu['link_href']
                );
                $existing = $existingRowsByRoute[$routeSignature] ?? null;
            }

            if ($existing === null) {
                $this->query()->insert([
                    'heading_menu' => $flatMenu['heading_menu'],
                    'label_parent' => $flatMenu['label_parent'],
                    'label_child' => $flatMenu['label_child'],
                    'link_href' => $flatMenu['link_href'],
                    'icon_menu' => $flatMenu['icon_menu'],
                ]);
                continue;
            }

            $needsUpdate =
                trim((string) ($existing['heading_menu'] ?? '')) !== $flatMenu['heading_menu'] ||
                trim((string) ($existing['label_parent'] ?? '')) !== $flatMenu['label_parent'] ||
                trim((string) ($existing['label_child'] ?? '')) !== (string) $flatMenu['label_child'] ||
                trim((string) ($existing['link_href'] ?? '')) !== $flatMenu['link_href'] ||
                trim((string) ($existing['icon_menu'] ?? '')) !== $flatMenu['icon_menu'];

            if (!$needsUpdate) {
                continue;
            }

            $this->query()
                ->where('id', (int) $existing['id'])
                ->update([
                    'heading_menu' => $flatMenu['heading_menu'],
                    'label_parent' => $flatMenu['label_parent'],
                    'label_child' => $flatMenu['label_child'],
                    'link_href' => $flatMenu['link_href'],
                    'icon_menu' => $flatMenu['icon_menu'],
                ]);
        }
    }

    private function seedRoleAccess(array $masterBlueprint, array $defaultRoleAccessMap): void
    {
        $flatMenus = $this->flattenBlueprint($masterBlueprint);
        $existingRows = $this->getAllMenusIndexedBySignature();

        $resolvedMenus = [];
        $allMenuIds = [];

        foreach ($flatMenus as $flatMenu) {
            $existing = $existingRows[$flatMenu['signature']] ?? null;
            if ($existing === null) {
                continue;
            }

            $menuId = (int) ($existing['id'] ?? 0);
            if ($menuId <= 0) {
                continue;
            }

            $resolvedMenus[] = [
                'key' => (string) ($flatMenu['key'] ?? ''),
                'menu_id' => $menuId,
                'access_signature' => $this->buildAccessSignature(
                    (string) ($flatMenu['heading_menu'] ?? ''),
                    (string) ($flatMenu['label_parent'] ?? ''),
                    $flatMenu['label_child'] ?? null,
                    (string) ($flatMenu['link_href'] ?? '')
                ),
            ];
            $allMenuIds[] = $menuId;
        }

        $roles = $this->getRoles();
        foreach ($roles as $role) {
            $roleId = (int) ($role['id'] ?? 0);
            if ($roleId <= 0) {
                continue;
            }

            if ($roleId === 1) {
                $this->saveRoleAccessMenuIds($roleId, $allMenuIds, $allMenuIds);
                continue;
            }

            $defaultKeys = $defaultRoleAccessMap[$roleId] ?? [];
            $defaultLookup = array_fill_keys(array_map('strval', $defaultKeys), true);
            $savedAccessBySignature = $this->getRoleAccessBySignature($roleId);
            $defaultMenuIds = [];

            foreach ($resolvedMenus as $resolvedMenu) {
                $menuKey = (string) ($resolvedMenu['key'] ?? '');
                $accessSignature = (string) ($resolvedMenu['access_signature'] ?? '');
                $isAllowed = $savedAccessBySignature[$accessSignature] ?? isset($defaultLookup[$menuKey]);

                if ($isAllowed) {
                    $defaultMenuIds[] = (int) ($resolvedMenu['menu_id'] ?? 0);
                }
            }

            $this->saveRoleAccessMenuIds($roleId, $defaultMenuIds, $allMenuIds);
        }
    }

    private function getAllMenusIndexedBySignature(): array
    {
        $rows = $this->getAllMenuRows();
        $indexed = [];

        foreach ($rows as $row) {
            $signature = $this->buildSignature(
                (string) ($row['heading_menu'] ?? ''),
                (string) ($row['label_parent'] ?? ''),
                $row['label_child'] ?? null
            );

            $indexed[$signature] = $row;
        }

        return $indexed;
    }

    private function getAllMenusIndexedByRoute(): array
    {
        $rows = $this->getAllMenuRows();
        $indexed = [];

        foreach ($rows as $row) {
            $signature = $this->buildRouteSignature(
                (string) ($row['heading_menu'] ?? ''),
                (string) ($row['link_href'] ?? '')
            );

            if (!isset($indexed[$signature])) {
                $indexed[$signature] = $row;
            }
        }

        return $indexed;
    }

    private function getRoleAccessBySignature(int $roleId): array
    {
        $rows = $this->rows(
            $this->table('menu_peran as mp')
                ->select('m.heading_menu', 'm.label_parent', 'm.label_child', 'm.link_href', 'mp.is_allowed')
                ->join('menu as m', 'm.id', '=', 'mp.menu_id')
                ->where('mp.peran_id', $roleId)
                ->orderBy('mp.id')
                ->get()
        );
        $access = [];

        foreach ($rows as $row) {
            $signature = $this->buildAccessSignature(
                (string) ($row['heading_menu'] ?? ''),
                (string) ($row['label_parent'] ?? ''),
                $row['label_child'] ?? null,
                (string) ($row['link_href'] ?? '')
            );

            $isAllowed = (int) ($row['is_allowed'] ?? 0) === 1;

            if (!isset($access[$signature])) {
                $access[$signature] = $isAllowed;
                continue;
            }

            $access[$signature] = $access[$signature] || $isAllowed;
        }

        return $access;
    }

    private function getAllMenuRows(): array
    {
        return $this->rows(
            $this->query()
                ->select('id', 'heading_menu', 'label_parent', 'label_child', 'link_href', 'icon_menu')
                ->orderBy('id')
                ->get()
        );
    }

    private function flattenBlueprint(array $masterBlueprint): array
    {
        $flatMenus = [];

        foreach ($masterBlueprint as $section) {
            $sectionHeading = (string) ($section['heading'] ?? '');

            foreach (($section['items'] ?? []) as $item) {
                $itemLabel = (string) ($item['label'] ?? '');
                $children = (array) ($item['children'] ?? []);

                if (!empty($children)) {
                    foreach ($children as $child) {
                        $grandChildren = (array) ($child['children'] ?? []);

                        if (!empty($grandChildren)) {
                            foreach ($grandChildren as $grandChild) {
                                $flatMenus[] = $this->buildLeafPayload(
                                    $sectionHeading,
                                    (string) ($child['label'] ?? ''),
                                    (string) ($grandChild['label'] ?? ''),
                                    $grandChild
                                );
                            }
                        } else {
                            $flatMenus[] = $this->buildLeafPayload(
                                $sectionHeading,
                                $itemLabel,
                                (string) ($child['label'] ?? ''),
                                $child
                            );
                        }
                    }
                } else {
                    $flatMenus[] = $this->buildLeafPayload(
                        $sectionHeading,
                        (string) ($item['label'] ?? ''),
                        null,
                        $item
                    );
                }
            }
        }

        return $flatMenus;
    }

    private function buildLeafPayload(string $heading, string $parentLabel, ?string $childLabel, array $item): array
    {
        $relativeHref = $this->normalizeHrefForStorage((string) ($item['href'] ?? 'javascript:void(0)'));
        $icon = trim((string) ($item['icon'] ?? ''));

        return [
            'key' => (string) ($item['key'] ?? ''),
            'heading_menu' => $heading,
            'label_parent' => $parentLabel,
            'label_child' => $childLabel !== null ? $childLabel : null,
            'link_href' => $relativeHref,
            'icon_menu' => $icon,
            'signature' => $this->buildSignature($heading, $parentLabel, $childLabel),
        ];
    }

    private function applyMenuRowToLeaf(array &$leafItem, array $menuRows, string $heading, string $parentLabel, ?string $childLabel): void
    {
        $signature = $this->buildSignature($heading, $parentLabel, $childLabel);
        $menuRow = $menuRows[$signature] ?? null;

        if ($menuRow === null) {
            return;
        }

        $leafItem['menu_id'] = (int) ($menuRow['id'] ?? 0);
    }

    private function buildSignature(string $heading, string $parentLabel, ?string $childLabel): string
    {
        return implode('|', [
            $this->normalizeLabel($heading),
            $this->normalizeLabel($parentLabel),
            $this->normalizeLabel((string) ($childLabel ?? '')),
        ]);
    }

    private function buildRouteSignature(string $heading, string $href): string
    {
        return implode('|', [
            $this->normalizeLabel($heading),
            strtolower(trim($this->normalizeHrefForStorage($href))),
        ]);
    }

    private function buildAccessSignature(string $heading, string $parentLabel, ?string $childLabel, string $href): string
    {
        $normalizedHref = strtolower(trim($this->normalizeHrefForStorage($href)));

        if ($normalizedHref === 'javascript:void(0)' || $normalizedHref === '') {
            return $this->buildSignature($heading, $parentLabel, $childLabel);
        }

        return $this->buildRouteSignature($heading, $href);
    }

    private function normalizeLabel(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/\s+/', ' ', $value);

        return (string) $value;
    }

    private function normalizeHrefForStorage(string $href): string
    {
        $href = trim($href);

        if ($href === '' || stripos($href, 'javascript:') === 0) {
            return 'javascript:void(0)';
        }

        $base = url('/');
        if (strpos($href, $base . '/') === 0) {
            return ltrim(substr($href, strlen($base . '/')), '/');
        }

        if (strpos($href, $base) === 0) {
            return ltrim(substr($href, strlen($base)), '/');
        }

        return ltrim($href, '/');
    }
}
