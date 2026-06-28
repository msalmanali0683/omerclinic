<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientPersonalInfoApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_guest_cannot_access_patient_apis(): void
    {
        $this->getJson('/api/patients')->assertUnauthorized();
        $this->postJson('/api/patients', [])->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_create_patient(): void
    {
        $user = $this->makeUser('lab-technician');

        $this->actingAs($user)
            ->postJson('/api/patients', $this->validPayload())
            ->assertForbidden();
    }

    public function test_receptionist_can_create_patient(): void
    {
        $user = $this->makeUser('receptionist');

        $this->actingAs($user)
            ->postJson('/api/patients', $this->validPayload())
            ->assertCreated()
            ->assertJsonPath('patient.patient_name', 'Ali Khan');

        $this->assertDatabaseHas('patients', [
            'patient_name' => 'Ali Khan',
            'patient_cell' => '03001234567',
        ]);
    }

    public function test_data_entry_operator_can_create_patient(): void
    {
        $user = $this->makeUser('data-entry-operator');

        $this->actingAs($user)
            ->postJson('/api/patients', $this->validPayload())
            ->assertCreated()
            ->assertJsonStructure(['patient' => ['mr_number', 'patient_name']]);
    }

    public function test_user_with_edit_patients_can_update_patient(): void
    {
        $user = $this->makeUser('receptionist');
        $patient = Patient::create([
            'patient_name' => 'Old Name',
            'patient_cell' => '03001111111',
            'name' => 'Old Name',
            'phone' => '03001111111',
        ]);

        $this->actingAs($user)
            ->putJson("/api/patients/{$patient->id}", [
                'patient_name'     => 'Updated Name',
                'patient_gender'   => 'male',
                'patient_age'      => 30,
                'patient_age_unit' => 'years',
                'patient_cell'     => '03002222222',
            ])
            ->assertOk();

        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'patient_name' => 'Updated Name',
        ]);
    }

    public function test_user_without_delete_patients_cannot_delete_patient(): void
    {
        $user = $this->makeUser('receptionist');
        $patient = Patient::create([
            'patient_name' => 'Test',
            'patient_cell' => '03003333333',
            'name' => 'Test',
            'phone' => '03003333333',
        ]);

        $this->actingAs($user)
            ->deleteJson("/api/patients/{$patient->id}")
            ->assertForbidden();
    }

    public function test_hospital_admin_can_delete_patient(): void
    {
        $user = $this->makeUser('hospital-admin');
        $patient = Patient::create([
            'patient_name' => 'To Delete',
            'patient_cell' => '03004444444',
            'name' => 'To Delete',
            'phone' => '03004444444',
        ]);

        $this->actingAs($user)
            ->deleteJson("/api/patients/{$patient->id}")
            ->assertOk();

        $this->assertSoftDeleted('patients', ['id' => $patient->id]);
    }

    public function test_super_admin_can_list_deleted_patients(): void
    {
        $superAdmin = $this->makeUser('super-admin');
        $patient = Patient::create([
            'patient_name' => 'Deleted List Patient',
            'patient_cell' => '03005556666',
            'name'         => 'Deleted List Patient',
            'phone'        => '03005556666',
        ]);
        $patient->delete();

        $this->actingAs($superAdmin)->getJson('/api/patients?deleted=1')
            ->assertOk()
            ->assertJsonPath('data.0.id', $patient->id)
            ->assertJsonPath('data.0.is_deleted', true);
    }

    public function test_hospital_admin_cannot_list_deleted_patients(): void
    {
        $admin = $this->makeUser('hospital-admin');

        $this->actingAs($admin)->getJson('/api/patients?deleted=1')
            ->assertForbidden();
    }

    public function test_super_admin_can_restore_deleted_patient(): void
    {
        $superAdmin = $this->makeUser('super-admin');
        $patient = Patient::create([
            'patient_name' => 'Restore Me',
            'patient_cell' => '03006667777',
            'name'         => 'Restore Me',
            'phone'        => '03006667777',
        ]);
        $patient->delete();

        $this->actingAs($superAdmin)->postJson("/api/patients/{$patient->id}/restore")
            ->assertOk()
            ->assertJsonPath('patient.patient_name', 'Restore Me')
            ->assertJsonPath('patient.is_deleted', false);

        $this->assertDatabaseHas('patients', [
            'id'         => $patient->id,
            'deleted_at' => null,
        ]);
    }

    public function test_hospital_admin_cannot_restore_deleted_patient(): void
    {
        $admin = $this->makeUser('hospital-admin');
        $patient = Patient::create([
            'patient_name' => 'No Restore',
            'patient_cell' => '03007778888',
            'name'         => 'No Restore',
            'phone'        => '03007778888',
        ]);
        $patient->delete();

        $this->actingAs($admin)->postJson("/api/patients/{$patient->id}/restore")
            ->assertForbidden();

        $this->assertSoftDeleted('patients', ['id' => $patient->id]);
    }

    public function test_patient_cnic_must_be_unique(): void
    {
        $user = $this->makeUser('receptionist');

        Patient::create([
            'patient_name' => 'First',
            'patient_cell' => '03005555555',
            'patient_cnic' => '35202-1234567-1',
            'name' => 'First',
            'phone' => '03005555555',
        ]);

        $this->actingAs($user)
            ->postJson('/api/patients', array_merge($this->validPayload(), [
                'patient_cnic' => '35202-1234567-1',
            ]))
            ->assertStatus(409)
            ->assertJsonPath('code', 'patient_exists');
    }

    public function test_patient_name_is_required(): void
    {
        $user = $this->makeUser('receptionist');

        $this->actingAs($user)
            ->postJson('/api/patients', [
                'patient_cell' => '03001234567',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['patient_name']);
    }

    public function test_patient_cell_is_required(): void
    {
        $user = $this->makeUser('receptionist');

        $this->actingAs($user)
            ->postJson('/api/patients', [
                'patient_name' => 'Ali Khan',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['patient_cell']);
    }

    protected function makeUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    protected function validPayload(): array
    {
        return [
            'patient_name'        => 'Ali Khan',
            'patient_father_name' => 'Muhammad Khan',
            'patient_gender'      => 'male',
            'patient_age'         => 25,
            'patient_age_unit'    => 'years',
            'patient_cell'        => '03001234567',
            'patient_address'     => 'Lahore, Pakistan',
            'patient_cnic'        => '35202-9999999-9',
        ];
    }

    public function test_patient_gender_is_required_on_create(): void
    {
        $user = $this->makeUser('receptionist');
        $payload = $this->validPayload();
        unset($payload['patient_gender']);

        $this->actingAs($user)
            ->postJson('/api/patients', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['patient_gender']);
    }

    public function test_patient_gender_must_be_valid_value(): void
    {
        $user = $this->makeUser('receptionist');

        $this->actingAs($user)
            ->postJson('/api/patients', array_merge($this->validPayload(), ['patient_gender' => 'invalid']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['patient_gender']);
    }

    public function test_patient_gender_is_saved_and_returned_in_api(): void
    {
        $user = $this->makeUser('receptionist');

        $response = $this->actingAs($user)
            ->postJson('/api/patients', array_merge($this->validPayload(), ['patient_gender' => 'female']))
            ->assertCreated()
            ->assertJsonPath('patient.patient_gender', 'female');

        $this->assertDatabaseHas('patients', [
            'patient_name'   => 'Ali Khan',
            'patient_gender' => 'female',
        ]);

        $patientId = $response->json('patient.id');

        $this->actingAs($user)
            ->getJson("/api/patients/{$patientId}")
            ->assertOk()
            ->assertJsonPath('data.patient_gender', 'female');
    }

    public function test_patient_gender_can_be_updated(): void
    {
        $user = $this->makeUser('receptionist');
        $patient = Patient::create([
            'patient_name'   => 'Old Name',
            'patient_gender' => 'male',
            'patient_cell'   => '03001111111',
            'name'           => 'Old Name',
            'phone'          => '03001111111',
        ]);

        $this->actingAs($user)
            ->putJson("/api/patients/{$patient->id}", [
                'patient_name'     => 'Updated Name',
                'patient_gender'   => 'other',
                'patient_age'      => 6,
                'patient_age_unit' => 'months',
                'patient_cell'     => '03002222222',
            ])
            ->assertOk()
            ->assertJsonPath('patient.patient_gender', 'other')
            ->assertJsonPath('patient.patient_age', 6)
            ->assertJsonPath('patient.patient_age_unit', 'months')
            ->assertJsonPath('patient.patient_age_display', '6 Months');

        $this->assertDatabaseHas('patients', [
            'id'             => $patient->id,
            'patient_gender' => 'other',
            'patient_age'    => 6,
        ]);
    }

    public function test_patient_age_is_required_on_create(): void
    {
        $user = $this->makeUser('receptionist');
        $payload = $this->validPayload();
        unset($payload['patient_age']);

        $this->actingAs($user)
            ->postJson('/api/patients', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['patient_age']);
    }

    public function test_patient_age_unit_is_required_on_create(): void
    {
        $user = $this->makeUser('receptionist');
        $payload = $this->validPayload();
        unset($payload['patient_age_unit']);

        $this->actingAs($user)
            ->postJson('/api/patients', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['patient_age_unit']);
    }

    public function test_patient_age_must_be_integer(): void
    {
        $user = $this->makeUser('receptionist');

        $this->actingAs($user)
            ->postJson('/api/patients', array_merge($this->validPayload(), ['patient_age' => 'abc']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['patient_age']);
    }

    public function test_patient_age_unit_must_be_valid_value(): void
    {
        $user = $this->makeUser('receptionist');

        $this->actingAs($user)
            ->postJson('/api/patients', array_merge($this->validPayload(), ['patient_age_unit' => 'weeks']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['patient_age_unit']);
    }

    public function test_patient_age_is_saved_and_returned_in_api(): void
    {
        $user = $this->makeUser('receptionist');

        $response = $this->actingAs($user)
            ->postJson('/api/patients', array_merge($this->validPayload(), [
                'patient_age'      => 15,
                'patient_age_unit' => 'days',
            ]))
            ->assertCreated()
            ->assertJsonPath('patient.patient_age', 15)
            ->assertJsonPath('patient.patient_age_unit', 'days')
            ->assertJsonPath('patient.patient_age_display', '15 Days');

        $this->assertDatabaseHas('patients', [
            'patient_name'     => 'Ali Khan',
            'patient_age'      => 15,
            'patient_age_unit' => 'days',
        ]);

        $patientId = $response->json('patient.id');

        $this->actingAs($user)
            ->getJson("/api/patients/{$patientId}")
            ->assertOk()
            ->assertJsonPath('data.patient_age_display', '15 Days');
    }

    public function test_invalid_patient_age_is_rejected_on_update(): void
    {
        $user = $this->makeUser('receptionist');
        $patient = Patient::create([
            'patient_name'   => 'Old Name',
            'patient_gender' => 'male',
            'patient_cell'   => '03001111111',
            'name'           => 'Old Name',
            'phone'          => '03001111111',
        ]);

        $this->actingAs($user)
            ->putJson("/api/patients/{$patient->id}", [
                'patient_name'     => 'Updated Name',
                'patient_gender'   => 'male',
                'patient_age'      => 200,
                'patient_age_unit' => 'years',
                'patient_cell'     => '03002222222',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['patient_age']);
    }

    public function test_authorized_user_can_load_patient_list(): void
    {
        $user = $this->makeUser('receptionist');

        Patient::create([
            'mr_number'    => '01062026',
            'patient_name' => 'List Patient',
            'patient_cell' => '03001111111',
            'name'         => 'List Patient',
            'phone'        => '03001111111',
        ]);

        $this->actingAs($user)
            ->getJson('/api/patients')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'mr_number',
                    'patient_name',
                    'patient_gender',
                    'patient_age',
                    'patient_age_unit',
                    'patient_age_display',
                ]],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_unauthorized_user_cannot_load_patient_list(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/patients')
            ->assertForbidden();
    }

    public function test_patient_list_supports_search_by_mr_number(): void
    {
        $user = $this->makeUser('receptionist');

        Patient::create([
            'mr_number'    => 'MRSEARCH01',
            'patient_name' => 'Search One',
            'patient_cell' => '03001111111',
            'name'         => 'Search One',
            'phone'        => '03001111111',
        ]);

        Patient::create([
            'mr_number'    => 'MRSEARCH02',
            'patient_name' => 'Other Patient',
            'patient_cell' => '03002222222',
            'name'         => 'Other Patient',
            'phone'        => '03002222222',
        ]);

        $this->actingAs($user)
            ->getJson('/api/patients?search=MRSEARCH01')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.mr_number', 'MRSEARCH01');
    }

    public function test_patient_list_supports_search_by_name_and_cell(): void
    {
        $user = $this->makeUser('receptionist');

        Patient::create([
            'mr_number'           => 'MRNAME01',
            'patient_name'        => 'Ali Khan',
            'patient_father_name' => 'Muhammad Khan',
            'patient_cell'        => '03009998888',
            'name'                => 'Ali Khan',
            'phone'               => '03009998888',
        ]);

        $this->actingAs($user)
            ->getJson('/api/patients?search=Ali')
            ->assertOk()
            ->assertJsonPath('data.0.patient_name', 'Ali Khan');

        $this->actingAs($user)
            ->getJson('/api/patients?search=03009998888')
            ->assertOk()
            ->assertJsonPath('data.0.patient_cell', '03009998888');

        $this->actingAs($user)
            ->getJson('/api/patients?search=Muhammad')
            ->assertOk()
            ->assertJsonPath('data.0.patient_father_name', 'Muhammad Khan');
    }

    public function test_patient_list_supports_pagination(): void
    {
        $user = $this->makeUser('receptionist');

        for ($i = 1; $i <= 12; $i++) {
            Patient::create([
                'mr_number'    => 'PG'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'patient_name' => "Patient {$i}",
                'patient_cell' => '0300'.str_pad((string) $i, 7, '0', STR_PAD_LEFT),
                'name'         => "Patient {$i}",
                'phone'        => '0300'.str_pad((string) $i, 7, '0', STR_PAD_LEFT),
            ]);
        }

        $this->actingAs($user)
            ->getJson('/api/patients?per_page=10&page=2')
            ->assertOk()
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonCount(2, 'data');
    }
}
