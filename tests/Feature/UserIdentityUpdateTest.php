<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserIdentityUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_non_admin_cannot_update_own_profile_name_or_email(): void
    {
        $doctor = $this->makeUser('doctor');

        $this->actingAs($doctor)->patchJson('/api/profile', [
            'name' => 'Changed Name',
        ])
            ->assertForbidden()
            ->assertJsonPath('message', 'Only administrators can update name and email.');

        $this->actingAs($doctor)->patchJson('/api/profile', [
            'email' => 'changed@example.com',
        ])
            ->assertForbidden();

        $doctor->refresh();

        $this->assertNotSame('Changed Name', $doctor->name);
        $this->assertNotSame('changed@example.com', $doctor->email);
    }

    public function test_non_admin_can_update_own_password(): void
    {
        $doctor = $this->makeUser('doctor');

        $this->actingAs($doctor)->patchJson('/api/profile', [
            'password' => 'newpassword1',
            'password_confirmation' => 'newpassword1',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Profile updated successfully.');
    }

    public function test_admin_can_update_own_profile_name_and_email(): void
    {
        $admin = $this->makeUser('hospital-admin');

        $this->actingAs($admin)->patchJson('/api/profile', [
            'name' => 'Admin Updated',
            'email' => 'admin-updated@example.com',
        ])
            ->assertOk()
            ->assertJsonPath('user.name', 'Admin Updated')
            ->assertJsonPath('user.email', 'admin-updated@example.com');
    }

    public function test_non_admin_cannot_update_other_user_name_or_email(): void
    {
        $doctor = $this->makeUser('doctor');
        $doctor->givePermissionTo('edit users');
        $target = $this->makeUser('nurse');

        $originalName = $target->name;
        $originalEmail = $target->email;

        $this->actingAs($doctor)->putJson("/api/users/{$target->id}", [
            'name' => 'Updated Nurse',
            'email' => 'nurse-updated@example.com',
        ])
            ->assertForbidden()
            ->assertJsonPath('message', 'Only administrators can update user name and email.');

        $target->refresh();

        $this->assertSame($originalName, $target->name);
        $this->assertSame($originalEmail, $target->email);
    }

    public function test_admin_can_update_other_user_name_and_email(): void
    {
        $admin = $this->makeUser('hospital-admin');
        $target = $this->makeUser('nurse');

        $this->actingAs($admin)->putJson("/api/users/{$target->id}", [
            'name' => 'Updated Nurse',
            'email' => 'nurse-updated@example.com',
        ])
            ->assertOk()
            ->assertJsonPath('user.name', 'Updated Nurse')
            ->assertJsonPath('user.email', 'nurse-updated@example.com');
    }

    protected function makeUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
