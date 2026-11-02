<?php

namespace App\Models;

use App\Support\Role;
use App\Services\Email\NotificationService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function sendPasswordResetNotification($token): void
    {
        $url = url(route('password.reset', [
            'token' => $token,
            'email' => $this->getEmailForPasswordReset(),
        ], false));

        app(NotificationService::class)->sendPasswordReset($this, $url);
    }

    public function expert(): HasOne
    {
        // Primary profile for the portal (lowest id when several share a login).
        return $this->hasOne(Expert::class)->orderBy('id');
    }

    public function experts(): HasMany
    {
        return $this->hasMany(Expert::class);
    }

    /**
     * Resolve the expert portal profile. If user_id is missing but the expert
     * e-mail matches this login, re-link automatically (common after imports).
     */
    public function resolvePortalExpert(bool $heal = true): ?Expert
    {
        $this->loadMissing('expert');

        if ($this->relationLoaded('expert') && $this->getRelation('expert') !== null) {
            return $this->getRelation('expert');
        }

        // Fresh query (avoids stale null relation after heal).
        $linked = Expert::query()->where('user_id', $this->id)->orderBy('id')->first();
        if ($linked) {
            $this->setRelation('expert', $linked);

            return $linked;
        }

        if (! $heal) {
            return null;
        }

        $email = mb_strtolower(trim((string) $this->email));
        if ($email === '' || $email === 'import@nachfolge-experten.local') {
            return null;
        }

        $byEmail = Expert::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->orderBy('id')
            ->first();

        if ($byEmail === null) {
            return null;
        }

        $byEmail->forceFill(['user_id' => $this->id])->save();
        $this->setRelation('expert', $byEmail->fresh());

        return $this->getRelation('expert');
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::ADMIN;
    }

    public function isExpert(): bool
    {
        return $this->role === Role::EXPERT;
    }
}
