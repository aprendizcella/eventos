<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\InvoiceFactory>
 *
 * @property int $invoice_id
 * @property int $organizer_id
 * @property int $ticket_order_id
 * @property int|null $payment_id
 * @property int|null $refund_id
 * @property InvoiceType $type
 * @property int $year
 * @property int $number
 * @property string|null $invoice_number
 * @property int $amount
 * @property int|null $tax_amount
 * @property int|null $fee_amount
 * @property string $currency
 * @property InvoiceStatus $status
 * @property string|null $notes
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'invoice';

    protected $primaryKey = 'invoice_id';

    protected $fillable = [
        'organizer_id',
        'ticket_order_id',
        'payment_id',
        'refund_id',
        'type',
        'year',
        'number',
        'invoice_number',
        'amount',
        'tax_amount',
        'fee_amount',
        'currency',
        'status',
        'notes',
    ];

    #[Override]
    protected static function booted(): void
    {
        self::creating(function (Invoice $invoice): void {
            if ($invoice->invoice_number === null) {
                $invoice->invoice_number = sprintf(
                    '%s-%d-%04d',
                    $invoice->type->prefix(),
                    $invoice->year,
                    $invoice->number,
                );
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'amount', 'invoice_number'])
            ->logOnlyDirty()
            ->useLogName('invoice');
    }

    /**
     * @return BelongsTo<Organizer, $this>
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class, 'organizer_id', 'id');
    }

    /**
     * @return BelongsTo<TicketOrder, $this>
     */
    public function ticketOrder(): BelongsTo
    {
        return $this->belongsTo(TicketOrder::class, 'ticket_order_id', 'ticket_order_id');
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id', 'payment_id');
    }

    /**
     * @return BelongsTo<Refund, $this>
     */
    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class, 'refund_id', 'refund_id');
    }

    /**
     * @return HasOne<Payout, $this>
     */
    public function payout(): HasOne
    {
        return $this->hasOne(Payout::class, 'invoice_id', 'invoice_id');
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'type' => InvoiceType::class,
            'status' => InvoiceStatus::class,
            'amount' => 'integer',
            'tax_amount' => 'integer',
            'fee_amount' => 'integer',
            'year' => 'integer',
            'number' => 'integer',
        ];
    }
}
