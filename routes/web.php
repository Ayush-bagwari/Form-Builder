<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Forms\Dashboard;
use App\Livewire\Forms\Builder;
use App\Livewire\Forms\PublicForm;
use App\Livewire\Forms\SubmissionsList;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Public Form Fill Route (Derived strictly from schema validation)
Route::get('/f/{slug}', PublicForm::class)->name('forms.public');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/builder/{form?}', Builder::class)->name('builder');
    Route::get('/forms/{form}/submissions', SubmissionsList::class)->name('forms.submissions');
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::get('/profile/edit', fn() => redirect()->route('profile'))->name('profile.edit');

require __DIR__.'/auth.php';