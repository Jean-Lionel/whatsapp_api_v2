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
        Schema::create('whatsapp_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('wa_group_id')->nullable()->unique(); // WhatsApp Group ID if synced
            $table->string('invite_link')->nullable();
            $table->string('icon_url')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // Owner/Creator in our system
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_groups');
    }
};
