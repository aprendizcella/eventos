<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payout', function (Blueprint $table) {
            $table->id('payout_id');
            $table->foreignId('organizer_id')->constrained();
            $table->foreignId('invoice_id')->constrained('invoice', 'invoice_id')->cascadeOnDelete();
            $table->foreignId('refund_id')->nullable()->constrained('refund', 'refund_id')->nullOnDelete();
            $table->integer('gross_amount');
            $table->integer('commission_amount');
            $table->integer('net_amount');
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('pending');
            $table->dateTime('processed_at')->nullable();
            $table->dateTime('reversed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('invoice_id');
            $table->index('refund_id');
            $table->index(['organizer_id', 'status'], 'payout_organizer_status_index');
            $table->index(['organizer_id', 'created_at'], 'payout_organizer_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout');
    }
};
