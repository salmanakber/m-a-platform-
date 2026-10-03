<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Support\InvoicePaymentStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $paymentStatus = $request->query('payment_status');

        $invoices = Invoice::query()
            ->when($paymentStatus, fn ($q) => $q->where('payment_status', $paymentStatus))
            ->with(['expert', 'promotion'])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.invoices.index', [
            'invoices' => $invoices,
            'paymentStatus' => $paymentStatus,
            'paymentStatuses' => InvoicePaymentStatus::all(),
        ]);
    }

    public function updatePaymentStatus(Request $request, Invoice $invoice): RedirectResponse
    {
        $validated = $request->validate([
            'payment_status' => ['required', 'in:'.implode(',', InvoicePaymentStatus::all())],
        ]);

        $invoice->update(['payment_status' => $validated['payment_status']]);

        return back()->with('success', 'Zahlungsstatus aktualisiert.');
    }
}
