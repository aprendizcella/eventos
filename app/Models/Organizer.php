<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Multitenancy\Concerns\UsesMultitenancyConfig;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Models\Concerns\ImplementsTenant;

/**
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\OrganizerFactory>
 *
 * @property array<string, mixed>|null $settings
 */
class Organizer extends Model implements IsTenant
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\OrganizerFactory> */
    use HasFactory, ImplementsTenant, LogsActivity, SoftDeletes, UsesMultitenancyConfig;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'settings',
        'status',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'slug', 'status'])
            ->logOnlyDirty()
            ->useLogName('organizer');
    }

    /**
     * @return BelongsToMany<User, $this, \Illuminate\Database\Eloquent\Relations\Pivot>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organizer_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * @return HasMany<Venue, $this>
     */
    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class);
    }

    /**
     * Single-database mode: return the default connection database name.
     * Overrides `ImplementsTenant::getDatabaseName()` which expects a `database` column.
     */
    #[Override]
    public function getDatabaseName(): string
    {
        /** @var non-empty-string $connectionName */
        $connectionName = config('database.default');

        return config("database.connections.{$connectionName}.database", $connectionName);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function withDomain(Builder $query): Builder
    {
        return $query->whereNotNull('domain');
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }
}
