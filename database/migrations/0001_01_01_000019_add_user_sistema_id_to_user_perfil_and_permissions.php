<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_perfil', function (Blueprint $table) {
            $table->foreignId('user_sistema_id')->nullable()->after('user_lotacao_id')->constrained('user_sistemas')->cascadeOnDelete();
        });

        Schema::table('user_permissions', function (Blueprint $table) {
            $table->foreignId('user_sistema_id')->nullable()->after('user_lotacao_id')->constrained('user_sistemas')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('user_permissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_sistema_id');
        });

        Schema::table('user_perfil', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_sistema_id');
        });
    }
};
