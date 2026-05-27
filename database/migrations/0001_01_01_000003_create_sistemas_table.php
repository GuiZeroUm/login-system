<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sistemas', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('slug')->unique();
            $table->string('url');
            $table->string('url_logout')->nullable();
            $table->enum('ambiente', ['production', 'homologacao', 'desenvolvimento'])->default('desenvolvimento');
            $table->text('descricao')->nullable();
            $table->string('caminho_logo')->nullable();
            $table->string('caminho_ilustracao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sistemas');
    }
};

