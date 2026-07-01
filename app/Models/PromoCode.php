<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PromoCodeType;
use Database\Factories\PromoCodeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\PromoCodeFactory>
 *
 * @property int $promo_code_id
 * @property int $event_id
 * @property string $code
 * @property PromoCodeType $type
 * @property float $value
 * @property int|null $max_uses
 * @property int $uses_count
 * @property \Carbon\Carbon|null $start_at
 * @property \Carbon\Carbon|null $end_at
 * @property string $status
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
final class PromoCode extends Model
{
    /** @use HasFactory<PromoCodeFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'promo_code';

    protected $primaryKey = 'promo_code_id';

    protected $fillable = [
        'event_id',
        'code',
        'type',
        'value',
        'max_uses',
        'uses_count',
        'start_at',
        'end_at',
        'status',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'type', 'value', 'status'])
            ->logOnlyDirty()
            ->useLogName('promo_code');
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id', 'event_id');
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
            'type' => PromoCodeType::class,
            'value' => 'float',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
        ];
    }
}
