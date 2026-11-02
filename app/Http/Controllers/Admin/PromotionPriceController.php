<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromotionPrice;
use App\Support\RankingScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromotionPriceController extends Controller
{
    public function index(): View
    {
        $prices = PromotionPrice::query()
            ->orderBy('scope')
            ->orderBy('position')
            ->get()
            ->groupBy('scope');

        return view('admin.promotion-prices.index', [
            'pricesByScope' => $prices,
            'scopeLabels' => RankingScope::labels(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'prices' => ['required', 'array'],
            'prices.*.id' => ['required', 'integer', 'exists:promotion_prices,id'],
            'prices.*.price_chf' => ['required', 'numeric', 'min:0'],
            'prices.*.note' => ['nullable', 'string', 'max:255'],
        ]);

        foreach ($validated['prices'] as $row) {
            PromotionPrice::query()->whereKey($row['id'])->update([
                'price_chf' => $row['price_chf'],
                'note' => $row['note'] ?? null,
            ]);
        }

        return back()->with('success', 'Preismatrix gespeichert.');
    }
}
