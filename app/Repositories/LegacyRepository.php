<?php

namespace App\Repositories;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use PDO;

abstract class LegacyRepository
{
    protected PDO $db;

    protected $table = '';

    public function __construct(array $attributes = [])
    {
        $this->db = DB::connection()->getPdo();
    }

    public function getTable(): string
    {
        return $this->table;
    }

    protected function query(): Builder
    {
        return DB::table($this->table);
    }

    protected function table(string $table): Builder
    {
        return DB::table($table);
    }

    protected function rows(iterable $rows): array
    {
        $result = [];

        foreach ($rows as $row) {
            $result[] = (array) $row;
        }

        return $result;
    }

    protected function row(mixed $row): ?array
    {
        return $row !== null ? (array) $row : null;
    }
}
