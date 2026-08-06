<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::where('email', 'test@example.com')->first();
        
        if (!$user) {
            $user = User::create([
                'name' => 'Demo User',
                'email' => 'test@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
        } else {
            $user->update([
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
        }

        // Sample Form 1: Job Application Form
        $form1 = Form::firstOrCreate(
            ['slug' => 'fall-2026-job-application'],
            [
                'user_id' => $user->id,
                'title' => 'Fall 2026 Job Application Form',
                'description' => 'Please fill out all fields below to apply for our open engineering positions.',
                'status' => 'published',
                'version' => 1,
                'settings' => [
                    'submit_button_text' => 'Submit Job Application',
                    'success_message' => 'Thank you for applying! Our HR team will review your application.',
                    'enable_rate_limit' => false,
                ],
                'schema' => [
                    'version' => 1,
                    'sections' => [
                        [
                            'id' => 'sec_personal',
                            'title' => 'Personal & Contact Information',
                            'description' => 'Enter your primary contact details',
                            'fields' => [
                                [
                                    'id' => 'f_name',
                                    'type' => 'text',
                                    'key' => 'full_name',
                                    'label' => 'Full Legal Name',
                                    'placeholder' => 'Jane Doe',
                                    'required' => true,
                                    'validation' => ['min_length' => 2, 'max_length' => 100],
                                ],
                                [
                                    'id' => 'f_email',
                                    'type' => 'email',
                                    'key' => 'email_address',
                                    'label' => 'Email Address',
                                    'placeholder' => 'jane@example.com',
                                    'required' => true,
                                    'validation' => ['email' => true],
                                ],
                                [
                                    'id' => 'f_phone',
                                    'type' => 'phone',
                                    'key' => 'phone_number',
                                    'label' => 'Phone Number',
                                    'placeholder' => '+1 (555) 019-2834',
                                    'required' => true,
                                ],
                                [
                                    'id' => 'f_dept',
                                    'type' => 'dropdown',
                                    'key' => 'target_department',
                                    'label' => 'Target Department',
                                    'placeholder' => '-- Select Department --',
                                    'required' => true,
                                    'options' => [
                                        ['label' => 'Backend Engineering', 'value' => 'backend'],
                                        ['label' => 'Frontend Engineering', 'value' => 'frontend'],
                                        ['label' => 'Product Design', 'value' => 'design'],
                                        ['label' => 'DevOps & Cloud', 'value' => 'devops'],
                                    ]
                                ]
                            ]
                        ],
                        [
                            'id' => 'sec_experience',
                            'title' => 'Work Experience & Resume',
                            'description' => 'Share your qualifications and experience',
                            'fields' => [
                                [
                                    'id' => 'f_experience_summary',
                                    'type' => 'textarea',
                                    'key' => 'experience_summary',
                                    'label' => 'Brief Career Summary',
                                    'placeholder' => 'Summarize your recent engineering roles...',
                                    'required' => false,
                                ],
                                [
                                    'id' => 'f_skill_rating',
                                    'type' => 'rating',
                                    'key' => 'self_rating',
                                    'label' => 'Self-Assessed Skill Rating (1-5)',
                                    'required' => true,
                                    'validation' => ['min' => 1, 'max' => 5]
                                ],
                                [
                                    'id' => 'f_resume',
                                    'type' => 'file',
                                    'key' => 'resume_file',
                                    'label' => 'Upload Resume (PDF/DOCX)',
                                    'required' => false,
                                    'validation' => ['file_types' => ['pdf', 'doc', 'docx'], 'max_size_kb' => 5120]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );

        // Seed Sample Submissions for Form 1
        FormSubmission::firstOrCreate(
            ['form_id' => $form1->id, 'ip_address' => '127.0.0.1'],
            [
                'data' => [
                    'full_name' => 'Alice Smith',
                    'email_address' => 'alice@example.com',
                    'phone_number' => '+1 555-123-4567',
                    'target_department' => 'backend',
                    'experience_summary' => '5 years building high scale PHP/Laravel & Python APIs.',
                    'self_rating' => '5',
                ],
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            ]
        );

        FormSubmission::firstOrCreate(
            ['form_id' => $form1->id, 'ip_address' => '192.168.1.100'],
            [
                'data' => [
                    'full_name' => 'Bob Johnson',
                    'email_address' => 'bob@example.com',
                    'phone_number' => '+1 555-987-6543',
                    'target_department' => 'frontend',
                    'experience_summary' => '3 years experience with Livewire, Alpine.js, Tailwind CSS.',
                    'self_rating' => '4',
                ],
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
            ]
        );
    }
}
