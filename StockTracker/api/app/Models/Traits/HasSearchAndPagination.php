<?php

namespace App\Models\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

trait HasSearchAndPagination
{
    public static function buildSearchQuery(array $params): Builder
    {
        $query  = static::query();
        $search = static::normalizeString($params['q'] ?? null);
        $sort   = static::normalizeString($params['sort'] ?? null);
        $dir    = static::normalizeDir($params['dir'] ?? null);

        static::applySearch($query, $search);
        static::applySort($query, $sort, $dir);

        return $query;
    }

    public static function searchAndPaginate(array $params): LengthAwarePaginator
    {
        $query   = static::buildSearchQuery($params);
        $perPage = static::parsePerPage($params['per_page'] ?? null);

        return $query->paginate($perPage);
    }

    protected static function normalizeString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    protected static function normalizeDir(mixed $dir): string
    {
        $dir = static::normalizeString($dir);

        return ($dir !== null && strtolower($dir) === 'desc') ? 'desc' : 'asc';
    }

    protected static function parsePerPage(mixed $value): int
    {
        $perPage = static::PER_PAGE_DEFAULT;

        if (is_string($value)) {
            $value = trim($value);
            if (ctype_digit($value)) {
                $perPage = (int) $value;
            }
        } elseif (is_int($value)) {
            $perPage = $value;
        }

        if ($perPage <= 0) {
            return static::PER_PAGE_DEFAULT;
        } elseif ($perPage > static::PER_PAGE_MAX) {
            return static::PER_PAGE_MAX;
        }

        return $perPage;
    }

    protected static function applySearch(Builder $query, ?string $search): void
    {
        if ($search === null) {
            return;
        }

        $fields = static::SEARCHABLE ?? [];
        if (empty($fields)) {
            return;
        }

        $query->where(function ($q) use ($fields, $search) {
            $first = array_shift($fields);
            $q->where($first, 'like', "%{$search}%");

            foreach ($fields as $field) {
                $q->orWhere($field, 'like', "%{$search}%");
            }
        });
    }

    protected static function applySort(Builder $query, ?string $sort, string $dir): void
    {
        if ($sort === null) {
            $query->orderBy('id', 'asc');
            return;
        }

        $allowed = static::ALLOWED_SORTS ?? [];
        if (!in_array($sort, $allowed, true)) {
            $query->orderBy('id', 'asc');
            return;
        }

        $query->orderBy($sort, $dir);
    }
}
