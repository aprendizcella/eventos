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
        Schema::create('check_in_log', function (Blueprint $table) {
            $table->bigIncrements('check_in_log_id');
            $table->unsignedBigInteger('check_in_list_id');
            $table->unsignedBigInteger('attendee_id');
            $table->string('action'); // check_in, undo
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['check_in_list_id', 'attendee_id']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('check_in_log');
    }
};
