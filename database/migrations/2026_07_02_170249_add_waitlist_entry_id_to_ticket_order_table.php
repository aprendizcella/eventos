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
            $table->unsignedBigInteger('waitlist_entry_id')->nullable()->after('promo_code_id');

            $table->foreign('waitlist_entry_id')
                ->references('waitlist_entry_id')
                ->on('waitlist_entry')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_order', function (Blueprint $table) {
            $table->dropForeign(['waitlist_entry_id']);
            $table->dropColumn('waitlist_entry_id');
        });
    }
};
