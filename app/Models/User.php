<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Override;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property CarbonInterface|null $suspended_at
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, LogsActivity, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Privacy-safe activity log options: only non-sensitive profile attributes
     * are recorded. Credentials, tokens, and remember tokens are never logged.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email'])
            ->logOnlyDirty()
            ->useLogName('user')
            ->dontLogEmptyChanges();
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function isGlobalSuperAdmin(): bool
    {
        return $this->hasGlobalRole('super_admin');
    }

    /**
     * @param  array<string>|string  $roles
     */
    public function hasGlobalRole(array|string $roles): bool
    {
        $roles = (array) $roles;

        return \Illuminate\Support\Facades\DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_id', $this->id)
            ->where('model_has_roles.model_type', $this->getMorphClass())
            ->whereIn('roles.name', $roles)
            ->where('model_has_roles.organizer_id', 0)
            ->exists();
    }

    /**
     * @return BelongsToMany<Organizer, $this, \Illuminate\Database\Eloquent\Relations\Pivot>
     */
    public function organizers(): BelongsToMany
    {
        return $this->belongsToMany(Organizer::class, 'organizer_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function currentOrganizer(): ?Organizer
    {
        /** @var Request $request */
        $request = resolve('request');

        // Try request attribute first
        $organizer = $request->attributes->get('current_organizer');

        if ($organizer instanceof Organizer) {
            return $organizer;
        }

        // Try session (if available)
        if ($request->hasSession()) {
            $organizerId = $request->session()->get('current_organizer_id');

            if ($organizerId) {
                /** @var Organizer|null */
                return $this->organizers()->where('organizers.id', $organizerId)->first();
            }
        }

        return null;
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function active(Builder $query): Builder
    {
        return $query->whereNull('suspended_at');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'suspended_at' => 'datetime',
        ];
    }
}
