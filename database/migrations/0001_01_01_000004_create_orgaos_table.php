<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orgaos', function (Blueprint $table) {
            $table->id();
            $table->string('descricao_orgao');
            $table->string('sigla_orgao', 20)->nullable();
            $table->string('cnpj', 18)->nullable()->unique();
            $table->enum('status', ['ativo', 'inativo'])->default('ativo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orgaos');
    }
};

