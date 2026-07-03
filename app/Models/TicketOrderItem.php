<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TicketOrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\TicketOrderItemFactory>
 *
 * @property int $ticket_order_item_id
 * @property int $ticket_order_id
 * @property int $product_id
 * @property int|null $product_price_id
 * @property int $quantity
 * @property float $price
 * @property float $subtotal
 * @property float $discount
 * @property float $total
 * @property array<mixed>|null $custom_answers_staging
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
final class TicketOrderItem extends Model
{
    /** @use HasFactory<TicketOrderItemFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'ticket_order_item';

    protected $primaryKey = 'ticket_order_item_id';

    protected $fillable = [
        'ticket_order_id',
        'product_id',
        'product_price_id',
        'quantity',
        'price',
        'subtotal',
        'discount',
        'total',
        'custom_answers_staging',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['quantity', 'price', 'total'])
            ->logOnlyDirty()
            ->useLogName('ticket_order_item');
    }

    /**
     * @return BelongsTo<TicketOrder, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(TicketOrder::class, 'ticket_order_id', 'ticket_order_id');
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    /**
     * @return BelongsTo<ProductPrice, $this>
     */
    public function productPrice(): BelongsTo
    {
        return $this->belongsTo(ProductPrice::class, 'product_price_id', 'product_price_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'price' => 'float',
            'subtotal' => 'float',
            'discount' => 'float',
            'total' => 'float',
            'custom_answers_staging' => 'array',
        ];
    }
}
