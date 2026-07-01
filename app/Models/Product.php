<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PricingMode;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\ProductVisibility;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\ProductFactory>
 *
 * @property int $product_id
 * @property int $event_id
 * @property int $organizer_id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property ProductType $type
 * @property PricingMode $pricing_mode
 * @property ProductStatus $status
 * @property ProductVisibility $visibility
 * @property string|null $password
 * @property int $min_qty
 * @property int $max_qty
 * @property int $sort_order
 * @property \Carbon\Carbon|null $deleted_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
final class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'product';

    protected $primaryKey = 'product_id';

    protected $fillable = [
        'event_id',
        'organizer_id',
        'title',
        'slug',
        'description',
        'type',
        'pricing_mode',
        'status',
        'visibility',
        'password',
        'min_qty',
        'max_qty',
        'sort_order',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'status', 'visibility', 'pricing_mode'])
            ->logOnlyDirty()
            ->useLogName('product');
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id', 'event_id');
    }

    /**
     * @return BelongsTo<Organizer, $this>
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class, 'organizer_id', 'id');
    }

    /**
     * @return HasMany<ProductPrice, $this>
     */
    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class, 'product_id', 'product_id');
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
            'type' => ProductType::class,
            'pricing_mode' => PricingMode::class,
            'status' => ProductStatus::class,
            'visibility' => ProductVisibility::class,
        ];
    }
}
