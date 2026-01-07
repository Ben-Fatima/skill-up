<?php

namespace App\Models;

use App\Models\Traits\HasSearchAndPagination;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Location extends Model
{
    use HasFactory;
    use HasSearchAndPagination;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'code', 'address',
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
    private const ALLOWED_SORTS = ['code', 'address'];

    private const SEARCHABLE = ['code','address'];
}
