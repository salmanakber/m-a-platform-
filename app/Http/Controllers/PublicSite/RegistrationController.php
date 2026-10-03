<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\Canton;
use App\Services\Expert\ExpertRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function __construct(private ExpertRegistrationService $registrationService)
    {
    }

    public function create(): View
    {
        session()->forget('registration_thanks');

        $cantons = Canton::query()->orderBy('name_de')->get();

        return view('public.registration.create', [
            'cantons' => $cantons,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'contact_person_name' => ['required', 'string', 'max:255'],
            'contact_person_last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
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
        ]);

        $logoPath = null;
        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory('experts/logos');
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
        ]);

        return redirect()->route('registration.thanks');
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
        ]);
    }
}
