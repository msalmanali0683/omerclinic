/**
 * Central sidebar menu configuration.
 * Each item uses permission-based visibility (no role-only checks).
 *
 * Fields:
 * - label: display text
 * - icon: AppIcon name
 * - to: route path (leaf items)
 * - routeName: Vue Router route name (used for default redirects)
 * - permissions: required permission name(s)
 * - permissionMode: 'any' (default) | 'all'
 * - publicAuthenticated: visible to any logged-in user when true
 * - placeholder: shows "Soon" badge; excluded from default redirect
 * - children: nested menu items
 */
export const sidebarMenu = [
    {
        label: 'Dashboard',
        icon: 'dashboard',
        to: '/dashboard',
        routeName: 'dashboard',
        permissions: ['view dashboard'],
    },
    {
        label: 'Administration',
        icon: 'settings',
        permissions: ['view users', 'assign roles', 'assign permissions'],
        permissionMode: 'any',
        children: [
            {
                label: 'Users',
                to: '/admin/users',
                routeName: 'users.index',
                permissions: ['view users'],
            },
            {
                label: 'Roles',
                to: '/admin/roles',
                routeName: 'roles.index',
                permissions: ['assign roles'],
            },
            {
                label: 'Permissions',
                to: '/admin/permissions',
                routeName: 'permissions.index',
                permissions: ['assign permissions'],
            },
        ],
    },
    {
        label: 'Patients',
        icon: 'patients',
        permissions: [
            'view patients',
            'view limited patient info',
            'search patients',
            'create patients',
            'view patient visits',
            'view limited patient visit history',
        ],
        permissionMode: 'any',
        children: [
            {
                label: 'Patient List',
                to: '/patients',
                routeName: 'patients.index',
                permissions: ['view patients', 'view limited patient info'],
                permissionMode: 'any',
            },
            {
                label: 'Register New Patient',
                to: '/patients/create',
                routeName: 'patients.create',
                permissions: ['create patients'],
            },
            {
                label: 'Search Patient',
                to: '/patients/search',
                routeName: 'patients.search',
                permissions: ['search patients', 'view patient visits', 'view limited patient visit history'],
                permissionMode: 'any',
            },
        ],
    },
    {
        label: 'Queue',
        icon: 'calendar',
        permissions: ['view patient queue', 'start consultation', 'create prescription'],
        permissionMode: 'any',
        children: [
            {
                label: 'Patient Queue',
                to: '/queue',
                routeName: 'queue.index',
                permissions: ['view patient queue'],
            },
            {
                label: 'Doctor Queue',
                to: '/doctor-queue',
                routeName: 'queue.doctor',
                permissions: ['start consultation', 'create prescription'],
                permissionMode: 'any',
            },
        ],
    },
    {
        label: 'Medicine Master',
        icon: 'pharmacy',
        permissions: [
            'view medicines',
            'view medicine dose times',
            'view medicine dose from meals',
            'manage medicine master data',
        ],
        permissionMode: 'any',
        children: [
            {
                label: 'Medicines',
                to: '/medicine-master/medicines',
                routeName: 'medicines.index',
                permissions: ['view medicines'],
            },
            {
                label: 'Dose Times',
                to: '/medicine-master/dose-times',
                routeName: 'medicine-dose-times.index',
                permissions: ['view medicine dose times'],
            },
            {
                label: 'Dose From Meal',
                to: '/medicine-master/dose-from-meals',
                routeName: 'medicine-dose-from-meals.index',
                permissions: ['view medicine dose from meals'],
            },
        ],
    },
    {
        label: 'Clinical Master',
        icon: 'prescription',
        permissions: ['view complaint masters', 'view diagnosis masters'],
        permissionMode: 'any',
        children: [
            {
                label: 'Complaint Master',
                to: '/clinical-master/complaints',
                routeName: 'complaint-masters.index',
                permissions: ['view complaint masters'],
            },
            {
                label: 'Diagnosis Master',
                to: '/clinical-master/diagnosis',
                routeName: 'diagnosis-masters.index',
                permissions: ['view diagnosis masters'],
            },
        ],
    },
    {
        label: 'Clinical Scans',
        icon: 'report',
        permissions: [
            'view clinical scans',
            'create clinical scans',
            'view clinical scan templates',
            'create clinical scan templates',
        ],
        permissionMode: 'any',
        children: [
            {
                label: 'Scan Patient',
                to: '/clinical-scans/create',
                routeName: 'clinical-scans.create',
                permissions: ['create clinical scans'],
            },
            {
                label: 'Clinical Scan List',
                to: '/clinical-scans',
                routeName: 'clinical-scans.index',
                permissions: ['view clinical scans'],
            },
            {
                label: 'Scan Templates',
                to: '/clinical-scans/templates',
                routeName: 'clinical-scan-templates.index',
                permissions: ['view clinical scan templates'],
            },
        ],
    },
    {
        label: 'Laboratory',
        icon: 'lab',
        permissions: [
            'view laboratory results',
            'create laboratory results',
            'view laboratory test templates',
            'create laboratory test templates',
        ],
        permissionMode: 'any',
        children: [
            {
                label: 'Test Billing',
                to: '/laboratory/billing',
                routeName: 'laboratory.billing',
                permissions: ['create lab bills'],
            },
            {
                label: 'Laboratory Results',
                to: '/laboratory-results',
                routeName: 'laboratory-results.index',
                permissions: ['view laboratory results', 'create laboratory results'],
                permissionMode: 'any',
            },
            {
                label: 'Test Templates',
                to: '/laboratory-results/templates',
                routeName: 'laboratory-test-templates.index',
                permissions: ['view laboratory test templates'],
            },
        ],
    },
    {
        label: 'Prescriptions',
        icon: 'prescription',
        to: '/prescriptions',
        permissions: ['view prescriptions'],
        placeholder: true,
    },
    {
        label: 'Lab Requests',
        icon: 'lab',
        to: '/lab-requests',
        permissions: ['view lab requests'],
        placeholder: true,
    },
    {
        label: 'Lab Reports',
        icon: 'report',
        to: '/lab-reports',
        permissions: ['view lab reports', 'view assigned lab reports'],
        permissionMode: 'any',
        placeholder: true,
    },
    {
        label: 'Medicine Stock',
        icon: 'pharmacy',
        to: '/medicine-stock',
        permissions: ['manage medicine stock'],
        placeholder: true,
    },
    {
        label: 'Invoices',
        icon: 'invoice',
        to: '/invoices',
        permissions: ['view invoice'],
        placeholder: true,
    },
    {
        label: 'Reports',
        icon: 'chart',
        permissions: [
            'view reports',
            'view patient reports',
            'export patient reports pdf',
            'print patient reports',
            'view laboratory reports',
            'export laboratory reports pdf',
            'print laboratory reports',
        ],
        permissionMode: 'any',
        children: [
            {
                label: 'Patient Report',
                to: '/reports/patients',
                routeName: 'reports.patients',
                permissions: ['view patient reports'],
            },
            {
                label: 'Laboratory Report',
                to: '/reports/laboratory',
                routeName: 'reports.laboratory',
                permissions: ['view laboratory reports'],
            },
        ],
    },
    {
        label: 'Profile',
        icon: 'users',
        to: '/profile',
        routeName: 'profile',
        publicAuthenticated: true,
    },
    {
        label: 'My Appointments',
        icon: 'calendar',
        to: '/my-appointments',
        permissions: ['view own appointments'],
        placeholder: true,
    },
    {
        label: 'My Prescriptions',
        icon: 'prescription',
        to: '/my-prescriptions',
        permissions: ['view own prescriptions'],
        placeholder: true,
    },
    {
        label: 'My Lab Reports',
        icon: 'report',
        to: '/my-lab-reports',
        permissions: ['view own lab reports'],
        placeholder: true,
    },
];
