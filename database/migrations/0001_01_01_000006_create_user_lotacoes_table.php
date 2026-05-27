<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_lotacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('orgao_id')->constrained('orgaos');
            $table->foreignId('lotacao_id')->nullable()->constrained('lotacoes');
            $table->boolean('lotacao_exercicio')->default(false);
            $table->boolean('administrador')->default(false);
            $table->enum('status', ['S', 'N'])->default('S');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_lotacoes');
    }
};

