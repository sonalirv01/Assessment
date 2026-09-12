<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@jewellery.test'],
            [
                'name' => 'Store Admin',
                'password' => 'Password123!',
                'role' => 'admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'storemanager@jewellery.test'],
            [
                'name' => 'Store Manager',
                'password' => 'Password123!',
                'role' => 'manager',
            ]
        );

    }
}
