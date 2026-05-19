<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\SalaryInsightController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Main Dashboard is the employee directory and analytical insights hub
Route::get('/', [EmployeeController::class, 'index'])->name('employees.index');

// Individual CRUD endpoints
Route::resource('employees', EmployeeController::class)->except(['index']);

// Dynamic AJAX insights analytics endpoint
Route::get('/salary-insights', [SalaryInsightController::class, 'index'])->name('insights.index');
