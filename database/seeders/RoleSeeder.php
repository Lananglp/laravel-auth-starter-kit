<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::updateOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Administrator']
        );

        Role::updateOrCreate(
            ['slug' => 'user'],
            ['name' => 'User']
        );
    }
}
