<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Http\Resources\PatientResource;
use App\Http\Resources\PatientVisitResource;
use App\Models\Patient;
use App\Models\User;
use App\Services\PatientMrNumberService;
use App\Services\PatientQueueService;
use App\Services\PatientVisitTokenService;
use App\Support\PatientVisitTokenResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatientController extends Controller
{
    public function __construct(
        protected PatientMrNumberService $mrNumberService,
        protected PatientQueueService $queueService,
        protected PatientVisitTokenService $tokenService,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Patient::class);

        if ($request->boolean('deleted')) {
            abort_unless($request->user()->hasRole('super-admin'), 403);

            $query = Patient::onlyTrashed()->withInQueueTodayFlag()->latest();
        } else {
            $query = Patient::query()->withInQueueTodayFlag()->latest();
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('patient_name', 'like', "%{$search}%")
                    ->orWhere('patient_father_name', 'like', "%{$search}%")
                    ->orWhere('patient_cell', 'like', "%{$search}%")
                    ->orWhere('patient_cnic', 'like', "%{$search}%")
                    ->orWhere('mr_number', 'like', "%{$search}%");
            });
        }

        $patients = $query->paginate($request->get('per_page', 15));

        return PatientResource::collection($patients);
    }

    public function store(StorePatientRequest $request): JsonResponse
    {
        if ($request->user()->hasRole('doctor')) {
            return $this->storeForDoctor($request);
        }

        return $this->storeForStaff($request);
    }

    protected function storeForDoctor(StorePatientRequest $request): JsonResponse
    {
        $user = $request->user();

        $result = DB::transaction(function () use ($request, $user) {
            [$patient, $isNew] = $this->findOrCreatePatientForDoctor($request);

            $queueResult = $this->queueService->addToQueue($patient, $user, [
                'doctor_id'        => $user->id,
                'reason_for_visit' => $request->reason_for_visit,
                'notes'            => $request->notes,
            ]);

            return compact('patient', 'isNew', 'queueResult');
        });

        $message = match (true) {
            ! $result['queueResult']['created'] => 'Patient is already in your queue.',
            $result['isNew'] => 'Patient registered and added to your queue.',
            default => 'Existing patient added to your queue.',
        };

        $status = $result['isNew'] && $result['queueResult']['created'] ? 201 : 200;

        $response = [
            'message'         => $message,
            'patient'         => new PatientResource($result['patient']),
            'visit'           => new PatientVisitResource($result['queueResult']['visit']),
            'created'         => $result['queueResult']['created'],
            'patient_created' => $result['isNew'],
        ];

        $response = PatientVisitTokenResponse::appendAutoToken(
            $response,
            $result['queueResult']['visit'],
            $user,
            $this->tokenService,
            'Patient registered and token generated successfully.',
            (bool) $result['queueResult']['created'],
        );

        return response()->json($response, $status);
    }

    protected function storeForStaff(StorePatientRequest $request): JsonResponse
    {
        if ($request->filled('patient_cnic')) {
            $existing = Patient::withInQueueTodayFlag()->where('patient_cnic', $request->patient_cnic)->first();
            if ($existing) {
                return response()->json([
                    'message' => 'Patient already exists. Use Add to Queue for repeat visit.',
                    'patient' => new PatientResource($existing),
                    'code'    => 'patient_exists',
                ], 409);
            }
        }

        if (! $request->boolean('force_create')) {
            $possible = Patient::query()
                ->withInQueueTodayFlag()
                ->where('patient_cell', $request->patient_cell)
                ->where('patient_name', $request->patient_name)
                ->first();

            if ($possible) {
                return response()->json([
                    'message' => 'A patient with the same name and cell number may already exist.',
                    'patient' => new PatientResource($possible),
                    'code'    => 'possible_duplicate',
                ], 409);
            }
        }

        $result = DB::transaction(function () use ($request) {
            $patient = $this->createNewPatient($request);

            $visit = null;
            $queueResult = null;
            $user = $request->user();

            if ($this->shouldAddToQueueOnCreate($request, $user)) {
                $queueResult = $this->queueService->addToQueue($patient, $user, [
                    'doctor_id'        => $this->resolveQueueDoctorId($request, $user),
                    'reason_for_visit' => $request->reason_for_visit,
                    'notes'            => $request->notes,
                ]);
                $visit = $queueResult['visit'];
            }

            return compact('patient', 'visit', 'queueResult');
        });

        $message = 'Patient created successfully.';
        if ($result['queueResult'] && ! $result['queueResult']['created']) {
            $message = $result['queueResult']['message'];
        }

        $response = [
            'message' => $message,
            'patient' => new PatientResource($result['patient']),
        ];

        if ($result['visit']) {
            $response['visit'] = new PatientVisitResource($result['visit']);
            $response['created'] = $result['queueResult']['created'];
        }

        $response = PatientVisitTokenResponse::appendAutoToken(
            $response,
            $result['visit'],
            $request->user(),
            $this->tokenService,
            'Patient registered and token generated successfully.',
            (bool) ($result['queueResult']['created'] ?? false),
        );

        return response()->json($response, 201);
    }

    /**
     * @return array{0: Patient, 1: bool} [patient, isNew]
     */
    protected function findOrCreatePatientForDoctor(StorePatientRequest $request): array
    {
        if ($request->filled('patient_cnic')) {
            $existing = Patient::withInQueueTodayFlag()->where('patient_cnic', $request->patient_cnic)->first();
            if ($existing) {
                return [$existing, false];
            }
        }

        $possible = Patient::query()
            ->where('patient_cell', $request->patient_cell)
            ->where('patient_name', $request->patient_name)
            ->first();

        if ($possible) {
            return [$possible, false];
        }

        return [$this->createNewPatient($request), true];
    }

    protected function createNewPatient(StorePatientRequest $request): Patient
    {
        $mrNumber = $this->mrNumberService->generate();

        return Patient::create([
            ...$request->safe()->only([
                'patient_name', 'patient_father_name', 'patient_gender',
                'patient_age', 'patient_age_unit',
                'patient_cell', 'patient_address', 'patient_cnic',
            ]),
            'mr_number'  => $mrNumber,
            'created_by' => $request->user()->id,
            'name'       => $request->patient_name,
            'phone'      => $request->patient_cell,
        ]);
    }

    public function show(Patient $patient)
    {
        $this->authorize('view', $patient);

        return new PatientResource($patient);
    }

    public function update(UpdatePatientRequest $request, Patient $patient)
    {
        $patient->update([
            ...$request->validated(),
            'updated_by' => $request->user()->id,
            'name'       => $request->patient_name,
            'phone'      => $request->patient_cell,
        ]);

        return response()->json([
            'message' => 'Patient updated successfully.',
            'patient' => new PatientResource($patient->fresh()),
        ]);
    }

    public function destroy(Patient $patient)
    {
        $this->authorize('delete', $patient);

        $cancelledVisits = $this->queueService->cancelActiveVisitsForPatient($patient, request()->user());

        $patient->delete();

        $message = 'Patient deleted successfully.';
        if ($cancelledVisits > 0) {
            $message .= ' '.$cancelledVisits.' active queue '.($cancelledVisits === 1 ? 'entry was' : 'entries were').' cancelled.';
        }

        return response()->json(['message' => $message]);
    }

    public function restore(Request $request, int $patient)
    {
        $patientModel = Patient::onlyTrashed()->findOrFail($patient);

        $this->authorize('restore', $patientModel);

        $patientModel->restore();

        return response()->json([
            'message' => 'Patient restored successfully.',
            'patient' => new PatientResource(
                Patient::query()->withInQueueTodayFlag()->findOrFail($patientModel->id)
            ),
        ]);
    }

    protected function shouldAddToQueueOnCreate(StorePatientRequest $request, User $user): bool
    {
        return $user->can('add patient to queue');
    }

    protected function resolveQueueDoctorId(StorePatientRequest $request, User $user): ?int
    {
        if ($user->hasRole('doctor') && ! $user->can('assign doctor to queue')) {
            return $user->id;
        }

        if ($request->filled('doctor_id')) {
            return (int) $request->doctor_id;
        }

        return null;
    }
}
