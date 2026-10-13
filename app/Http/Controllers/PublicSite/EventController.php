<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Support\EventStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request): View
    {
        $type = trim((string) $request->query('typ', ''));

        $events = Event::query()
            ->where('status', EventStatus::PUBLISHED)
            ->with(['expert', 'canton'])
            ->when($type !== '', fn ($q) => $q->where('event_type', $type))
            ->orderByRaw('CASE WHEN starts_on IS NULL THEN 1 ELSE 0 END')
            ->orderBy('starts_on')
            ->orderBy('starts_at_time')
            ->paginate(12)
            ->withQueryString();

        return view('public.events.index', [
            'events' => $events,
            'type' => $type,
        ]);
    }

    public function show(string $slug): View
    {
        $event = Event::query()
            ->where('slug', $slug)
            ->where('status', EventStatus::PUBLISHED)
            ->with(['expert', 'canton'])
            ->firstOrFail();

        return view('public.events.show', [
            'event' => $event,
        ]);
    }
}
