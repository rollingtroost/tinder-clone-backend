<?php

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
        Schema::create('swipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('swiper_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('target_person_id')->constrained('persons')->cascadeOnDelete();
            $table->string('action'); // 'like' or 'dislike'
            $table->timestamps();

            $table->index(['swiper_user_id', 'target_person_id']);
            $table->index(['target_person_id', 'action']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('swipes');
    }
};