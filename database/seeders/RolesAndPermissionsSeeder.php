<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Define all permissions grouped by categories
        $permissions = [
            // User Management
            'view users',
            'create users',
            'edit users',
            'delete users',
            'assign roles',
            'assign permissions',

            // Patient Management
            'view patients',
            'create patients',
            'edit patients',
            'delete patients',
            'view patient medical history',
            'view assigned patients',
            'view limited patient info',
            'search patients',
            'upload patient documents',

            // Visit History
            'view patient visits',
            'view patient visit details',
            'view full patient visit history',
            'view limited patient visit history',

            // Patient Queue
            'view patient queue',
            'view all patient queue',
            'add patient to queue',
            'assign doctor to queue',
            'cancel patient queue',
            'start consultation',
            'mark patient prescribed',
            'return visit to pending prescription',

            // Patient Tokens
            'view patient tokens',
            'generate patient tokens',
            'print patient tokens',
            'reprint patient tokens',

            // Patient Vitals
            'view patient vitals',
            'create patient vitals',
            'edit patient vitals',
            'delete patient vitals',
            'view previous patient vitals',

            // Appointment Management
            'view appointments',
            'create appointments',
            'edit appointments',
            'cancel appointments',
            'assign doctor',

            // Doctor / Medical
            'add diagnosis',
            'create prescription',
            'edit prescription',
            'update prescription',
            're-prescribe prescription',
            'delete prescription',
            'view prescriptions',
            'print prescription',
            'request lab test',
            'view lab reports',
            'view assigned lab reports',

            // Lab
            'view lab requests',
            'create lab report',
            'edit lab report',
            'approve lab report',
            'print lab report',

            // Pharmacy
            'dispense medicine',
            'manage medicine stock',

            // Medicine Master
            'view medicine dose from meals',
            'create medicine dose from meals',
            'edit medicine dose from meals',
            'delete medicine dose from meals',
            'view medicine dose times',
            'create medicine dose times',
            'edit medicine dose times',
            'delete medicine dose times',
            'view medicines',
            'create medicines',
            'edit medicines',
            'delete medicines',
            'select medicines in prescription',
            'manage medicine master data',

            // Complaint Master
            'view complaint masters',
            'create complaint masters',
            'edit complaint masters',
            'delete complaint masters',

            // Diagnosis Master
            'view diagnosis masters',
            'create diagnosis masters',
            'edit diagnosis masters',
            'delete diagnosis masters',

            // Diagnosis Medicine Templates
            'view diagnosis medicine templates',
            'create diagnosis medicine templates',
            'edit diagnosis medicine templates',
            'delete diagnosis medicine templates',
            'use diagnosis medicine templates in prescription',

            // Visit Complaints
            'view visit complaints',
            'create visit complaints',
            'edit visit complaints',
            'delete visit complaints',

            // Visit Diagnosis
            'view visit diagnosis',
            'create visit diagnosis',
            'edit visit diagnosis',
            'delete visit diagnosis',

            // Prescription helpers
            'add complaints during prescription',
            'add diagnosis during prescription',

            // Clinical Scans
            'view clinical scan templates',
            'create clinical scan templates',
            'edit clinical scan templates',
            'delete clinical scan templates',
            'view clinical scans',
            'create clinical scans',
            'edit clinical scans',
            'delete clinical scans',
            'view patient clinical scan history',
            'print clinical scans',
            'search queue patients for scan',
            'select patient for scan',

            // Laboratory
            'view laboratory test templates',
            'create laboratory test templates',
            'edit laboratory test templates',
            'delete laboratory test templates',
            'view laboratory results',
            'create laboratory results',
            'edit laboratory results',
            'delete laboratory results',
            'verify laboratory results',
            'view patient laboratory history',
            'print laboratory results',
            'search patients for laboratory',
            'select patient for laboratory',

            // Billing
            'create invoice',
            'view invoice',
            'edit invoice',
            'receive payment',
            'print receipt',

            // Reports
            'view reports',
            'export reports',
            'view patient reports',
            'export patient reports pdf',
            'print patient reports',
            'view laboratory reports',
            'export laboratory reports pdf',
            'print laboratory reports',

            // System
            'view dashboard',
            'manage settings',
            'view audit logs',

            // Patient Specific (Self-service)
            'view own profile',
            'view own appointments',
            'view own prescriptions',
            'view own lab reports',
            'view own invoices',
        ];

        // Create permissions idempotently
        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web'
            ]);
        }

        // 2. Define Roles and map their permissions
        $roleMappings = [
            'super-admin' => [], // Will be given all permissions dynamically

            'hospital-admin' => array_filter($permissions, function ($p) {
                // hospital-admin has all permissions except destructive/super-system actions (if any)
                return !in_array($p, ['manage settings']); // hide core system-level config
            }),

            'doctor' => [
                'view dashboard',
                'view patients',
                'create patients',
                'search patients',
                'view limited patient info',
                'view assigned patients',
                'view patient medical history',
                'view patient visits',
                'view patient visit details',
                'view full patient visit history',
                'view patient queue',
                'add patient to queue',
                'start consultation',
                'mark patient prescribed',
                'view patient vitals',
                'create patient vitals',
                'edit patient vitals',
                'view previous patient vitals',
                'add diagnosis',
                'create prescription',
                'edit prescription',
                'update prescription',
                're-prescribe prescription',
                'view prescriptions',
                'print prescription',
                'request lab test',
                'view lab reports',
                'view assigned lab reports',
                'view appointments',
                'view medicines',
                'view medicine dose times',
                'view medicine dose from meals',
                'select medicines in prescription',
                'view complaint masters',
                'view diagnosis masters',
                'view diagnosis medicine templates',
                'use diagnosis medicine templates in prescription',
                'view visit complaints',
                'create visit complaints',
                'edit visit complaints',
                'delete visit complaints',
                'view visit diagnosis',
                'create visit diagnosis',
                'edit visit diagnosis',
                'delete visit diagnosis',
                'add complaints during prescription',
                'add diagnosis during prescription',
                'view clinical scans',
                'view patient clinical scan history',
                'print clinical scans',
                'view laboratory results',
                'view patient laboratory history',
                'print laboratory results',
                'view patient tokens',
                'generate patient tokens',
                'print patient tokens',
                'reprint patient tokens',
                'view patient reports',
                'view laboratory reports',
            ],

            'scan-operator' => [
                'view dashboard',
                'view limited patient info',
                'search queue patients for scan',
                'select patient for scan',
                'view clinical scan templates',
                'view clinical scans',
                'create clinical scans',
                'edit clinical scans',
                'view patient clinical scan history',
                'print clinical scans',
            ],

            'nurse' => [
                'view dashboard',
                'view patients',
                'search patients',
                'view assigned patients',
                'view limited patient info',
                'view patient visits',
                'view patient visit details',
                'view limited patient visit history',
                'view patient queue',
                'add patient to queue',
                'return visit to pending prescription',
                'view patient vitals',
                'create patient vitals',
                'edit patient vitals',
                'view previous patient vitals',
                'view appointments',
                'upload patient documents',
                'view medicines',
                'view medicine dose times',
                'view medicine dose from meals',
                'view complaint masters',
                'view diagnosis masters',
                'view visit complaints',
                'create visit complaints',
                'edit visit complaints',
                'view clinical scans',
                'view patient clinical scan history',
                'view laboratory results',
                'view patient laboratory history',
            ],

            'receptionist' => [
                'view dashboard',
                'view patients',
                'create patients',
                'edit patients',
                'search patients',
                'view limited patient info',
                'view patient visits',
                'view limited patient visit history',
                'view patient queue',
                'add patient to queue',
                'assign doctor to queue',
                'cancel patient queue',
                'return visit to pending prescription',
                'view patient vitals',
                'create patient vitals',
                'view appointments',
                'create appointments',
                'edit appointments',
                'cancel appointments',
                'assign doctor',
                'view patient tokens',
                'generate patient tokens',
                'print patient tokens',
                'reprint patient tokens',
                'view patient reports',
                'print patient reports',
                'view laboratory reports',
                'print laboratory reports',
            ],

            'data-entry-operator' => [
                'view dashboard',
                'view patients',
                'create patients',
                'edit patients',
                'search patients',
                'view limited patient info',
                'view patient visits',
                'view limited patient visit history',
                'view patient queue',
                'add patient to queue',
                'return visit to pending prescription',
                'view patient vitals',
                'create patient vitals',
                'upload patient documents',
                'view patient tokens',
                'generate patient tokens',
                'print patient tokens',
                'reprint patient tokens',
                'view patient reports',
                'view laboratory reports',
            ],

            'lab-technician' => [
                'view dashboard',
                'view limited patient info',
                'view patient visits',
                'view limited patient visit history',
                'view lab requests',
                'create lab report',
                'edit lab report',
                'print lab report',
                'view laboratory test templates',
                'view laboratory results',
                'create laboratory results',
                'edit laboratory results',
                'verify laboratory results',
                'view patient laboratory history',
                'print laboratory results',
                'search patients for laboratory',
                'select patient for laboratory',
                'view laboratory reports',
                'print laboratory reports',
            ],

            'lab-operator' => [
                'view dashboard',
                'view limited patient info',
                'view laboratory test templates',
                'view laboratory results',
                'create laboratory results',
                'edit laboratory results',
                'view patient laboratory history',
                'print laboratory results',
                'search patients for laboratory',
                'select patient for laboratory',
                'view laboratory reports',
                'print laboratory reports',
            ],

            'lab-manager' => [
                'view dashboard',
                'view lab requests',
                'create lab report',
                'edit lab report',
                'approve lab report',
                'print lab report',
                'view reports',
                'view laboratory reports',
                'print laboratory reports',
                'export laboratory reports pdf',
            ],

            'pharmacist' => [
                'view dashboard',
                'view limited patient info',
                'view patient visits',
                'view patient visit details',
                'view prescriptions',
                'print prescription',
                'dispense medicine',
                'manage medicine stock',
                'view medicines',
                'create medicines',
                'edit medicines',
                'delete medicines',
                'view medicine dose times',
                'create medicine dose times',
                'edit medicine dose times',
                'delete medicine dose times',
                'view medicine dose from meals',
                'create medicine dose from meals',
                'edit medicine dose from meals',
                'delete medicine dose from meals',
                'select medicines in prescription',
                'manage medicine master data',
                'view visit diagnosis',
                'view diagnosis medicine templates',
                'create diagnosis medicine templates',
                'edit diagnosis medicine templates',
                'use diagnosis medicine templates in prescription',
            ],

            'accountant' => [
                'view dashboard',
                'view limited patient info',
                'view patient visits',
                'view limited patient visit history',
                'create invoice',
                'view invoice',
                'edit invoice',
                'receive payment',
                'print receipt',
                'view reports',
                'export reports',
            ],

            'patient' => [
                'view dashboard',
                'view own profile',
                'view own appointments',
                'view own prescriptions',
                'view own lab reports',
                'view own invoices',
            ],
        ];

        // Create roles and sync permissions idempotently
        foreach ($roleMappings as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web'
            ]);

            if ($roleName === 'super-admin') {
                // super-admin gets all permissions
                $role->syncPermissions(Permission::all());
            } else {
                $role->syncPermissions($rolePermissions);
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
