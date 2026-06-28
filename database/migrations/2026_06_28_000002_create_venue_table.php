<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue', static function (Blueprint $table): void {
            $table->id('venue_id');
            $table->foreignId('organizer_id')->constrained('organizers')->cascadeOnDelete();
            $table->string('name');
            $table->string('address');
            $table->string('city')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('organizer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue');
    }
};
