<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('whatsapp_configuration_id')
                ->nullable()
                ->after('conversation_id')
                ->constrained('whatsapp_configurations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['whatsapp_configuration_id']);
            $table->dropColumn('whatsapp_configuration_id');
        });
    }
};
