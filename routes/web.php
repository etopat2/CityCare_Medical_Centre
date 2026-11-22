<?php

use App\Http\Controllers\Admin\AccessControlController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\SystemLogController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Auth\RegisteredPatientController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\DoctorScheduleController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PatientPortalController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PesapalPaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/payments/pesapal/callback', [PesapalPaymentController::class, 'callback'])->name('payments.pesapal.callback');
Route::get('/payments/pesapal/ipn', [PesapalPaymentController::class, 'ipn'])->name('payments.pesapal.ipn');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredPatientController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredPatientController::class, 'store'])->middleware('throttle:5,1')->name('register.store');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'redirect'])->name('dashboard');
    Route::get('/admin/dashboard', [DashboardController::class, 'show'])->middleware('role:admin')->name('dashboard.admin');
    Route::get('/receptionist/dashboard', [DashboardController::class, 'show'])->middleware('role:receptionist')->name('dashboard.receptionist');
    Route::get('/doctor/dashboard', [DashboardController::class, 'show'])->middleware('role:doctor')->name('dashboard.doctor');
    Route::get('/doctor/schedule', [DoctorScheduleController::class, 'own'])->middleware('role:doctor')->name('doctor-schedule.own');
    Route::get('/cashier/dashboard', [DashboardController::class, 'show'])->middleware('role:cashier')->name('dashboard.cashier');
    Route::get('/patient/dashboard', [DashboardController::class, 'show'])->middleware('role:patient')->name('dashboard.patient');

    Route::middleware('permission:users.manage')->prefix('users')->name('users.')->group(function (): void {
        Route::get('/', [UserManagementController::class, 'index'])->name('index');
        Route::post('/', [UserManagementController::class, 'store'])->name('store');
        Route::patch('/{user}', [UserManagementController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserManagementController::class, 'destroy'])->name('destroy');
        Route::post('/{userId}/restore', [UserManagementController::class, 'restore'])->whereNumber('userId')->name('restore');
    });

    Route::middleware('permission:access.manage')->prefix('access-control')->name('access.')->group(function (): void {
        Route::get('/', [AccessControlController::class, 'index'])->name('index');
        Route::post('/roles', [AccessControlController::class, 'store'])->name('roles.store');
        Route::post('/permissions', [AccessControlController::class, 'storePermission'])->name('permissions.store');
        Route::put('/roles/{role}', [AccessControlController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [AccessControlController::class, 'destroy'])->name('roles.destroy');
    });

    Route::middleware('permission:audit.view')->prefix('admin/audit-trail')->name('audit.')->group(function (): void {
        Route::get('/', [AuditLogController::class, 'index'])->name('index');
        Route::get('/export', [AuditLogController::class, 'export'])->middleware('permission:audit.export')->name('export');
    });

    Route::middleware('permission:system-logs.view')->prefix('admin/system-logs')->name('system-logs.')->group(function (): void {
        Route::get('/', [SystemLogController::class, 'index'])->name('index');
        Route::get('/export', [SystemLogController::class, 'export'])->middleware('permission:system-logs.export')->name('export');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/profile/photo', [ProfileController::class, 'photo'])->name('profile.photo');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('permission:departments.manage')->group(function (): void {
        Route::resource('departments', DepartmentController::class)->except(['show']);
    });

    Route::middleware('permission:doctors.view|doctors.manage')->group(function (): void {
        Route::resource('doctors', DoctorController::class)->only(['index', 'show']);
    });
    Route::middleware('permission:doctors.manage')->group(function (): void {
        Route::resource('doctors', DoctorController::class)->except(['index', 'show']);
    });

    Route::middleware('permission:schedules.manage')->group(function (): void {
        Route::resource('doctor-schedules', DoctorScheduleController::class)->except(['show']);
    });

    Route::middleware('permission:patients.view|patients.create|patients.update|patients.delete|patients.treated.view|portal.profile.view')->group(function (): void {
        Route::resource('patients', PatientController::class)->except(['destroy']);
        Route::delete('/patients/{patient}', [PatientController::class, 'destroy'])->name('patients.destroy')->middleware('permission:patients.delete');
    });

    Route::middleware('permission:appointments.view|appointments.create|appointments.update|appointments.cancel|portal.appointments.view')->group(function (): void {
        Route::resource('appointments', AppointmentController::class)->except(['destroy']);
        Route::post('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel')->middleware('permission:appointments.cancel');
        Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.status')->middleware('permission:appointments.update');
    });

    Route::middleware('permission:consultations.view|consultations.create|portal.visits.view')->group(function (): void {
        Route::get('/consultations', [ConsultationController::class, 'index'])->name('consultations.index');
        Route::get('/consultations/{consultation}', [ConsultationController::class, 'show'])->name('consultations.show');
        Route::get('/consultations/{consultation}/edit', [ConsultationController::class, 'edit'])->name('consultations.edit');
        Route::put('/consultations/{consultation}', [ConsultationController::class, 'update'])->name('consultations.update');
        Route::get('/appointments/{appointment}/consultation/create', [ConsultationController::class, 'create'])->name('appointments.consultations.create');
        Route::post('/appointments/{appointment}/consultation', [ConsultationController::class, 'store'])->name('appointments.consultations.store');
    });

    Route::middleware('permission:payments.view|payments.create|payments.update|portal.payments.view')->group(function (): void {
        Route::resource('payments', PaymentController::class)->except(['destroy']);
        Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');
    });

    Route::prefix('portal')->name('portal.')->middleware('role:patient')->group(function (): void {
        Route::get('/profile', [PatientPortalController::class, 'profile'])->name('profile');
        Route::get('/appointments', [PatientPortalController::class, 'appointments'])->name('appointments');
        Route::get('/visits', [PatientPortalController::class, 'visits'])->name('visits');
        Route::get('/payments', [PatientPortalController::class, 'payments'])->name('payments');
        Route::post('/appointments/{appointment}/pay', [PesapalPaymentController::class, 'start'])->middleware('throttle:8,1')->name('appointments.pay');
    });

    Route::middleware('permission:reports.view|reports.daily.view|reports.payments.view')->prefix('reports')->name('reports.')->group(function (): void {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/{report}', [ReportController::class, 'show'])->name('show');
    });

    Route::prefix('api')->name('api.')->group(function (): void {
        Route::get('/doctors/{doctor}/available-slots', [AppointmentController::class, 'availableSlots'])->name('doctors.available-slots');
        Route::get('/patients/search', [AppointmentController::class, 'searchPatients'])->name('patients.search');
        Route::get('/departments/{department}/doctors', [AppointmentController::class, 'doctorsByDepartment'])->name('departments.doctors');
    });
});

require __DIR__.'/auth.php';
