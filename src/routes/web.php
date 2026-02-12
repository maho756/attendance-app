<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\StampCorrectionRequestController;
use App\Http\Controllers\AdminLoginController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminStaffController;
use App\Http\Controllers\AdminStampCorrectionRequestController;

Route::get('/admin/login', [AdminLoginController::class, 'show'])
    ->name('admin.login');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clockIn');
    Route::post('/attendance/break-start', [AttendanceController::class, 'breakStart'])->name('attendance.breakStart');
    Route::post('/attendance/break-end', [AttendanceController::class, 'breakEnd'])->name('attendance.breakEnd');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clockOut');

    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'detail'])->name('attendance.detail');

    Route::get('/stamp_correction_request/list', [StampCorrectionRequestController::class, 'index'])
        ->name('stamp_correction_request.list');

    Route::post('/attendance/detail/{id}/request', [StampCorrectionRequestController::class, 'store'])
        ->name('stamp_correction_request.store');
});

Route::middleware(['auth', 'verified', 'admin'])
    ->name('admin.')
    ->group(function () {

        Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}',
            [AdminStampCorrectionRequestController::class, 'show']
        )->name('requests.show');

        Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}',
            [AdminStampCorrectionRequestController::class, 'approve']
        )->name('requests.approve');

        Route::prefix('admin')->group(function () {

            Route::get('/attendance/list', [AdminAttendanceController::class, 'list'])
                ->name('attendance.list');

            Route::get('/attendance/{id}', [AdminAttendanceController::class, 'detail'])
                ->name('attendance.detail');

            Route::post('/attendance/{id}/update', [AdminAttendanceController::class, 'update'])
                ->name('attendance.update');

            Route::get('/staff/list', [AdminStaffController::class, 'index'])
                ->name('staff.index');

            Route::get('/attendance/staff/{id}', [AdminStaffController::class, 'attendances'])
                ->name('staff.attendances');

            Route::get('/attendance/staff/{id}/csv', [AdminStaffController::class, 'exportCsv'])
                ->name('staff.attendances.csv');
        });
    });