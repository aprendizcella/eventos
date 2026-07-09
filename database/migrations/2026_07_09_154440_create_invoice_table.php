<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice', function (Blueprint $table) {
            $table->id('invoice_id');
            $table->foreignId('organizer_id')->constrained();
            $table->foreignId('ticket_order_id')->constrained('ticket_order', 'ticket_order_id');
            $table->foreignId('payment_id')->nullable()->constrained('payment', 'payment_id');
            $table->foreignId('refund_id')->nullable()->constrained('refund', 'refund_id');
            $table->string('type');
            $table->unsignedInteger('year');
            $table->unsignedInteger('number');
            $table->string('invoice_number');
            $table->integer('amount');
            $table->integer('tax_amount')->nullable();
            $table->integer('fee_amount')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organizer_id', 'year', 'number']);
            $table->index('ticket_order_id');
            $table->index('payment_id');
            $table->index('refund_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice');
    }
};
