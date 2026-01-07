<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasSearchAndPagination;


class Product extends Model
{
    use HasFactory;
    use HasSearchAndPagination;

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
    private const PER_PAGE_DEFAULT = 50;

    /**
     * Maximum pagination size.
     */
    private const PER_PAGE_MAX = 100;

    /**
     * Allowed sorting fields.
     */
    private const ALLOWED_SORTS = ['sku', 'name', 'unit_cost_cents', 'min_stock'];

    private const SEARCHABLE = ['sku','name'];
}
