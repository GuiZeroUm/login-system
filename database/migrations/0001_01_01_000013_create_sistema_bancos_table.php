<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sistema_bancos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sistema_id')->constrained('sistemas')->cascadeOnDelete();
            $table->string('tipo')->default('postgresql');
            $table->string('host');
            $table->unsignedInteger('porta')->default(5432);
            $table->string('nome_banco');
            $table->string('usuario');
            $table->text('senha')->nullable();
            $table->timestamps();

            $table->unique('sistema_id');
            $table->index(['sistema_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sistema_bancos');
    }
};

