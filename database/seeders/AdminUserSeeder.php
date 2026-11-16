<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('preview.admin.email');

        if (blank($email)) {
            $this->command?->warn('ADMIN_EMAIL is empty — admin user not seeded.');

            return;
        }

        if (User::where('email', $email)->exists()) {
            $this->command?->info("Admin [{$email}] already exists — left untouched.");

            return;
        }

        $password = config('preview.admin.password');
        $generated = blank($password);
        $password = $generated ? Str::password(20) : $password;

        User::forceCreate([
            'name' => config('preview.admin.name', 'Admin'),
            'email' => $email,
            'password' => $password,
            'email_verified_at' => now(),
        ]);

        $this->command?->info("Admin [{$email}] created.".($generated ? " Generated password: {$password}" : ''));
    }
}
