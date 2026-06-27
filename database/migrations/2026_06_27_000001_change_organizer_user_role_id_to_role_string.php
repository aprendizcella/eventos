<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Preflight: ensure every existing role_id maps to a known role name.
        // Fail early BEFORE any destructive schema change if dirty data exists.
        $roleMap = DB::table('roles')
            ->whereIn('name', ['admin', 'editor', 'viewer'])
            ->pluck('name', 'id')
            ->toArray();

        $totalRows = DB::table('organizer_user')->count();
        $mappableRows = DB::table('organizer_user')
            ->whereIn('role_id', array_keys($roleMap))
            ->count();

        if ($totalRows > 0 && $mappableRows !== $totalRows) {
            $unmapped = $totalRows - $mappableRows;

            throw new RuntimeException(
                "Cannot migrate organizer_user.role_id -> role: {$unmapped} row(s) have unmapped role_id values. "
                .'Clean up dirty data before running this migration.',
            );
        }

        // Add the new string-based role column
        Schema::table('organizer_user', static function (Blueprint $table): void {
            $table->string('role', 32)->nullable()->after('role_id');
        });

        // Migrate existing role_id values to role names
        foreach (DB::table('organizer_user')->cursor() as $pivot) {
            $roleName = $roleMap[$pivot->role_id] ?? null;

            if ($roleName !== null) {
                DB::table('organizer_user')
                    ->where('organizer_id', $pivot->organizer_id)
                    ->where('user_id', $pivot->user_id)
                    ->update(['role' => $roleName]);
            }
        }

        // Drop the old FK column and recreate as string-based
        Schema::table('organizer_user', static function (Blueprint $table): void {
            $table->dropForeign(['role_id']);
            $table->dropIndex(['organizer_id', 'role_id']);
            $table->dropColumn('role_id');
        });

        Schema::table('organizer_user', static function (Blueprint $table): void {
            $table->string('role', 32)->nullable(false)->change();
            $table->index(['organizer_id', 'role']);
        });
    }

    public function down(): void
    {
        $legacyRoles = ['admin', 'editor', 'viewer'];

        // Preflight 1: validate every existing role string is in the known legacy set
        // BEFORE any writes to the `roles` table or schema mutation.
        // This checks data validity against the known domain set, not the roles table,
        // because legacy roles may not exist yet (they are recreated in Preflight 2).
        // This ensures a failed rollback never creates/updates legacy role rows.
        $totalRows = DB::table('organizer_user')->count();
        $mappableRows = DB::table('organizer_user')
            ->whereIn('role', $legacyRoles)
            ->count();

        if ($totalRows > 0 && $mappableRows !== $totalRows) {
            $unmapped = $totalRows - $mappableRows;

            throw new RuntimeException(
                "Cannot rollback organizer_user.role -> role_id: {$unmapped} row(s) have unmapped role values. "
                .'Clean up dirty data before running this rollback.',
            );
        }

        // Preflight 2: ensure legacy Spatie roles exist before any schema mutation.
        // The current RoleSeeder no longer seeds admin/editor/viewer as global roles,
        // so we must recreate them if missing to ensure deterministic rollback.
        // Only reached after validation passes — no dirty writes on failure.
        $now = now();

        foreach ($legacyRoles as $roleName) {
            DB::table('roles')->updateOrInsert(
                ['name' => $roleName, 'guard_name' => 'web'],
                ['created_at' => $now, 'updated_at' => $now],
            );
        }

        // Fetch map after ensuring roles exist (IDs may have been just created).
        $roleMap = DB::table('roles')
            ->whereIn('name', $legacyRoles)
            ->pluck('id', 'name')
            ->toArray();

        // Add back the role_id column (nullable initially for backfill)
        Schema::table('organizer_user', static function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id')->nullable()->after('role');
        });

        // Map role names back to role IDs
        foreach (DB::table('organizer_user')->cursor() as $pivot) {
            $roleId = $roleMap[$pivot->role] ?? null;

            if ($roleId !== null) {
                DB::table('organizer_user')
                    ->where('organizer_id', $pivot->organizer_id)
                    ->where('user_id', $pivot->user_id)
                    ->update(['role_id' => $roleId]);
            }
        }

        // Drop the new string-based role column and its index
        Schema::table('organizer_user', static function (Blueprint $table): void {
            $table->dropIndex(['organizer_id', 'role']);
            $table->dropColumn('role');
        });

        // Make role_id non-nullable and restore FK + composite index.
        // The column already exists (added earlier in this method); do NOT recreate it.
        Schema::table('organizer_user', static function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id')->nullable(false)->change();
            $table->foreign('role_id')->references('id')->on('roles')->restrictOnDelete();
            $table->index(['organizer_id', 'role_id']);
        });
    }
};
