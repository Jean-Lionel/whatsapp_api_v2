<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('contact_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();
            $table->index('contact_id');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['contact_id']);
            $table->dropIndex(['contact_id']);
            $table->dropColumn('contact_id');
        });
    }
};
