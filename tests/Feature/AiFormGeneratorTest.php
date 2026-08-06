<?php

namespace Tests\Feature;

use App\Jobs\GenerateAiFormJob;
use App\Jobs\RefineAiFormJob;
use App\Models\AiGenerationLog;
use App\Models\Form;
use App\Models\User;
use App\Services\AiFormGeneratorService;
use App\Services\FormSchemaValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class AiFormGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_generator_service_mock_creates_valid_schema(): void
    {
        $validator = new FormSchemaValidator();
        $service = new AiFormGeneratorService($validator);

        $result = $service->generateFromPrompt('Internship application with education history and resume upload');

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['schema']['sections']);
        $this->assertEquals('gemini-1.5-flash (mock)', $result['model']);
        $this->assertGreaterThan(0, $result['total_tokens']);
        $this->assertGreaterThan(0, $result['latency_ms']);
    }

    public function test_json_sanitizer_cleans_markdown_and_trailing_commas(): void
    {
        $validator = new FormSchemaValidator();
        $service = new AiFormGeneratorService($validator);

        $rawContent = <<<JSON
```json
{
  "title": "Cleaned Form",
  "sections": [
    {
      "id": "sec_1",
      "title": "General",
      "fields": [
        {
          "id": "field_1",
          "type": "dropdown",
          "key": "test_field",
          "label": "Test Field",
        }
      ],
    }
  ],
}
```
JSON;

        $parsed = $service->sanitizeAndParseJson($rawContent);

        $this->assertEquals('Cleaned Form', $parsed['title']);
        $this->assertEquals('select', $parsed['sections'][0]['fields'][0]['type']); // Hallucinated type mapped
    }

    public function test_generate_ai_form_job_executes_and_logs_metrics(): void
    {
        $user = User::factory()->create();
        $form = Form::create([
            'user_id' => $user->id,
            'title' => 'Pending AI Form',
            'schema' => ['version' => 1, 'sections' => []],
            'status' => 'draft',
            'ai_status' => 'queued',
        ]);

        $service = new AiFormGeneratorService(new FormSchemaValidator());
        $job = new GenerateAiFormJob($form, 'Internship application form', $user->id);
        $job->handle($service);

        $form->refresh();
        $this->assertEquals('completed', $form->ai_status);
        $this->assertEquals('published', $form->status);

        $this->assertDatabaseHas('ai_generation_logs', [
            'form_id' => $form->id,
            'user_id' => $user->id,
            'action_type' => 'create',
        ]);
    }

    public function test_refine_ai_form_job_modifies_schema_and_logs(): void
    {
        $user = User::factory()->create();
        $form = Form::create([
            'user_id' => $user->id,
            'title' => 'Existing Form',
            'schema' => [
                'version' => 1,
                'sections' => [
                    [
                        'id' => 'sec_1',
                        'title' => 'Personal Info',
                        'fields' => [
                            ['id' => 'f1', 'type' => 'text', 'key' => 'name', 'label' => 'Name']
                        ]
                    ]
                ]
            ],
            'status' => 'published',
            'version' => 1,
        ]);

        $service = new AiFormGeneratorService(new FormSchemaValidator());
        $job = new RefineAiFormJob($form, 'Add emergency contact section', $user->id);
        $job->handle($service);

        $form->refresh();
        $this->assertEquals('completed', $form->ai_status);
        $this->assertEquals(2, $form->version);

        $this->assertDatabaseHas('ai_generation_logs', [
            'form_id' => $form->id,
            'action_type' => 'refine',
        ]);
    }

    public function test_ai_modal_dispatches_background_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Forms\AiFormGeneratorModal::class)
            ->set('prompt', 'Event registration form')
            ->call('submit');

        Queue::assertPushed(GenerateAiFormJob::class);
    }
}
