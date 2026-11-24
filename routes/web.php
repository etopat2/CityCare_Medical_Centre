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
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\PharmacyController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\LabController;
use App\Http\Controllers\PesapalPaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

// Public pages are available without an account; payment provider callbacks must also be reachable externally.
Route::get('/', function () {
    return view('welcome');
});

Route::view('/terms-of-use', 'legal.terms')->name('legal.terms');
Route::view('/privacy-policy', 'legal.privacy')->name('legal.privacy');

Route::get('/payments/pesapal/callback', [PesapalPaymentController::class, 'callback'])->name('payments.pesapal.callback');
Route::get('/payments/pesapal/ipn', [PesapalPaymentController::class, 'ipn'])->name('payments.pesapal.ipn');

// Patient registration is public but throttled to reduce automated account creation.
Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredPatientController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredPatientController::class, 'store'])->middleware('throttle:5,1')->name('register.store');
});

// Every clinic workspace below requires a signed-in account that is still active.
Route::middleware(['auth', 'active'])->group(function (): void {
    // Send each role to its corresponding dashboard and expose the doctor's personal schedule.
    Route::get('/dashboard', [DashboardController::class, 'redirect'])->name('dashboard');
    Route::get('/admin/dashboard', [DashboardController::class, 'show'])->middleware('role:admin')->name('dashboard.admin');
    Route::get('/receptionist/dashboard', [DashboardController::class, 'show'])->middleware('role:receptionist')->name('dashboard.receptionist');
    Route::get('/doctor/dashboard', [DashboardController::class, 'show'])->middleware('role:doctor')->name('dashboard.doctor');
    Route::get('/doctor/schedule', [DoctorScheduleController::class, 'own'])->middleware('role:doctor')->name('doctor-schedule.own');
    Route::get('/cashier/dashboard', [DashboardController::class, 'show'])->middleware('role:cashier')->name('dashboard.cashier');
    Route::get('/patient/dashboard', [DashboardController::class, 'show'])->middleware('role:patient')->name('dashboard.patient');

    // Account administration is restricted to users with explicit user-management permission.
    Route::middleware('permission:users.manage')->prefix('users')->name('users.')->group(function (): void {
        Route::get('/', [UserManagementController::class, 'index'])->name('index');
        Route::post('/', [UserManagementController::class, 'store'])->name('store');
        Route::patch('/{user}', [UserManagementController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserManagementController::class, 'destroy'])->name('destroy');
        Route::post('/{userId}/restore', [UserManagementController::class, 'restore'])->whereNumber('userId')->name('restore');
    });

    // Role and permission changes affect authorization across the application.
    Route::middleware('permission:access.manage')->prefix('access-control')->name('access.')->group(function (): void {
        Route::get('/', [AccessControlController::class, 'index'])->name('index');
        Route::post('/roles', [AccessControlController::class, 'store'])->name('roles.store');
        Route::post('/permissions', [AccessControlController::class, 'storePermission'])->name('permissions.store');
        Route::put('/roles/{role}', [AccessControlController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [AccessControlController::class, 'destroy'])->name('roles.destroy');
    });

    // Auditing is read-only; exports have a separate permission from viewing the trail.
    Route::middleware('permission:audit.view')->prefix('admin/audit-trail')->name('audit.')->group(function (): void {
        Route::get('/', [AuditLogController::class, 'index'])->name('index');
        Route::get('/export', [AuditLogController::class, 'export'])->middleware('permission:audit.export')->name('export');
    });

    // Application log access is separate from the audit trail and exports are separately authorized.
    Route::middleware('permission:system-logs.view')->prefix('admin/system-logs')->name('system-logs.')->group(function (): void {
        Route::get('/', [SystemLogController::class, 'index'])->name('index');
        Route::get('/export', [SystemLogController::class, 'export'])->middleware('permission:system-logs.export')->name('export');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/profile/photo', [ProfileController::class, 'photo'])->name('profile.photo');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Clinic setup and catalog routes are split between viewing and management permissions.
    Route::middleware('permission:departments.manage')->group(function (): void {
        Route::resource('departments', DepartmentController::class)->except(['show']);
    });

    Route::middleware('permission:medicines.view|medicines.manage')->group(function (): void {
        Route::get('/medicines', [MedicineController::class, 'index'])->name('medicines.index');
    });
    Route::middleware('permission:medicines.manage')->group(function (): void {
        Route::post('/medicines', [MedicineController::class, 'store'])->name('medicines.store');
        Route::put('/medicines/{medicine}', [MedicineController::class, 'update'])->name('medicines.update');
    });
    Route::middleware('permission:pricing.manage')->group(function (): void {
        Route::get('/pricing', [PricingController::class, 'index'])->name('pricing.index');
        Route::put('/pricing/doctors/{doctor}', [PricingController::class, 'updateDoctor'])->name('pricing.doctors.update');
        Route::put('/pricing/lab-tests/{labTest}', [PricingController::class, 'updateLabTest'])->name('pricing.lab-tests.update');
    });
    // Laboratory order access is shared by clinicians, technicians, administrators, and patients as permitted.
    Route::middleware('permission:lab.view|lab.process|lab.manage|portal.lab-orders.create')->prefix('laboratory')->name('lab.')->group(function (): void {
        Route::get('/', [LabController::class, 'index'])->name('index');
        Route::get('/orders/{labOrder}', [LabController::class, 'show'])->name('show');
    });
    Route::put('/laboratory/orders/{labOrder}', [LabController::class, 'update'])->middleware('permission:lab.process')->name('lab.update');
    Route::middleware('permission:lab.manage')->group(function (): void {
        Route::post('/lab-tests', [LabController::class, 'storeTest'])->name('lab-tests.store');
        Route::put('/lab-tests/{labTest}', [LabController::class, 'updateTest'])->name('lab-tests.update');
    });
    // Pharmacists may review orders with view permission; dispensing and clearance require a dedicated permission.
    Route::middleware('permission:pharmacy.view')->prefix('pharmacy')->name('pharmacy.')->group(function (): void {
        Route::get('/', [PharmacyController::class, 'index'])->name('index');
        Route::get('/orders/{medicationOrder}', [PharmacyController::class, 'show'])->name('show');
    });
    Route::middleware('permission:prescriptions.dispense')->prefix('pharmacy')->name('pharmacy.')->group(function (): void {
        Route::put('/orders/{medicationOrder}/review', [PharmacyController::class, 'review'])->name('review');
        Route::post('/items/{prescriptionItem}/dispense', [PharmacyController::class, 'dispense'])->name('dispense');
        Route::post('/orders/{medicationOrder}/clear', [PharmacyController::class, 'clear'])->name('clear');
    });

    // Separate doctor write routes from the shared index and profile routes.
    Route::middleware('permission:doctors.manage')->group(function (): void {
        Route::resource('doctors', DoctorController::class)->except(['index', 'show']);
    });
    Route::middleware('permission:doctors.view|doctors.manage')->group(function (): void {
        Route::resource('doctors', DoctorController::class)->only(['index', 'show']);
    });

    Route::middleware('permission:schedules.manage')->group(function (): void {
        Route::resource('doctor-schedules', DoctorScheduleController::class)->except(['show']);
    });

    // Patient record routes are available only to users with an appropriate patient permission.
    Route::middleware('permission:patients.view|patients.create|patients.update|patients.delete|patients.treated.view|portal.profile.view')->group(function (): void {
        Route::resource('patients', PatientController::class)->except(['destroy']);
        Route::delete('/patients/{patient}', [PatientController::class, 'destroy'])->name('patients.destroy')->middleware('permission:patients.delete');
    });

    // Appointment operations combine staff scheduling with the patient portal's own booking access.
    Route::middleware('permission:appointments.view|appointments.create|appointments.update|appointments.cancel|portal.appointments.view')->group(function (): void {
        Route::resource('appointments', AppointmentController::class)->except(['destroy']);
        Route::post('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel')->middleware('permission:appointments.cancel');
        Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.status')->middleware('permission:appointments.update');
    });

    // Clinical note routes use role-level permissions; record policies further scope patient access.
    Route::middleware('permission:consultations.view|consultations.create|portal.visits.view')->group(function (): void {
        Route::get('/consultations', [ConsultationController::class, 'index'])->name('consultations.index');
        Route::get('/consultations/{consultation}', [ConsultationController::class, 'show'])->name('consultations.show');
        Route::get('/consultations/{consultation}/edit', [ConsultationController::class, 'edit'])->name('consultations.edit');
        Route::put('/consultations/{consultation}', [ConsultationController::class, 'update'])->name('consultations.update');
        Route::get('/appointments/{appointment}/consultation/create', [ConsultationController::class, 'create'])->name('appointments.consultations.create');
        Route::post('/appointments/{appointment}/consultation', [ConsultationController::class, 'store'])->name('appointments.consultations.store');
    });

    // Payment records and receipts are permission-gated; order payment endpoints are scoped separately below.
    Route::middleware('permission:payments.view|payments.create|payments.update|portal.payments.view')->group(function (): void {
        Route::resource('payments', PaymentController::class)->except(['destroy']);
        Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');
        Route::get('/payments/{payment}/transactions/{paymentTransaction}/receipt', [PaymentController::class, 'transactionReceipt'])->name('payments.transaction-receipt');
    });
    Route::post('/appointments/{appointment}/fee-exemption', [PaymentController::class, 'exemptAppointment'])
        ->middleware('permission:payments.exempt')
        ->name('appointments.fee-exemption');
    Route::post('/medication-orders/{medicationOrder}/payments', [PaymentController::class, 'storeMedication'])->middleware('permission:payments.create')->name('medication-orders.payments.store');
    Route::post('/lab-orders/{labOrder}/payments', [PaymentController::class, 'storeLab'])->middleware('permission:payments.create')->name('lab-orders.payments.store');

    // Patient-only portal routes; online checkout initiation and refresh are rate-limited.
    Route::prefix('portal')->name('portal.')->middleware('role:patient')->group(function (): void {
        Route::get('/profile', [PatientPortalController::class, 'profile'])->name('profile');
        Route::get('/appointments', [PatientPortalController::class, 'appointments'])->name('appointments');
        Route::get('/visits', [PatientPortalController::class, 'visits'])->name('visits');
        Route::get('/payments', [PatientPortalController::class, 'payments'])->name('payments');
        Route::get('/lab-tests/book', [LabController::class, 'bookingForm'])->name('lab-tests.book');
        Route::post('/lab-orders', [LabController::class, 'book'])->middleware('throttle:10,1')->name('lab-orders.store');
        Route::post('/appointments/{appointment}/pay', [PesapalPaymentController::class, 'start'])->middleware('throttle:8,1')->name('appointments.pay');
        Route::post('/medication-orders/{medicationOrder}/pay', [PesapalPaymentController::class, 'startMedication'])->middleware('throttle:8,1')->name('medication-orders.pay');
        Route::post('/lab-orders/{labOrder}/pay', [PesapalPaymentController::class, 'startLab'])->middleware('throttle:8,1')->name('lab-orders.pay');
        Route::get('/payment-transactions/{paymentTransaction}/checkout', [PesapalPaymentController::class, 'checkout'])->name('payments.checkout');
        Route::post('/payment-transactions/{paymentTransaction}/refresh', [PesapalPaymentController::class, 'refresh'])->middleware('throttle:12,1')->name('payments.refresh');
    });

    // Report definitions perform their own permission checks for sensitive report types.
    Route::middleware('permission:reports.view|reports.daily.view|reports.payments.view')->prefix('reports')->name('reports.')->group(function (): void {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/{report}', [ReportController::class, 'show'])->name('show');
    });

    // Lightweight JSON endpoints support appointment forms and patient lookups.
    Route::prefix('api')->name('api.')->group(function (): void {
        Route::get('/doctors/{doctor}/available-slots', [AppointmentController::class, 'availableSlots'])->name('doctors.available-slots');
        Route::get('/patients/search', [AppointmentController::class, 'searchPatients'])->name('patients.search');
        Route::get('/departments/{department}/doctors', [AppointmentController::class, 'doctorsByDepartment'])->name('departments.doctors');
    });
});

require __DIR__.'/auth.php';
