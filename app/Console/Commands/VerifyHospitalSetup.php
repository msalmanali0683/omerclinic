<?php

namespace App\Console\Commands;

use App\Models\LaboratoryTestTemplate;
use App\Models\Medicine;
use App\Models\User;
use App\Support\HospitalSetupVerifier;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class VerifyHospitalSetup extends Command
{
    protected $signature = 'hospital:verify-setup {--smoke : Run authenticated API smoke checks}';

    protected $description = 'Verify roles, permissions, seed data, and optional API smoke checks';

    public function handle(): int
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $failed = false;

        $this->info('Checking roles and permissions…');
        $this->line('Roles: '.Role::count().' | Permissions: '.Permission::count());

        if (Role::count() < 10) {
            $this->error('Expected at least 10 roles. Run: php artisan db:seed --class=RolesAndPermissionsSeeder');
            $failed = true;
        }

        $routeStrings = HospitalSetupVerifier::routeMiddlewarePermissions();
        $missingRoute = HospitalSetupVerifier::missingMiddlewarePermissions($routeStrings);

        if ($missingRoute !== []) {
            $this->error('Route middleware references permissions not in database:');
            foreach ($missingRoute as $permission) {
                $this->line("  - {$permission}");
            }
            $failed = true;
        } else {
            $this->info('All route middleware permission strings exist ('.count($routeStrings).' checked).');
        }

        $missingFrontend = HospitalSetupVerifier::missingFrontendPermissions();

        if ($missingFrontend !== []) {
            $this->error('Frontend router references permissions not in database:');
            foreach ($missingFrontend as $permission) {
                $this->line("  - {$permission}");
            }
            $failed = true;
        } else {
            $this->info('All frontend router permission strings exist.');
        }

        $this->newLine();
        $this->info('Checking seeded data…');
        $this->line('Users: '.User::count());
        $this->line('Lab templates: '.LaboratoryTestTemplate::count());
        $this->line('Medicines: '.Medicine::count());

        if (User::role('super-admin')->count() === 0) {
            $this->error('No super-admin user found. Run: php artisan db:seed --class=UsersSeeder');
            $failed = true;
        }

        if (LaboratoryTestTemplate::count() === 0) {
            $this->error('No lab templates found. Run: php artisan db:seed --class=LaboratorySeeder');
            $failed = true;
        }

        if ($this->option('smoke')) {
            $this->newLine();
            $failed = $this->runSmokeChecks() || $failed;
        }

        if ($failed) {
            $this->newLine();
            $this->error('Setup verification failed.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Setup verification passed.');
        $this->line('Demo login: super-admin@example.com / password');

        return self::SUCCESS;
    }

    protected function runSmokeChecks(): bool
    {
        $this->info('Running API smoke checks…');

        $user = User::role('super-admin')->first();

        if (! $user) {
            $this->error('Cannot run smoke checks without a super-admin user.');

            return true;
        }

        $cases = [
            ['GET', '/api/me'],
            ['GET', '/api/dashboard/stats'],
            ['GET', '/api/patients'],
            ['GET', '/api/patient-queue'],
            ['GET', '/api/medicines'],
            ['GET', '/api/clinical-scan-templates/options'],
            ['GET', '/api/clinical-scans/queue-patients/search', ['today_only' => 'false', 'limit' => 5]],
            ['GET', '/api/laboratory-test-templates/options'],
            ['GET', '/api/reports/patients'],
            ['GET', '/api/roles'],
        ];

        $failed = false;

        foreach ($cases as $case) {
            [$method, $uri] = $case;
            $query = $case[2] ?? [];
            $url = $uri.($query ? '?'.http_build_query($query) : '');

            Auth::guard('web')->login($user);

            $request = Request::create($url, $method);
            $request->headers->set('Accept', 'application/json');
            $request->setUserResolver(fn () => $user);

            $status = app()->handle($request)->getStatusCode();
            Auth::guard('web')->logout();

            if ($status >= 500) {
                $this->error("{$method} {$uri} → {$status}");
                $failed = true;
            } else {
                $this->line("{$method} {$uri} → {$status}");
            }
        }

        return $failed;
    }
}
