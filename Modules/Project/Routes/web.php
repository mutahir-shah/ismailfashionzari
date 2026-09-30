<?php

use Illuminate\Support\Facades\Route;
use Modules\Project\Http\Controllers\ProjectController;
use Modules\Project\Http\Controllers\ProjectDiscussionController;
use Modules\Project\Http\Controllers\ProjectBugController;
use Modules\Project\Http\Controllers\ProjectFileController;
use Modules\Project\Http\Controllers\ProjectTaskController;
use Modules\Project\Http\Controllers\TaskController;
use Modules\Project\Http\Controllers\TaskDiscussionController;
use Modules\Project\Http\Controllers\TaskFileController;
use Modules\Project\Http\Controllers\EmployeeAssignedController;
use Modules\Project\Http\Controllers\ProjectCategoryController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

// 1. Determine if this is a SaaS Landlord environment
$isSaaS = (bool) config('database.connections.saleprosaas_landlord');

// 2. Build Tenancy middleware array dynamically
$tenancyMiddleware = $isSaaS ? [InitializeTenancyByDomain::class, PreventAccessFromCentralDomains::class] : [];

// ==========================================
// ADMIN/COMMON ROUTES
// ==========================================
$middlewares = array_merge(
    $tenancyMiddleware,
    ['common', 'auth', 'active']
);

Route::middleware($middlewares)->group(function () {

    Route::prefix('project-management')->group(function () {
        Route::get('project-categories', [ProjectCategoryController::class, 'index'])->name('project_categories.index');
        Route::post('project-categories', [ProjectCategoryController::class, 'store'])->name('project_categories.store');
        Route::get('project-categories/{id}/edit', [ProjectCategoryController::class, 'edit'])->name('project_categories.edit');
        Route::post('project-categories/update', [ProjectCategoryController::class, 'update'])->name('project_categories.update');
        Route::get('project-categories/{id}/delete', [ProjectCategoryController::class, 'destroy'])->name('project_categories.destroy');

        Route::post('projects/{project}/assigned', [EmployeeAssignedController::class, 'employeeProjectAssigned'])->name('projects.assigned');

        Route::post('projects/update', [ProjectController::class, 'update'])->name('projects.update');
        Route::resource('projects', ProjectController::class)->except(['destroy', 'create', 'update']);
        Route::post('dynamic_dependent/fetch_employee', [ProjectController::class, 'fetchEmployee'])->name('dynamic_employee');
        Route::get('projects/{id}/delete', [ProjectController::class, 'destroy'])->name('projects.destroy');
        if (method_exists(ProjectController::class, 'calendarableDetails')) {
            Route::get('projects/{id}/calendarable', [ProjectController::class, 'calendarableDetails'])->name('projects.calendarable');
        }
        Route::post('projects/{project}/progress', [ProjectController::class, 'progressStore'])->name('project_progress.store');

        Route::post('projects/{project}/discussions', [ProjectDiscussionController::class, 'index'])->name('project_discussions.index');
        Route::post('projects/store_discussions/{project}', [ProjectDiscussionController::class, 'store'])->name('project_discussions.store');
        Route::get('projects/{id}/delete_discussions', [ProjectDiscussionController::class, 'destroy'])->name('project_discussions.destroy');

        Route::post('projects/{project}/bugs', [ProjectBugController::class, 'index'])->name('project_bugs.index');
        Route::post('projects/store_bugs/{project}', [ProjectBugController::class, 'store'])->name('project_bugs.store');

        Route::post('projects/{project}/files', [ProjectFileController::class, 'index'])->name('project_files.index');
        Route::post('projects/store_files/{project}', [ProjectFileController::class, 'store'])->name('project_files.store');
        Route::get('projects/{id}/delete_files', [ProjectFileController::class, 'destroy'])->name('project_files.destroy');

        Route::post('projects/{project}/tasks', [ProjectTaskController::class, 'index'])->name('project_tasks.index');
        Route::post('projects/store_tasks/{project}', [ProjectTaskController::class, 'store'])->name('project_tasks.store');
        Route::get('projects/{id}/delete_tasks', [ProjectTaskController::class, 'destroy'])->name('project_tasks.destroy');

        Route::get('projects/bug_status/{id}', [ProjectBugController::class, 'editStatus'])->name('bug_status.edit');
        Route::post('projects/bug_status/update', [ProjectBugController::class, 'updateStatus'])->name('bug_status.update');
        Route::get('projects/{id}/delete_bugs', [ProjectBugController::class, 'destroy'])->name('project_bugs.destroy');
        Route::get('projects/discussion_download/{id}', [ProjectDiscussionController::class, 'download'])->name('projects.downloadAttachment');
        Route::get('projects/bug_download/{id}', [ProjectBugController::class, 'download'])->name('projects.downloadBug');

        Route::get('projects/file_download/{id}', [ProjectFileController::class, 'download'])->name('projects.downloadFile');
        Route::post('projects/{project}/notes', [ProjectController::class, 'notesStore'])->name('project_notes.store');
    });

    Route::prefix('project-management')->group(function () {
        Route::post('tasks/{task}/assigned', [EmployeeAssignedController::class, 'employeeTaskAssigned'])->name('tasks.assigned');

        Route::post('tasks/update', [TaskController::class, 'update'])->name('tasks.update');
        Route::resource('tasks', TaskController::class)->except(['destroy', 'create', 'update']);
        Route::get('tasks/{id}/delete', [TaskController::class, 'destroy'])->name('tasks.destroy');
        if (method_exists(TaskController::class, 'calendarableDetails')) {
            Route::get('tasks/{id}/calendarable', [TaskController::class, 'calendarableDetails'])->name('tasks.calendarable');
        }
        Route::post('tasks/{task}/progress', [TaskController::class, 'progressStore'])->name('task_progress.store');

        Route::post('tasks/{task}/discussions', [TaskDiscussionController::class, 'index'])->name('task_discussions.index');
        Route::post('tasks/store_discussions/{task}', [TaskDiscussionController::class, 'store'])->name('task_discussions.store');
        Route::get('tasks/{id}/delete_discussions', [TaskDiscussionController::class, 'destroy'])->name('task_discussions.destroy');

        Route::post('tasks/{task}/files', [TaskFileController::class, 'index'])->name('task_files.index');
        Route::post('tasks/store_files/{task}', [TaskFileController::class, 'store'])->name('task_files.store');
        Route::get('tasks/{id}/delete_files', [TaskFileController::class, 'destroy'])->name('task_files.destroy');
        Route::get('tasks/file_download/{id}', [TaskFileController::class, 'download'])->name('tasks.downloadFile');

        Route::post('tasks/{task}/notes', [TaskController::class, 'notesStore'])->name('task_notes.store');
    });

    $projectEmployeeController = 'Modules\\Project\\Http\\Controllers\\EmployeeController';
    if (class_exists($projectEmployeeController)) {
        Route::resource('project-employees', $projectEmployeeController);
    }

});
