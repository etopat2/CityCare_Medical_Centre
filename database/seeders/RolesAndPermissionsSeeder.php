<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /** @var array<string, array<int, string>> */
    private array $permissionsByRole = [
        'admin' => ['*'],
        'receptionist' => ['patients.view', 'patients.create', 'patients.update', 'appointments.view', 'appointments.create', 'appointments.update', 'appointments.cancel', 'doctors.view', 'schedules.view', 'reports.daily.view', 'reports.export'],
        'doctor' => ['appointments.view', 'consultations.create', 'consultations.view', 'schedules.own.view', 'patients.treated.view', 'reports.view', 'reports.export'],
        'cashier' => ['appointments.view', 'payments.view', 'payments.create', 'payments.update', 'reports.payments.view', 'reports.export'],
        'patient' => ['portal.profile.view', 'portal.appointments.view', 'portal.visits.view', 'portal.payments.view'],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'patients.view', 'patients.create', 'patients.update', 'patients.delete',
            'doctors.view', 'doctors.manage', 'departments.view', 'departments.manage',
            'schedules.view', 'schedules.manage', 'schedules.own.view',
            'appointments.view', 'appointments.create', 'appointments.update', 'appointments.cancel',
            'consultations.create', 'consultations.view', 'patients.treated.view',
            'payments.view', 'payments.create', 'payments.update',
            'reports.view', 'reports.export', 'reports.daily.view', 'reports.payments.view',
            'users.manage', 'portal.profile.view', 'portal.appointments.view', 'portal.visits.view', 'portal.payments.view',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach ($this->permissionsByRole as $roleName => $rolePermissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($rolePermissions[0] === '*' ? Permission::all() : $rolePermissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
