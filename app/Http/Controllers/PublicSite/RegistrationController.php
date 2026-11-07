<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\Canton;
use App\Models\Expert;
use App\Models\User;
use App\Services\Expert\ExpertRegistrationService;
use App\Support\TextLines;
use App\Support\UrlNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function __construct(private ExpertRegistrationService $registrationService)
    {
    }

    public function create(): View
    {
        session()->forget('registration_thanks');

        return view('public.registration.create', [
            'cantons' => Canton::query()->orderBy('name_de')->get(),
        ]);
    }

    public function createAdInterim(): View
    {
        session()->forget('registration_thanks');

        return view('public.registration.ad-interim', [
            'cantons' => Canton::query()->orderBy('name_de')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        UrlNormalizer::mergeRequest($request, ['website']);

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'contact_person_name' => ['required', 'string', 'max:255'],
            'contact_person_last_name' => ['required', 'string', 'max:255'],
            'email' => $this->emailRules(),
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['required', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string'],
            'services_text' => ['nullable', 'string'],
            'offers_buy' => ['nullable', 'boolean'],
            'offers_sell' => ['nullable', 'boolean'],
            'address_line' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'city' => ['required', 'string', 'max:255'],
            'canton_id' => ['required', 'exists:cantons,id'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
        ], [
            'email.unique' => 'Diese E-Mail-Adresse ist bereits registriert. Bitte melden Sie sich an oder verwenden Sie eine andere Adresse.',
        ]);

        $logoPath = null;
        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            Storage::disk('public')->makeDirectory('experts/logos');
            $logoPath = $request->file('logo')->store('experts/logos', 'public');
        }

        $this->registrationService->register(
            [
                'name' => trim($validated['contact_person_name'].' '.($validated['contact_person_last_name'] ?? '')),
                'email' => $validated['email'],
                'password' => $validated['password'],
            ],
            [
                'company_name' => $validated['company_name'],
                'contact_person_name' => $validated['contact_person_name'],
                'contact_person_last_name' => $validated['contact_person_last_name'] ?? null,
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'website' => $validated['website'] ?? null,
                'description' => $validated['description'] ?? null,
                'services_text' => $validated['services_text'] ?? null,
                'offers_buy' => $request->boolean('offers_buy'),
                'offers_sell' => $request->boolean('offers_sell'),
                'logo_path' => $logoPath,
            ],
            [
                'address_line' => $validated['address_line'],
                'postal_code' => $validated['postal_code'],
                'city' => $validated['city'],
                'canton_id' => $validated['canton_id'],
                'is_primary' => true,
            ]
        );

        $request->session()->put('registration_thanks', [
            'company' => $validated['company_name'],
            'email' => $validated['email'],
            'kind' => 'company',
        ]);

        return redirect()->route('registration.thanks');
    }

    public function storeAdInterim(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'contact_person_name' => ['required', 'string', 'max:255'],
            'contact_person_last_name' => ['required', 'string', 'max:255'],
            'email' => $this->emailRules(),
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['required', 'string', 'max:50'],
            'postal_code' => ['required', 'string', 'max:20'],
            'city' => ['required', 'string', 'max:255'],
            'canton_id' => ['required', 'exists:cantons,id'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'equity_capital' => ['required', 'string', 'max:120'],
            'experience_areas' => ['nullable', 'string'],
            'diplomas' => ['nullable', 'string'],
            'interim_criteria' => ['nullable', 'string'],
            'description' => ['nullable', 'string', 'max:5000'],
            'contact_photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'cv' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:8192'],
            'intends_purchase' => ['nullable', 'boolean'],
        ], [
            'email.unique' => 'Diese E-Mail-Adresse ist bereits registriert. Bitte melden Sie sich an oder verwenden Sie eine andere Adresse.',
            'equity_capital.required' => 'Bitte geben Sie Ihr verfügbares Eigenkapital an (z. B. 200kCHF).',
        ]);

        $photoPath = null;
        if ($request->hasFile('contact_photo') && $request->file('contact_photo')->isValid()) {
            Storage::disk('public')->makeDirectory('experts/contacts');
            $photoPath = $request->file('contact_photo')->store('experts/contacts', 'public');
        }

        $cvPath = null;
        if ($request->hasFile('cv') && $request->file('cv')->isValid()) {
            Storage::disk('public')->makeDirectory('experts/cvs');
            $cvPath = $request->file('cv')->store('experts/cvs', 'public');
        }

        $first = trim($validated['contact_person_name']);
        $last = trim($validated['contact_person_last_name']);
        $display = trim($first.' '.$last);

        $description = trim((string) ($validated['description'] ?? ''));
        if ($request->boolean('intends_purchase')) {
            $note = 'Ziel: Ad Interim Mandate mit Absicht, das Unternehmen später zu übernehmen (Future Owner).';
            $description = $description !== '' ? $description."\n\n".$note : $note;
        }

        $expert = $this->registrationService->registerAdInterim(
            [
                'name' => $display,
                'email' => $validated['email'],
                'password' => $validated['password'],
            ],
            [
                'contact_person_name' => $first,
                'contact_person_last_name' => $last,
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'description' => $description !== '' ? $description : null,
                'equity_capital' => $validated['equity_capital'],
                'experience_areas' => TextLines::fromInput($validated['experience_areas'] ?? null),
                'diplomas' => TextLines::fromInput($validated['diplomas'] ?? null),
                'interim_criteria' => TextLines::fromInput($validated['interim_criteria'] ?? null),
                'contact_photo_path' => $photoPath,
                'cv_path' => $cvPath,
                'offers_buy' => true,
            ],
            [
                'address_line' => $validated['address_line'] ?: $validated['city'],
                'postal_code' => $validated['postal_code'],
                'city' => $validated['city'],
                'canton_id' => $validated['canton_id'],
                'is_primary' => true,
            ]
        );

        // Log in immediately and open the Ad Interim profile for completion.
        if ($expert->user) {
            Auth::login($expert->user);
            $request->session()->regenerate();
        }

        return redirect()
            ->route('expert.profile.edit')
            ->with('success', 'Registrierung erfolgreich. Ihr Ad-Interim-Profil wird geprüft — Sie können es hier bereits ergänzen.');
    }

    public function thanks(): View|RedirectResponse
    {
        $thanks = session('registration_thanks');

        if (! is_array($thanks)) {
            return redirect()->route('registration.create');
        }

        return view('public.registration.thanks', [
            'company' => $thanks['company'] ?? null,
            'email' => $thanks['email'] ?? null,
            'kind' => $thanks['kind'] ?? 'company',
        ]);
    }

    /** @return list<mixed> */
    private function emailRules(): array
    {
        return [
            'required',
            'email',
            'max:255',
            Rule::unique('users', 'email'),
            Rule::unique('experts', 'email'),
            function (string $attribute, mixed $value, \Closure $fail) {
                $email = mb_strtolower(trim((string) $value));
                if ($email === '') {
                    return;
                }
                $userExists = User::query()->whereRaw('LOWER(email) = ?', [$email])->exists();
                $expertExists = Expert::query()->whereRaw('LOWER(email) = ?', [$email])->exists();
                if ($userExists || $expertExists) {
                    $fail('Diese E-Mail-Adresse ist bereits registriert. Bitte melden Sie sich an oder verwenden Sie eine andere Adresse.');
                }
            },
        ];
    }
}
