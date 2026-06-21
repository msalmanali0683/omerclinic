<?php



namespace Tests\Feature;



use App\Models\Patient;

use App\Models\User;

use Database\Seeders\RolesAndPermissionsSeeder;

use Illuminate\Foundation\Testing\RefreshDatabase;

use Tests\TestCase;



class PatientNameSuggestionTest extends TestCase

{

    use RefreshDatabase;



    protected function setUp(): void

    {

        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

    }



    public function test_guest_cannot_fetch_patient_name_suggestions(): void

    {

        $this->getJson('/api/patients/name-suggestions?q=muh')

            ->assertUnauthorized();

    }



    public function test_user_without_permission_cannot_fetch_name_suggestions(): void

    {

        $user = $this->makeUser('lab-technician');



        $this->actingAs($user)->getJson('/api/patients/name-suggestions?q=muh')

            ->assertForbidden();

    }



    public function test_patient_name_word_suggestions_start_with_single_letter(): void

    {

        $user = $this->makeUser('receptionist');



        Patient::create([

            'mr_number'      => '01052026',

            'patient_name'   => 'Muhammad Ali',

            'patient_gender' => 'male',

            'patient_age'    => 30,

            'patient_cell'   => '03001111111',

            'name'           => 'Muhammad Ali',

            'phone'          => '03001111111',

        ]);



        Patient::create([

            'mr_number'      => '02052026',

            'patient_name'   => 'Mehmood Ahmad',

            'patient_gender' => 'male',

            'patient_age'    => 28,

            'patient_cell'   => '03002222222',

            'name'           => 'Mehmood Ahmad',

            'phone'          => '03002222222',

        ]);



        $response = $this->actingAs($user)->getJson('/api/patients/name-suggestions?q=M');



        $response->assertOk()

            ->assertJsonCount(2, 'data')

            ->assertJsonPath('data.0.type', 'word')

            ->assertJsonPath('data.1.type', 'word');



        $values = collect($response->json('data'))->pluck('value')->all();



        $this->assertEquals(['Mehmood', 'Muhammad'], $values);

    }



    public function test_patient_name_completion_suggestions_match_full_prefix(): void

    {

        $user = $this->makeUser('receptionist');



        Patient::create([

            'mr_number'      => '01052026',

            'patient_name'   => 'Mehmood Ahmad',

            'patient_gender' => 'male',

            'patient_age'    => 30,

            'patient_cell'   => '03001111111',

            'name'           => 'Mehmood Ahmad',

            'phone'          => '03001111111',

        ]);



        Patient::create([

            'mr_number'      => '02052026',

            'patient_name'   => 'Mehmood Ali',

            'patient_gender' => 'male',

            'patient_age'    => 28,

            'patient_cell'   => '03002222222',

            'name'           => 'Mehmood Ali',

            'phone'          => '03002222222',

        ]);



        Patient::create([

            'mr_number'      => '03052026',

            'patient_name'   => 'Muhammad Ali',

            'patient_gender' => 'male',

            'patient_age'    => 25,

            'patient_cell'   => '03003333333',

            'name'           => 'Muhammad Ali',

            'phone'          => '03003333333',

        ]);



        $response = $this->actingAs($user)->getJson('/api/patients/name-suggestions?q=Mehmood A');



        $response->assertOk()

            ->assertJsonCount(2, 'data')

            ->assertJsonPath('data.0.type', 'completion')

            ->assertJsonPath('data.1.type', 'completion');



        $values = collect($response->json('data'))->pluck('value')->all();



        $this->assertEquals(['Mehmood Ahmad', 'Mehmood Ali'], $values);

    }



    public function test_name_word_suggestions_do_not_match_second_word_only(): void

    {

        $user = $this->makeUser('receptionist');



        Patient::create([

            'mr_number'      => '01052026',

            'patient_name'   => 'Muhammad Ali',

            'patient_gender' => 'male',

            'patient_age'    => 30,

            'patient_cell'   => '03001111111',

            'name'           => 'Muhammad Ali',

            'phone'          => '03001111111',

        ]);



        $this->actingAs($user)->getJson('/api/patients/name-suggestions?q=Ali')

            ->assertOk()

            ->assertJsonCount(0, 'data');

    }



    public function test_name_suggestions_are_case_insensitive(): void

    {

        $user = $this->makeUser('receptionist');



        Patient::create([

            'mr_number'      => '01052026',

            'patient_name'   => 'Muhammad Ali',

            'patient_gender' => 'male',

            'patient_age'    => 30,

            'patient_cell'   => '03001111111',

            'name'           => 'Muhammad Ali',

            'phone'          => '03001111111',

        ]);



        $this->actingAs($user)->getJson('/api/patients/name-suggestions?q=muha')

            ->assertOk()

            ->assertJsonCount(1, 'data')

            ->assertJsonPath('data.0.type', 'word')

            ->assertJsonPath('data.0.value', 'Muhammad');

    }



    public function test_father_name_suggestions_include_patient_and_father_names(): void

    {

        $user = $this->makeUser('receptionist');



        Patient::create([

            'mr_number'           => '01052026',

            'patient_name'        => 'Ali Raza',

            'patient_father_name' => 'Abdul Khan',

            'patient_gender'        => 'male',

            'patient_age'           => 30,

            'patient_cell'          => '03001111111',

            'name'                  => 'Ali Raza',

            'phone'                 => '03001111111',

        ]);



        Patient::create([

            'mr_number'           => '02052026',

            'patient_name'        => 'Fatima Bibi',

            'patient_father_name' => 'Ahmed Ali',

            'patient_gender'        => 'female',

            'patient_age'           => 25,

            'patient_cell'          => '03002222222',

            'name'                  => 'Fatima Bibi',

            'phone'                 => '03002222222',

        ]);



        $response = $this->actingAs($user)->getJson('/api/patients/name-suggestions?q=A&field=patient_father_name');



        $response->assertOk()

            ->assertJsonCount(3, 'data');



        $values = collect($response->json('data'))->pluck('value')->all();



        $this->assertEquals(['Abdul', 'Ahmed', 'Ali'], $values);

    }



    public function test_father_name_completion_suggestions_match_full_prefix(): void

    {

        $user = $this->makeUser('receptionist');



        Patient::create([

            'mr_number'           => '01052026',

            'patient_name'        => 'Ali Raza',

            'patient_father_name' => 'Abdul Khan',

            'patient_gender'        => 'male',

            'patient_age'           => 30,

            'patient_cell'          => '03001111111',

            'name'                  => 'Ali Raza',

            'phone'                 => '03001111111',

        ]);



        Patient::create([

            'mr_number'           => '02052026',

            'patient_name'        => 'Abdul Razzaq',

            'patient_father_name' => 'Abdul Hameed',

            'patient_gender'        => 'male',

            'patient_age'           => 40,

            'patient_cell'          => '03002222222',

            'name'                  => 'Abdul Razzaq',

            'phone'                 => '03002222222',

        ]);



        $response = $this->actingAs($user)->getJson('/api/patients/name-suggestions?q=Abdul K&field=patient_father_name');



        $response->assertOk()

            ->assertJsonCount(1, 'data')

            ->assertJsonPath('data.0.type', 'completion')

            ->assertJsonPath('data.0.value', 'Abdul Khan');
    }

    public function test_address_word_suggestions_start_with_single_letter(): void
    {
        $user = $this->makeUser('receptionist');

        Patient::create([
            'mr_number'       => '01052026',
            'patient_name'    => 'Ali Raza',
            'patient_address' => 'House 12 Main Street',
            'patient_gender'  => 'male',
            'patient_age'     => 30,
            'patient_cell'    => '03001111111',
            'name'            => 'Ali Raza',
            'phone'           => '03001111111',
        ]);

        Patient::create([
            'mr_number'       => '02052026',
            'patient_name'    => 'Sara Khan',
            'patient_address' => 'Haveli Road Lahore',
            'patient_gender'  => 'female',
            'patient_age'     => 25,
            'patient_cell'    => '03002222222',
            'name'            => 'Sara Khan',
            'phone'           => '03002222222',
        ]);

        $response = $this->actingAs($user)->getJson('/api/patients/name-suggestions?q=H&field=patient_address');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.type', 'word')
            ->assertJsonPath('data.1.type', 'word');

        $values = collect($response->json('data'))->pluck('value')->all();

        $this->assertEquals(['Haveli', 'House'], $values);
    }

    public function test_address_completion_suggestions_match_full_prefix(): void
    {
        $user = $this->makeUser('receptionist');

        Patient::create([
            'mr_number'       => '01052026',
            'patient_name'    => 'Ali Raza',
            'patient_address' => 'House 12 Main Street',
            'patient_gender'  => 'male',
            'patient_age'     => 30,
            'patient_cell'    => '03001111111',
            'name'            => 'Ali Raza',
            'phone'           => '03001111111',
        ]);

        Patient::create([
            'mr_number'       => '02052026',
            'patient_name'    => 'Sara Khan',
            'patient_address' => 'House 45 Model Town',
            'patient_gender'  => 'female',
            'patient_age'     => 25,
            'patient_cell'    => '03002222222',
            'name'            => 'Sara Khan',
            'phone'           => '03002222222',
        ]);

        $response = $this->actingAs($user)->getJson('/api/patients/name-suggestions?q=House 1&field=patient_address');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'completion')
            ->assertJsonPath('data.0.value', 'House 12 Main Street');
    }

    protected function makeUser(string $role): User

    {

        $user = User::factory()->create();

        $user->assignRole($role);



        return $user;

    }

}

