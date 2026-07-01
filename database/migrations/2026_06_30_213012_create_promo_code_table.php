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
        Schema::create('promo_code', function (Blueprint $table) {
            $table->bigIncrements('promo_code_id');
            $table->unsignedBigInteger('event_id');
            $table->string('code');
            $table->string('type'); // percentage, fixed
            $table->decimal('value', 10, 2);
            $table->integer('max_uses')->nullable();
            $table->integer('uses_count')->default(0);
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->string('status'); // active, inactive
            $table->timestamps();

            $table->foreign('event_id')
                ->references('event_id')
                ->on('event')
                ->cascadeOnDelete();

            $table->unique(['event_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promo_code');
    }
};
