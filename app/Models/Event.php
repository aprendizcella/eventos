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
use Laravel\Scout\Searchable;
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
 * @property string|null $previous_status
 * @property CarbonInterface|null $suspended_at
 */
final class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory, LogsActivity, Searchable, SoftDeletes;

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
        'settings',
    ];

    #[Override]
    protected static function booted(): void
    {
        self::saved(function () {
            \Illuminate\Support\Facades\Cache::tags(['catalog'])->flush();
        });

        self::deleted(function () {
            \Illuminate\Support\Facades\Cache::tags(['catalog'])->flush();
        });
    }

    /**
     * Handle removal from search index on soft-delete.
     * Scout's `soft_delete` config is disabled, so we must explicitly
     * unsearchable when an event is soft-deleted (not force-deleted,
     * because Scout handles force-deletion automatically).
     */
    #[Override]
    protected static function boot(): void
    {
        parent::boot();

        self::deleted(function (self $event): void {
            if (!$event->isForceDeleting()) {
                $event->unsearchable();
            }
        });
    }

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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<NotificationTemplate, $this>
     */
    public function notificationTemplates(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(NotificationTemplate::class, 'event_id', 'event_id');
    }

    /**
     * Get the indexable data array for Scout.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'organizer_id' => $this->organizer_id,
            'category_id' => $this->category_id,
            'venue_city' => $this->venue?->city,
            'starts_at' => $this->starts_at?->timestamp,
            'starts_at_date' => $this->starts_at?->toDateString(),
        ];
    }

    /**
     * Determine if the model should be searchable.
     *
     * Excludes soft-deleted, non-published, and non-public events.
     * Removal from the search index is handled explicitly on soft-delete
     * via the `deleted` model event (see ::boot), since Scout's
     * `soft_delete` config is set to `false`.
     */
    public function shouldBeSearchable(): bool
    {
        return !$this->trashed()
            && $this->status === EventStatus::Published
            && $this->visibility === EventVisibility::Public;
    }

    /**
     * Get the canonical public URL for this event.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute<string, never>
     */
    protected function canonicalUrl(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(get: fn () => route('public.events.detail', $this->slug));
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

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function public(Builder $query): Builder
    {
        return $query->where('visibility', EventVisibility::Public->value);
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
            'settings' => 'array',
            'suspended_at' => 'datetime',
        ];
    }
}
