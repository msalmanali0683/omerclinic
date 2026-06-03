<?php

namespace App\Services;

use App\Models\LaboratoryBill;
use App\Models\LaboratoryResult;
use App\Models\LaboratoryTestTemplate;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LaboratoryBillService
{
    public function create(array $data, User $user): LaboratoryBill
    {
        return DB::transaction(function () use ($data, $user) {
            $patient = Patient::findOrFail($data['patient_id']);
            $visitId = $this->resolveVisitId($patient, $data['patient_visit_id'] ?? null);

            $items = collect($data['test_items'] ?? []);
            $templateIds = $items->pluck('template_id')->unique()->values()->all();

            $templates = LaboratoryTestTemplate::query()
                ->whereIn('id', $templateIds)
                ->where('is_active', true)
                ->get()
                ->keyBy('id');

            if ($templates->isEmpty()) {
                throw new \InvalidArgumentException('No valid active test templates selected.');
            }

            $linePrices = [];
            foreach ($items as $item) {
                $template = $templates->get($item['template_id']);
                if (! $template) {
                    continue;
                }
                $linePrices[] = array_key_exists('test_price', $item)
                    ? (float) $item['test_price']
                    : (float) ($template->test_price ?? 0);
            }

            $subtotal = round((float) array_sum($linePrices), 2);
            $discount = round((float) ($data['discount'] ?? 0), 2);
            $total = max(round($subtotal - $discount, 2), 0);

            $bill = LaboratoryBill::create([
                'bill_no'          => $this->generateBillNo(),
                'patient_id'       => $patient->id,
                'patient_visit_id' => $visitId,
                'subtotal'         => $subtotal,
                'discount'         => $discount,
                'total'            => $total,
                'status'           => LaboratoryBill::STATUS_DRAFT,
                'created_by'       => $user->id,
                'updated_by'       => $user->id,
            ]);

            foreach ($items as $item) {
                $template = $templates->get($item['template_id']);
                if (! $template) {
                    continue;
                }

                $testPrice = array_key_exists('test_price', $item)
                    ? (float) $item['test_price']
                    : (float) ($template->test_price ?? 0);

                LaboratoryResult::create([
                    'patient_id'                  => $patient->id,
                    'patient_visit_id'            => $visitId,
                    'laboratory_bill_id'          => $bill->id,
                    'laboratory_test_template_id' => $template->id,
                    'test_name'                   => $template->test_name,
                    'test_code'                   => $template->test_code,
                    'test_price'                  => $testPrice,
                    'lab_operator_id'             => $user->id,
                    'result_date'                 => now()->toDateString(),
                    'result_time'                 => now()->format('H:i:s'),
                    'status'                      => LaboratoryResult::STATUS_DRAFT,
                    'created_by'                  => $user->id,
                    'updated_by'                  => $user->id,
                ]);
            }

            return $bill->load([
                'patient',
                'visit.doctor',
                'results.template',
                'createdBy',
            ]);
        });
    }

    public function getPrintData(LaboratoryBill $bill): array
    {
        $bill->loadMissing(['patient', 'visit.doctor', 'results', 'createdBy']);

        return [
            'title'         => 'Laboratory Test Bill',
            'hospital_name' => config('app.name'),
            'bill'          => [
                'bill_no'    => $bill->bill_no,
                'status'     => $bill->status,
                'subtotal'   => $bill->subtotal,
                'discount'   => $bill->discount,
                'total'      => $bill->total,
                'created_at' => $bill->created_at?->format('Y-m-d H:i'),
            ],
            'patient'       => [
                'mr_number'           => $bill->patient?->mr_number,
                'patient_name'        => $bill->patient?->patient_name,
                'patient_father_name' => $bill->patient?->patient_father_name,
                'patient_cell'        => $bill->patient?->patient_cell,
                'patient_gender'      => $bill->patient?->patient_gender,
                'patient_age'         => $bill->patient?->patient_age,
                'patient_age_unit'    => $bill->patient?->patient_age_unit,
            ],
            'visit'         => $bill->visit ? [
                'id'         => $bill->visit->id,
                'visit_date' => $bill->visit->visit_date?->format('Y-m-d'),
                'visit_time' => $bill->visit->visit_time,
                'doctor_name'=> $bill->visit->doctor?->name,
            ] : null,
            'visit_label'   => $bill->visit
                ? 'Visit #'.$bill->visit->id.' · '.$bill->visit->visit_date?->format('Y-m-d')
                : 'Not Linked / No Visit',
            'tests'         => $bill->results->map(fn ($r) => [
                'test_name'  => $r->test_name,
                'test_price' => $r->test_price,
                'status'     => $r->status,
            ])->values()->all(),
            'printed_by'    => auth()->user()?->name,
            'generated_at'  => now()->format('Y-m-d H:i'),
        ];
    }

    protected function resolveVisitId(Patient $patient, mixed $visitId): ?int
    {
        if ($visitId === null || $visitId === '' || $visitId === 0) {
            return null;
        }

        $visit = PatientVisit::query()
            ->whereKey($visitId)
            ->where('patient_id', $patient->id)
            ->firstOrFail();

        return $visit->id;
    }

    protected function generateBillNo(): string
    {
        $prefix = 'LB-'.now()->format('Ymd').'-';
        $latest = LaboratoryBill::withTrashed()
            ->where('bill_no', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('bill_no');

        $sequence = 1;
        if ($latest && preg_match('/-(\d+)$/', $latest, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
