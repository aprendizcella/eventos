<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WaitlistStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $waitlist_entry_id
 * @property int $event_id
 * @property int $product_price_id
 * @property string $email
 * @property string|null $first_name
 * @property string|null $last_name
 * @property WaitlistStatus $status
 * @property \Carbon\Carbon|null $notified_at
 * @property \Carbon\Carbon|null $expires_at
 * @property string|null $token
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read Event $event
 * @property-read ProductPrice $productPrice
 *
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\WaitlistEntryFactory>
 */
final class WaitlistEntry extends Model
{
    /** @use HasFactory<\Database\Factories\WaitlistEntryFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'waitlist_entry';

    protected $primaryKey = 'waitlist_entry_id';

    protected $fillable = [
        'event_id',
        'product_price_id',
        'email',
        'first_name',
        'last_name',
        'status',
        'notified_at',
        'expires_at',
        'token',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'email', 'expires_at', 'token'])
            ->logOnlyDirty()
            ->useLogName('waitlist_entry');
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id', 'event_id');
    }

    /**
     * @return BelongsTo<ProductPrice, $this>
     */
    public function productPrice(): BelongsTo
    {
        return $this->belongsTo(ProductPrice::class, 'product_price_id', 'product_price_id');
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'status' => WaitlistStatus::class,
            'notified_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
