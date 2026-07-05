<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationLogStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $notification_log_id
 * @property int $event_id
 * @property int|null $sent_by_user_id
 * @property string $subject
 * @property string $body
 * @property int $recipient_count
 * @property array<string, mixed>|null $filter_criteria
 * @property NotificationLogStatus $status
 * @property \Carbon\Carbon|null $completed_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class NotificationLog extends Model
{
    use LogsActivity, SoftDeletes;

    protected $table = 'notification_log';

    protected $primaryKey = 'notification_log_id';

    protected $fillable = [
        'event_id',
        'sent_by_user_id',
        'subject',
        'body',
        'recipient_count',
        'filter_criteria',
        'status',
        'completed_at',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'recipient_count'])
            ->logOnlyDirty()
            ->useLogName('notification_log');
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id', 'event_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id', 'id');
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'status' => NotificationLogStatus::class,
            'filter_criteria' => 'array',
            'completed_at' => 'datetime',
            'recipient_count' => 'integer',
        ];
    }
}
