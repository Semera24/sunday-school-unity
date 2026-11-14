<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Admin\MediaManagementController;
use App\Http\Controllers\Admin\RoleManagementController;
use App\Http\Controllers\Admin\SettingsManagementController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportCenterController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\HierarchyDemoController;
use App\Http\Controllers\MembershipInvitationController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $user = auth()->user();

    if ($user === null) {
        return redirect()->route('login');
    }

    return redirect()->route('dashboard');
})->name('home');

// School/member-scoped single-use invitation acceptance routes (rate-limited; works for new or signed-in invited users)
Route::middleware('throttle:10,1')->group(function () {
    Route::get('invitations/{token}', [MembershipInvitationController::class, 'show'])->name('invitations.show');
    Route::post('invitations/{token}/accept', [MembershipInvitationController::class, 'accept'])->name('invitations.accept');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::post('workspace/scope', [HierarchyDemoController::class, 'switchScope'])->name('workspace.scope.switch');

    // Hierarchy, school, department, member and report demonstration routes (server-side scoped in controller)
    Route::get('hierarchy', [HierarchyDemoController::class, 'hierarchyIndex'])->name('hierarchy.index');
    Route::post('hierarchy/organizations', [HierarchyDemoController::class, 'storeOrganization'])->name('hierarchy.organizations.store');
    Route::put('hierarchy/organizations/{organization}', [HierarchyDemoController::class, 'updateOrganization'])->name('hierarchy.organizations.update');
    Route::post('hierarchy/appointments', [HierarchyDemoController::class, 'storeLocalLeadAppointment'])->name('hierarchy.appointments.store');
    Route::patch('appointments/{scopedAppointment}', [HierarchyDemoController::class, 'updateAppointment'])->name('appointments.update');

    Route::get('schools/{organization}', [HierarchyDemoController::class, 'showSchool'])->name('schools.show');
    Route::post('schools/{organization}/members', [HierarchyDemoController::class, 'storeMember'])->name('schools.members.store');
    Route::post('schools/{organization}/invitations', [HierarchyDemoController::class, 'storeInvitation'])->name('schools.invitations.store');
    Route::post('schools/{organization}/departments', [HierarchyDemoController::class, 'storeDepartment'])->name('schools.departments.store');
    Route::post('schools/{organization}/appointments', [HierarchyDemoController::class, 'storeDepartmentCoordinatorAppointment'])->name('schools.appointments.store');

    Route::get('departments/{organizationalUnit}', [HierarchyDemoController::class, 'showDepartment'])->name('departments.show');

    Route::get('members/{schoolMembership}', [HierarchyDemoController::class, 'showMember'])->name('members.show');
    Route::put('members/{schoolMembership}', [HierarchyDemoController::class, 'updateMember'])->name('members.update');
    Route::post('members/{schoolMembership}/departments', [HierarchyDemoController::class, 'storeDepartmentMembership'])->name('members.departments.store');
    Route::patch('department-memberships/{departmentMembership}', [HierarchyDemoController::class, 'updateDepartmentMembership'])->name('department-memberships.update');

    Route::post('memberships/{schoolMembership}/approve', [HierarchyDemoController::class, 'approveMembership'])->name('memberships.approve');
    Route::post('memberships/{schoolMembership}/status', [HierarchyDemoController::class, 'transitionMembershipStatus'])->name('memberships.status');

    Route::post('invitations/{membershipInvitation}/resend', [HierarchyDemoController::class, 'resendInvitation'])->name('invitations.resend');
    Route::post('invitations/{membershipInvitation}/revoke', [HierarchyDemoController::class, 'revokeInvitation'])->name('invitations.revoke');
    Route::post('invitations/{membershipInvitation}/approve', [HierarchyDemoController::class, 'approveInvitationLink'])->name('invitations.approve');
    Route::post('invitations/{membershipInvitation}/reject', [HierarchyDemoController::class, 'rejectInvitationLink'])->name('invitations.reject');

    Route::get('reports', [HierarchyDemoController::class, 'reportsIndex'])->name('reports.index');
    Route::get('reports/{demoReport}', [HierarchyDemoController::class, 'showReport'])->name('reports.show');
    Route::post('reports/{demoReport}/submit-local', [HierarchyDemoController::class, 'submitReportLocally'])->name('reports.submit-local');
    Route::post('reports/{demoReport}/release', [HierarchyDemoController::class, 'releaseReportUpward'])->name('reports.release');

    Route::get('my-profile', [HierarchyDemoController::class, 'showMyProfile'])->name('my-profile.show');
    Route::put('my-profile', [HierarchyDemoController::class, 'updateMyProfile'])->name('my-profile.update');

    Route::get('search', GlobalSearchController::class)->middleware('permission:search.view')->name('search.index');

    Route::get('exports', [ExportCenterController::class, 'index'])->middleware('permission:exports.view')->name('exports.index');
    Route::get('exports/users.csv', [ExportCenterController::class, 'usersCsv'])->middleware(['permission:exports.view', 'permission:users.view'])->name('exports.users.csv');
    Route::get('exports/users.xlsx', [ExportCenterController::class, 'usersXlsx'])->middleware(['permission:exports.view', 'permission:users.view'])->name('exports.users.xlsx');
    Route::get('exports/users.xml', [ExportCenterController::class, 'usersXml'])->middleware(['permission:exports.view', 'permission:users.view'])->name('exports.users.xml');
    Route::get('exports/summary/print', [ExportCenterController::class, 'printSummary'])->middleware('permission:exports.view')->name('exports.summary.print');
    Route::get('exports/summary.pdf', [ExportCenterController::class, 'summaryPdf'])->middleware(['permission:exports.view'])->name('exports.summary.pdf');

    Route::get('admin/users', [UserManagementController::class, 'index'])->middleware('permission:users.view')->name('users.index');
    Route::get('admin/users/create', [UserManagementController::class, 'create'])->middleware('permission:users.create')->name('users.create');
    Route::post('admin/users', [UserManagementController::class, 'store'])->middleware('permission:users.create')->name('users.store');
    Route::get('admin/users/{user}/edit', [UserManagementController::class, 'edit'])->middleware('permission:users.update')->name('users.edit');
    Route::put('admin/users/{user}', [UserManagementController::class, 'update'])->middleware('permission:users.update')->name('users.update');
    Route::put('admin/users/{user}/roles', [UserManagementController::class, 'updateRoles'])->middleware(['permission:users.update', 'permission:roles.update'])->name('users.roles.update');
    Route::delete('admin/users/{user}', [UserManagementController::class, 'destroy'])->middleware('permission:users.delete')->name('users.destroy');

    Route::get('admin/roles', [RoleManagementController::class, 'index'])->middleware('permission:roles.view')->name('roles.index');
    Route::get('admin/roles/create', [RoleManagementController::class, 'create'])->middleware('permission:roles.create')->name('roles.create');
    Route::post('admin/roles', [RoleManagementController::class, 'store'])->middleware('permission:roles.create')->name('roles.store');
    Route::get('admin/roles/{role}/edit', [RoleManagementController::class, 'edit'])->middleware('permission:roles.update')->name('roles.edit');
    Route::put('admin/roles/{role}', [RoleManagementController::class, 'update'])->middleware('permission:roles.update')->name('roles.update');
    Route::put('admin/roles/{role}/permissions', [RoleManagementController::class, 'updatePermissions'])->middleware('permission:roles.update')->name('roles.permissions.update');
    Route::delete('admin/roles/{role}', [RoleManagementController::class, 'destroy'])->middleware('permission:roles.delete')->name('roles.destroy');

    Route::get('admin/settings', [SettingsManagementController::class, 'edit'])->middleware('permission:settings.view')->name('admin-settings.edit');
    Route::put('admin/settings', [SettingsManagementController::class, 'update'])->middleware('permission:settings.update')->name('admin-settings.update');
    Route::get('admin/media', [MediaManagementController::class, 'index'])->middleware('permission:media.view')->name('media.index');
    Route::post('admin/media', [MediaManagementController::class, 'store'])->middleware('permission:media.create')->name('media.store');
    Route::get('admin/media/{media}/preview', [MediaManagementController::class, 'preview'])->middleware('permission:media.view')->name('media.preview');
    Route::get('admin/media/{media}/download', [MediaManagementController::class, 'download'])->middleware('permission:media.view')->name('media.download');
    Route::delete('admin/media/{media}', [MediaManagementController::class, 'destroy'])->middleware('permission:media.delete')->name('media.destroy');

    Route::get('notifications', [NotificationController::class, 'index'])->middleware('permission:notifications.view')->name('notifications.index');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'read'])->middleware('permission:notifications.view')->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->middleware('permission:notifications.view')->name('notifications.read-all');
    Route::get('activity-logs', [ActivityLogController::class, 'index'])->middleware('permission:activity-logs.view')->name('activity-logs.index');
    Route::get('activity-logs/{activityLog}', [ActivityLogController::class, 'show'])->middleware('permission:activity-logs.view')->name('activity-logs.show');
});

require __DIR__.'/settings.php';
