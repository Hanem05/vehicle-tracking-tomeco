<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DriverPortalController;
use App\Http\Controllers\EnforcerDashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\ViolationController;
use App\Http\Controllers\ViolationTypeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/driver/scan', [DriverPortalController::class, 'scan'])->name('driver.scan');
Route::get('/driver/lookup', [DriverPortalController::class, 'lookup'])->middleware('throttle:30,1')->name('driver.lookup');
Route::get('/scan/{token}', [DriverPortalController::class, 'scanned'])->whereUuid('token')->middleware('throttle:30,1')->name('vehicles.scanned');

Route::middleware(['auth', 'role:admin,enforcer'])->group(function () {
    Route::get('/dashboard', function (Request $request) {
        return redirect()->route($request->user()->role === 'admin' ? 'admin.dashboard' : 'enforcer.dashboard');
    })->name('dashboard');

    Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
    Route::get('/vehicles/create', [VehicleController::class, 'create'])->name('vehicles.create');
    Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
    Route::get('/vehicles/scan', [VehicleController::class, 'scan'])->name('vehicles.scan');
    Route::post('/vehicles/lookup', [VehicleController::class, 'lookup'])->name('vehicles.lookup');
    Route::get('/vehicles/{vehicle}', [VehicleController::class, 'show'])->whereNumber('vehicle')->name('vehicles.show');
    Route::get('/vehicles/{vehicle}/qr', [VehicleController::class, 'qr'])->whereNumber('vehicle')->name('vehicles.qr');
    Route::get('/vehicles/{vehicle}/qr-card', [VehicleController::class, 'qrCard'])->whereNumber('vehicle')->name('vehicles.qr-card');

    Route::get('/violations/create', [ViolationController::class, 'create'])->name('violations.create');
    Route::post('/violations', [ViolationController::class, 'store'])->name('violations.store');
    Route::get('/violations/{violation}', [ViolationController::class, 'show'])->whereNumber('violation')->name('violations.show');
    Route::get('/violations/{violation}/evidence', [ViolationController::class, 'evidence'])->whereNumber('violation')->name('violations.evidence');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', DashboardController::class)->name('admin.dashboard');
    Route::get('/vehicles/{vehicle}/edit', [VehicleController::class, 'edit'])->whereNumber('vehicle')->name('vehicles.edit');
    Route::put('/vehicles/{vehicle}', [VehicleController::class, 'update'])->whereNumber('vehicle')->name('vehicles.update');

    Route::get('/violations', [ViolationController::class, 'index'])->name('violations.index');
    Route::patch('/violations/{violation}/timeline', [ViolationController::class, 'updateTimeline'])->whereNumber('violation')->name('violations.timeline');
    Route::patch('/violations/{violation}/status', [ViolationController::class, 'status'])->whereNumber('violation')->name('violations.status');
    Route::patch('/violations/{violation}/subpoena-action', [ViolationController::class, 'recordSubpoenaAction'])->whereNumber('violation')->name('violations.subpoena-action');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('/settings/types', [ViolationTypeController::class, 'index'])->name('types.index');
    Route::post('/settings/types', [ViolationTypeController::class, 'store'])->name('types.store');
    Route::put('/settings/types/{type}', [ViolationTypeController::class, 'update'])->name('types.update');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::get('/activity', [ReportController::class, 'activity'])->name('reports.activity');
});

Route::get('/enforcer/dashboard', EnforcerDashboardController::class)
    ->middleware(['auth', 'role:enforcer'])
    ->name('enforcer.dashboard');
