<?php

namespace App\Console\Commands;

use App\Services\PatientQueueService;
use Illuminate\Console\Command;

class CancelStalePatientQueueVisits extends Command
{
    protected $signature = 'patient-queue:cancel-stale';

    protected $description = 'Cancel active patient queue visits from previous days';

    public function handle(PatientQueueService $queueService): int
    {
        $count = $queueService->cancelStaleQueueVisits();

        $this->info("Cancelled {$count} stale queue visit(s).");

        return self::SUCCESS;
    }
}
