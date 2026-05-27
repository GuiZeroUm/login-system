<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sistemas', function (Blueprint $table) {
            $table->string('login_nome')->nullable()->after('descricao');
            $table->enum('tema_login', ['escuro', 'claro'])->default('escuro')->after('login_nome');
        });
    }

    public function down(): void
    {
        Schema::table('sistemas', function (Blueprint $table) {
            $table->dropColumn(['login_nome', 'tema_login']);
        });
    }
};

