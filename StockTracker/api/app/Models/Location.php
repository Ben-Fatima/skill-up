<?php

namespace App\Models;

use App\Models\Traits\HasSearchAndPagination;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Location model.
 *
 * @property int $id
 * @property string $code
 * @property string $address
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Location extends Model
{
    use HasFactory;
    use HasSearchAndPagination;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code', 'address',
    ];

    /**
     * Default pagination size.
     *
     * @var int
     */
    private const PER_PAGE_DEFAULT = 50;

    /**
     * Maximum pagination size.
     *
     * @var int
     */
    private const PER_PAGE_MAX = 100;

    /**
     * Allowed sorting fields.
     *
     * @var array<int, string>
     */
    private const ALLOWED_SORTS = ['code', 'address'];

    /**
     * Allowed search fields.
     *
     * @var array<int, string>
     */
    private const SEARCHABLE = ['code','address'];
}
