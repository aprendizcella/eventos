<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductPriceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\ProductPriceFactory>
 *
 * @property int $product_price_id
 * @property int $product_id
 * @property string $name
 * @property float $price
 * @property int|null $capacity
 * @property int $quantity_sold
 * @property \Carbon\Carbon|null $sales_start_at
 * @property \Carbon\Carbon|null $sales_end_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
final class ProductPrice extends Model
{
    /** @use HasFactory<ProductPriceFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'product_price';

    protected $primaryKey = 'product_price_id';

    protected $fillable = [
        'product_id',
        'name',
        'price',
        'capacity',
        'quantity_sold',
        'sales_start_at',
        'sales_end_at',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'price', 'capacity'])
            ->logOnlyDirty()
            ->useLogName('product_price');
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
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
            'sales_start_at' => 'datetime',
            'sales_end_at' => 'datetime',
        ];
    }
}
