<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Traits\HasSearchAndPagination;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $location_id
 * @property int $product_id
 * @property string|null $note
 * @property string $type
 * @property int $qty
 */
class StockMovement extends Model
{
    use HasFactory;
    use HasSearchAndPagination;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'location_id',
        'product_id',
        'note',
        'type',
        'qty',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'qty' => 'integer',
    ];

    /**
     * @var string[]
     */
    public const TYPES = [
        'IN',
        'OUT',
        'ADJUST'
    ];

    /**
     * @var int
     */
    public const PER_PAGE_DEFAULT = 20;

    /**
     * Maximum pagination size.
     *
     * @var int
     */
    public const PER_PAGE_MAX = 100;

    /**
     * @var int
     */
    public const MIN_ALLOWED_QTY = 100;

    /**
     * Get the product in the movement.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the location where the movement happened.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the current stock on hand for a product at a location.
     */
    public static function stockOnHand(int $productId, int $locationId): int
    {
        return (int) static::where('product_id', $productId)
            ->where('location_id', $locationId)
            ->sum('qty');
    }
}
