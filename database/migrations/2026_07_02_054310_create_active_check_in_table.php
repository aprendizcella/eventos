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
        Schema::create('active_check_in', function (Blueprint $table) {
            $table->bigIncrements('active_check_in_id');
            $table->unsignedBigInteger('check_in_list_id');
            $table->unsignedBigInteger('attendee_id');
            $table->datetime('checked_in_at');
            $table->unsignedBigInteger('checked_in_by_user_id')->nullable();
            $table->timestamps();

            $table->foreign('check_in_list_id')
                ->references('check_in_list_id')
                ->on('check_in_list')
                ->cascadeOnDelete();

            $table->foreign('attendee_id')
                ->references('attendee_id')
                ->on('attendee')
                ->cascadeOnDelete();

            $table->foreign('checked_in_by_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->unique(['check_in_list_id', 'attendee_id']);
            $table->index('attendee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('active_check_in');
    }
};
