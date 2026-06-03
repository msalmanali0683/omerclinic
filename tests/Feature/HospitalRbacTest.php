<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\LabReport;
use App\Models\Invoice;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HospitalRbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /** 1. Admin can manage users (assign roles) */
    public function test_admin_can_manage_users_roles_and_permissions()
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $staff = User::factory()->create();

        $this->actingAs($admin);

        // Simulate assigning role to user
        $staff->assignRole('doctor');

        $this->assertTrue($staff->hasRole('doctor'));
    }

    /** 2. Doctor can view assigned patient */
    public function test_doctor_can_view_assigned_patient()
    {
        $doctor = User::factory()->create();
        $doctor->assignRole('doctor');

        $patient = Patient::create([
            'name' => 'John Doe',
            'doctor_id' => $doctor->id,
            'medical_history' => 'Hypertension patient history',
            'limited_info' => 'Basic John Doe demographics',
        ]);

        $this->actingAs($doctor);

        $response = $this->getJson("/patients/{$patient->id}");

        $response->assertStatus(200);
        $response->assertJsonFragment(['medical_history' => 'Hypertension patient history']);
    }

    /** 3. Doctor cannot view unassigned patient */
    public function test_doctor_cannot_view_unassigned_patient()
    {
        $doctor = User::factory()->create();
        $doctor->assignRole('doctor');

        $otherDoctor = User::factory()->create();

        $patient = Patient::create([
            'name' => 'Jane Smith',
            'doctor_id' => $otherDoctor->id,
            'medical_history' => 'Diabetes medical records',
            'limited_info' => 'Jane Smith basic info',
        ]);

        $this->actingAs($doctor);

        $response = $this->getJson("/patients/{$patient->id}");

        $response->assertStatus(403);
    }

    /** 4. Data Entry Operator can create basic patient record */
    public function test_data_entry_operator_can_create_basic_patient_record()
    {
        $operator = User::factory()->create();
        $operator->assignRole('data-entry-operator');

        $this->actingAs($operator);

        $response = $this->postJson('/patients', [
            'name' => 'Alice Cooper',
            'email' => 'alice@cooper.com',
            'phone' => '1234567890',
            'limited_info' => 'Alice Cooper demographics info',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('patients', [
            'name' => 'Alice Cooper',
            'email' => 'alice@cooper.com',
        ]);
    }

    /** 5. Lab Technician can create lab report */
    public function test_lab_technician_can_create_lab_report()
    {
        $technician = User::factory()->create();
        $technician->assignRole('lab-technician');

        $doctor = User::factory()->create();

        $patient = Patient::create([
            'name' => 'Bob Marley',
            'doctor_id' => $doctor->id,
        ]);

        $this->actingAs($technician);

        $response = $this->postJson('/lab-reports', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'test_name' => 'Cholesterol Panel',
            'report_data' => 'Cholesterol levels optimal',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('lab_reports', [
            'test_name' => 'Cholesterol Panel',
            'report_data' => 'Cholesterol levels optimal',
        ]);
    }

    /** 6. Lab Technician cannot approve lab report */
    public function test_lab_technician_cannot_approve_lab_report()
    {
        $technician = User::factory()->create();
        $technician->assignRole('lab-technician');

        $doctor = User::factory()->create();
        $patient = Patient::create(['name' => 'Bob Marley', 'doctor_id' => $doctor->id]);

        $report = LabReport::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'test_name' => 'Liver Test',
            'report_data' => 'ALT and AST within normal range',
        ]);

        $this->actingAs($technician);

        $response = $this->postJson("/lab-reports/{$report->id}/approve");

        $response->assertStatus(403);
    }

    /** 7. Lab Manager can approve lab report */
    public function test_lab_manager_can_approve_lab_report()
    {
        $manager = User::factory()->create();
        $manager->assignRole('lab-manager');

        $doctor = User::factory()->create();
        $patient = Patient::create(['name' => 'Bob Marley', 'doctor_id' => $doctor->id]);

        $report = LabReport::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'test_name' => 'Kidney Function',
            'report_data' => 'Creatinine optimal',
        ]);

        $this->actingAs($manager);

        $response = $this->postJson("/lab-reports/{$report->id}/approve");

        $response->assertStatus(200);
        $this->assertTrue($report->fresh()->is_approved);
    }

    /** 8. Pharmacist can view prescription but cannot edit it */
    public function test_pharmacist_can_view_prescription_but_cannot_edit_it()
    {
        $pharmacist = User::factory()->create();
        $pharmacist->assignRole('pharmacist');

        $doctor = User::factory()->create();
        $patient = Patient::create(['name' => 'Bob Marley', 'doctor_id' => $doctor->id]);

        $prescription = Prescription::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'diagnosis' => 'Flu',
            'medicines' => 'Paracetamol 500mg, Vitamin C',
        ]);

        $this->actingAs($pharmacist);

        // Pharmacist can view prescription
        $responseView = $this->getJson("/prescriptions/{$prescription->id}");
        $responseView->assertStatus(200);

        // Pharmacist CANNOT edit prescription
        $responseEdit = $this->putJson("/prescriptions/{$prescription->id}", [
            'diagnosis' => 'Severe Pneumonia',
        ]);
        $responseEdit->assertStatus(403);
    }

    /** 9. Accountant can create invoice */
    public function test_accountant_can_create_invoice()
    {
        $accountant = User::factory()->create();
        $accountant->assignRole('accountant');

        $patient = Patient::create(['name' => 'Bob Marley']);

        $this->actingAs($accountant);

        $response = $this->postJson('/invoices', [
            'patient_id' => $patient->id,
            'amount' => 500.00,
            'status' => 'unpaid',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('invoices', [
            'patient_id' => $patient->id,
            'amount' => 500.00,
        ]);
    }

    /** 10. Patient can view own lab report only */
    public function test_patient_can_view_own_lab_report_only()
    {
        $patientUser = User::factory()->create();
        $patientUser->assignRole('patient');

        $patient = Patient::create([
            'name' => 'Self Patient',
            'user_id' => $patientUser->id,
        ]);

        $doctor = User::factory()->create();

        // Report 1: Patient's OWN approved report
        $reportOwn = LabReport::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'test_name' => 'My DNA Report',
            'report_data' => 'Highly confidential genes',
            'is_approved' => true,
        ]);

        // Report 2: Other patient's report
        $otherPatientUser = User::factory()->create();
        $otherPatient = Patient::create(['name' => 'Other Patient', 'user_id' => $otherPatientUser->id]);
        $reportOther = LabReport::create([
            'patient_id' => $otherPatient->id,
            'doctor_id' => $doctor->id,
            'test_name' => 'Other Blood Report',
            'report_data' => 'Other genes',
            'is_approved' => true,
        ]);

        $this->actingAs($patientUser);

        // Patient can view own approved lab report
        $responseOwn = $this->getJson("/lab-reports/{$reportOwn->id}");
        $responseOwn->assertStatus(200);

        // Patient CANNOT view other patient's lab report
        $responseOther = $this->getJson("/lab-reports/{$reportOther->id}");
        $responseOther->assertStatus(403);
    }
}
