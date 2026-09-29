<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@malkuthar.test'],
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'phone' => '+229 97 00 00 00',
                'country' => 'BJ',
                'currency' => 'XOF',
                'role' => 'admin',
                'status' => 'active',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        if ($admin->role !== 'admin' || $admin->status !== 'active') {
            $admin->update([
                'role' => 'admin',
                'status' => 'active',
                'password' => Hash::make('password'),
            ]);
        }

        $this->command?->info("Super Admin prêt : admin@malkuthar.test / password");
    }
}
