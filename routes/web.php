<?php

use App\Http\Controllers\AutenticacaoController;
use App\Http\Controllers\SistemaController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

// Logos/ilustrações em storage/app/public/sistemas exigem o symlink público:
// php artisan storage:link

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::get('/login/{slug?}', [AutenticacaoController::class, 'index'])->name('login');
Route::post('/login', [AutenticacaoController::class, 'store'])->name('login.store');

Route::get('/logout/{slug?}', [AutenticacaoController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'can:sistema'])->group(function () {
    Route::inertia('/dashboard', 'Dashboard')->name('dashboard');

    Route::post('sistema/testar-banco', [SistemaController::class, 'testarBanco'])
        ->name('sistema.testar-banco');
    Route::resource('sistema', SistemaController::class)->except(['show']);
    Route::patch('sistema/{sistema}/reativar', [SistemaController::class, 'reativar'])
        ->name('sistema.reativar');
    Route::patch('sistema/{sistema}/personalizacao', [SistemaController::class, 'atualizarPersonalizacao'])
        ->name('sistema.personalizacao');
    Route::post('sistema/{sistema}/sincronizar-permissoes', [SistemaController::class, 'sincronizarPermissoes'])
        ->name('sistema.sincronizar-permissoes');

    Route::resource('usuario', UsuarioController::class)->except(['show']);

    Route::inertia('/sessoes', 'Sessoes/Index')
        ->middleware('can:7')
        ->name('sessoes.index');

    Route::prefix('documentacao')->name('documentacao.')->group(function () {
        Route::inertia('/', 'Documentacao/Index')->name('index');
        Route::inertia('/configuracao', 'Documentacao/Configuracao')->name('configuracao');
        Route::inertia('/laravel', 'Documentacao/Laravel')->name('laravel');
        Route::inertia('/react-next', 'Documentacao/ReactNext')->name('react-next');
    });

    Route::redirect('/orgao', '/dashboard')->name('orgao.index');
    Route::redirect('/lotacao', '/dashboard')->name('lotacao.index');
});
