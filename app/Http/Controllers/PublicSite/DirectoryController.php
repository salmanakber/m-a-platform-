<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\Canton;
use App\Models\Expert;
use App\Models\Promotion;
use App\Support\ExpertStatus;
use App\Support\PromotionStatus;
use App\Support\SettingKey;
use App\Services\Settings\SettingsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DirectoryController extends Controller
{
    public function __construct(private SettingsService $settings)
    {
    }

    public function index(Request $request): View
    {
        $direction = $request->query('richtung');
        $cantonCode = strtoupper(trim((string) $request->query('kanton', '')));
        $q = trim((string) $request->query('q', ''));

        $promotedExpertIds = Promotion::query()
            ->where('status', PromotionStatus::ACTIVE)
            ->pluck('expert_id')
            ->unique();

        $base = Expert::query()
            ->where('status', ExpertStatus::APPROVED)
            ->where('is_public', true)
            ->with(['offices.canton']);

        if ($direction === 'kaufen') {
            $base->where('offers_buy', true);
        } elseif ($direction === 'verkaufen') {
            $base->where('offers_sell', true);
        }

        if ($cantonCode !== '') {
            $base->whereHas('offices.canton', fn ($c) => $c->where('code', $cantonCode));
        }

        if ($q !== '') {
            $base->where(function ($inner) use ($q) {
                $inner->where('company_name', 'like', '%'.$q.'%')
                    ->orWhere('description', 'like', '%'.$q.'%')
                    ->orWhere('services_text', 'like', '%'.$q.'%')
                    ->orWhereHas('offices', function ($o) use ($q) {
                        $o->where('city', 'like', '%'.$q.'%')
                            ->orWhere('address_line', 'like', '%'.$q.'%')
                            ->orWhere('postal_code', 'like', '%'.$q.'%');
                    });
            });
        }

        $ids = $promotedExpertIds->values()->all();
        if (count($ids) > 0) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $base->orderByRaw('CASE WHEN id IN ('.$placeholders.') THEN 0 ELSE 1 END', $ids);
        }
        $base->orderBy('company_name');

        $experts = $base->clone()->paginate(12)->withQueryString();

        $mapExperts = $base->clone()
            ->limit(200)
            ->get();

        $cantons = Canton::query()
            ->orderBy('code')
            ->get()
            ->map(function (Canton $canton) {
                $canton->experts_count = Expert::query()
                    ->where('status', ExpertStatus::APPROVED)
                    ->where('is_public', true)
                    ->whereHas('offices', fn ($o) => $o->where('canton_id', $canton->id))
                    ->count();

                return $canton;
            });

        $totalPublic = Expert::query()
            ->where('status', ExpertStatus::APPROVED)
            ->where('is_public', true)
            ->count();

        return view('public.directory.index', [
            'experts' => $experts,
            'direction' => $direction,
            'cantonCode' => $cantonCode,
            'q' => $q,
            'promotedExpertIds' => $promotedExpertIds->flip(),
            'cantons' => $cantons,
            'totalPublic' => $totalPublic,
            'mapMarkers' => $this->buildMarkers($mapExperts, $promotedExpertIds),
            'mapsApiKey' => $this->settings->get(SettingKey::GOOGLE_MAPS_API_KEY),
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Expert>  $experts
     * @param  \Illuminate\Support\Collection<int, int>  $promotedIds
     * @return list<array<string, mixed>>
     */
    private function buildMarkers($experts, $promotedIds): array
    {
        $markers = [];

        foreach ($experts as $expert) {
            foreach ($expert->offices as $office) {
                $lat = $office->latitude !== null ? (float) $office->latitude : null;
                $lng = $office->longitude !== null ? (float) $office->longitude : null;

                if (($lat === null || $lng === null) && $office->canton) {
                    $lat = $office->canton->latitude !== null ? (float) $office->canton->latitude : null;
                    $lng = $office->canton->longitude !== null ? (float) $office->canton->longitude : null;
                }

                if ($lat === null || $lng === null) {
                    continue;
                }

                $markers[] = [
                    'id' => $office->id,
                    'expert_id' => $expert->id,
                    'lat' => $lat,
                    'lng' => $lng,
                    'name' => $expert->company_name,
                    'initial' => mb_strtoupper(mb_substr($expert->company_name, 0, 1)),
                    'logo' => $expert->logo_path ? asset('storage/'.$expert->logo_path) : null,
                    'city' => trim(($office->postal_code ?? '').' '.($office->city ?? '')),
                    'address' => trim((string) ($office->address_line ?? '')),
                    'canton' => $office->canton->code ?? '',
                    'url' => route('experts.show', $expert->slug),
                    'promoted' => $promotedIds->contains($expert->id),
                    'buy' => (bool) $expert->offers_buy,
                    'sell' => (bool) $expert->offers_sell,
                ];
            }
        }

        return $markers;
    }
}
