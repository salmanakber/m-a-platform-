<?php

namespace App\Services\Expert;

use App\Models\Expert;
use App\Models\ExpertOffice;
use App\Models\User;
use App\Services\Email\NotificationService;
use App\Support\ExpertStatus;
use App\Support\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ExpertRegistrationService
{
    public function __construct(private NotificationService $notifications)
    {
    }

    /**
     * @param  array<string, mixed>  $userData
     * @param  array<string, mixed>  $expertData
     * @param  array<string, mixed>  $officeData
     */
    public function register(array $userData, array $expertData, array $officeData): Expert
    {
        return DB::transaction(function () use ($userData, $expertData, $officeData) {
            $user = User::query()->create([
                'name' => $userData['name'] ?? $expertData['company_name'],
                'email' => $userData['email'],
                'password' => Hash::make($userData['password']),
                'role' => Role::EXPERT,
                'is_active' => true,
            ]);

            $slugBase = Str::slug($expertData['company_name']);
            $slug = $this->uniqueSlug($slugBase);

            $expert = Expert::query()->create(array_merge($expertData, [
                'user_id' => $user->id,
                'slug' => $slug,
                'email' => $expertData['email'] ?? $user->email,
                'status' => ExpertStatus::PENDING,
                'is_public' => false,
            ]));

            ExpertOffice::query()->create(array_merge($officeData, [
                'expert_id' => $expert->id,
                'is_primary' => $officeData['is_primary'] ?? true,
                'sort_order' => $officeData['sort_order'] ?? 0,
            ]));

            $this->notifications->sendExpertRegistrationWelcome($expert);
            $this->notifications->notifyAdminNewExpertRegistration($expert);

            return $expert->fresh(['user', 'offices']);
        });
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base !== '' ? $base : Str::random(8);
        $original = $slug;
        $counter = 1;

        while (Expert::query()->where('slug', $slug)->exists()) {
            $slug = $original.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
