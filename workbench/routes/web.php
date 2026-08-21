<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Workbench\App\Models\User;

// Screenshot convenience: skips the login form.
Route::get('/login-as-admin', function () {
    Auth::login(User::query()->firstOrFail());

    return redirect('/admin/posts');
});
