<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\VenueFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

/**
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\VenueFactory>
 */
final class Venue extends Model
{
    /** @use HasFactory<VenueFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'venue';

    protected $primaryKey = 'venue_id';

    protected $fillable = [
        'organizer_id',
        'name',
        'address',
        'city',
        'capacity',
        'description',
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
     * @return BelongsTo<Organizer, $this>
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'venue_id', 'venue_id');
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

    #[Override]
    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
        ];
    }
}
