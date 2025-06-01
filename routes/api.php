<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoutineController;

Route::post('/schedule/import', [RoutineController::class, 'importSchedule']);
Route::get('/schedule', [RoutineController::class, 'getSchedule']);
