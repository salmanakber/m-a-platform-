<?php

namespace App\Http\Controllers\Expert;

use App\Http\Controllers\Controller;
use App\Models\Canton;
use App\Models\Event;
use App\Services\Event\EventAiService;
use App\Support\EventStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EventController extends Controller
{
    public function __construct(private EventAiService $eventAi)
    {
    }

    public function index(): View
    {
        $expert = Auth::user()?->expert;

        $events = $expert
            ? Event::query()->where('expert_id', $expert->id)->orderByDesc('starts_on')->paginate(15)
            : Event::query()->whereRaw('1 = 0')->paginate(15);

        return view('expert.events.index', compact('events'));
    }

    public function create(): View
    {
        return view('expert.events.create', [
            'cantons' => Canton::query()->orderBy('name_de')->get(),
        ]);
    }

    public function generateAi(Request $request): JsonResponse
    {
        $expert = $this->requireExpert();

        $validated = $request->validate([
            'briefing' => ['required', 'string', 'min:8', 'max:2000'],
            'event_type' => ['nullable', 'string', 'max:40'],
        ]);

        try {
            $draft = $this->eventAi->generateDraft(
                $expert,
                $validated['briefing'],
                $validated['event_type'] ?? null
            );
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage() ?: 'Entwurf konnte nicht erzeugt werden.',
            ], 422);
        }

        return response()->json($draft);
    }

    public function store(Request $request): RedirectResponse
    {
        $expert = $this->requireExpert();
        $validated = $this->validateEvent($request);

        $event = Event::query()->create([
            ...$validated,
            'expert_id' => $expert->id,
            'slug' => $this->uniqueSlug($validated['title']),
            'status' => EventStatus::PUBLISHED,
            'published_at' => now(),
            'is_crawled' => false,
            'needs_review' => false,
        ]);

        return redirect()
            ->route('expert.events.edit', $event)
            ->with('success', 'Event veröffentlicht.');
    }

    public function edit(Event $event): View
    {
        $this->authorizeEvent($event);

        return view('expert.events.edit', [
            'event' => $event,
            'cantons' => Canton::query()->orderBy('name_de')->get(),
        ]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeEvent($event);
        $validated = $this->validateEvent($request);
        $action = $request->input('action', 'save');

        $payload = $validated;
        if ($action === 'publish') {
            $payload['status'] = EventStatus::PUBLISHED;
            $payload['needs_review'] = false;
            $payload['published_at'] = $event->published_at ?: now();
        } elseif ($action === 'draft') {
            $payload['status'] = EventStatus::DRAFT;
            $payload['published_at'] = null;
        }

        $event->update($payload);

        return back()->with(
            'success',
            $action === 'publish' ? 'Event geprüft und veröffentlicht.' : 'Event gespeichert.'
        );
    }

    public function destroy(Event $event): RedirectResponse
    {
        $this->authorizeEvent($event);
        $event->delete();

        return redirect()
            ->route('expert.events.index')
            ->with('success', 'Event gelöscht.');
    }

    /** @return array<string, mixed> */
    private function validateEvent(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'event_type' => ['required', 'in:workshop,presentation,hearing,other'],
            'starts_on' => ['nullable', 'date'],
            'starts_at_time' => ['nullable', 'date_format:H:i'],
            'ends_on' => ['nullable', 'date'],
            'ends_at_time' => ['nullable', 'date_format:H:i'],
            'location_name' => ['nullable', 'string', 'max:255'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:120'],
            'canton_id' => ['nullable', 'exists:cantons,id'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'source_url' => ['nullable', 'url', 'max:500'],
            'price_label' => ['nullable', 'string', 'max:80'],
            'capacity_label' => ['nullable', 'string', 'max:80'],
            'registration_email' => ['nullable', 'email', 'max:255'],
            'is_top_event' => ['nullable', 'boolean'],
        ]);

        $data['is_top_event'] = $request->boolean('is_top_event');

        // Incomplete dates stay nullable — never invent them.
        return $data;
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base !== '' ? $base : Str::random(8);
        $original = $slug;
        $i = 1;
        while (Event::query()->where('slug', $slug)->exists()) {
            $slug = $original.'-'.$i;
            $i++;
        }

        return $slug;
    }

    private function requireExpert()
    {
        $expert = Auth::user()?->expert;
        if ($expert === null) {
            abort(403);
        }

        return $expert;
    }

    private function authorizeEvent(Event $event): void
    {
        $expert = Auth::user()?->expert;
        if ($expert === null || (int) $event->expert_id !== (int) $expert->id) {
            abort(403);
        }
    }
}
