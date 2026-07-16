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
        Schema::table('event', function (Blueprint $table) {
            $table->index('category_id', 'idx_event_category_id');
            $table->index('venue_id', 'idx_event_venue_id');
            $table->index('starts_at', 'idx_event_starts_at');
            $table->index('ends_at', 'idx_event_ends_at');
        });

        Schema::table('category', function (Blueprint $table) {
            $table->index('parent_id', 'idx_category_parent_id');
            $table->index('slug', 'idx_category_slug');
        });

        Schema::table('venue', function (Blueprint $table) {
            $table->index('city', 'idx_venue_city');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venue', function (Blueprint $table) {
            $table->dropIndex('idx_venue_city');
        });

        Schema::table('category', function (Blueprint $table) {
            $table->dropIndex('idx_category_slug');
            $table->dropIndex('idx_category_parent_id');
        });

        Schema::table('event', function (Blueprint $table) {
            $table->dropIndex('idx_event_ends_at');
            $table->dropIndex('idx_event_starts_at');
            $table->dropIndex('idx_event_venue_id');
            $table->dropIndex('idx_event_category_id');
        });
    }
};
