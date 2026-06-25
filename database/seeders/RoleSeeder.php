<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Idempotently seeds the six initial Sprint 1.1 roles under the web guard.
 *
 * Uses `firstOrCreate` so repeated seeding never duplicates roles and never
 * mutulates existing role assignments or permissions.
 */
final class RoleSeeder extends Seeder
{
    /**
     * Initial roles in canonical declaration order.
     *
     * @var list<string>
     */
    public const array ROLES = [
        'super_admin',
        'platform_admin',
        'organizer_admin',
        'organizer_editor',
        'organizer_viewer',
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
