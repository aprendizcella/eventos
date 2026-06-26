<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('roles', 'organizer_id')) {
            Schema::table('roles', static function (Blueprint $table): void {
                $table->unsignedBigInteger('organizer_id')->nullable()->after('id');
                $table->index('organizer_id', 'roles_team_foreign_key_index');
            });
        }

        if (!Schema::hasColumn('model_has_roles', 'organizer_id')) {
            Schema::table('model_has_roles', static function (Blueprint $table): void {
                $table->unsignedBigInteger('organizer_id')->after('role_id');
                $table->index('organizer_id', 'model_has_roles_team_foreign_key_index');
            });
        }

        if (!Schema::hasColumn('model_has_permissions', 'organizer_id')) {
            Schema::table('model_has_permissions', static function (Blueprint $table): void {
                $table->unsignedBigInteger('organizer_id')->after('permission_id');
                $table->index('organizer_id', 'model_has_permissions_team_foreign_key_index');
            });
        }
    }

    public function down(): void
    {
        Schema::table('roles', static function (Blueprint $table): void {
            $table->dropIndex('roles_team_foreign_key_index');
            $table->dropColumn('organizer_id');
        });

        Schema::table('model_has_roles', static function (Blueprint $table): void {
            $table->dropIndex('model_has_roles_team_foreign_key_index');
            $table->dropColumn('organizer_id');
        });

        Schema::table('model_has_permissions', static function (Blueprint $table): void {
            $table->dropIndex('model_has_permissions_team_foreign_key_index');
            $table->dropColumn('organizer_id');
        });
    }
};
