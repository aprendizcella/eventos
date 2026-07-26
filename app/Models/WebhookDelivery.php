<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WebhookDeliveryStatus;
use Database\Factories\WebhookDeliveryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

/**
 * @property \Carbon\Carbon $occurred_at
 * @property Payment|null $payment
 */
final class WebhookDelivery extends Model
{
    /** @use HasFactory<WebhookDeliveryFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'webhook_delivery';

    protected $primaryKey = 'webhook_delivery_id';

    protected $fillable = ['delivery_id', 'organizer_id', 'webhook_id', 'payment_id', 'event', 'version', 'envelope', 'status', 'attempts', 'occurred_at', 'delivered_at', 'failed_at', 'response_status', 'failure_reason', 'expires_at'];

    /** @return BelongsTo<Organizer, $this> */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    /** @return BelongsTo<Webhook, $this> */
    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class, 'webhook_id', 'webhook_id');
    }

    /** @return BelongsTo<Payment, $this> */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id', 'payment_id');
    }

    /** @return array<string, string> */
    #[Override]
    protected function casts(): array
    {
        return [
            'status' => WebhookDeliveryStatus::class,
            'envelope' => 'encrypted',
            'occurred_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
            'expires_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }
}
