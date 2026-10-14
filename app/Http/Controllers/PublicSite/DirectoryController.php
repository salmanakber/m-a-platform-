<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\Canton;
use App\Models\Event;
use App\Models\Expert;
use App\Models\Promotion;
use App\Support\EventStatus;
use App\Support\ExpertStatus;
use App\Support\ProfessionalGroup;
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
        $group = trim((string) $request->query('gruppe', ''));
        if ($group !== '' && ! in_array($group, ProfessionalGroup::all(), true)) {
            $group = '';
        }
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

        if ($group !== '') {
            $base->where('professional_group', $group);
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
            'group' => $group,
            'professionalGroups' => ProfessionalGroup::labels(),
            'q' => $q,
            'promotedExpertIds' => $promotedExpertIds->flip(),
            'cantons' => $cantons,
            'totalPublic' => $totalPublic,
            'mapMarkers' => $this->buildMarkers($mapExperts, $promotedExpertIds, $cantonCode),
            'mapsApiKey' => $this->settings->get(SettingKey::GOOGLE_MAPS_API_KEY),
            'suggestUrl' => route('map.suggest'),
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Expert>  $experts
     * @param  \Illuminate\Support\Collection<int, int>  $promotedIds
     * @return list<array<string, mixed>>
     */
    private function buildMarkers($experts, $promotedIds, string $cantonCode = ''): array
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

                $groupKey = $expert->professional_group ?: ProfessionalGroup::MA_SPECIALIST;

                $markers[] = [
                    'id' => 'office-'.$office->id,
                    'type' => 'expert',
                    'expert_id' => $expert->id,
                    'lat' => $lat,
                    'lng' => $lng,
                    'name' => $expert->company_name,
                    'initial' => mb_strtoupper(mb_substr($expert->company_name, 0, 1)),
                    'logo' => $expert->logo_path ? asset('storage/'.$expert->logo_path) : null,
                    'logo_position' => $expert->logo_position ?: 'left',
                    'city' => trim(($office->postal_code ?? '').' '.($office->city ?? '')),
                    'address' => trim((string) ($office->address_line ?? '')),
                    'canton' => $office->canton->code ?? '',
                    'group' => $groupKey,
                    'group_label' => ProfessionalGroup::label($groupKey),
                    'color' => ProfessionalGroup::color($groupKey),
                    'url' => route('experts.show', $expert->slug),
                    'promoted' => $promotedIds->contains($expert->id),
                    'buy' => (bool) $expert->offers_buy,
                    'sell' => (bool) $expert->offers_sell,
                ];
            }
        }

        $events = Event::query()
            ->where('status', EventStatus::PUBLISHED)
            ->with('canton')
            ->orderBy('starts_on')
            ->limit(80)
            ->get();

        foreach ($events as $event) {
            $lat = $event->latitude !== null ? (float) $event->latitude : null;
            $lng = $event->longitude !== null ? (float) $event->longitude : null;
            if (($lat === null || $lng === null) && $event->canton) {
                $lat = $event->canton->latitude !== null ? (float) $event->canton->latitude : null;
                $lng = $event->canton->longitude !== null ? (float) $event->canton->longitude : null;
            }
            if ($lat === null || $lng === null) {
                continue;
            }
            $code = $event->canton->code ?? '';
            if ($cantonCode !== '' && $code !== '' && $code !== $cantonCode) {
                continue;
            }

            $markers[] = [
                'id' => 'event-'.$event->id,
                'type' => 'event',
                'expert_id' => $event->expert_id,
                'lat' => $lat,
                'lng' => $lng,
                'name' => $event->title,
                'initial' => 'E',
                'logo' => null,
                'city' => trim(($event->postal_code ?? '').' '.($event->city ?? '')),
                'address' => trim((string) ($event->location_name ?? $event->address_line ?? '')),
                'canton' => $code,
                'url' => route('events.show', $event->slug),
                'promoted' => false,
                'buy' => false,
                'sell' => false,
                'date' => $event->starts_on?->format('d.m.Y'),
                'event_type' => $event->event_type,
                'cta_label' => 'Event öffnen',
            ];
        }

        return $markers;
    }
}
