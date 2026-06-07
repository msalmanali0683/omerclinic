<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PrescriptionPrintSettingService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrescriptionPrintSettingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_authorized_user_can_view_prescription_print_settings(): void
    {
        $pharmacist = $this->makeUser('pharmacist');

        $this->actingAs($pharmacist)->getJson('/api/prescription-print-settings')
            ->assertOk()
            ->assertJsonPath('data.active_paper_key', 'A4')
            ->assertJsonPath('data.paper_presets.A4.page_size', 'A4')
            ->assertJsonPath('data.paper_presets.Legal.page_size', 'Legal')
            ->assertJsonPath('can_manage', false);
    }

    public function test_admin_can_update_prescription_print_settings(): void
    {
        $admin = $this->makeUser('hospital-admin');
        $defaults = app(PrescriptionPrintSettingService::class)->resolve();

        $payload = $defaults;
        $payload['active_paper_key'] = 'Legal';
        $payload['letterhead_height'] = '2.8in';
        $payload['font_size_vitals'] = 11;
        $payload['font_size_medicines'] = 14;
        $payload['paper_presets']['Legal']['margin_left'] = '0.6in';

        $this->actingAs($admin)->putJson('/api/prescription-print-settings', $payload)
            ->assertOk()
            ->assertJsonPath('data.active_paper_key', 'Legal')
            ->assertJsonPath('data.letterhead_height', '2.8in')
            ->assertJsonPath('data.font_size_vitals', 11)
            ->assertJsonPath('data.font_size_medicines', 14)
            ->assertJsonPath('data.margin_left', '0.6in');
    }

    public function test_user_without_manage_permission_cannot_update_settings(): void
    {
        $pharmacist = $this->makeUser('pharmacist');

        $defaults = app(PrescriptionPrintSettingService::class)->resolve();

        $this->actingAs($pharmacist)->putJson('/api/prescription-print-settings', $defaults)
            ->assertForbidden();
    }

    protected function makeUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
