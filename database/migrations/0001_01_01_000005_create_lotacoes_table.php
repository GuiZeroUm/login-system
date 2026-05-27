<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lotacoes', function (Blueprint $table) {
            $table->id();
            $table->string('nome_lotacao');
            $table->string('sigla_lotacao')->nullable();
            $table->foreignId('orgao_id')->constrained('orgaos');
            $table->integer('nivel_hierarquico')->default(1);
            $table->foreignId('subordinada_id')->nullable()->constrained('lotacoes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lotacoes');
    }
};

