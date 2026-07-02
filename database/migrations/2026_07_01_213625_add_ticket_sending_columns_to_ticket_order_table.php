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
        Schema::table('ticket_order', function (Blueprint $table) {
            $table->dateTime('tickets_sent_at')->nullable();
            $table->dateTime('tickets_processing_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_order', function (Blueprint $table) {
            $table->dropColumn(['tickets_sent_at', 'tickets_processing_at']);
        });
    }
};
