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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('from')->nullable();
            $table->string('to')->nullable();
            $table->foreignId('contact_id')->nullable();
            $table->text('text')->nullable();
            $table->string('media_type')->nullable();
            $table->string('media_url')->nullable();
            $table->string('media_id')->nullable();
            $table->boolean('is_read')->default(false);
            $table->boolean('is_me')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
