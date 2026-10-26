<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Canton;
use App\Models\Expert;
use App\Models\ExpertOffice;
use App\Models\User;
use App\Services\Email\NotificationService;
use App\Services\Expert\ExpertAccountService;
use App\Services\Maps\GeocodingService;
use App\Services\Settings\SettingsService;
use App\Support\ExpertStatus;
use App\Support\ProfessionalGroup;
use App\Support\Role;
use App\Support\SettingKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class ExpertController extends Controller
{
    public function __construct(
        private NotificationService $notifications,
        private SettingsService $settings,
        private ExpertAccountService $accounts,
        private GeocodingService $geocoder
    ) {
    }

    public function index(Request $request): View
    {
        $status = $request->query('status');
        $q = trim((string) $request->query('q', ''));
        $cantonId = $request->query('canton_id');

        $experts = Expert::query()
            ->when($status !== null && $status !== '', fn ($query) => $query->where('status', $status))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('company_name', 'like', '%'.$q.'%')
                        ->orWhere('email', 'like', '%'.$q.'%')
                        ->orWhere('contact_person_name', 'like', '%'.$q.'%')
                        ->orWhere('contact_person_last_name', 'like', '%'.$q.'%');
                });
            })
            ->when($cantonId, function ($query) use ($cantonId) {
                $query->whereHas('offices', fn ($o) => $o->where('canton_id', $cantonId));
            })
            ->with(['user', 'offices.canton'])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.experts.index', [
            'experts' => $experts,
            'status' => $status,
            'q' => $q,
            'cantonId' => $cantonId,
            'cantons' => Canton::query()->orderBy('name_de')->get(),
            'statuses' => ExpertStatus::all(),
        ]);
    }

    public function create(): View
    {
        $cantons = Canton::query()->orderBy('name_de')->get();

        return view('admin.experts.create', [
            'cantons' => $cantons,
            'professionalGroups' => ProfessionalGroup::labels(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:experts,email', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:500'],
            'contact_person_name' => ['nullable', 'string', 'max:120'],
            'contact_person_last_name' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'professional_group' => ['nullable', 'in:'.implode(',', ProfessionalGroup::all())],
            'offers_buy' => ['nullable', 'boolean'],
            'offers_sell' => ['nullable', 'boolean'],
            'is_public' => ['nullable', 'boolean'],
            'approve_now' => ['nullable', 'boolean'],
            'address_line' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'city' => ['required', 'string', 'max:120'],
            'canton_id' => ['required', 'exists:cantons,id'],
        ], [
            'email.unique' => 'Diese E-Mail wird bereits verwendet.',
        ]);

        try {
            $expert = DB::transaction(function () use ($validated, $request) {
                $approve = $request->boolean('approve_now');
                $slugBase = Str::slug($validated['company_name']);
                $slug = $slugBase !== '' ? $slugBase : 'experte';
                $original = $slug;
                $i = 2;
                while (Expert::withTrashed()->where('slug', $slug)->exists()) {
                    $slug = $original.'-'.$i;
                    $i++;
                }

                $displayName = trim(($validated['contact_person_name'] ?? '').' '.($validated['contact_person_last_name'] ?? ''));
                if ($displayName === '') {
                    $displayName = $validated['company_name'];
                }

                $password = ! empty($validated['password'])
                    ? $validated['password']
                    : Str::random(32);

                $user = User::query()->create([
                    'name' => $displayName,
                    'email' => $validated['email'],
                    'password' => Hash::make($password),
                    'role' => Role::EXPERT,
                    'is_active' => true,
                ]);

                $expert = Expert::query()->create([
                    'user_id' => $user->id,
                    'company_name' => $validated['company_name'],
                    'slug' => $slug,
                    'email' => $validated['email'],
                    'phone' => $validated['phone'] ?? null,
                    'website' => $validated['website'] ?? null,
                    'contact_person_name' => $validated['contact_person_name'] ?? null,
                    'contact_person_last_name' => $validated['contact_person_last_name'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'professional_group' => $validated['professional_group'] ?? ProfessionalGroup::MA_SPECIALIST,
                    'offers_buy' => $request->boolean('offers_buy'),
                    'offers_sell' => $request->boolean('offers_sell'),
                    'status' => $approve ? ExpertStatus::APPROVED : ExpertStatus::PENDING,
                    'is_public' => $approve ? true : $request->boolean('is_public'),
                    'imported_from' => 'admin_manual',
                ]);

                $canton = Canton::query()->findOrFail($validated['canton_id']);
                $coords = $this->geocoder->geocodeSwissAddress(
                    $validated['address_line'],
                    $validated['postal_code'],
                    $validated['city'],
                    $canton->code
                );

                ExpertOffice::query()->create([
                    'expert_id' => $expert->id,
                    'label' => 'Hauptsitz',
                    'address_line' => $validated['address_line'],
                    'postal_code' => $validated['postal_code'],
                    'city' => $validated['city'],
                    'canton_id' => $canton->id,
                    'latitude' => $coords['latitude'] ?? $canton->latitude,
                    'longitude' => $coords['longitude'] ?? $canton->longitude,
                    'is_primary' => true,
                    'sort_order' => 0,
                ]);

                return $expert->fresh();
            });
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->withErrors(['company_name' => 'Anlegen fehlgeschlagen: '.$e->getMessage()]);
        }

        return redirect()
            ->route('admin.experts.edit', $expert)
            ->with('success', 'Experte manuell angelegt.');
    }

    public function destroy(Expert $expert): RedirectResponse
    {
        $name = $expert->company_name;
        $expert->delete(); // soft delete

        return redirect()
            ->route('admin.experts.index')
            ->with('success', 'Experte «'.$name.'» gelöscht (kann technisch wiederhergestellt werden).');
    }

    public function show(Expert $expert): View
    {
        $expert->load([
            'user',
            'offices.canton',
            'articles.category',
            'events.canton',
            'promotions.canton',
            'aiSuggestions' => fn ($q) => $q->where('status', 'pending')->orderByDesc('created_at')->limit(20),
            'crawlJobs' => fn ($q) => $q->orderByDesc('created_at')->limit(8),
        ]);

        return view('admin.experts.show', [
            'expert' => $expert,
            'professionalGroups' => ProfessionalGroup::labels(),
            'articles' => $expert->articles->sortByDesc('created_at')->values(),
            'events' => $expert->events->sortByDesc('starts_on')->values(),
            'aiSuggestions' => $expert->aiSuggestions,
            'crawlJobs' => $expert->crawlJobs,
        ]);
    }

    public function edit(Expert $expert): View
    {
        $expert->load(['offices.canton', 'user']);
        $cantons = Canton::query()->orderBy('name_de')->get();

        $isSharedImportLogin = $this->accounts->isSharedImportUser($expert->user);
        $hasPersonalLogin = $this->accounts->hasPersonalLogin($expert);
        $loginStatusHint = null;
        if ($isSharedImportLogin) {
            $loginStatusHint = 'Dieser Experte hat noch kein persönliches Login (Import-Konto) — Speichern legt eines an.';
        } elseif ($hasPersonalLogin) {
            $state = ($expert->user && $expert->user->is_active) ? 'aktiv' : 'deaktiviert';
            $loginStatusHint = 'Persönliches Login vorhanden ('.$state.').';
        }

        return view('admin.experts.edit', [
            'expert' => $expert,
            'professionalGroups' => ProfessionalGroup::labels(),
            'cantons' => $cantons,
            'cantonCodeMap' => $cantons->pluck('id', 'code'),
            'suggestUrl' => route('admin.experts.offices.suggest', $expert),
            'mapsApiKey' => $this->settings->get(SettingKey::GOOGLE_MAPS_API_KEY),
            'hasPersonalLogin' => $hasPersonalLogin,
            'isSharedImportLogin' => $isSharedImportLogin,
            'loginStatusHint' => $loginStatusHint,
        ]);
    }

    public function update(Request $request, Expert $expert): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('experts', 'email')->ignore($expert->id),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:500'],
            'description' => ['nullable', 'string'],
            'services_text' => ['nullable', 'string'],
            'offers_buy' => ['nullable', 'boolean'],
            'offers_sell' => ['nullable', 'boolean'],
            'professional_group' => ['nullable', 'in:'.implode(',', ProfessionalGroup::all())],
            'logo_position' => ['nullable', 'in:left,center,right'],
            'is_public' => ['nullable', 'boolean'],
            'crawl_enabled' => ['nullable', 'boolean'],
            'website_crawl_url' => ['nullable', 'url', 'max:500'],
            'contact_person_name' => ['nullable', 'string', 'max:120'],
            'contact_person_last_name' => ['nullable', 'string', 'max:120'],
            'public_rating' => ['nullable', 'numeric', 'between:0,5'],
            'public_deals_count' => ['nullable', 'integer', 'min:0', 'max:99999'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
        ], [
            'email.unique' => 'Diese E-Mail-Adresse wird bereits verwendet.',
        ]);

        $data = [
            'company_name' => $validated['company_name'],
            'phone' => $validated['phone'] ?? null,
            'website' => $validated['website'] ?? null,
            'description' => $validated['description'] ?? null,
            'services_text' => $validated['services_text'] ?? null,
            'offers_buy' => $request->boolean('offers_buy'),
            'offers_sell' => $request->boolean('offers_sell'),
            'professional_group' => $validated['professional_group'] ?? ProfessionalGroup::MA_SPECIALIST,
            'logo_position' => $validated['logo_position'] ?? 'left',
            'is_public' => $request->boolean('is_public'),
            'crawl_enabled' => $request->boolean('crawl_enabled'),
            'website_crawl_url' => $validated['website_crawl_url'] ?? null,
            'contact_person_name' => $validated['contact_person_name'] ?? null,
            'contact_person_last_name' => $validated['contact_person_last_name'] ?? null,
            'public_rating' => $validated['public_rating'] ?? null,
            'public_deals_count' => $validated['public_deals_count'] ?? null,
        ];

        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            if ($expert->logo_path) {
                Storage::disk('public')->delete($expert->logo_path);
            }
            Storage::disk('public')->makeDirectory('experts/logos');
            $data['logo_path'] = $request->file('logo')->store('experts/logos', 'public');
            $data['logo_locked'] = true;
        }

        $expert->update($data);

        try {
            $this->accounts->updateCredentials($expert->fresh(), $validated['email'], null, true);
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['email' => $e->getMessage()]);
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->withErrors(['email' => 'Login konnte nicht aktualisiert werden: '.$e->getMessage()]);
        }

        return redirect()
            ->route('admin.experts.edit', $expert)
            ->with('success', 'Experte aktualisiert.');
    }

    public function updateCredentials(Request $request, Expert $expert): RedirectResponse
    {
        $validated = $request->validate([
            'login_email' => ['required', 'email', 'max:255'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'send_reset_link' => ['nullable', 'boolean'],
        ], [
            'password.min' => 'Das Passwort muss mindestens 8 Zeichen haben.',
            'password.confirmed' => 'Passwort und Bestätigung stimmen nicht überein.',
        ]);

        try {
            $result = $this->accounts->updateCredentials(
                $expert,
                $validated['login_email'],
                $validated['password'] ?? null,
                true
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['login_email' => $e->getMessage()]);
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->withErrors(['login_email' => $e->getMessage()]);
        }

        $parts = ['Login-Zugang gespeichert.'];
        if ($result['created']) {
            $parts[] = 'Persönliches Konto wurde angelegt.';
        }
        if ($result['password_set']) {
            $parts[] = 'Neues Passwort gesetzt.';
        }

        if ($request->boolean('send_reset_link')) {
            $reset = $this->accounts->sendPasswordResetLink($expert->fresh());
            $parts[] = $reset['ok']
                ? 'Passwort-Reset-Link an '.$reset['email'].' gesendet.'
                : 'Reset-Link fehlgeschlagen: '.$reset['message'];
        }

        return redirect()
            ->route('admin.experts.edit', $expert)
            ->with('success', implode(' ', $parts))
            ->with('credentials_hint', true);
    }

    public function sendPasswordReset(Expert $expert): RedirectResponse
    {
        $result = $this->accounts->sendPasswordResetLink($expert);

        if (! $result['ok']) {
            return back()->withErrors(['credentials' => $result['message']]);
        }

        return back()
            ->with('success', 'Passwort-Reset-Link an '.$result['email'].' gesendet.')
            ->with('credentials_hint', true);
    }

    public function approve(Expert $expert): RedirectResponse
    {
        $expert->update([
            'status' => ExpertStatus::APPROVED,
            'is_public' => true,
            'rejection_reason' => null,
        ]);

        try {
            $this->notifications->sendExpertRegistrationWelcome($expert->fresh());
        } catch (\Throwable) {
            // E-Mail optional when templates or Resend fehlen.
        }

        return back()->with('success', 'Experte freigegeben.');
    }

    public function reject(Request $request, Expert $expert): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $expert->update([
            'status' => ExpertStatus::REJECTED,
            'is_public' => false,
            'rejection_reason' => $validated['rejection_reason'] ?? null,
        ]);

        return back()->with('success', 'Experte abgelehnt.');
    }

    public function deactivate(Expert $expert): RedirectResponse
    {
        $expert->update([
            'status' => ExpertStatus::DISABLED,
            'is_public' => false,
        ]);

        return back()->with('success', 'Experte deaktiviert.');
    }

    public function activate(Expert $expert): RedirectResponse
    {
        $expert->update([
            'status' => ExpertStatus::APPROVED,
            'is_public' => true,
            'rejection_reason' => null,
        ]);

        return back()->with('success', 'Experte wieder aktiviert / freigegeben.');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:experts,id'],
            'action' => ['required', 'in:approve,reject,deactivate,delete,set_password,send_password_reset'],
            'rejection_reason' => ['nullable', 'string', 'max:2000'],
            'bulk_password' => ['nullable', 'string', 'min:8'],
            'bulk_password_confirmation' => ['nullable', 'same:bulk_password'],
            'confirm_reset_spam_notice' => ['nullable', 'boolean'],
        ], [
            'bulk_password.min' => 'Das Bulk-Passwort muss mindestens 8 Zeichen haben.',
            'bulk_password_confirmation.same' => 'Passwort und Bestätigung stimmen nicht überein.',
        ]);

        $experts = Expert::query()->whereIn('id', $validated['ids'])->with('user')->get();
        $action = $validated['action'];

        if ($action === 'set_password') {
            if (empty($validated['bulk_password'])) {
                return back()->withErrors(['bulk_password' => 'Bitte ein neues Passwort für die Auswahl eingeben.']);
            }

            $result = $this->accounts->setPasswordBulk($experts, $validated['bulk_password']);
            $msg = $result['updated'].' Passwort(e) gesetzt.';
            if ($result['failed'] > 0) {
                $msg .= ' '.$result['failed'].' fehlgeschlagen.';
            }

            return back()
                ->with('success', $msg)
                ->with('bulk_errors', $result['errors']);
        }

        if ($action === 'send_password_reset') {
            if (! $request->boolean('confirm_reset_spam_notice')) {
                return back()->withErrors([
                    'confirm_reset_spam_notice' => 'Bitte bestätigen Sie den Hinweis zum Spam-Ordner, bevor Reset-Links gesendet werden.',
                ]);
            }

            $result = $this->accounts->sendPasswordResetLinksBulk($experts);
            $msg = $result['sent'].' Passwort-Reset-Link(s) gesendet.';
            if ($result['failed'] > 0) {
                $msg .= ' '.$result['failed'].' fehlgeschlagen.';
            }

            return back()
                ->with('success', $msg)
                ->with('credentials_hint', true)
                ->with('bulk_errors', $result['errors']);
        }

        $count = 0;

        foreach ($experts as $expert) {
            if ($action === 'approve') {
                $expert->update([
                    'status' => ExpertStatus::APPROVED,
                    'is_public' => true,
                    'rejection_reason' => null,
                ]);
                try {
                    $this->notifications->sendExpertRegistrationWelcome($expert->fresh());
                } catch (Throwable) {
                }
            } elseif ($action === 'reject') {
                $expert->update([
                    'status' => ExpertStatus::REJECTED,
                    'is_public' => false,
                    'rejection_reason' => $validated['rejection_reason'] ?? 'Bulk-Ablehnung',
                ]);
            } elseif ($action === 'delete') {
                $expert->delete();
            } else {
                $expert->update([
                    'status' => ExpertStatus::DISABLED,
                    'is_public' => false,
                ]);
            }
            $count++;
        }

        $labels = [
            'approve' => 'freigegeben',
            'reject' => 'abgelehnt',
            'deactivate' => 'deaktiviert',
            'delete' => 'gelöscht',
        ];

        return back()->with('success', $count.' Experte(n) '.$labels[$action].'.');
    }
}
