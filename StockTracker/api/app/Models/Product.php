<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;


class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = ['sku', 'name', 'unit_cost_cents', 'min_stock'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'unit_cost_cents' => 'int',
        'min_stock' => 'int',
    ];

    /**
     * Default pagination size.
     */
    private const PER_PAGE_DEFAULT = 20;

    /**
     * Maximum pagination size.
     */
    private const PER_PAGE_MAX = 100;

    /**
     * Allowed sorting fields.
     */
    private const ALLOWED_SORTS = ['sku', 'name', 'unit_cost_cents', 'min_stock'];

    /**
     * Builds a query for searching and sorting products based on parameters.
     * @param array $params
     * @return Builder
     */
    public static function buildSearchQuery(array $params): Builder
    {
        $query = static::query();

        $search = $params['q'] ?? null;
        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $sort = $params['sort'] ?? null;
        $dir = $params['dir'] ?? null;

        if ($sort && in_array($sort, self::ALLOWED_SORTS, true)) {
            $dir = strtolower($dir) === 'desc' ? 'desc' : 'asc';
            $query->orderBy($sort, $dir);
        } else {
            $query->orderBy('id', 'asc');
        }

        return $query;
    }

    /**
     * Searches and paginates products based on parameters.
     * @param array $params
     * @return LengthAwarePaginator
     */
    public static function searchAndPaginate(array $params): LengthAwarePaginator
    {
        $query = static::buildSearchQuery($params);

        $perPage = isset($params['per_page']) ? (int) $params['per_page'] : self::PER_PAGE_DEFAULT;
        if ($perPage <= 0 || $perPage > self::PER_PAGE_MAX) {
            $perPage = self::PER_PAGE_DEFAULT;
        }

        return $query->paginate($perPage);
    }
}
