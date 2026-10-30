<?php

namespace App\Services\Expert;

use App\Models\Expert;
use App\Models\User;
use App\Support\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ExpertAccountService
{
    public const IMPORT_OWNER_EMAIL = 'import@nachfolge-experten.local';

    public function isSharedImportUser(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return mb_strtolower((string) $user->email) === self::IMPORT_OWNER_EMAIL;
    }

    /**
     * Ensure the expert has a personal, login-capable User (not the shared import owner).
     */
    public function ensureLoginUser(Expert $expert, ?string $email = null): User
    {
        $email = $this->normalizeEmail($email ?? (string) $expert->email);

        if ($email === '' || $email === self::IMPORT_OWNER_EMAIL) {
            throw new RuntimeException('Für den Login wird eine gültige Experten-E-Mail benötigt.');
        }

        return DB::transaction(function () use ($expert, $email) {
            $expert->refresh();
            $expert->loadMissing('user');
            $user = $expert->user;
            $name = $this->displayName($expert);

            if ($user && ! $this->isSharedImportUser($user)) {
                return $user;
            }

            $existing = User::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();

            if ($existing && ! $this->isSharedImportUser($existing)) {
                // Same login may own several profiles (e.g. M&A-Spezialist + Marktplatz).
                $expert->forceFill(['user_id' => $existing->id])->save();

                return $existing->fresh();
            }

            $newUser = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(40)),
                'role' => Role::EXPERT,
                'is_active' => true,
            ]);

            $expert->forceFill(['user_id' => $newUser->id])->save();

            return $newUser;
        });
    }

    /**
     * Sync expert email + optional password to a personal login account.
     *
     * @return array{user: User, created: bool, password_set: bool}
     */
    public function updateCredentials(
        Expert $expert,
        string $email,
        ?string $password = null,
        bool $activate = true
    ): array {
        $email = $this->normalizeEmail($email);

        if ($email === '') {
            throw new RuntimeException('E-Mail darf nicht leer sein.');
        }

        $this->assertEmailAvailable($expert, $email);

        $hadPersonal = $expert->user && ! $this->isSharedImportUser($expert->user);
        $user = $this->ensureLoginUser($expert, $email);
        $passwordSet = false;

        $payload = [
            'email' => $email,
            'name' => $this->displayName($expert),
            'role' => Role::EXPERT,
        ];

        if ($activate) {
            $payload['is_active'] = true;
        }

        if ($password !== null && $password !== '') {
            $payload['password'] = Hash::make($password);
            $passwordSet = true;
        }

        $user->fill($payload)->save();
        $expert->forceFill(['email' => $email, 'user_id' => $user->id])->save();

        return [
            'user' => $user->fresh(),
            'created' => ! $hadPersonal,
            'password_set' => $passwordSet,
        ];
    }

    /**
     * Send a password-reset link to the expert's login email.
     *
     * @return array{ok: bool, email: string, message: string}
     */
    public function sendPasswordResetLink(Expert $expert): array
    {
        $email = $this->normalizeEmail((string) $expert->email);

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'ok' => false,
                'email' => $email,
                'message' => 'Keine gültige E-Mail am Profil.',
            ];
        }

        try {
            $user = $this->ensureLoginUser($expert, $email);

            if (mb_strtolower((string) $user->email) !== $email) {
                $this->assertEmailAvailable($expert, $email);
                $user->forceFill([
                    'email' => $email,
                    'name' => $this->displayName($expert),
                    'is_active' => true,
                ])->save();
            } elseif (! $user->is_active) {
                $user->forceFill(['is_active' => true])->save();
            }

            $token = Password::broker()->createToken($user);
            $user->sendPasswordResetNotification($token);

            return [
                'ok' => true,
                'email' => $user->email,
                'message' => 'Reset-Link gesendet an '.$user->email,
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'email' => $email,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @param  iterable<Expert>  $experts
     * @return array{sent: int, failed: int, errors: list<string>}
     */
    public function sendPasswordResetLinksBulk(iterable $experts): array
    {
        $sent = 0;
        $failed = 0;
        $errors = [];

        foreach ($experts as $expert) {
            $result = $this->sendPasswordResetLink($expert);
            if ($result['ok']) {
                $sent++;
            } else {
                $failed++;
                if (count($errors) < 25) {
                    $errors[] = ($expert->company_name ?: 'Experte #'.$expert->id).': '.$result['message'];
                }
            }
            // Be kind to the mail provider on larger batches.
            usleep(120000);
        }

        return compact('sent', 'failed', 'errors');
    }

    /**
     * @param  iterable<Expert>  $experts
     * @return array{updated: int, failed: int, errors: list<string>}
     */
    public function setPasswordBulk(iterable $experts, string $password): array
    {
        $updated = 0;
        $failed = 0;
        $errors = [];

        foreach ($experts as $expert) {
            try {
                $email = $this->normalizeEmail((string) $expert->email);
                if ($email === '') {
                    throw new RuntimeException('Keine E-Mail am Profil.');
                }
                $this->updateCredentials($expert, $email, $password, true);
                $updated++;
            } catch (Throwable $e) {
                $failed++;
                if (count($errors) < 25) {
                    $errors[] = ($expert->company_name ?: 'Experte #'.$expert->id).': '.$e->getMessage();
                }
            }
        }

        return compact('updated', 'failed', 'errors');
    }

    public function hasPersonalLogin(Expert $expert): bool
    {
        $expert->loadMissing('user');

        return $expert->user !== null && ! $this->isSharedImportUser($expert->user);
    }

    private function assertEmailAvailable(Expert $expert, string $email): void
    {
        // Duplicate contact e-mails across expert profiles are allowed (M&A + Marktplatz).
        // Login users may be shared; only block if the address belongs to a non-expert account.
        $expert->loadMissing('user');

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        if ($user === null) {
            return;
        }

        if ($expert->user && (int) $expert->user_id === (int) $user->id) {
            return;
        }

        if ($user->role === Role::EXPERT || $this->isSharedImportUser($user)) {
            return;
        }

        throw new RuntimeException('Diese E-Mail ist bereits als Login vergeben.');
    }

    private function displayName(Expert $expert): string
    {
        $name = trim(($expert->contact_person_name ?? '').' '.($expert->contact_person_last_name ?? ''));

        return $name !== '' ? $name : (string) $expert->company_name;
    }

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
