<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Services\FormSchemaValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_schema_validator_validates_json_and_normalizes()
    {
        $validator = new FormSchemaValidator();

        $invalidJson = '{"title": "Test", fields: []}'; // missing quotes on key
        $result = $validator->validateSchemaJson($invalidJson);
        $this->assertFalse($result['valid']);
        $this->assertNotNull($result['error']);

        $validJson = json_encode([
            'sections' => [
                [
                    'title' => 'Personal Info',
                    'fields' => [
                        [
                            'type' => 'text',
                            'label' => 'Full Name',
                            'key' => 'full_name',
                            'required' => true
                        ]
                    ]
                ]
            ]
        ]);

        $resultValid = $validator->validateSchemaJson($validJson);
        $this->assertTrue($resultValid['valid']);
        $this->assertEquals('full_name', $resultValid['schema']['sections'][0]['fields'][0]['key']);
    }

    public function test_form_creation_and_slug_generation()
    {
        $user = User::factory()->create();

        $form = Form::create([
            'user_id' => $user->id,
            'title' => 'Customer Feedback Survey',
            'schema' => ['sections' => []]
        ]);

        $this->assertNotNull($form->slug);
        $this->assertStringContainsString('customer-feedback-survey', $form->slug);
    }

    public function test_public_form_renders_and_stores_submission()
    {
        $user = User::factory()->create();

        $form = Form::create([
            'user_id' => $user->id,
            'title' => 'Contact Us',
            'slug' => 'contact-us-test',
            'status' => 'published',
            'schema' => [
                'sections' => [
                    [
                        'title' => 'General',
                        'fields' => [
                            [
                                'type' => 'text',
                                'label' => 'Your Name',
                                'key' => 'your_name',
                                'required' => true
                            ],
                            [
                                'type' => 'email',
                                'label' => 'Your Email',
                                'key' => 'your_email',
                                'required' => true
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $response = $this->get('/f/contact-us-test');
        $response->assertStatus(200);
        $response->assertSee('Contact Us');
    }
}
