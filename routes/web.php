<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\NavbarController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MyTaskController;
use App\Http\Controllers\UnassignedTaskController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\ProjectTaskController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/navbar/search', [NavbarController::class, 'search'])->name('navbar.search');

    Route::resource('projects', ProjectController::class);

    Route::scopeBindings()->group(function () {
        Route::post('projects/{project}/tasks', [ProjectTaskController::class, 'store'])
            ->name('projects.tasks.store');
        Route::put('projects/{project}/tasks/{task}', [ProjectTaskController::class, 'update'])
            ->name('projects.tasks.update');
        Route::patch('projects/{project}/tasks/{task}/status', [ProjectTaskController::class, 'updateStatus'])
            ->name('projects.tasks.update-status');
        Route::delete('projects/{project}/tasks/{task}', [ProjectTaskController::class, 'destroy'])
            ->name('projects.tasks.destroy');

        Route::get('projects/{project}/tasks/{task}/comments', [TaskCommentController::class, 'index'])
            ->name('projects.tasks.comments.index');
        Route::post('projects/{project}/tasks/{task}/comments', [TaskCommentController::class, 'store'])
            ->name('projects.tasks.comments.store');
        Route::delete('projects/{project}/tasks/{task}/comments/{comment}', [TaskCommentController::class, 'destroy'])
            ->name('projects.tasks.comments.destroy');

        Route::put('projects/{project}/members', [ProjectMemberController::class, 'sync'])
            ->name('projects.members.sync');
    });

    Route::get('/my-tasks', [MyTaskController::class, 'index'])->name('my-tasks.index');
    Route::patch('/my-tasks/bulk-status', [MyTaskController::class, 'bulkUpdateStatus'])
        ->name('my-tasks.bulk-status');

    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');

    Route::get('/unassigned', [UnassignedTaskController::class, 'index'])->name('unassigned.index');
    Route::patch('/unassigned/bulk-assign', [UnassignedTaskController::class, 'bulkAssign'])
        ->name('unassigned.bulk-assign');
    Route::patch('/unassigned/tasks/{task}/assign', [UnassignedTaskController::class, 'assign'])
        ->name('unassigned.assign');

    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat/conversations/{conversation}/messages', [ChatController::class, 'storeMessage'])
        ->name('chat.messages.store');
    Route::delete('/chat/conversations/{conversation}/messages/{message}', [ChatController::class, 'destroyMessage'])
        ->name('chat.messages.destroy');

    Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download'])
        ->name('attachments.download');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    Route::get('/activity', [ActivityLogController::class, 'index'])->name('activity.index');

    Route::middleware('super_admin')->group(function () {
        Route::resource('roles', RoleController::class)->except(['show']);
        Route::resource('users', UserController::class)->except(['show']);
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
