<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $check_in_list_id
 * @property int $event_id
 * @property string $name
 * @property string|null $description
 * @property bool $is_active
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\CheckInListFactory>
 */
final class CheckInList extends Model
{
    /** @use HasFactory<\Database\Factories\CheckInListFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'check_in_list';

    protected $primaryKey = 'check_in_list_id';

    protected $fillable = [
        'event_id',
        'name',
        'description',
        'is_active',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('check_in_list');
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id', 'event_id');
    }

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function eligibleProducts(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'check_in_list_product',
            'check_in_list_id',
            'product_id',
        );
    }

    /**
     * @return HasMany<ActiveCheckIn, $this>
     */
    public function activeCheckIns(): HasMany
    {
        return $this->hasMany(ActiveCheckIn::class, 'check_in_list_id', 'check_in_list_id');
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
