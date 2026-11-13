<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', '/dashboard');

Route::get('/dashboard',fn()=>Inertia::render('Dashboard',[
    'stats'=>['sent'=>0, 'delivered'=>0, 'failed'=>0],
]))->name('dashboard');