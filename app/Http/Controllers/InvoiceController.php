<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InvoiceController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Invoice::class);

        $user = auth()->user();
        if ($user->hasRole('super-admin') || $user->hasRole('hospital-admin') || $user->hasRole('accountant')) {
            $invoices = Invoice::with('patient')->get();
        } else {
            $invoices = Invoice::whereHas('patient', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })->get();
        }

        return response()->json($invoices);
    }

    public function show(Invoice $invoice)
    {
        Gate::authorize('view', $invoice);

        return response()->json($invoice->load('patient'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Invoice::class);

        $validated = $request->validate([
            'patient_id' => 'required|integer|exists:patients,id',
            'amount' => 'required|numeric|min:0',
            'status' => 'nullable|string',
            'payment_details' => 'nullable|string',
        ]);

        $validated['accountant_id'] = auth()->id();

        $invoice = Invoice::create($validated);

        return response()->json([
            'message' => 'Invoice created successfully',
            'invoice' => $invoice
        ], 201);
    }

    public function update(Request $request, Invoice $invoice)
    {
        Gate::authorize('update', $invoice);

        $validated = $request->validate([
            'amount' => 'sometimes|required|numeric|min:0',
            'status' => 'sometimes|required|string',
            'payment_details' => 'nullable|string',
        ]);

        $invoice->update($validated);

        return response()->json([
            'message' => 'Invoice updated successfully',
            'invoice' => $invoice
        ]);
    }

    public function pay(Request $request, Invoice $invoice)
    {
        Gate::authorize('pay', $invoice);

        $validated = $request->validate([
            'payment_details' => 'required|string',
        ]);

        $invoice->update([
            'status' => 'paid',
            'payment_details' => $validated['payment_details'],
            'accountant_id' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Payment received and processed successfully',
            'invoice' => $invoice
        ]);
    }
}
