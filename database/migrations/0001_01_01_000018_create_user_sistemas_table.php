<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_sistemas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sistema_id')->constrained('sistemas')->cascadeOnDelete();
            $table->boolean('administrador')->default(false);
            $table->enum('status', ['S', 'N'])->default('S');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'sistema_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sistemas');
    }
};
