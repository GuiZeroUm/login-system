<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_perfil', function (Blueprint $table) {
            $table->dropForeign(['user_lotacao_id']);
            $table->foreignId('user_lotacao_id')->nullable()->change();
            $table->foreign('user_lotacao_id')->references('id')->on('user_lotacoes')->cascadeOnDelete();
            $table->unique(['role_id', 'user_sistema_id'], 'user_perfil_sistema_unique');
        });

        Schema::table('user_permissions', function (Blueprint $table) {
            $table->dropForeign(['user_lotacao_id']);
            $table->foreignId('user_lotacao_id')->nullable()->change();
            $table->foreign('user_lotacao_id')->references('id')->on('user_lotacoes')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('user_perfil', function (Blueprint $table) {
            $table->dropUnique('user_perfil_sistema_unique');
            $table->dropForeign(['user_lotacao_id']);
            $table->foreignId('user_lotacao_id')->nullable(false)->change();
            $table->foreign('user_lotacao_id')->references('id')->on('user_lotacoes')->cascadeOnDelete();
        });

        Schema::table('user_permissions', function (Blueprint $table) {
            $table->dropForeign(['user_lotacao_id']);
            $table->foreignId('user_lotacao_id')->nullable(false)->change();
            $table->foreign('user_lotacao_id')->references('id')->on('user_lotacoes')->cascadeOnDelete();
        });
    }
};
