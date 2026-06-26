<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizer_user', static function (Blueprint $table): void {
            $table->foreignId('organizer_id')->constrained('organizers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['organizer_id', 'user_id']);
            $table->index('user_id');
            $table->index(['organizer_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizer_user');
    }
};
