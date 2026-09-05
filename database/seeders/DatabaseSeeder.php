<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Admin
        User::create([
            'name' => 'Admin TutorHUB',
            'email' => 'admin@tutorhub.my',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'phone' => '0123456789',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Demo Tutor
        $tutor = User::create([
            'name' => 'Ahmad Tutor',
            'email' => 'tutor@tutorhub.my',
            'password' => Hash::make('tutor123'),
            'role' => 'tutor',
            'phone' => '0198765432',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        TutorProfile::create([
            'user_id' => $tutor->id,
            'subjects' => ['Mathematics', 'Science'],
            'education_level' => 'Degree',
            'experience_years' => 5,
            'hourly_rate' => 50.00,
            'location_area' => 'Petaling Jaya',
            'location_state' => 'Selangor',
            'verification_status' => 'verified',
            'verified_at' => now(),
            'bio' => 'Experienced tutor specializing in SPM Mathematics and Science',
        ]);

        // Demo Parent
        $parent = User::create([
            'name' => 'Siti Parent',
            'email' => 'parent@tutorhub.my',
            'password' => Hash::make('parent123'),
            'role' => 'parent',
            'phone' => '0176543210',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        Student::create([
            'parent_id' => $parent->id,
            'name' => 'Ali',
            'age' => 16,
            'school' => 'SMK Taman SEA',
            'education_level' => 'SPM',
        ]);

        // Subjects
        // hourly_rate was renamed to hourly_rate_home; the online rate is
        // 80% of it, rounded to the nearest RM5. Seeding a zero online rate
        // would price every online booking at nothing.
        $subjects = [
            ['name' => 'Mathematics', 'category' => 'academic', 'education_level' => 'SPM', 'hourly_rate_home' => 45.00, 'hourly_rate_online' => 35.00],
            ['name' => 'Additional Mathematics', 'category' => 'academic', 'education_level' => 'SPM', 'hourly_rate_home' => 55.00, 'hourly_rate_online' => 45.00],
            ['name' => 'Science', 'category' => 'academic', 'education_level' => 'SPM', 'hourly_rate_home' => 40.00, 'hourly_rate_online' => 30.00],
            ['name' => 'Physics', 'category' => 'academic', 'education_level' => 'SPM', 'hourly_rate_home' => 50.00, 'hourly_rate_online' => 40.00],
            ['name' => 'Chemistry', 'category' => 'academic', 'education_level' => 'SPM', 'hourly_rate_home' => 50.00, 'hourly_rate_online' => 40.00],
            ['name' => 'Biology', 'category' => 'academic', 'education_level' => 'SPM', 'hourly_rate_home' => 45.00, 'hourly_rate_online' => 35.00],
            ['name' => 'Bahasa Melayu', 'category' => 'language', 'education_level' => 'SPM', 'hourly_rate_home' => 35.00, 'hourly_rate_online' => 30.00],
            ['name' => 'English', 'category' => 'language', 'education_level' => 'SPM', 'hourly_rate_home' => 40.00, 'hourly_rate_online' => 30.00],
            ['name' => 'Al-Quran', 'category' => 'quran', 'education_level' => 'All', 'hourly_rate_home' => 30.00, 'hourly_rate_online' => 25.00],
            ['name' => 'Tajweed', 'category' => 'quran', 'education_level' => 'All', 'hourly_rate_home' => 30.00, 'hourly_rate_online' => 25.00],
            ['name' => 'Piano', 'category' => 'music', 'education_level' => 'All', 'hourly_rate_home' => 80.00, 'hourly_rate_online' => 65.00],
            ['name' => 'Guitar', 'category' => 'music', 'education_level' => 'All', 'hourly_rate_home' => 60.00, 'hourly_rate_online' => 50.00],
        ];

        foreach ($subjects as $subject) {
            Subject::create($subject);
        }

        // Settings
        Setting::set('commission_rate', '20');
        Setting::set('site_name', 'TutorHUB');
    }
}
