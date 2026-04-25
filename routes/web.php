<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/running-jobs', 'running-jobs')->name('running-jobs');
