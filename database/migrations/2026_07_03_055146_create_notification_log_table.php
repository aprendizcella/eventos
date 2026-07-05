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
        Schema::create('notification_log', function (Blueprint $table) {
            $table->id('notification_log_id');
            $table->foreignId('event_id')->constrained('event', 'event_id')->onDelete('cascade');
            $table->foreignId('sent_by_user_id')->nullable()->constrained('users', 'id')->onDelete('set null');
            $table->string('subject');
            $table->text('body');
            $table->integer('recipient_count')->default(0);
            $table->json('filter_criteria')->nullable();
            $table->string('status');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_log');
    }
};
