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
        Schema::create('webhook_delivery', static function (Blueprint $table): void {
            $table->id('webhook_delivery_id');
            $table->uuid('delivery_id')->unique();
            $table->foreignId('organizer_id')->constrained('organizers');
            $table->foreignId('webhook_id')->constrained('webhook', 'webhook_id');
            $table->foreignId('payment_id')->constrained('payment', 'payment_id');
            $table->string('event');
            $table->string('version', 8);
            $table->text('envelope');
            $table->string('status', 32)->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('occurred_at');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->string('failure_reason', 160)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organizer_id', 'status']);
            $table->index(['webhook_id', 'status']);
            $table->index(['payment_id']);
            $table->index(['status', 'expires_at']);
            $table->unique(['webhook_id', 'payment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_delivery');
    }
};
