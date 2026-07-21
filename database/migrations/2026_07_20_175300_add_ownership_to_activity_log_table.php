<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->unsignedBigInteger('organizer_id')->nullable();
            $table->boolean('is_global')->default(false);

            $table->foreign('organizer_id')->references('id')->on('organizers')->restrictOnDelete();

            $table->index(['organizer_id', 'created_at'], 'activity_log_organizer_id_created_at_index');
            $table->index(['is_global', 'created_at'], 'activity_log_is_global_created_at_index');
        });

        // Add invariants
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('
                CREATE TRIGGER enforce_activity_log_global_invariant_insert
                BEFORE INSERT ON activity_log
                FOR EACH ROW
                BEGIN
                    SELECT CASE
                        WHEN NEW.is_global = 1 AND NEW.organizer_id IS NOT NULL
                        THEN RAISE(ABORT, \'is_global=true implies organizer_id IS NULL\')
                    END;
                END;
            ');

            DB::statement('
                CREATE TRIGGER enforce_activity_log_global_invariant_update
                BEFORE UPDATE ON activity_log
                FOR EACH ROW
                BEGIN
                    SELECT CASE
                        WHEN NEW.is_global = 1 AND NEW.organizer_id IS NOT NULL
                        THEN RAISE(ABORT, \'is_global=true implies organizer_id IS NULL\')
                    END;
                END;
            ');
        } else {
            DB::statement('
                ALTER TABLE activity_log
                ADD CONSTRAINT chk_activity_log_global_invariant
                CHECK (is_global = 0 OR organizer_id IS NULL)
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Output a warning about data loss
        if (property_exists($this, 'command') && $this->command !== null) {
            $this->command->warn('WARNING: Dropping ownership columns destroys captured classification data.');
        } else {
            echo "WARNING: Dropping ownership columns destroys captured classification data.\n";
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('DROP TRIGGER IF EXISTS enforce_activity_log_global_invariant_insert');
            DB::statement('DROP TRIGGER IF EXISTS enforce_activity_log_global_invariant_update');
        } else {
            DB::statement('ALTER TABLE activity_log DROP CONSTRAINT chk_activity_log_global_invariant');
        }

        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropIndex('activity_log_organizer_id_created_at_index');
            $table->dropIndex('activity_log_is_global_created_at_index');
            $table->dropForeign(['organizer_id']);
            $table->dropColumn(['organizer_id', 'is_global']);
        });
    }
};
