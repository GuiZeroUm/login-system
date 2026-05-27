<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_perfil', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles');
            $table->foreignId('user_lotacao_id')->constrained('user_lotacoes');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['role_id', 'user_lotacao_id'], 'user_perfil_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_perfil');
    }
};

