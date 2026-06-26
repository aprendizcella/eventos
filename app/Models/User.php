<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Request;
use Laravel\Sanctum\HasApiTokens;
use Override;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

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
            ->useLogName('user');
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
        ];
    }

    public function organizers(): BelongsToMany
    {
        return $this->belongsToMany(Organizer::class, 'organizer_user')
            ->withPivot('role_id')
            ->withTimestamps();
    }

    public function currentOrganizer(): ?Organizer
    {
        /** @var Request $request */
        $request = app('request');

        // Try request attribute first
        $organizer = $request->attributes->get('current_organizer');
        if ($organizer instanceof Organizer) {
            return $organizer;
        }

        // Try session (if available)
        if ($request->hasSession()) {
            $organizerId = $request->session()->get('current_organizer_id');
            if ($organizerId) {
                return $this->organizers()->find($organizerId);
            }
        }

        return null;
    }
}

