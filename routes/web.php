<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::livewire('calendario', 'pages::calendar.index')->name('calendar');
    Route::livewire('reservas', 'pages::reservations.index')->name('reservations.index');
    Route::livewire('administracion/espacios', 'pages::admin.rooms.index')->name('admin.rooms.index');
});

require __DIR__.'/settings.php';
