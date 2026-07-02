<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AttendeeStatus;
use Database\Factories\AttendeeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\AttendeeFactory>
 *
 * @property int $attendee_id
 * @property int $ticket_order_id
 * @property int $ticket_order_item_id
 * @property int $sequence
 * @property string $unique_code
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property AttendeeStatus $status
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class Attendee extends Model
{
    /** @use HasFactory<AttendeeFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'attendee';

    protected $primaryKey = 'attendee_id';

    protected $fillable = [
        'ticket_order_id',
        'ticket_order_item_id',
        'sequence',
        'unique_code',
        'first_name',
        'last_name',
        'email',
        'status',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'email', 'unique_code'])
            ->logOnlyDirty()
            ->useLogName('attendee');
    }

    /**
     * @return BelongsTo<TicketOrder, $this>
     */
    public function ticketOrder(): BelongsTo
    {
        return $this->belongsTo(TicketOrder::class, 'ticket_order_id', 'ticket_order_id');
    }

    /**
     * @return BelongsTo<TicketOrderItem, $this>
     */
    public function ticketOrderItem(): BelongsTo
    {
        return $this->belongsTo(TicketOrderItem::class, 'ticket_order_item_id', 'ticket_order_item_id');
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'status' => AttendeeStatus::class,
            'sequence' => 'integer',
        ];
    }
}
