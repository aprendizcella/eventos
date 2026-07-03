<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TicketOrderStatus;
use Database\Factories\TicketOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\TicketOrderFactory>
 *
 * @property int $ticket_order_id
 * @property int $event_id
 * @property int|null $promo_code_id
 * @property string $order_reference
 * @property TicketOrderStatus $status
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property float $subtotal
 * @property float $discount
 * @property float $total
 * @property \Carbon\Carbon|null $reserved_until
 * @property \Carbon\Carbon|null $tickets_sent_at
 * @property \Carbon\Carbon|null $tickets_processing_at
 * @property int|null $waitlist_entry_id
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
final class TicketOrder extends Model
{
    /** @use HasFactory<TicketOrderFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'ticket_order';

    protected $primaryKey = 'ticket_order_id';

    protected $fillable = [
        'event_id',
        'promo_code_id',
        'order_reference',
        'status',
        'first_name',
        'last_name',
        'email',
        'subtotal',
        'discount',
        'total',
        'reserved_until',
        'waitlist_entry_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'total', 'order_reference'])
            ->logOnlyDirty()
            ->useLogName('ticket_order');
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id', 'event_id');
    }

    /**
     * @return BelongsTo<PromoCode, $this>
     */
    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class, 'promo_code_id', 'promo_code_id');
    }

    /**
     * @return BelongsTo<WaitlistEntry, $this>
     */
    public function waitlistEntry(): BelongsTo
    {
        return $this->belongsTo(WaitlistEntry::class, 'waitlist_entry_id', 'waitlist_entry_id');
    }

    /**
     * @return HasMany<TicketOrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(TicketOrderItem::class, 'ticket_order_id', 'ticket_order_id');
    }

    /**
     * @return HasMany<Attendee, $this>
     */
    public function attendees(): HasMany
    {
        return $this->hasMany(Attendee::class, 'ticket_order_id', 'ticket_order_id');
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
            'status' => TicketOrderStatus::class,
            'subtotal' => 'float',
            'discount' => 'float',
            'total' => 'float',
            'reserved_until' => 'datetime',
            'tickets_sent_at' => 'datetime',
            'tickets_processing_at' => 'datetime',
            'waitlist_entry_id' => 'integer',
        ];
    }
}
