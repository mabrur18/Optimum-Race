<?php

use App\Enums\UserRole;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EntryListExportController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\RaceClassController;
use App\Http\Controllers\Admin\RegistrationController as AdminRegistrationController;
use App\Http\Controllers\Admin\SmtpSettingsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Public\EventController as PublicEventController;
use App\Http\Controllers\Rider\DashboardController as RiderDashboardController;
use App\Http\Controllers\Rider\RegistrationController as RiderRegistrationController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/events/{slug}', [PublicEventController::class, 'show'])->name('events.show');

// FORMULIR & SUBMIT PUBLIK (GUEST TANPA LOGIN) - SESUAI KONSEP BARU 2026-08-18
Route::get('/events/{slug}/register', [PublicEventController::class, 'showRegistrationForm'])->name('events.register.form');
Route::post('/events/{slug}/register', [PublicEventController::class, 'submitRegistration'])->name('events.register.submit');

// KONFIRMASI PEMBAYARAN (GUEST BISA AKSES VIA INVOICE NUMBER YANG DIKIRIM EMAIL)
Route::get('/payments/confirm', [PublicEventController::class, 'showPaymentConfirmForm'])->name('payments.confirm.form');
Route::get('/payments/confirm/{invoiceNumber}', [PublicEventController::class, 'showPaymentConfirmForm'])->name('payments.confirm.form.invoice');
Route::post('/payments/confirm', [PublicEventController::class, 'submitPaymentProof'])->name('payments.confirm.submit');

// Authenticated routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard redirect berdasarkan role user
    Route::get('dashboard', function () {
        $user = Auth::user();

        // Normalisasi role ke string value
        if ($user->role instanceof UserRole) {
            $roleValue = $user->role->value;
        } else {
            $roleValue = (string) $user->role;
        }

        return match ($roleValue) {
            UserRole::Superadmin->value => redirect()->route('superadmin.dashboard'),
            UserRole::Admin->value      => redirect()->route('admin.dashboard'),
            UserRole::Staff->value      => redirect()->route('staff.dashboard'),
            UserRole::Rider->value      => redirect()->route('rider.dashboard'),
            default                     => redirect()->route('home'),
        };
    })->name('dashboard');

    // Superadmin routes
    Route::middleware('role:superadmin')->prefix('superadmin')->name('superadmin.')->group(function () {
        Route::inertia('dashboard', 'Superadmin/Dashboard')->name('dashboard');
    });

    // Admin EO routes
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Event management
        Route::resource('events', EventController::class);
        // Race Classes management
        Route::resource('race-classes', RaceClassController::class);
        // Registrations management
        Route::resource('registrations', AdminRegistrationController::class)->except(['create', 'store', 'destroy']);
        Route::post('registrations/{registration}/update-status', [AdminRegistrationController::class, 'updateStatus'])->name('registrations.update-status');
        Route::post('registrations/{registration}/generate-invoice', [AdminRegistrationController::class, 'generateManualInvoice'])->name('registrations.generate-invoice');
        Route::post('registrations/{registration}/manual-payment-proof', [AdminRegistrationController::class, 'storeManualPaymentProof'])->name('registrations.manual-payment-proof');
        Route::post('registrations/{registration}/manual-document', [AdminRegistrationController::class, 'storeManualDocument'])->name('registrations.manual-document');

        // Entry List Export
        Route::get('entry-list', [EntryListExportController::class, 'index'])->name('entry-list.index');
        Route::get('entry-list/export/{format}', [EntryListExportController::class, 'export'])->name('entry-list.export');

        // SMTP Settings (hanya admin / superadmin)
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('smtp', [SmtpSettingsController::class, 'index'])->name('smtp.index');
            Route::post('smtp', [SmtpSettingsController::class, 'store'])->name('smtp.store');
            Route::post('smtp/test', [SmtpSettingsController::class, 'test'])->name('smtp.test');
        });
    });

    // Staff EO routes (mirip admin untuk operasional lapangan)
    Route::middleware('role:staff')->prefix('staff')->name('staff.')->group(function () {
        Route::inertia('dashboard', 'Staff/Dashboard')->name('dashboard');
        Route::resource('events', EventController::class);
        Route::resource('race-classes', RaceClassController::class);
        Route::resource('registrations', AdminRegistrationController::class)->except(['create', 'store', 'destroy']);
        Route::post('registrations/{registration}/update-status', [AdminRegistrationController::class, 'updateStatus'])->name('registrations.update-status');
        Route::post('registrations/{registration}/generate-invoice', [AdminRegistrationController::class, 'generateManualInvoice'])->name('registrations.generate-invoice');

        // Staff hanya bisa akses Entry List (bukan SMTP settings)
        Route::get('entry-list', [EntryListExportController::class, 'index'])->name('entry-list.index');
        Route::get('entry-list/export/{format}', [EntryListExportController::class, 'export'])->name('entry-list.export');
    });

    // Rider routes
    Route::middleware('role:rider')->prefix('rider')->name('rider.')->group(function () {
        Route::get('dashboard', [RiderDashboardController::class, 'index'])->name('dashboard');
        Route::get('registrations', [RiderRegistrationController::class, 'index'])->name('registrations.index');
        Route::get('registrations/{id}', [RiderRegistrationController::class, 'show'])->name('registrations.show');
    });
});

require __DIR__.'/settings.php';
