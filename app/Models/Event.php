<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use Carbon\CarbonInterface;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\EventFactory>
 *
 * @property CarbonInterface|null $starts_at
 * @property CarbonInterface|null $ends_at
 * @property EventStatus $status
 * @property EventVisibility $visibility
 * @property array<mixed>|null $custom_questions
 */
final class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'event';

    protected $primaryKey = 'event_id';

    protected $fillable = [
        'organizer_id',
        'category_id',
        'venue_id',
        'title',
        'slug',
        'description',
        'starts_at',
        'ends_at',
        'status',
        'visibility',
        'custom_questions',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'status', 'visibility'])
            ->logOnlyDirty()
            ->useLogName('event');
    }

    /**
     * @return BelongsTo<Organizer, $this>
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    /**
     * @return BelongsTo<Venue, $this>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class, 'venue_id', 'venue_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Product, $this>
     */
    public function products(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Product::class, 'event_id', 'event_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<NotificationLog, $this>
     */
    public function notificationLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(NotificationLog::class, 'event_id', 'event_id');
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function forOrganizer(Builder $query, int $organizerId): Builder
    {
        return $query->where('organizer_id', $organizerId);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function published(Builder $query): Builder
    {
        return $query->where('status', EventStatus::Published->value);
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => EventStatus::class,
            'visibility' => EventVisibility::class,
            'custom_questions' => 'array',
        ];
    }
}
