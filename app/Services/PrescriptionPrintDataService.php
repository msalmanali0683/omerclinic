<?php

namespace App\Services;

use App\Models\Prescription;
use App\Models\User;

class PrescriptionPrintDataService
{
    public function __construct(protected VisitPrintDataService $visitPrintDataService) {}

    public function getPrintData(Prescription $prescription, ?User $user = null): array
    {
        return $this->visitPrintDataService->getForPrescription($prescription, $user);
    }
}
