<?php

namespace Database\Seeders;

use App\Models\{Department, TimeSlot, User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UniversitySeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Computer Science',       'code' => 'CS'],
            ['name' => 'Information Technology', 'code' => 'IT'],
            ['name' => 'Software Engineering',   'code' => 'SE'],
            ['name' => 'Electrical Engineering', 'code' => 'EE'],
            ['name' => 'Artificial Intelligence','code' => 'AI'],
        ];

        foreach ($departments as $d) {
            Department::firstOrCreate(['code' => $d['code']], $d);
        }

        $slots = [
            ['label' => '08:00 – 09:30', 'start_time' => '08:00', 'end_time' => '09:30', 'duration_minutes' => 90],
            ['label' => '09:45 – 11:15', 'start_time' => '09:45', 'end_time' => '11:15', 'duration_minutes' => 90],
            ['label' => '11:30 – 13:00', 'start_time' => '11:30', 'end_time' => '13:00', 'duration_minutes' => 90],
            ['label' => '14:00 – 15:30', 'start_time' => '14:00', 'end_time' => '15:30', 'duration_minutes' => 90],
            ['label' => '15:45 – 17:15', 'start_time' => '15:45', 'end_time' => '17:15', 'duration_minutes' => 90],
            ['label' => '17:30 – 19:00', 'start_time' => '17:30', 'end_time' => '19:00', 'duration_minutes' => 90],
        ];

        foreach ($slots as $s) {
            TimeSlot::firstOrCreate(['start_time' => $s['start_time']], $s);
        }

        $cs = Department::where('code', 'CS')->first();

        User::firstOrCreate(['email' => 'hod@university.edu'], [
            'name'          => 'Dr. Ahmad Hassan',
            'password'      => Hash::make('Password@123'),
            'role'          => 'hod',
            'department_id' => $cs->id,
            'phone'         => '+92-300-1234567',
        ]);

        User::firstOrCreate(['email' => 'teacher@university.edu'], [
            'name'          => 'Mr. Bilal Ahmed',
            'password'      => Hash::make('Password@123'),
            'role'          => 'teacher',
            'department_id' => $cs->id,
        ]);

        User::firstOrCreate(['email' => 'student@university.edu'], [
            'name'          => 'Ayesha Malik',
            'password'      => Hash::make('Password@123'),
            'role'          => 'student',
            'department_id' => $cs->id,
        ]);

        $this->command->info('University seed data loaded!');
    }
}
