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
        Schema::create('notification_recipient_log', function (Blueprint $table) {
            $table->id('notification_recipient_log_id');
            $table->foreignId('notification_log_id')->constrained('notification_log', 'notification_log_id')->onDelete('cascade');
            $table->foreignId('attendee_id')->constrained('attendee', 'attendee_id')->onDelete('cascade');
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->unique(['notification_log_id', 'attendee_id'], 'unique_recipient_delivery');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_recipient_log');
    }
};
