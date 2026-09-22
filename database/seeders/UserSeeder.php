<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's internal users.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@menuvision.test'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'role' => Role::Administrador,
            ]
        );

        User::updateOrCreate(
            ['email' => 'mozo@menuvision.test'],
            [
                'name' => 'Mozo',
                'password' => Hash::make('password'),
                'role' => Role::Mozo,
            ]
        );

        User::updateOrCreate(
            ['email' => 'cocina@menuvision.test'],
            [
                'name' => 'Cocina',
                'password' => Hash::make('password'),
                'role' => Role::Cocina,
            ]
        );
    }
}