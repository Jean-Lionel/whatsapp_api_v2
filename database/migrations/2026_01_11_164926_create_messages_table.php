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
            $table->string('wa_message_id')->nullable()->unique();
            $table->string('conversation_id')->nullable();
            $table->enum('direction',['in','out']);
            $table->string('from_number');
            $table->string('to_number');
            $table->enum('type',['text','image','video','audio','document','location','template','interactive']);
            $table->text('body')->nullable();
            $table->json('payload')->nullable();
            $table->enum('status',['sent','delivered','read','failed'])->default('sent');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
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
