<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('active_check_in', function (Blueprint $table) {
            $table->unique(['check_in_list_id', 'attendee_id'], 'unique_active_check_in_per_list');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('active_check_in', function (Blueprint $table) {
            $table->dropUnique('unique_active_check_in_per_list');
        });
    }
};
