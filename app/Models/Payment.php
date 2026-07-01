<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\PaymentFactory>
 *
 * @property int $payment_id
 * @property int $ticket_order_id
 * @property string|null $provider_id
 * @property PaymentMethod $payment_method
 * @property PaymentStatus $status
 * @property float $amount
 * @property string $currency
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
final class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'payment';

    protected $primaryKey = 'payment_id';

    protected $fillable = [
        'ticket_order_id',
        'provider_id',
        'payment_method',
        'status',
        'amount',
        'currency',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'amount', 'provider_id'])
            ->logOnlyDirty()
            ->useLogName('payment');
    }

    /**
     * @return BelongsTo<TicketOrder, $this>
     */
    public function ticketOrder(): BelongsTo
    {
        return $this->belongsTo(TicketOrder::class, 'ticket_order_id', 'ticket_order_id');
    }

    /**
     * @return HasMany<Refund, $this>
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class, 'payment_id', 'payment_id');
    }

    public function getTotalRefundedAmount(): float
    {
        return (float) $this->refunds()
            ->where('status', 'completed')
            ->sum('amount');
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'payment_method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount' => 'float',
        ];
    }
}
