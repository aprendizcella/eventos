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
        Schema::create('payment', function (Blueprint $table) {
            $table->id('payment_id');
            $table->foreignId('ticket_order_id')->constrained('ticket_order', 'ticket_order_id');
            $table->string('provider_id')->nullable()->unique();
            $table->string('payment_method');
            $table->string('status');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->timestamps();

            $table->index('ticket_order_id');
            $table->index('provider_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment');
    }
};
