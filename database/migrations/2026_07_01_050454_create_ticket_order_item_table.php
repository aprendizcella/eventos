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
        Schema::create('ticket_order_item', function (Blueprint $table) {
            $table->bigIncrements('ticket_order_item_id');
            $table->unsignedBigInteger('ticket_order_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_price_id')->nullable();
            $table->integer('quantity');
            $table->decimal('price', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount', 10, 2)->default(0.00);
            $table->decimal('total', 10, 2);
            $table->timestamps();

            $table->foreign('ticket_order_id')
                ->references('ticket_order_id')
                ->on('ticket_order')
                ->cascadeOnDelete();

            $table->foreign('product_id')
                ->references('product_id')
                ->on('product')
                ->restrictOnDelete();

            $table->foreign('product_price_id')
                ->references('product_price_id')
                ->on('product_price')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_order_item');
    }
};
