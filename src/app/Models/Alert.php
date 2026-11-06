<?php

namespace App\Models;

use App\Enums\AlertType;
use Database\Factories\AlertFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $product_id
 * @property AlertType $type
 * @property string $triggered_price
 * @property string|null $installment_price
 * @property string|null $previous_price
 * @property string $target_price
 * @property Carbon|null $read_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Product $product
 */
#[Fillable([
    'user_id',
    'product_id',
    'type',
    'triggered_price',
    'installment_price',
    'previous_price',
    'target_price',
    'read_at',
])]
class Alert extends Model
{
    /** @use HasFactory<AlertFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AlertType::class,
            'triggered_price' => 'decimal:2',
            'installment_price' => 'decimal:2',
            'previous_price' => 'decimal:2',
            'target_price' => 'decimal:2',
            'read_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope a query to only include unread alerts.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope a query to only include read alerts.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Mark the alert as read.
     */
    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->update(['read_at' => now()]);
        }
    }
}
