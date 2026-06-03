<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\LabReport;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $user = $request->user();

        $stats = [
            'total_users' => $user->can('view users') ? User::count() : null,
            'total_patients' => $user->can('view patients') || $user->can('view limited patient info')
                ? Patient::count()
                : null,
            'appointments_today' => $user->can('view appointments')
                ? Appointment::whereDate('appointment_date', today())->count()
                : null,
            'pending_lab_reports' => ($user->can('view lab requests') || $user->can('view lab reports'))
                ? LabReport::where('is_approved', false)->count()
                : null,
            'unpaid_invoices' => $user->can('view invoice')
                ? Invoice::where('status', 'unpaid')->count()
                : null,
        ];

        return response()->json(['stats' => $stats]);
    }
}
