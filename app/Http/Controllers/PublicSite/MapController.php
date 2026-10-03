<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\ExpertOffice;
use App\Models\Promotion;
use App\Support\ExpertStatus;
use App\Support\PromotionStatus;
use App\Support\SettingKey;
use App\Services\Settings\SettingsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MapController extends Controller
{
    public function __construct(private SettingsService $settings)
    {
    }

    public function index(Request $request): View
    {
        $direction = $request->query('richtung');
        $cantonCode = strtoupper(trim((string) $request->query('kanton', '')));

        $promotedIds = Promotion::query()
            ->where('status', PromotionStatus::ACTIVE)
            ->pluck('expert_id')
            ->unique();

        $offices = ExpertOffice::query()
            ->whereHas('expert', function ($query) use ($direction) {
                $query->where('status', ExpertStatus::APPROVED)
                    ->where('is_public', true);

                if ($direction === 'kaufen') {
                    $query->where('offers_buy', true);
                } elseif ($direction === 'verkaufen') {
                    $query->where('offers_sell', true);
                }
            })
            ->when($cantonCode !== '', function ($query) use ($cantonCode) {
                $query->whereHas('canton', fn ($c) => $c->where('code', $cantonCode));
            })
            ->with(['expert', 'canton'])
            ->orderBy('city')
            ->get();

        $markers = [];
        foreach ($offices as $office) {
            $lat = $office->latitude !== null ? (float) $office->latitude : null;
            $lng = $office->longitude !== null ? (float) $office->longitude : null;

            if (($lat === null || $lng === null) && $office->canton) {
                $lat = $office->canton->latitude !== null ? (float) $office->canton->latitude : null;
                $lng = $office->canton->longitude !== null ? (float) $office->canton->longitude : null;
            }

            if ($lat === null || $lng === null || ! $office->expert) {
                continue;
            }

            $markers[] = [
                'id' => $office->id,
                'expert_id' => $office->expert_id,
                'lat' => $lat,
                'lng' => $lng,
                'name' => $office->expert->company_name,
                'initial' => mb_strtoupper(mb_substr($office->expert->company_name, 0, 1)),
                'logo' => $office->expert->logo_path ? asset('storage/'.$office->expert->logo_path) : null,
                'city' => trim(($office->postal_code ?? '').' '.($office->city ?? '')),
                'address' => trim((string) ($office->address_line ?? '')),
                'canton' => $office->canton->code ?? '',
                'url' => route('experts.show', $office->expert->slug),
                'promoted' => $promotedIds->contains($office->expert_id),
                'buy' => (bool) $office->expert->offers_buy,
                'sell' => (bool) $office->expert->offers_sell,
            ];
        }

        return view('public.map.index', [
            'offices' => $offices,
            'markers' => $markers,
            'direction' => $direction,
            'cantonCode' => $cantonCode,
            'mapsApiKey' => $this->settings->get(SettingKey::GOOGLE_MAPS_API_KEY),
            'promotedIds' => $promotedIds->flip(),
        ]);
    }
}
