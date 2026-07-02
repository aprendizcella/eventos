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
        Schema::create('check_in_list', function (Blueprint $table) {
            $table->bigIncrements('check_in_list_id');
            $table->unsignedBigInteger('event_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('event_id')
                ->references('event_id')
                ->on('event')
                ->cascadeOnDelete();

            $table->index(['event_id', 'is_active']);
        });

        Schema::create('check_in_list_product', function (Blueprint $table) {
            $table->unsignedBigInteger('check_in_list_id');
            $table->unsignedBigInteger('product_id');

            $table->foreign('check_in_list_id')
                ->references('check_in_list_id')
                ->on('check_in_list')
                ->cascadeOnDelete();

            $table->foreign('product_id')
                ->references('product_id')
                ->on('product')
                ->cascadeOnDelete();

            $table->primary(['check_in_list_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('check_in_list_product');
        Schema::dropIfExists('check_in_list');
    }
};
