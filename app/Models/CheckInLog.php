<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $check_in_log_id
 * @property int $check_in_list_id
 * @property int $attendee_id
 * @property string $action
 * @property int|null $user_id
 * @property \Carbon\Carbon|null $created_at
 */
final class CheckInLog extends Model
{
    // Esta tabla es inmutable y no tiene updated_at
    public const UPDATED_AT = null;

    protected $table = 'check_in_log';

    protected $primaryKey = 'check_in_log_id';

    protected $fillable = [
        'check_in_list_id',
        'attendee_id',
        'action',
        'user_id',
    ];

    /**
     * @return BelongsTo<CheckInList, $this>
     */
    public function checkInList(): BelongsTo
    {
        return $this->belongsTo(CheckInList::class, 'check_in_list_id', 'check_in_list_id');
    }

    /**
     * @return BelongsTo<Attendee, $this>
     */
    public function attendee(): BelongsTo
    {
        return $this->belongsTo(Attendee::class, 'attendee_id', 'attendee_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
