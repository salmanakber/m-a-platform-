<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@nachfolge-experten.ch'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('ChangeMe!Admin'),
                'role' => Role::ADMIN,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
