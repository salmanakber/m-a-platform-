<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\Canton;
use App\Models\Event;
use App\Support\EventStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class EventController extends Controller
{
    public function index(Request $request): View
    {
        $type = trim((string) $request->query('typ', ''));
        $cantonCode = strtoupper(trim((string) $request->query('kanton', '')));

        $events = Event::query()
            ->where('status', EventStatus::PUBLISHED)
            ->with(['expert', 'canton'])
            ->when($type !== '', fn ($q) => $q->where('event_type', $type))
            ->when($cantonCode !== '', fn ($q) => $q->whereHas('canton', fn ($c) => $c->where('code', $cantonCode)))
            ->orderByRaw('CASE WHEN starts_on IS NULL THEN 1 ELSE 0 END')
            ->orderBy('starts_on')
            ->orderBy('starts_at_time')
            ->paginate(12)
            ->withQueryString();

        return view('public.events.index', [
            'events' => $events,
            'type' => $type,
            'cantonCode' => $cantonCode,
            'cantons' => Canton::query()->orderBy('name_de')->get(),
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

    public function register(Request $request, string $slug): RedirectResponse
    {
        $event = Event::query()
            ->where('slug', $slug)
            ->where('status', EventStatus::PUBLISHED)
            ->with('expert')
            ->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $to = $event->registrationTargetEmail();
        if (! $to) {
            return back()->withErrors(['email' => 'Für dieses Event ist keine Anmelde-E-Mail hinterlegt.']);
        }

        $body = "Neue Event-Anmeldung\n\n"
            ."Event: {$event->title}\n"
            .'Datum: '.($event->starts_on?->format('d.m.Y') ?? '—')."\n\n"
            ."Name: {$validated['name']}\n"
            ."E-Mail: {$validated['email']}\n"
            .'Telefon: '.($validated['phone'] ?? '—')."\n"
            .'Nachricht: '.($validated['message'] ?? '—')."\n";

        try {
            Mail::raw($body, function ($message) use ($to, $event, $validated) {
                $message->to($to)
                    ->replyTo($validated['email'], $validated['name'])
                    ->subject('Event-Anmeldung: '.$event->title);
            });
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->withErrors(['email' => 'Anmeldung konnte nicht gesendet werden. Bitte später erneut versuchen.']);
        }

        return back()->with('success', 'Ihre Anmeldung wurde an den Veranstalter gesendet.');
    }
}
