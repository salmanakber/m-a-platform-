<?php

namespace App\Http\Controllers\Expert;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\Invoice\InvoiceService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $invoices)
    {
    }

    public function index(): View
    {
        $expert = Auth::user()?->resolvePortalExpert();
        abort_if($expert === null, 403);

        $items = Invoice::query()
            ->where('expert_id', $expert->id)
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate(20);

        return view('expert.invoices.index', [
            'expert' => $expert,
            'invoices' => $items,
        ]);
    }

    public function show(Invoice $invoice): View
    {
        $expert = Auth::user()?->resolvePortalExpert();
        abort_if($expert === null, 403);
        abort_unless((int) $invoice->expert_id === (int) $expert->id, 403);

        $invoice->loadMissing(['product', 'promotion.canton', 'linkedPromotion.canton']);

        return view('expert.invoices.show', [
            'expert' => $expert,
            'invoice' => $invoice,
            'studio' => $this->invoices->toStudioArray($invoice),
            'sender' => $this->invoices->senderSettings(),
        ]);
    }
}
