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
        Schema::create('product', function (Blueprint $table) {
            $table->bigIncrements('product_id');
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('organizer_id');
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('type'); // ticket, addon, merchandise
            $table->string('pricing_mode'); // free, paid, donation
            $table->string('status'); // active, paused, closed
            $table->string('visibility'); // public, hidden, password
            $table->string('password')->nullable();
            $table->integer('min_qty')->default(1);
            $table->integer('max_qty')->default(10);
            $table->integer('sort_order')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('event_id')
                ->references('event_id')
                ->on('event')
                ->cascadeOnDelete();

            $table->foreign('organizer_id')
                ->references('id')
                ->on('organizers')
                ->cascadeOnDelete();

            $table->unique(['event_id', 'slug']);
            $table->index(['organizer_id', 'event_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product');
    }
};
