import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { checkRouteAccess } from '@/utils/permissions';
import { resolveDefaultRoute, resolvePostLoginRedirect } from '@/utils/navigation';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('@/views/auth/Login.vue'),
        meta: { guest: true, layout: 'auth' },
    },
    {
        path: '/',
        name: 'home',
        redirect: '/login',
    },
    {
        path: '/dashboard',
        name: 'dashboard',
        component: () => import('@/views/dashboard/Dashboard.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permission: 'view dashboard', title: 'Dashboard' },
    },
    {
        path: '/admin/users',
        name: 'users.index',
        component: () => import('@/views/admin/users/UsersIndex.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permission: 'view users', title: 'Users' },
    },
    {
        path: '/admin/users/create',
        name: 'users.create',
        component: () => import('@/views/admin/users/UserCreate.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permission: 'create users', title: 'Create User' },
    },
    {
        path: '/admin/users/:id/edit',
        name: 'users.edit',
        component: () => import('@/views/admin/users/UserEdit.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permission: 'edit users', title: 'Edit User' },
    },
    {
        path: '/admin/roles',
        name: 'roles.index',
        component: () => import('@/views/admin/roles/RolesIndex.vue'),
        meta: {
            requiresAuth: true,
            layout: 'dashboard',
            permissions: ['assign roles'],
            title: 'Roles',
        },
    },
    {
        path: '/admin/permissions',
        name: 'permissions.index',
        component: () => import('@/views/admin/permissions/PermissionsIndex.vue'),
        meta: {
            requiresAuth: true,
            layout: 'dashboard',
            permissions: ['assign permissions'],
            title: 'Permissions',
        },
    },
    {
        path: '/patients',
        name: 'patients.index',
        component: () => import('@/views/patients/PatientsIndex.vue'),
        meta: {
            requiresAuth: true,
            layout: 'dashboard',
            permissionAny: ['view patients', 'view limited patient info'],
            title: 'Patients',
        },
    },
    {
        path: '/patients/create',
        name: 'patients.create',
        component: () => import('@/views/patients/PatientCreate.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permission: 'create patients', title: 'Create Patient' },
    },
    {
        path: '/patients/:id/edit',
        name: 'patients.edit',
        component: () => import('@/views/patients/PatientEdit.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permission: 'edit patients', title: 'Edit Patient' },
    },
    {
        path: '/patients/search',
        name: 'patients.search',
        component: () => import('@/views/patients/PatientSearch.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permissions: ['search patients', 'view patient visits', 'view limited patient visit history'], permissionMode: 'any', title: 'Search Patient' },
    },
    {
        path: '/queue',
        name: 'queue.index',
        component: () => import('@/views/queue/PatientQueue.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permission: 'view patient queue', title: 'Patient Queue' },
    },
    {
        path: '/doctor-queue',
        name: 'queue.doctor',
        component: () => import('@/views/queue/DoctorQueue.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permissions: ['view patient queue', 'start consultation', 'create prescription'], permissionMode: 'any', title: 'Doctor Queue' },
    },
    {
        path: '/queue/:id',
        name: 'queue.detail',
        component: () => import('@/views/queue/QueuePatientDetail.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permission: 'view patient queue', title: 'Queue Detail' },
    },
    {
        path: '/medicine-master/dose-from-meals',
        name: 'medicine-dose-from-meals.index',
        component: () => import('@/views/medicine-master/MedicineDoseFromMealsIndex.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permission: 'view medicine dose from meals', title: 'Dose From Meal' },
    },
    {
        path: '/medicine-master/dose-from-meals/create',
        name: 'medicine-dose-from-meals.create',
        component: () => import('@/views/medicine-master/MedicineDoseFromMealCreate.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permission: 'create medicine dose from meals', title: 'Add Dose From Meal' },
    },
    {
        path: '/medicine-master/dose-from-meals/:id/edit',
        name: 'medicine-dose-from-meals.edit',
        component: () => import('@/views/medicine-master/MedicineDoseFromMealEdit.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permission: 'edit medicine dose from meals', title: 'Edit Dose From Meal' },
    },
    {
        path: '/medicine-master/dose-times',
        name: 'medicine-dose-times.index',
        component: () => import('@/views/medicine-master/MedicineDoseTimesIndex.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permission: 'view medicine dose times', title: 'Dose Times' },
    },
    {
        path: '/medicine-master/dose-times/create',
        name: 'medicine-dose-times.create',
        component: () => import('@/views/medicine-master/MedicineDoseTimeCreate.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permission: 'create medicine dose times', title: 'Add Dose Time' },
    },
    {
        path: '/medicine-master/dose-times/:id/edit',
        name: 'medicine-dose-times.edit',
        component: () => import('@/views/medicine-master/MedicineDoseTimeEdit.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permission: 'edit medicine dose times', title: 'Edit Dose Time' },
    },
    {
        path: '/medicine-master/medicines',
        name: 'medicines.index',
        component: () => import('@/views/medicine-master/MedicinesIndex.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permission: 'view medicines', title: 'Medicines' },
    },
    {
        path: '/medicine-master/medicines/create',
        name: 'medicines.create',
        component: () => import('@/views/medicine-master/MedicineCreate.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permission: 'create medicines', title: 'Add Medicine' },
    },
    {
        path: '/medicine-master/medicines/:id/edit',
        name: 'medicines.edit',
        component: () => import('@/views/medicine-master/MedicineEdit.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permission: 'edit medicines', title: 'Edit Medicine' },
    },
    {
        path: '/clinical-master/complaints',
        name: 'complaint-masters.index',
        component: () => import('@/views/clinical-master/ComplaintMastersIndex.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permission: 'view complaint masters', title: 'Complaint Master' },
    },
    {
        path: '/clinical-master/diagnosis',
        name: 'diagnosis-masters.index',
        component: () => import('@/views/clinical-master/DiagnosisMastersIndex.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permission: 'view diagnosis masters', title: 'Diagnosis Master' },
    },
    {
        path: '/clinical-scans/create',
        name: 'clinical-scans.create',
        component: () => import('@/views/clinical-scans/ClinicalScanCreate.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permission: 'create clinical scans', title: 'Clinical Scan' },
    },
    {
        path: '/clinical-scans',
        name: 'clinical-scans.index',
        component: () => import('@/views/clinical-scans/ClinicalScansIndex.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permission: 'view clinical scans', title: 'Clinical Scans' },
    },
    {
        path: '/clinical-scans/templates',
        name: 'clinical-scan-templates.index',
        component: () => import('@/views/clinical-scans/templates/ClinicalScanTemplatesIndex.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permission: 'view clinical scan templates', title: 'Scan Templates' },
    },
    {
        path: '/clinical-scans/templates/create',
        name: 'clinical-scan-templates.create',
        component: () => import('@/views/clinical-scans/templates/ClinicalScanTemplateCreate.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permission: 'create clinical scan templates', title: 'Create Scan Template' },
    },
    {
        path: '/clinical-scans/templates/:id/edit',
        name: 'clinical-scan-templates.edit',
        component: () => import('@/views/clinical-scans/templates/ClinicalScanTemplateEdit.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permission: 'edit clinical scan templates', title: 'Edit Scan Template' },
    },
    {
        path: '/clinical-scans/:id/edit',
        name: 'clinical-scans.edit',
        component: () => import('@/views/clinical-scans/ClinicalScanEdit.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permission: 'edit clinical scans', title: 'Edit Clinical Scan' },
    },
    {
        path: '/clinical-scans/:id',
        name: 'clinical-scans.show',
        component: () => import('@/views/clinical-scans/ClinicalScanDetail.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permission: 'view clinical scans', title: 'Clinical Scan Detail' },
    },
    {
        path: '/laboratory/billing',
        name: 'laboratory.billing',
        component: () => import('@/views/laboratory/LaboratoryBillCreate.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permission: 'create lab bills', title: 'Laboratory Test Billing' },
    },
    {
        path: '/laboratory-results/create',
        name: 'laboratory-results.create',
        component: () => import('@/views/laboratory/LaboratoryResultCreate.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permission: 'create laboratory results', title: 'Laboratory Result Entry' },
    },
    {
        path: '/laboratory-results',
        name: 'laboratory-results.index',
        component: () => import('@/views/laboratory/LaboratoryResultsIndex.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permission: 'view laboratory results', title: 'Laboratory Results' },
    },
    {
        path: '/laboratory-results/templates',
        name: 'laboratory-test-templates.index',
        component: () => import('@/views/laboratory/templates/LaboratoryTestTemplatesIndex.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permission: 'view laboratory test templates', title: 'Laboratory Test Templates' },
    },
    {
        path: '/laboratory-results/templates/create',
        name: 'laboratory-test-templates.create',
        component: () => import('@/views/laboratory/templates/LaboratoryTestTemplateCreate.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permission: 'create laboratory test templates', title: 'Create Laboratory Test Template' },
    },
    {
        path: '/laboratory-results/templates/:id/edit',
        name: 'laboratory-test-templates.edit',
        component: () => import('@/views/laboratory/templates/LaboratoryTestTemplateEdit.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permission: 'edit laboratory test templates', title: 'Edit Laboratory Test Template' },
    },
    {
        path: '/laboratory-results/:id/edit',
        name: 'laboratory-results.edit',
        component: () => import('@/views/laboratory/LaboratoryResultEdit.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permission: 'edit laboratory results', title: 'Edit Laboratory Result' },
    },
    {
        path: '/laboratory-results/:id',
        name: 'laboratory-results.show',
        component: () => import('@/views/laboratory/LaboratoryResultDetail.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permission: 'view laboratory results', title: 'Laboratory Result Detail' },
    },
    {
        path: '/reports/patients',
        name: 'reports.patients',
        component: () => import('@/views/reports/PatientReport.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permission: 'view patient reports', title: 'Patient Report' },
    },
    {
        path: '/reports/laboratory',
        name: 'reports.laboratory',
        component: () => import('@/views/reports/LaboratoryReport.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', permission: 'view laboratory reports', title: 'Laboratory Report' },
    },
    {
        path: '/profile',
        name: 'profile',
        component: () => import('@/views/profile/Profile.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', title: 'Profile' },
    },
    {
        path: '/no-access',
        name: 'no-access',
        component: () => import('@/views/errors/NoAccess.vue'),
        meta: { requiresAuth: true, layout: 'dashboard', title: 'No Access' },
    },
    {
        path: '/403',
        name: 'unauthorized',
        component: () => import('@/views/errors/Unauthorized.vue'),
        meta: { layout: 'auth', title: 'Unauthorized' },
    },
    {
        path: '/:pathMatch(.*)*',
        name: 'not-found',
        component: () => import('@/views/errors/NotFound.vue'),
        meta: { layout: 'auth', title: 'Not Found' },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior: () => ({ top: 0 }),
});

router.beforeEach(async (to) => {
    const authStore = useAuthStore();

    if (!authStore.initialized) {
        await authStore.fetchUser();
    } else if (authStore.isAuthenticated && to.meta.requiresAuth) {
        await authStore.refreshUser();
    }

    if (to.name === 'home') {
        if (!authStore.isAuthenticated) {
            return { name: 'login' };
        }

        return resolveDefaultRoute(authStore.user);
    }

    if (to.meta.guest && authStore.isAuthenticated) {
        return resolveDefaultRoute(authStore.user);
    }

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    if (to.name !== 'no-access' && to.meta.requiresAuth && !checkRouteAccess(to.meta, authStore.user)) {
        return { name: 'unauthorized' };
    }

    document.title = to.meta.title
        ? `${to.meta.title} | Hospital Admin`
        : 'Hospital Admin';
});

export default router;
