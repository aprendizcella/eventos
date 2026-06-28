<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event', static function (Blueprint $table): void {
            $table->id('event_id');
            $table->foreignId('organizer_id')->constrained('organizers')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('category', 'category_id')->nullOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained('venue', 'venue_id')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status')->default('draft')->index();
            $table->string('visibility')->default('private')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index('organizer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event');
    }
};
