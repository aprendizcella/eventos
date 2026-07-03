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
        Schema::create('waitlist_entry', function (Blueprint $table) {
            $table->bigIncrements('waitlist_entry_id');
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('product_price_id');
            $table->string('email');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('status'); // waiting, notified, reserved, expired, converted
            $table->dateTime('notified_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->string('token', 32)->nullable();
            $table->timestamps();

            $driver = Schema::getConnection()->getDriverName();
            $expression = $driver === 'sqlite'
                ? "CASE WHEN status IN ('waiting', 'notified', 'reserved') THEN CAST(product_price_id AS TEXT) || '-' || LOWER(TRIM(email)) ELSE NULL END"
                : "IF(status IN ('waiting', 'notified', 'reserved'), CONCAT(product_price_id, '-', LOWER(TRIM(email))), NULL)";

            $table->string('active_email_unique')
                ->virtualAs($expression)
                ->nullable();

            $table->foreign('event_id')
                ->references('event_id')
                ->on('event')
                ->cascadeOnDelete();

            $table->foreign('product_price_id')
                ->references('product_price_id')
                ->on('product_price')
                ->cascadeOnDelete();

            $table->unique('active_email_unique');
            $table->unique('token');
            $table->index(['product_price_id', 'status', 'waitlist_entry_id'], 'waitlist_price_status_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waitlist_entry');
    }
};
