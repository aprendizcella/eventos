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
        Schema::create('attendee', function (Blueprint $table) {
            $table->bigIncrements('attendee_id');
            $table->unsignedBigInteger('ticket_order_id');
            $table->unsignedBigInteger('ticket_order_item_id');
            $table->integer('sequence');
            $table->string('unique_code')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('status'); // active, cancelled, checked_in
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('ticket_order_id')
                ->references('ticket_order_id')
                ->on('ticket_order')
                ->restrictOnDelete();

            $table->foreign('ticket_order_item_id')
                ->references('ticket_order_item_id')
                ->on('ticket_order_item')
                ->restrictOnDelete();

            $table->unique(['ticket_order_item_id', 'sequence']);
            $table->index('unique_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendee');
    }
};
