<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sistemas', function (Blueprint $table) {
            $table->string('login_subtitulo')->nullable()->after('tema_login');
            $table->string('login_painel_eyebrow')->nullable()->after('login_subtitulo');
            $table->string('login_painel_titulo')->nullable()->after('login_painel_eyebrow');
            $table->text('login_painel_descricao')->nullable()->after('login_painel_titulo');
            $table->boolean('exibir_logo_topo')->default(true)->after('login_painel_descricao');
            $table->boolean('exibir_bloco_inferior')->default(true)->after('exibir_logo_topo');
            $table->boolean('exibir_degrade_ilustracao')->default(true)->after('exibir_bloco_inferior');
        });
    }

    public function down(): void
    {
        Schema::table('sistemas', function (Blueprint $table) {
            $table->dropColumn([
                'login_subtitulo',
                'login_painel_eyebrow',
                'login_painel_titulo',
                'login_painel_descricao',
                'exibir_logo_topo',
                'exibir_bloco_inferior',
                'exibir_degrade_ilustracao',
            ]);
        });
    }
};
