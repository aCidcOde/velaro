<?php

use App\Http\Controllers\AgentConversationController;
use App\Http\Controllers\AgentConversationDeleteController;
use App\Http\Controllers\AgentConversationListController;
use App\Http\Controllers\AgentConversationTitleController;
use App\Http\Controllers\AgentDebugController;
use App\Http\Controllers\AgentLatestMessageController;
use App\Http\Controllers\AgentMessageController;
use App\Http\Controllers\AgentPageController;
use App\Http\Controllers\AgentUploadDeleteController;
use App\Http\Controllers\AgentUploadListController;
use App\Http\Controllers\AgentUploadStoreController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\GuestEmailVerificationController;
use App\Http\Controllers\Backend\AuditLogController;
use App\Http\Controllers\Backend\ChangelogController;
use App\Http\Controllers\Backend\CustomerController as BackendCustomerController;
use App\Http\Controllers\Backend\DashboardController as BackendDashboardController;
use App\Http\Controllers\Backend\OrderController as BackendOrderController;
use App\Http\Controllers\Backend\ProductController as BackendProductController;
use App\Http\Controllers\Backend\UserController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function (): void {
    Route::get('auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
    Route::get('email/verify/{id}/{hash}/guest', GuestEmailVerificationController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.guest.verify');
});

Route::get('dashboard', DashboardController::class)
    ->middleware(['auth', 'not_blocked', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'not_blocked', 'verified'])->group(function (): void {
    Route::resource('customers', CustomerController::class)->except(['show']);
    Route::resource('products', ProductController::class)->except(['show']);
    Route::resource('orders', OrdersController::class);
});

Route::middleware(['auth', 'not_blocked', 'verified'])->group(function (): void {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});

Route::prefix('agente')
    ->name('agent.')
    ->middleware(['auth', 'not_blocked', 'verified', 'agent'])
    ->group(function (): void {
        Route::get('/', [AgentPageController::class, 'chat'])->name('dashboard');
        Route::get('arquivos', [AgentPageController::class, 'files'])->name('files');
        Route::get('arquivos/uploads', AgentUploadListController::class)->middleware('throttle:agent')->name('uploads.index');
        Route::post('arquivos/uploads', AgentUploadStoreController::class)->middleware('throttle:agent')->name('uploads.store');
        Route::delete('arquivos/uploads/{upload}', AgentUploadDeleteController::class)->middleware('throttle:agent')->name('uploads.delete');
        Route::get('conversas', AgentConversationListController::class)->middleware('throttle:agent')->name('conversations');
        Route::post('mensagem', AgentMessageController::class)->middleware('throttle:agent')->name('message');
        Route::post('diagnostico', AgentDebugController::class)->middleware('throttle:agent')->name('debug');
        Route::get('conversa/{conversation}', AgentConversationController::class)->middleware('throttle:agent')->name('conversation');
        Route::patch('conversa/{conversation}/titulo', AgentConversationTitleController::class)->middleware('throttle:agent')->name('conversation.title');
        Route::delete('conversa/{conversation}', AgentConversationDeleteController::class)->middleware('throttle:agent')->name('conversation.delete');
        Route::get('ultima-mensagem', AgentLatestMessageController::class)->middleware('throttle:agent')->name('latest-message');
    });

Route::prefix('backend')
    ->name('backend.')
    ->middleware(['auth', 'not_blocked', 'verified', 'can:access-backend'])
    ->group(function (): void {
        Route::get('/', BackendDashboardController::class)->name('dashboard');

        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('changelog', [ChangelogController::class, 'index'])->name('changelog.index');
        Route::get('changelog/{version}', [ChangelogController::class, 'show'])->name('changelog.show');

        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::put('users/{user}/acl', [UserController::class, 'updateAcl'])->name('users.acl.update');

        Route::resource('customers', BackendCustomerController::class)->except(['create', 'store']);
        Route::resource('products', BackendProductController::class)->except(['create', 'store']);
        Route::resource('orders', BackendOrderController::class)->except(['create', 'store', 'edit']);
    });
