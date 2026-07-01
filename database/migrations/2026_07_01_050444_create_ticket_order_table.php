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
        Schema::create('ticket_order', function (Blueprint $table) {
            $table->bigIncrements('ticket_order_id');
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('promo_code_id')->nullable();
            $table->string('order_reference')->unique();
            $table->string('status'); // reserved, completed, cancelled, expired, refunded
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount', 10, 2)->default(0.00);
            $table->decimal('total', 10, 2);
            $table->dateTime('reserved_until')->nullable();
            $table->timestamps();

            $table->foreign('event_id')
                ->references('event_id')
                ->on('event')
                ->cascadeOnDelete();

            $table->foreign('promo_code_id')
                ->references('promo_code_id')
                ->on('promo_code')
                ->nullOnDelete();

            $table->index(['event_id', 'status', 'reserved_until']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_order');
    }
};
