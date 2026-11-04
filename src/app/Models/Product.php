<?php

namespace App\Models;

use App\Enums\ProductCategory;
use App\Enums\ProductStatus;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $url
 * @property string $normalized_url
 * @property string $domain
 * @property string|null $title
 * @property string|null $nickname
 * @property ProductCategory $category
 * @property string|null $image_url
 * @property string $target_price
 * @property string|null $current_price
 * @property string|null $installment_price
 * @property bool $in_stock
 * @property bool $notify_on_stock
 * @property ProductStatus $status
 * @property Carbon|null $last_checked_at
 * @property Carbon|null $last_manual_check_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
#[Fillable([
    'user_id',
    'url',
    'normalized_url',
    'domain',
    'title',
    'nickname',
    'category',
    'image_url',
    'target_price',
    'current_price',
    'installment_price',
    'in_stock',
    'notify_on_stock',
    'status',
    'last_checked_at',
    'last_manual_check_at',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => ProductCategory::class,
            'target_price' => 'decimal:2',
            'current_price' => 'decimal:2',
            'installment_price' => 'decimal:2',
            'in_stock' => 'boolean',
            'notify_on_stock' => 'boolean',
            'status' => ProductStatus::class,
            'last_checked_at' => 'datetime',
            'last_manual_check_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
