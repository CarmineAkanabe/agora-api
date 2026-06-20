<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = [
            [
                'name'     => 'Alice Mboua',
                'email'    => 'alice@agora.app',
                'password' => bcrypt('student1234'),
                'role'     => 'student',
            ],
            [
                'name'     => 'Bob Ngono',
                'email'    => 'bob@agora.app',
                'password' => bcrypt('student1234'),
                'role'     => 'student',
            ],
            [
                'name'     => 'Clara Fomba',
                'email'    => 'clara@agora.app',
                'password' => bcrypt('student1234'),
                'role'     => 'student',
            ],
        ];

        foreach ($students as $data) {
            $user = User::create($data);

            $user->studentProfile()->create([
                'matricule'           => '22T' . rand(1000, 9999),
                'school'              => 'ENSP',
                'department'          => 'Computer Engineering',
                'level'               => 'L3',
                'phone'               => '6' . rand(10000000, 99999999),
                'whatsapp_number'     => '6' . rand(10000000, 99999999),
                'id_card_path'        => 'id_cards/placeholder.jpg',
                'profile_picture'     => null,
                'verification_status' => 'approved',
                'verified_at'         => now(),
                'verified_by'         => 1,
            ]);
        }
    }
}
