<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    AuthController,
    ProgramController,
    CourseController,
    BatchController,
    HodTeacherController,
    TimetableController,
    RoomController,
    AnalyticsController,
    TeacherScheduleController,
    StudentAttendanceController,
    ReportController,
    TimeSlotController,
};

// ── Public routes ──────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);
});

Route::get('departments', fn() =>
    response()->json(\App\Models\Department::select('id', 'name', 'code')->get())
);

// ── Authenticated routes ───────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me',      [AuthController::class, 'me']);

    // ── HOD ──────────────────────────────────────────────────────────────
    Route::middleware('role:hod')->prefix('hod')->group(function () {

        Route::apiResource('programs', ProgramController::class);
        Route::apiResource('programs.courses', CourseController::class)->shallow();

        Route::apiResource('batches', BatchController::class);
        Route::post('batches/{batch}/students', [BatchController::class, 'addStudents']);
        Route::post('batches/{batch}/courses',  [BatchController::class, 'assignCourse']);

        Route::get('teachers',                              [HodTeacherController::class, 'index']);
        Route::post('teachers',                             [HodTeacherController::class, 'store']);
        Route::put('teachers/{teacher}/availability',       [HodTeacherController::class, 'setAvailability']);

        Route::apiResource('time-slots', TimeSlotController::class);
        Route::apiResource('rooms', RoomController::class);

        Route::post('timetable/generate',    [TimetableController::class, 'generate']);
        Route::get('timetable/logs',         [TimetableController::class, 'logs']);
        Route::get('timetable/{batch}',      [TimetableController::class, 'batchTimetable']);

        Route::prefix('analytics')->group(function () {
            Route::get('attendance-trends',          [AnalyticsController::class, 'attendanceTrends']);
            Route::get('weak-students/{batch}',      [AnalyticsController::class, 'weakStudents']);
            Route::get('batch-performance/{batch}',  [AnalyticsController::class, 'batchPerformance']);
        });

        Route::get('reports/attendance/{batch}', [ReportController::class, 'attendanceReport']);
    });

    // ── Teacher ───────────────────────────────────────────────────────────
    Route::middleware('role:teacher')->prefix('teacher')->group(function () {
        Route::get('schedule',                     [TeacherScheduleController::class, 'mySchedule']);
        Route::post('sessions',                    [TeacherScheduleController::class, 'openSession']);
        Route::put('sessions/{session}/close',     [TeacherScheduleController::class, 'closeSession']);
        Route::get('sessions/{session}/records',   [TeacherScheduleController::class, 'sessionRecords']);
    });

    // ── Student ───────────────────────────────────────────────────────────
    Route::middleware('role:student')->prefix('student')->group(function () {
        Route::get('schedule',               [StudentAttendanceController::class, 'mySchedule']);
        Route::get('sessions/active',        [StudentAttendanceController::class, 'activeSessions']);
        Route::post('attendance/mark',       [StudentAttendanceController::class, 'mark']);
        Route::get('attendance/history',     [StudentAttendanceController::class, 'history']);
        Route::get('attendance/summary',     [StudentAttendanceController::class, 'summary']);
    });
});
