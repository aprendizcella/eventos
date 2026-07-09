<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PayoutStatus;
use Database\Factories\PayoutFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\PayoutFactory>
 *
 * @property int $payout_id
 * @property int $organizer_id
 * @property int $invoice_id
 * @property int|null $refund_id
 * @property int $gross_amount
 * @property int $commission_amount
 * @property int $net_amount
 * @property string $currency
 * @property PayoutStatus $status
 * @property \Carbon\Carbon|null $processed_at
 * @property \Carbon\Carbon|null $reversed_at
 * @property string|null $notes
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class Payout extends Model
{
    /** @use HasFactory<PayoutFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'payout';

    protected $primaryKey = 'payout_id';

    protected $fillable = [
        'organizer_id',
        'invoice_id',
        'refund_id',
        'gross_amount',
        'commission_amount',
        'net_amount',
        'currency',
        'status',
        'processed_at',
        'reversed_at',
        'notes',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'status',
                'gross_amount',
                'commission_amount',
                'net_amount',
                'processed_at',
                'reversed_at',
            ])
            ->logOnlyDirty()
            ->useLogName('payout');
    }

    /**
     * @return BelongsTo<Organizer, $this>
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class, 'organizer_id', 'id');
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'invoice_id');
    }

    /**
     * @return BelongsTo<Refund, $this>
     */
    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class, 'refund_id', 'refund_id');
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'status' => PayoutStatus::class,
            'gross_amount' => 'integer',
            'commission_amount' => 'integer',
            'net_amount' => 'integer',
            'processed_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }
}
