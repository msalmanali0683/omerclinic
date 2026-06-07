/**
 * Central sidebar menu configuration.
 * Each item uses permission-based visibility (no role-only checks).
 *
 * Fields:
 * - label: display text
 * - icon: AppIcon name
 * - color: theme color key for sidebar badges
 * - to: route path (leaf items)
 * - routeName: Vue Router route name (used for default redirects)
 * - permissions: required permission name(s)
 * - permissionMode: 'any' (default) | 'all'
 * - publicAuthenticated: visible to any logged-in user when true
 * - hidden: excluded from sidebar (unfinished features)
 * - children: nested menu items
 */
export const sidebarMenu = [
    {
        label: 'Dashboard',
        icon: 'dashboard',
        color: 'teal',
        to: '/dashboard',
        routeName: 'dashboard',
        permissions: ['view dashboard'],
    },
    {
        label: 'Administration',
        icon: 'settings',
        color: 'indigo',
        permissions: ['view users', 'assign roles', 'assign permissions', 'manage prescription print settings'],
        permissionMode: 'any',
        children: [
            {
                label: 'Users',
                icon: 'users',
                to: '/admin/users',
                routeName: 'users.index',
                permissions: ['view users'],
            },
            {
                label: 'Roles',
                icon: 'shield',
                to: '/admin/roles',
                routeName: 'roles.index',
                permissions: ['assign roles'],
            },
            {
                label: 'Permissions',
                icon: 'key',
                to: '/admin/permissions',
                routeName: 'permissions.index',
                permissions: ['assign permissions'],
            },
            {
                label: 'Print Settings',
                icon: 'prescription',
                to: '/admin/prescription-print-settings',
                routeName: 'prescription-print-settings.index',
                permissions: ['manage prescription print settings'],
            },
        ],
    },
    {
        label: 'Patients',
        icon: 'patients',
        color: 'rose',
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
                icon: 'list',
                to: '/patients',
                routeName: 'patients.index',
                permissions: ['view patients', 'view limited patient info'],
                permissionMode: 'any',
            },
            {
                label: 'Register New Patient',
                icon: 'user-plus',
                to: '/patients/create',
                routeName: 'patients.create',
                permissions: ['create patients'],
            },
            {
                label: 'Search Patient',
                icon: 'search',
                to: '/patients/search',
                routeName: 'patients.search',
                permissions: ['search patients', 'view patient visits', 'view limited patient visit history'],
                permissionMode: 'any',
            },
        ],
    },
    {
        label: 'Queue',
        icon: 'queue',
        color: 'amber',
        permissions: ['view patient queue', 'start consultation', 'create prescription'],
        permissionMode: 'any',
        children: [
            {
                label: 'Patient Queue',
                icon: 'queue',
                to: '/queue',
                routeName: 'queue.index',
                permissions: ['view patient queue'],
            },
            {
                label: 'Doctor Queue',
                icon: 'stethoscope',
                to: '/doctor-queue',
                routeName: 'queue.doctor',
                permissions: ['start consultation', 'create prescription'],
                permissionMode: 'any',
            },
        ],
    },
    {
        label: 'Medicine Master',
        icon: 'pill',
        color: 'emerald',
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
                icon: 'pill',
                to: '/medicine-master/medicines',
                routeName: 'medicines.index',
                permissions: ['view medicines'],
            },
            {
                label: 'Dose Times',
                icon: 'clock',
                to: '/medicine-master/dose-times',
                routeName: 'medicine-dose-times.index',
                permissions: ['view medicine dose times'],
            },
            {
                label: 'Dose From Meal',
                icon: 'meal',
                to: '/medicine-master/dose-from-meals',
                routeName: 'medicine-dose-from-meals.index',
                permissions: ['view medicine dose from meals'],
            },
        ],
    },
    {
        label: 'Clinical Master',
        icon: 'prescription',
        color: 'violet',
        permissions: ['view complaint masters', 'view diagnosis masters'],
        permissionMode: 'any',
        children: [
            {
                label: 'Complaint Master',
                icon: 'clipboard',
                to: '/clinical-master/complaints',
                routeName: 'complaint-masters.index',
                permissions: ['view complaint masters'],
            },
            {
                label: 'Diagnosis Master',
                icon: 'diagnosis',
                to: '/clinical-master/diagnosis',
                routeName: 'diagnosis-masters.index',
                permissions: ['view diagnosis masters'],
            },
        ],
    },
    {
        label: 'Clinical Scans',
        icon: 'scan',
        color: 'sky',
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
                icon: 'scan',
                to: '/clinical-scans/create',
                routeName: 'clinical-scans.create',
                permissions: ['create clinical scans'],
            },
            {
                label: 'Clinical Scan List',
                icon: 'list',
                to: '/clinical-scans',
                routeName: 'clinical-scans.index',
                permissions: ['view clinical scans'],
            },
            {
                label: 'Scan Templates',
                icon: 'template',
                to: '/clinical-scans/templates',
                routeName: 'clinical-scan-templates.index',
                permissions: ['view clinical scan templates'],
            },
        ],
    },
    {
        label: 'Laboratory',
        icon: 'lab',
        color: 'blue',
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
                icon: 'billing',
                to: '/laboratory/billing',
                routeName: 'laboratory.billing',
                permissions: ['create lab bills'],
            },
            {
                label: 'Laboratory Results',
                icon: 'results',
                to: '/laboratory-results',
                routeName: 'laboratory-results.index',
                permissions: ['view laboratory results', 'create laboratory results'],
                permissionMode: 'any',
            },
            {
                label: 'Test Templates',
                icon: 'template',
                to: '/laboratory-results/templates',
                routeName: 'laboratory-test-templates.index',
                permissions: ['view laboratory test templates'],
            },
        ],
    },
    {
        label: 'Reports',
        icon: 'chart',
        color: 'fuchsia',
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
                icon: 'report',
                to: '/reports/patients',
                routeName: 'reports.patients',
                permissions: ['view patient reports'],
            },
            {
                label: 'Laboratory Report',
                icon: 'lab',
                to: '/reports/laboratory',
                routeName: 'reports.laboratory',
                permissions: ['view laboratory reports'],
            },
        ],
    },
    {
        label: 'Profile',
        icon: 'user',
        color: 'slate',
        to: '/profile',
        routeName: 'profile',
        publicAuthenticated: true,
    },
];
