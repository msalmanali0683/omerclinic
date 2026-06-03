<?php

namespace App\Providers;

use App\Models\ClinicalScan;
use App\Models\ClinicalScanTemplate;
use App\Models\LaboratoryBill;
use App\Models\LaboratoryResult;
use App\Models\LaboratoryTestTemplate;
use App\Models\ComplaintMaster;
use App\Models\DiagnosisMaster;
use App\Models\DiagnosisMedicineTemplate;
use App\Models\Medicine;
use App\Models\MedicineDoseFromMeal;
use App\Models\MedicineDoseTime;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\PatientVisitComplaint;
use App\Models\PatientVisitDiagnosis;
use App\Models\PatientVisitToken;
use App\Models\PatientVital;
use App\Models\Prescription;
use App\Models\User;
use App\Policies\ClinicalScanPolicy;
use App\Policies\ClinicalScanTemplatePolicy;
use App\Policies\LaboratoryBillPolicy;
use App\Policies\LaboratoryResultPolicy;
use App\Policies\LaboratoryTestTemplatePolicy;
use App\Policies\ComplaintMasterPolicy;
use App\Policies\DiagnosisMasterPolicy;
use App\Policies\DiagnosisMedicineTemplatePolicy;
use App\Policies\MedicineDoseFromMealPolicy;
use App\Policies\MedicineDoseTimePolicy;
use App\Policies\MedicinePolicy;
use App\Policies\PatientPolicy;
use App\Policies\PatientVisitComplaintPolicy;
use App\Policies\PatientVisitDiagnosisPolicy;
use App\Policies\PatientVitalPolicy;
use App\Policies\PatientVisitPolicy;
use App\Policies\PatientVisitTokenPolicy;
use App\Policies\PrescriptionPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        date_default_timezone_set(config('app.timezone'));

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Patient::class, PatientPolicy::class);
        Gate::policy(PatientVisit::class, PatientVisitPolicy::class);
        Gate::policy(PatientVital::class, PatientVitalPolicy::class);
        Gate::policy(Prescription::class, PrescriptionPolicy::class);
        Gate::policy(MedicineDoseFromMeal::class, MedicineDoseFromMealPolicy::class);
        Gate::policy(MedicineDoseTime::class, MedicineDoseTimePolicy::class);
        Gate::policy(Medicine::class, MedicinePolicy::class);
        Gate::policy(ComplaintMaster::class, ComplaintMasterPolicy::class);
        Gate::policy(DiagnosisMaster::class, DiagnosisMasterPolicy::class);
        Gate::policy(DiagnosisMedicineTemplate::class, DiagnosisMedicineTemplatePolicy::class);
        Gate::policy(PatientVisitComplaint::class, PatientVisitComplaintPolicy::class);
        Gate::policy(PatientVisitDiagnosis::class, PatientVisitDiagnosisPolicy::class);
        Gate::policy(ClinicalScanTemplate::class, ClinicalScanTemplatePolicy::class);
        Gate::policy(ClinicalScan::class, ClinicalScanPolicy::class);
        Gate::policy(LaboratoryTestTemplate::class, LaboratoryTestTemplatePolicy::class);
        Gate::policy(LaboratoryBill::class, LaboratoryBillPolicy::class);
        Gate::policy(LaboratoryResult::class, LaboratoryResultPolicy::class);
        Gate::policy(PatientVisitToken::class, PatientVisitTokenPolicy::class);
    }
}
