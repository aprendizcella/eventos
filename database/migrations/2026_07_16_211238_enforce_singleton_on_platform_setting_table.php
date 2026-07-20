<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_setting', function (Blueprint $table) {
            $table->boolean('is_singleton')->default(true);
            $table->unique('is_singleton');
        });
    }

    public function down(): void
    {
        Schema::table('platform_setting', function (Blueprint $table) {
            $table->dropUnique(['is_singleton']);
            $table->dropColumn('is_singleton');
        });
    }
};
