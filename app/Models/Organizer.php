<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\OrganizerFactory>
 */
class Organizer extends Model
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\OrganizerFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

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
