<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check()
    ? redirect()->route('dashboard')
    : redirect()->route('login')
)->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');
    Route::livewire('transactions', 'pages::transactions.index')->name('transactions.index');
    Route::livewire('loans', 'pages::loans.index')->name('loans.index');
    Route::livewire('budgets', 'pages::budgets.index')->name('budgets.index');
    Route::livewire('accounts', 'pages::accounts.index')->name('accounts.index');
    Route::livewire('settings/categories', 'pages::settings.categories')->name('settings.categories');
});

require __DIR__.'/settings.php';
