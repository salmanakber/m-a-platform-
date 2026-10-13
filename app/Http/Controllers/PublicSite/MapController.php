<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\ExpertOffice;
use App\Models\Promotion;
use App\Support\EventStatus;
use App\Support\ExpertStatus;
use App\Support\ProfessionalGroup;
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
        $group = trim((string) $request->query('gruppe', ''));
        if ($group !== '' && ! in_array($group, ProfessionalGroup::all(), true)) {
            $group = '';
        }

        $promotedIds = Promotion::query()
            ->where('status', PromotionStatus::ACTIVE)
            ->pluck('expert_id')
            ->unique();

        $offices = ExpertOffice::query()
            ->whereHas('expert', function ($query) use ($direction, $group) {
                $query->where('status', ExpertStatus::APPROVED)
                    ->where('is_public', true);

                if ($direction === 'kaufen') {
                    $query->where('offers_buy', true);
                } elseif ($direction === 'verkaufen') {
                    $query->where('offers_sell', true);
                }

                if ($group !== '') {
                    $query->where('professional_group', $group);
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
                'id' => 'office-'.$office->id,
                'type' => 'expert',
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

        // Published events with coordinates (or canton fallback) appear on the same map.
        $showEvents = $request->boolean('events', true);
        if ($showEvents) {
            $events = Event::query()
                ->where('status', EventStatus::PUBLISHED)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->with(['expert', 'canton'])
                ->when($cantonCode !== '', function ($query) use ($cantonCode) {
                    $query->whereHas('canton', fn ($c) => $c->where('code', $cantonCode));
                })
                ->limit(100)
                ->get();

            foreach ($events as $event) {
                $markers[] = [
                    'id' => 'event-'.$event->id,
                    'type' => 'event',
                    'expert_id' => $event->expert_id,
                    'lat' => (float) $event->latitude,
                    'lng' => (float) $event->longitude,
                    'name' => $event->title,
                    'initial' => 'E',
                    'logo' => null,
                    'city' => trim(($event->postal_code ?? '').' '.($event->city ?? '')),
                    'address' => trim((string) ($event->location_name ?? $event->address_line ?? '')),
                    'canton' => $event->canton->code ?? '',
                    'url' => route('events.show', $event->slug),
                    'promoted' => false,
                    'buy' => false,
                    'sell' => false,
                    'date' => $event->starts_on?->format('d.m.Y'),
                ];
            }
        }

        return view('public.map.index', [
            'offices' => $offices,
            'markers' => $markers,
            'direction' => $direction,
            'cantonCode' => $cantonCode,
            'mapsApiKey' => $this->settings->get(SettingKey::GOOGLE_MAPS_API_KEY),
            'promotedIds' => $promotedIds->flip(),
            'showEvents' => $showEvents,
        ]);
    }
}
