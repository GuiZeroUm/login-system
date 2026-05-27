<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sistemas', function (Blueprint $table) {
            $table->timestamp('permissions_synced_at')->nullable()->after('ativo');
        });
    }

    public function down(): void
    {
        Schema::table('sistemas', function (Blueprint $table) {
            $table->dropColumn('permissions_synced_at');
        });
    }
};

