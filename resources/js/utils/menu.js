import { sidebarMenu } from '@/config/sidebarMenu';
import { filterMenuByPermissions } from '@/utils/permissions';

/** @deprecated Use sidebarMenu from @/config/sidebarMenu */
export const menuItems = sidebarMenu;

export function filterMenuItems(items, user) {
    return filterMenuByPermissions(items, user);
}

export const permissionModules = {
    'User Management': [
        'view users', 'create users', 'edit users', 'delete users',
        'assign roles', 'assign permissions',
    ],
    'Patient Management': [
        'view patients', 'create patients', 'edit patients', 'delete patients',
        'search patients',
        'view patient medical history', 'view assigned patients',
        'view limited patient info', 'upload patient documents',
    ],
    'Patient Queue': [
        'view patient queue', 'add patient to queue', 'assign doctor to queue',
        'cancel patient queue', 'start consultation', 'mark patient prescribed',
        'return visit to pending prescription',
    ],
    'Patient Vitals': [
        'view patient vitals', 'create patient vitals', 'edit patient vitals',
        'delete patient vitals', 'view previous patient vitals',
    ],
    'Appointment Management': [
        'view appointments', 'create appointments', 'edit appointments',
        'cancel appointments', 'assign doctor',
    ],
    'Doctor / Medical': [
        'add diagnosis', 'create prescription', 'edit prescription',
        'view prescriptions', 'request lab test', 'view lab reports',
        'view assigned lab reports',
    ],
    'Lab': [
        'view lab requests', 'create lab report', 'edit lab report',
        'approve lab report', 'print lab report',
    ],
    'Pharmacy': ['dispense medicine', 'manage medicine stock'],
    'Medicine Master': [
        'view medicines', 'create medicines', 'edit medicines', 'delete medicines',
        'view medicine dose times', 'create medicine dose times', 'edit medicine dose times', 'delete medicine dose times',
        'view medicine dose from meals', 'create medicine dose from meals', 'edit medicine dose from meals', 'delete medicine dose from meals',
        'select medicines in prescription', 'manage medicine master data',
    ],
    'Billing': [
        'create invoice', 'view invoice', 'edit invoice',
        'receive payment', 'print receipt',
    ],
    'Reports': ['view reports', 'export reports'],
    'System': ['manage settings', 'view audit logs', 'view dashboard'],
    'Patient Self-Service': [
        'view own profile', 'view own appointments', 'view own prescriptions',
        'view own lab reports', 'view own invoices',
    ],
};

export function groupPermissions(allPermissions) {
    const grouped = {};
    const assigned = new Set();

    for (const [module, names] of Object.entries(permissionModules)) {
        const perms = allPermissions.filter((p) => names.includes(p.name));
        if (perms.length) {
            grouped[module] = perms;
            perms.forEach((p) => assigned.add(p.name));
        }
    }

    const other = allPermissions.filter((p) => !assigned.has(p.name));
    if (other.length) grouped['Other'] = other;

    return grouped;
}
