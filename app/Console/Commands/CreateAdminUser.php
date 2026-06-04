<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    protected $signature = 'hospital:create-admin
                            {email=admin@omerclinic.com : Admin login email}
                            {--password= : Password (min 8 chars; prompted if omitted)}
                            {--name=Super Admin : Display name}';

    protected $description = 'Create or update a super-admin user (for production servers without Tinker)';

    public function handle(): int
    {
        $this->callSilent('db:seed', ['--class' => 'RolesAndPermissionsSeeder', '--force' => true]);

        $email = (string) $this->argument('email');
        $password = $this->option('password') ?: $this->secret('Password (min 8 characters)');

        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            [
                'email'    => 'required|email',
                'password' => 'required|string|min:8',
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name'     => (string) $this->option('name'),
                'password' => Hash::make($password),
            ]
        );

        $user->syncRoles(['super-admin']);

        $this->info('Super admin ready.');
        $this->line("Email: {$email}");
        $this->line('Role: super-admin');
        $this->line('User count: '.User::count());

        return self::SUCCESS;
    }
}
