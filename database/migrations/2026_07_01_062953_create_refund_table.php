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
        Schema::create('refund', function (Blueprint $table) {
            $table->id('refund_id');
            $table->foreignId('payment_id')->constrained('payment', 'payment_id');
            $table->string('provider_id')->nullable()->unique();
            $table->uuid('idempotency_key')->unique();
            $table->string('status');
            $table->decimal('amount', 10, 2);
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index('payment_id');
            $table->index('provider_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund');
    }
};
