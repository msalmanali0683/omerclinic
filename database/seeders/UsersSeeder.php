<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /** Default password for all seeded demo accounts: password */
    public function run(): void
    {
        $accounts = [
            ['email' => 'super-admin@example.com', 'name' => 'Super Admin', 'role' => 'super-admin'],
            ['email' => 'admin@example.com', 'name' => 'Hospital Admin', 'role' => 'hospital-admin'],
            ['email' => 'doctor@example.com', 'name' => 'Demo Doctor', 'role' => 'doctor'],
            ['email' => 'scan-operator@example.com', 'name' => 'Scan Operator', 'role' => 'scan-operator'],
            ['email' => 'nurse@example.com', 'name' => 'Demo Nurse', 'role' => 'nurse'],
            ['email' => 'receptionist@example.com', 'name' => 'Receptionist', 'role' => 'receptionist'],
            ['email' => 'lab-technician@example.com', 'name' => 'Lab Technician', 'role' => 'lab-technician'],
            ['email' => 'lab-operator@example.com', 'name' => 'Lab Operator', 'role' => 'lab-operator'],
            ['email' => 'pharmacist@example.com', 'name' => 'Pharmacist', 'role' => 'pharmacist'],
            ['email' => 'accountant@example.com', 'name' => 'Accountant', 'role' => 'accountant'],
        ];

        foreach ($accounts as $account) {
            $user = User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name'     => $account['name'],
                    'password' => Hash::make('password'),
                ]
            );

            $user->syncRoles([$account['role']]);
        }
    }
}
