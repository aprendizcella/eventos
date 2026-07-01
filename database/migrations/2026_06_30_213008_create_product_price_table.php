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
        Schema::create('product_price', function (Blueprint $table) {
            $table->bigIncrements('product_price_id');
            $table->unsignedBigInteger('product_id');
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->integer('capacity')->nullable();
            $table->integer('quantity_sold')->default(0);
            $table->dateTime('sales_start_at')->nullable();
            $table->dateTime('sales_end_at')->nullable();
            $table->timestamps();

            $table->foreign('product_id')
                ->references('product_id')
                ->on('product')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_price');
    }
};
