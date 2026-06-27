<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Organizer;
use App\Models\User;
use App\Support\Organizers\OrganizerRoles;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;

class MigrateLegacyOrganizerRoles extends Command
{
    protected $signature = 'organizers:migrate-legacy-roles
                            {--dry-run : Show what would be done without making changes}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Migrate legacy global organizer_* roles to organizer-scoped pivot roles';

    public function handle(): int
    {
        $this->info('Legacy Organizer Roles Migration');
        $this->info('================================');
        $this->newLine();

        // Check for legacy roles
        $legacyRoles = Role::query()
            ->whereIn('name', ['organizer_admin', 'organizer_editor', 'organizer_viewer'])
            ->get();

        if ($legacyRoles->isEmpty()) {
            $this->info('✓ No legacy organizer_* roles found. Nothing to migrate.');
        } else {
            $this->processLegacyRoles($legacyRoles);
        }

        return self::SUCCESS;
    }

    /**
     * @param  EloquentCollection<int, Role>  $legacyRoles
     */
    private function processLegacyRoles(EloquentCollection $legacyRoles): void
    {
        $this->warn("Found {$legacyRoles->count()} legacy role(s):");

        foreach ($legacyRoles as $role) {
            $userCount = DB::table('model_has_roles')
                ->where('role_id', $role->id)
                ->count();
            $this->line("  - {$role->name} ({$userCount} user(s))");
        }
        $this->newLine();

        $usersWithLegacyRoles = User::query()
            ->whereHas('roles', function ($query): void {
                $query->whereIn('name', ['organizer_admin', 'organizer_editor', 'organizer_viewer']);
            })
            ->with('roles')
            ->get();

        if ($usersWithLegacyRoles->isEmpty()) {
            $this->info('✓ No users found with legacy organizer_* roles.');
            $this->cleanupLegacyRoles($legacyRoles);

            return;
        }

        $this->processUsersWithLegacyRoles($usersWithLegacyRoles, $legacyRoles);
    }

    /**
     * @param  EloquentCollection<int, User>  $usersWithLegacyRoles
     * @param  EloquentCollection<int, Role>  $legacyRoles
     */
    private function processUsersWithLegacyRoles(EloquentCollection $usersWithLegacyRoles, EloquentCollection $legacyRoles): void
    {
        $this->warn("Found {$usersWithLegacyRoles->count()} user(s) with legacy roles:");

        foreach ($usersWithLegacyRoles as $user) {
            $legacyRoleNames = $user->roles->pluck('name')->intersect(['organizer_admin', 'organizer_editor', 'organizer_viewer'])->implode(', ');
            $this->line("  - {$user->name} ({$user->email}): {$legacyRoleNames}");
        }
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->info('DRY RUN: No changes will be made.');
            $this->analyzeMigration($usersWithLegacyRoles);

            return;
        }

        if (!$this->option('force') && !$this->confirm('Proceed with migration? This will modify user roles and cannot be easily undone.')) {
            $this->info('Migration cancelled.');

            return;
        }

        $this->performMigration($usersWithLegacyRoles, $legacyRoles);
    }

    /**
     * @param  EloquentCollection<int, User>  $usersWithLegacyRoles
     * @param  EloquentCollection<int, Role>  $legacyRoles
     */
    private function performMigration(EloquentCollection $usersWithLegacyRoles, EloquentCollection $legacyRoles): void
    {
        $this->info('Migrating roles...');
        $migrated = 0;
        $skipped = 0;

        foreach ($usersWithLegacyRoles as $user) {
            $result = $this->migrateUser($user);
            $migrated += $result['migrated'];
            $skipped += $result['skipped'];
        }

        $this->newLine();
        $this->info("Migration complete: {$migrated} role(s) migrated, {$skipped} skipped.");

        $this->cleanupLegacyRoles($legacyRoles);

        resolve(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $this->info('✓ Permission cache cleared.');
    }

    /**
     * @param  EloquentCollection<int, User>  $users
     */
    private function analyzeMigration(EloquentCollection $users): void
    {
        $this->newLine();
        $this->info('Analysis:');

        foreach ($users as $user) {
            $legacyNames = ['organizer_admin', 'organizer_editor', 'organizer_viewer'];

            foreach ($user->roles as $role) {
                if (!$role instanceof Role || !in_array($role->name, $legacyNames, true)) {
                    continue;
                }

                $mappedRole = $this->mapLegacyRole($role->name);

                // Try to infer organizer from user's memberships
                /** @var EloquentCollection<int, Organizer> $organizers */
                $organizers = $user->organizers;

                if ($organizers->isEmpty()) {
                    $this->warn("  ⚠ {$user->email}: No organizer membership found. Cannot infer target organizer.");
                    $this->line("    Legacy role: {$role->name} → Would map to: {$mappedRole}");
                    $this->line('    ACTION REQUIRED: Manual assignment needed.');
                } else {
                    $this->line("  ✓ {$user->email}:");
                    $this->line("    Legacy role: {$role->name} → Would map to: {$mappedRole}");

                    foreach ($organizers as $organizer) {
                        $this->line("    Organizer: {$organizer->name} ({$organizer->slug})");
                    }
                }
            }
        }
    }

    /**
     * @return array<string, int>
     */
    private function migrateUser(User $user): array
    {
        $migrated = 0;
        $skipped = 0;

        $legacyNames = ['organizer_admin', 'organizer_editor', 'organizer_viewer'];

        foreach ($user->roles as $role) {
            if (!$role instanceof Role || !in_array($role->name, $legacyNames, true)) {
                continue;
            }

            $mappedRole = $this->mapLegacyRole($role->name);

            // Try to infer organizer from user's memberships
            /** @var EloquentCollection<int, Organizer> $organizers */
            $organizers = $user->organizers;

            if ($organizers->isEmpty()) {
                $this->warn("  ⚠ {$user->email}: No organizer membership found. Skipping {$role->name}.");
                $skipped++;

                continue;
            }

            // Assign mapped role to each organizer the user belongs to
            foreach ($organizers as $organizer) {
                // Check if user already has a pivot entry for this organizer
                $existingPivot = DB::table('organizer_user')
                    ->where('organizer_id', $organizer->id)
                    ->where('user_id', $user->id)
                    ->first();

                if ($existingPivot) {
                    // Update existing pivot
                    DB::table('organizer_user')
                        ->where('organizer_id', $organizer->id)
                        ->where('user_id', $user->id)
                        ->update(['role' => $mappedRole]);
                } else {
                    // Create new pivot
                    DB::table('organizer_user')->insert([
                        'organizer_id' => $organizer->id,
                        'user_id' => $user->id,
                        'role' => $mappedRole,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $this->line("  ✓ {$user->email}: Assigned {$mappedRole} in {$organizer->name}");
                $migrated++;
            }

            // Remove legacy role
            $user->roles()->detach($role->id);
        }

        return ['migrated' => $migrated, 'skipped' => $skipped];
    }

    private function mapLegacyRole(string $legacyRole): string
    {
        return match ($legacyRole) {
            'organizer_admin' => OrganizerRoles::Admin->value,
            'organizer_editor' => OrganizerRoles::Editor->value,
            'organizer_viewer' => OrganizerRoles::Viewer->value,
            default => throw new InvalidArgumentException("Unknown legacy role: {$legacyRole}"),
        };
    }

    /**
     * @param  EloquentCollection<int, Role>  $legacyRoles
     */
    private function cleanupLegacyRoles(EloquentCollection $legacyRoles): void
    {
        $this->newLine();
        $this->info('Cleaning up legacy roles...');

        foreach ($legacyRoles as $role) {
            // Check if any users still have this role
            $userCount = DB::table('model_has_roles')
                ->where('role_id', $role->id)
                ->count();

            if ($userCount > 0) {
                $this->warn("  ⚠ Role '{$role->name}' still has {$userCount} user(s). Not deleting.");
            } else {
                $role->delete();
                $this->line("  ✓ Deleted role: {$role->name}");
            }
        }
    }
}
