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

class Organizer extends Model
{
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

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organizer_user')
            ->withPivot('role_id')
            ->withTimestamps();
    }

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

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
