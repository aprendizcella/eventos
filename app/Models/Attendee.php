<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AttendeeStatus;
use Database\Factories\AttendeeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
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
 * @property-read bool|null $is_checked_in
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
        'custom_answers',
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
     * Scope para segmentar asistentes por filtros de evento.
     *
     * @param  Builder<Attendee>  $query
     * @param  array{product_price_id?: ?int, attendee_status?: ?string, check_in_status?: ?string}  $filters
     * @return Builder<Attendee>
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function forEventSegment(
        Builder $query,
        int $eventId,
        array $filters = [],
    ): Builder {
        $query->join('ticket_order', 'attendee.ticket_order_id', '=', 'ticket_order.ticket_order_id')
            ->where('ticket_order.event_id', $eventId);

        if (!empty($filters['product_price_id'])) {
            $query->join('ticket_order_item', 'attendee.ticket_order_item_id', '=', 'ticket_order_item.ticket_order_item_id')
                ->where('ticket_order_item.product_price_id', $filters['product_price_id']);
        }

        if (!empty($filters['attendee_status'])) {
            $query->where('attendee.status', $filters['attendee_status']);
        }

        if (!empty($filters['check_in_status'])) {
            if ($filters['check_in_status'] === 'checked_in') {
                $query->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('active_check_in')
                        ->whereColumn('active_check_in.attendee_id', 'attendee.attendee_id');
                });
            } elseif ($filters['check_in_status'] === 'not_checked_in') {
                $query->whereNotExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('active_check_in')
                        ->whereColumn('active_check_in.attendee_id', 'attendee.attendee_id');
                });
            }
        }

        return $query;
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
            'custom_answers' => 'array',
        ];
    }
}
