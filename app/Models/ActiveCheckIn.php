<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property int $active_check_in_id
 * @property int $check_in_list_id
 * @property int $attendee_id
 * @property \Carbon\Carbon $checked_in_at
 * @property int|null $checked_in_by_user_id
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 *
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\ActiveCheckInFactory>
 */
final class ActiveCheckIn extends Model
{
    /** @use HasFactory<\Database\Factories\ActiveCheckInFactory> */
    use HasFactory;

    protected $table = 'active_check_in';

    protected $primaryKey = 'active_check_in_id';

    protected $fillable = [
        'check_in_list_id',
        'attendee_id',
        'checked_in_at',
        'checked_in_by_user_id',
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
        return $this->belongsTo(User::class, 'checked_in_by_user_id', 'id');
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
        ];
    }
}
