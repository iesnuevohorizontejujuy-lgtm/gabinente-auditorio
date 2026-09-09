<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::livewire('calendario', 'pages::calendar.index')->name('calendar');
    Route::livewire('reservas', 'pages::reservations.index')->name('reservations.index');
    Route::livewire('administracion/espacios', 'pages::admin.rooms.index')->name('admin.rooms.index');
    Route::livewire('administracion/usuarios', 'pages::admin.users.index')->name('admin.users.index');
});

require __DIR__.'/settings.php';
