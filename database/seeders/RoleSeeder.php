<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Idempotently seeds the three global Sprint 1.2 roles under the web guard.
 *
 * Organizer-scoped roles (admin, editor, viewer) are managed via the
 * organizer_user pivot table, not as global Spatie roles.
 *
 * Uses `firstOrCreate` so repeated seeding never duplicates roles and never
 * mutates existing role assignments or permissions.
 */
final class RoleSeeder extends Seeder
{
    /**
     * Global roles in canonical declaration order.
     *
     * @var list<string>
     */
    public const array ROLES = [
        'super_admin',
        'platform_admin',
        'attendee',
    ];

    public const string GUARD = 'web';

    public function run(): void
    {
        foreach (self::ROLES as $name) {
            Role::query()->firstOrCreate([
                'name' => $name,
                'guard_name' => self::GUARD,
            ]);
        }
    }
}
