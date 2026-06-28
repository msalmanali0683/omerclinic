<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\UserManagementController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PatientSearchController;
use App\Http\Controllers\Api\PatientQueueController;
use App\Http\Controllers\Api\PatientVitalController;
use App\Http\Controllers\Api\PrescriptionPrintSettingController;
use App\Http\Controllers\Api\PrescriptionController;
use App\Http\Controllers\Api\MedicineDoseFromMealController;
use App\Http\Controllers\Api\MedicineDoseTimeController;
use App\Http\Controllers\Api\MedicineController;
use App\Http\Controllers\Api\ClinicalScanHistoryController;
use App\Http\Controllers\Api\ClinicalScanController;
use App\Http\Controllers\Api\ClinicalScanQueueSearchController;
use App\Http\Controllers\Api\ClinicalScanTemplateController;
use App\Http\Controllers\Api\LaboratoryBillController;
use App\Http\Controllers\Api\LaboratoryHistoryController;
use App\Http\Controllers\Api\LaboratoryPatientController;
use App\Http\Controllers\Api\LaboratoryPatientSearchController;
use App\Http\Controllers\Api\LaboratoryResultAttachmentController;
use App\Http\Controllers\Api\LaboratoryResultController;
use App\Http\Controllers\Api\LaboratoryTestTemplateController;
use App\Http\Controllers\Api\ComplaintMasterController;
use App\Http\Controllers\Api\DiagnosisMedicineTemplateController;
use App\Http\Controllers\Api\DiagnosisMasterController;
use App\Http\Controllers\Api\PatientVisitHistoryController;
use App\Http\Controllers\Api\PatientVisitComplaintController;
use App\Http\Controllers\Api\PatientVisitDiagnosisController;
use App\Http\Controllers\Api\PatientVisitTokenController;
use App\Http\Controllers\Api\PublicLaboratoryReportController;
use App\Http\Controllers\Api\Reports\LaboratoryReportController;
use App\Http\Controllers\Api\Reports\PatientReportController;

/*
|--------------------------------------------------------------------------
| Public Auth Routes (no middleware - accessible before login)
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Public patient lab reports (MR + cell or CNIC verification)
|--------------------------------------------------------------------------
*/
Route::prefix('public/lab-reports')->middleware('throttle:20,1')->group(function () {
    Route::post('verify', [PublicLaboratoryReportController::class, 'verify']);
    Route::post('logout', [PublicLaboratoryReportController::class, 'logout']);
    Route::get('results', [PublicLaboratoryReportController::class, 'results']);
    Route::get('print-data', [PublicLaboratoryReportController::class, 'printDataAll']);
    Route::get('results/{laboratoryResult}/print-data', [PublicLaboratoryReportController::class, 'printData']);
    Route::get('results/{laboratoryResult}/attachments/{attachment}/preview', [PublicLaboratoryReportController::class, 'previewAttachment'])
        ->name('public.lab-reports.attachments.preview');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes (Sanctum session-based)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::patch('/profile', [AuthController::class, 'updateProfile']);

    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])
        ->middleware('permission:view dashboard');

    // Patient search (must be before {patient} routes)
    Route::get('patients/search', [PatientSearchController::class, 'search'])
        ->middleware('permission:search patients');
    Route::get('patients/name-suggestions', [PatientSearchController::class, 'nameSuggestions'])
        ->middleware('permission:search patients');
    Route::get('patient-visits/search', [PatientSearchController::class, 'searchVisits'])
        ->middleware('permission:search patients');

    Route::get('patients/{patient}/visits', [PatientVisitHistoryController::class, 'indexByPatient'])
        ->middleware('role_or_permission:view patient visits|view limited patient visit history');
    Route::get('patients/{patient}/visits/{visit}/details', [PatientVisitHistoryController::class, 'showVisitDetails'])
        ->middleware('role_or_permission:view patient visit details|view limited patient visit history');

    // Patients (personal information)
    Route::get('patients', [PatientController::class, 'index'])
        ->middleware('role_or_permission:view patients|view limited patient info');
    Route::post('patients', [PatientController::class, 'store'])
        ->middleware('permission:create patients');
    Route::get('patients/{patient}', [PatientController::class, 'show'])
        ->middleware('role_or_permission:view patients|view limited patient info');
    Route::match(['put', 'patch'], 'patients/{patient}', [PatientController::class, 'update'])
        ->middleware('permission:edit patients');
    Route::delete('patients/{patient}', [PatientController::class, 'destroy'])
        ->middleware('permission:delete patients');

    // Patient queue
    Route::get('patient-queue', [PatientQueueController::class, 'index'])
        ->middleware('permission:view patient queue');
    Route::post('patient-queue/cancel-stale', [PatientQueueController::class, 'cancelStale'])
        ->middleware('permission:cancel patient queue');
    Route::post('patients/{patient}/add-to-queue', [PatientQueueController::class, 'addToQueue'])
        ->middleware('permission:add patient to queue');

    Route::get('patient-visits/{visit}/token', [PatientVisitTokenController::class, 'showByVisit'])
        ->middleware('permission:view patient tokens');
    Route::post('patient-visits/{visit}/token/generate', [PatientVisitTokenController::class, 'generate'])
        ->middleware('permission:generate patient tokens');
    Route::get('patient-visit-tokens/{token}/print-data', [PatientVisitTokenController::class, 'printData'])
        ->middleware('permission:print patient tokens');
    Route::post('patient-visit-tokens/{token}/reprint', [PatientVisitTokenController::class, 'reprint'])
        ->middleware('permission:reprint patient tokens');

    Route::get('patient-queue/{visit}', [PatientQueueController::class, 'show'])
        ->middleware('permission:view patient queue');
    Route::patch('patient-queue/{visit}/assign-doctor', [PatientQueueController::class, 'assignDoctor'])
        ->middleware('permission:assign doctor to queue');
    Route::patch('patient-queue/{visit}/start-consultation', [PatientQueueController::class, 'startConsultation'])
        ->middleware('permission:start consultation');
    Route::patch('patient-queue/{visit}/mark-prescribed', [PatientQueueController::class, 'markPrescribed'])
        ->middleware('permission:mark patient prescribed');
    Route::patch('patient-queue/{visit}/return-to-pending-prescription', [PatientQueueController::class, 'returnToPendingPrescription'])
        ->middleware('permission:return visit to pending prescription');
    Route::patch('patient-queue/{visit}/cancel', [PatientQueueController::class, 'cancel'])
        ->middleware('permission:cancel patient queue');

    // Patient vitals
    Route::get('patient-vitals', [PatientVitalController::class, 'index'])
        ->middleware('permission:view patient vitals');
    Route::post('patient-vitals', [PatientVitalController::class, 'store'])
        ->middleware('permission:create patient vitals');
    Route::get('patient-vitals/{vital}', [PatientVitalController::class, 'show'])
        ->middleware('permission:view patient vitals');
    Route::match(['put', 'patch'], 'patient-vitals/{vital}', [PatientVitalController::class, 'update'])
        ->middleware('permission:edit patient vitals');
    Route::delete('patient-vitals/{vital}', [PatientVitalController::class, 'destroy'])
        ->middleware('permission:delete patient vitals');
    Route::get('patient-visits/{visit}/vitals/latest', [PatientVitalController::class, 'latestByVisit'])
        ->middleware('permission:view patient vitals');
    Route::get('patient-visits/{visit}/complaints', [PatientVisitComplaintController::class, 'byVisit'])
        ->middleware('permission:view visit complaints');
    Route::get('patient-visits/{visit}/diagnoses', [PatientVisitDiagnosisController::class, 'byVisit'])
        ->middleware('permission:view visit diagnosis');
    Route::get('patients/{patient}/vitals-history', [PatientVitalController::class, 'historyByPatient'])
        ->middleware('permission:view previous patient vitals');

    // Prescriptions (API)
    Route::post('prescriptions', [PrescriptionController::class, 'store'])
        ->middleware('permission:create prescription');
    Route::get('prescriptions/{prescription}/print-data', [PrescriptionController::class, 'printData']);
    Route::get('patient-visits/{visit}/prescription', [PrescriptionController::class, 'showByVisit'])
        ->middleware('permission:view prescriptions');
    Route::patch('patient-visits/{visit}/prescription', [PrescriptionController::class, 'updateByVisit'])
        ->middleware('role_or_permission:edit prescription|update prescription|re-prescribe prescription');
    Route::apiResource('prescriptions', PrescriptionController::class)->except(['store']);
    Route::get('patient-visits/{visit}/prescription-create-data', [PrescriptionController::class, 'prescriptionCreateData'])
        ->middleware('permission:create prescription');
    Route::get('prescription-print-settings', [PrescriptionPrintSettingController::class, 'show']);
    Route::put('prescription-print-settings', [PrescriptionPrintSettingController::class, 'update'])
        ->middleware('permission:manage prescription print settings');

    // Clinical scans
    Route::get('clinical-scans/queue-patients/search', [ClinicalScanQueueSearchController::class, 'search'])
        ->middleware('role_or_permission:create clinical scans|select patient for scan|search queue patients for scan');
    Route::get('clinical-scan-templates/options', [ClinicalScanTemplateController::class, 'options'])
        ->middleware('permission:view clinical scan templates');
    Route::apiResource('clinical-scan-templates', ClinicalScanTemplateController::class);
    Route::get('clinical-scans/{clinicalScan}/print-data', [ClinicalScanController::class, 'printData']);
    Route::get('patient-visits/{visit}/clinical-scans', [ClinicalScanController::class, 'byVisit'])
        ->middleware('role_or_permission:view clinical scans|view patient clinical scan history');
    Route::get('patients/{patient}/clinical-scans-history', [ClinicalScanHistoryController::class, 'byPatient'])
        ->middleware('role_or_permission:view clinical scans|view patient clinical scan history');
    Route::get('patients/{patient}/clinical-scans', [ClinicalScanController::class, 'byPatient'])
        ->middleware('role_or_permission:view clinical scans|view patient clinical scan history');
    Route::apiResource('clinical-scans', ClinicalScanController::class);

    // Laboratory
    Route::get('laboratory/patients', [LaboratoryPatientController::class, 'index'])
        ->middleware('role_or_permission:view lab patients|search patients for laboratory|create lab bills|create laboratory results');
    Route::get('laboratory/patient-visits/search', [LaboratoryPatientSearchController::class, 'search'])
        ->middleware('role_or_permission:view lab patients|search patients for laboratory|create lab bills|create laboratory results');
    Route::post('laboratory/bills', [LaboratoryBillController::class, 'store'])
        ->middleware('permission:create lab bills');
    Route::get('laboratory/bills/{laboratoryBill}/print-data', [LaboratoryBillController::class, 'printData'])
        ->middleware('permission:print lab bills');
    Route::get('laboratory-results/patients-overview', [LaboratoryResultController::class, 'patientsOverview'])
        ->middleware('permission:view laboratory results');
    Route::get('laboratory-results/patients/{patient}/visits-overview', [LaboratoryResultController::class, 'patientVisitsOverview'])
        ->middleware('permission:view laboratory results');
    Route::get('laboratory-results/patients/{patient}/no-visit-tests', [LaboratoryResultController::class, 'noVisitTests'])
        ->middleware('permission:view laboratory results');
    Route::get('laboratory-results/patients/{patient}/visits/{visit}/tests', [LaboratoryResultController::class, 'visitTests'])
        ->middleware('permission:view laboratory results');
    Route::get('laboratory-test-templates/options', [LaboratoryTestTemplateController::class, 'options'])
        ->middleware('permission:view laboratory test templates');
    Route::apiResource('laboratory-test-templates', LaboratoryTestTemplateController::class);
    Route::get('laboratory-results/{laboratoryResult}/print-data', [LaboratoryResultController::class, 'printData']);
    Route::get('patient-visits/{visit}/laboratory-results/print-data', [LaboratoryResultController::class, 'visitPrintData']);
    Route::post('laboratory-results/{laboratoryResult}/verify', [LaboratoryResultController::class, 'verify']);
    Route::post('laboratory-results/{laboratoryResult}/attachments', [LaboratoryResultAttachmentController::class, 'store']);
    Route::delete('laboratory-results/{laboratoryResult}/attachments/{attachment}', [LaboratoryResultAttachmentController::class, 'destroy']);
    Route::get('laboratory-results/{laboratoryResult}/attachments/{attachment}/preview', [LaboratoryResultAttachmentController::class, 'preview'])
        ->name('laboratory-results.attachments.preview');
    Route::get('patient-visits/{visit}/laboratory-results', [LaboratoryResultController::class, 'byVisit'])
        ->middleware('role_or_permission:view laboratory results|view patient laboratory history');
    Route::get('patients/{patient}/laboratory-results', [LaboratoryResultController::class, 'byPatient'])
        ->middleware('role_or_permission:view laboratory results|view patient laboratory history');
    Route::get('patients/{patient}/laboratory-history', [LaboratoryHistoryController::class, 'byPatient'])
        ->middleware('role_or_permission:view laboratory results|view patient laboratory history');
    Route::apiResource('laboratory-results', LaboratoryResultController::class);

    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('patients', [PatientReportController::class, 'index'])
            ->middleware('permission:view patient reports');
        Route::get('patients/print-data', [PatientReportController::class, 'printData'])
            ->middleware('permission:print patient reports');
        Route::get('patients/pdf', [PatientReportController::class, 'pdf'])
            ->middleware('permission:export patient reports pdf');
        Route::get('laboratory', [LaboratoryReportController::class, 'index'])
            ->middleware('permission:view laboratory reports');
        Route::get('laboratory/print-data', [LaboratoryReportController::class, 'printData'])
            ->middleware('permission:print laboratory reports');
        Route::get('laboratory/pdf', [LaboratoryReportController::class, 'pdf'])
            ->middleware('permission:export laboratory reports pdf');
    });

    // Medicine master
    Route::get('medicine-dose-from-meals/options', [MedicineDoseFromMealController::class, 'options']);
    Route::apiResource('medicine-dose-from-meals', MedicineDoseFromMealController::class);
    Route::get('medicine-dose-times/options', [MedicineDoseTimeController::class, 'options']);
    Route::apiResource('medicine-dose-times', MedicineDoseTimeController::class);
    Route::get('medicines/options', [MedicineController::class, 'options']);
    Route::get('medicines/duplicates', [MedicineController::class, 'duplicates']);
    Route::post('medicines/delete-duplicates', [MedicineController::class, 'deleteDuplicates']);
    Route::post('medicines/find-or-create', [MedicineController::class, 'findOrCreate']);
    Route::apiResource('medicines', MedicineController::class)->whereNumber('medicine');

    // Clinical master — complaints & diagnosis
    Route::get('complaint-masters/options', [ComplaintMasterController::class, 'options']);
    Route::post('complaint-masters/find-or-create', [ComplaintMasterController::class, 'findOrCreate']);
    Route::apiResource('complaint-masters', ComplaintMasterController::class)->whereNumber('complaint_master');
    Route::get('diagnosis-masters/options', [DiagnosisMasterController::class, 'options']);
    Route::post('diagnosis-masters/find-or-create', [DiagnosisMasterController::class, 'findOrCreate']);
    Route::get('diagnosis-masters/{diagnosisMaster}/medicine-templates', [DiagnosisMedicineTemplateController::class, 'byDiagnosis'])
        ->whereNumber('diagnosisMaster');
    Route::apiResource('diagnosis-medicine-templates', DiagnosisMedicineTemplateController::class)
        ->whereNumber('diagnosis_medicine_template');
    Route::apiResource('diagnosis-masters', DiagnosisMasterController::class)->whereNumber('diagnosis_master');
    Route::apiResource('patient-visit-complaints', PatientVisitComplaintController::class);
    Route::apiResource('patient-visit-diagnoses', PatientVisitDiagnosisController::class);

    // Users
    Route::get('doctors', [UserManagementController::class, 'doctors'])
        ->middleware('role_or_permission:assign doctor to queue|add patient to queue|view users');
    Route::apiResource('users', UserManagementController::class);
    Route::post('users/{user}/roles', [UserManagementController::class, 'syncRoles']);
    Route::post('users/{user}/permissions', [UserManagementController::class, 'syncPermissions']);

    // Roles
    Route::apiResource('roles', RoleController::class);
    Route::post('roles/{role}/permissions', [RoleController::class, 'syncPermissions']);

    // Permissions
    Route::get('permissions', [PermissionController::class, 'index']);
    Route::post('permissions', [PermissionController::class, 'store']);
    Route::put('permissions/{permission}', [PermissionController::class, 'update']);
    Route::delete('permissions/{permission}', [PermissionController::class, 'destroy']);
});
