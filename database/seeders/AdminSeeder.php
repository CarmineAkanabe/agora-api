<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name'     => 'Admin One',
            'email'    => 'admin1@agora.app',
            'password' => bcrypt('admin1234'),
            'role'     => 'admin',
        ]);

        User::create([
            'name'     => 'Admin Two',
            'email'    => 'admin2@agora.app',
            'password' => bcrypt('admin1234'),
            'role'     => 'admin',
        ]);
    }
}
