<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\RefundFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\RefundFactory>
 *
 * @property int $refund_id
 * @property int $payment_id
 * @property string|null $provider_id
 * @property string $idempotency_key
 * @property string $status
 * @property float $amount
 * @property string|null $reason
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
final class Refund extends Model
{
    /** @use HasFactory<RefundFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'refund';

    protected $primaryKey = 'refund_id';

    protected $fillable = [
        'payment_id',
        'provider_id',
        'idempotency_key',
        'status',
        'amount',
        'reason',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'amount', 'provider_id'])
            ->logOnlyDirty()
            ->useLogName('refund');
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id', 'payment_id');
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'amount' => 'float',
        ];
    }
}
