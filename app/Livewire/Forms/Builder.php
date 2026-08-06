<?php

namespace App\Livewire\Forms;

use App\Models\Form;
use App\Services\FormSchemaValidator;
use Livewire\Component;
use Illuminate\Support\Str;

class Builder extends Component
{
    public ?Form $form = null;

    // Wizard Step: 1 = Details, 2 = Builder, 3 = Settings, 4 = Finish
    public int $currentStep = 1;

    // Form Basics
    public string $title = 'Untitled Form';
    public string $description = '';
    public string $status = 'published';

    // Settings
    public array $settings = [
        'submit_button_text' => 'Submit Response',
        'success_message' => 'Thank you! Your response has been recorded successfully.',
        'redirect_url' => '',
        'enable_rate_limit' => false,
    ];

    // Schema state (Array representation)
    public array $schema = [];

    // Raw JSON string representation
    public string $rawJson = '[]';
    public ?string $jsonError = null;

    // Builder View Mode: 'formbuilder' | 'raw-json'
    public string $activeView = 'formbuilder';

    public function mount(?Form $form = null)
    {
        $validator = app(FormSchemaValidator::class);

        if ($form && $form->exists) {
            $this->form = $form;
            $this->title = $form->title;
            $this->description = $form->description ?? '';
            $this->status = $form->status ?? 'published';
            $this->settings = array_merge($this->settings, $form->settings ?? []);

            $parsed = $validator->normalizeAndValidateSchema($form->schema ?? []);
            $this->schema = $parsed['schema'];
        } else {
            // Default initial schema
            $this->schema = [
                'version' => 1,
                'sections' => [
                    [
                        'id' => 'sec_' . uniqid(),
                        'title' => 'General Information',
                        'description' => 'Please fill in the details below.',
                        'fields' => [
                            [
                                'id' => 'field_' . uniqid(),
                                'type' => 'text',
                                'key' => 'full_name',
                                'label' => 'Full Name',
                                'placeholder' => 'e.g. John Doe',
                                'help_text' => '',
                                'default' => '',
                                'required' => true,
                                'options' => [],
                                'validation' => [
                                    'min_length' => null,
                                    'max_length' => 100,
                                    'email' => false,
                                    'regex' => null,
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        }

        $this->syncSchemaToRawJson();
    }

    public function setStep(int $step)
    {
        if ($step < 1) $step = 1;
        if ($step > 4) $step = 4;

        if ($step > 1 && empty(trim($this->title))) {
            $this->addError('title', 'Please provide a form title before proceeding.');
            return;
        }

        $this->currentStep = $step;
    }

    public function switchView(string $view)
    {
        $this->activeView = $view;
        if ($view === 'raw-json') {
            $this->syncSchemaToRawJson();
        }
    }

    public function syncRawJsonToSchema()
    {
        $this->jsonError = null;
        $validator = app(FormSchemaValidator::class);
        $res = $validator->validateSchemaJson($this->rawJson);

        if ($res['valid']) {
            $this->schema = $res['schema'];
        } else {
            $this->jsonError = $res['error'];
        }
    }

    public function syncSchemaToRawJson()
    {
        $this->jsonError = null;
        $this->rawJson = json_encode($this->schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function saveFormFromJs($jsonString)
    {
        if (is_array($jsonString)) {
            $jsonString = json_encode($jsonString);
        }

        $this->rawJson = (string)$jsonString;
        $validator = app(FormSchemaValidator::class);
        $res = $validator->validateSchemaJson($this->rawJson);

        if ($res['valid']) {
            $this->schema = $res['schema'];
            $this->saveForm();
        } else {
            $this->jsonError = $res['error'];
            session()->flash('error', 'Cannot save: ' . $res['error']);
        }
    }

    public function saveForm()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,published',
        ]);

        $validator = app(FormSchemaValidator::class);

        if ($this->activeView === 'raw-json') {
            $res = $validator->validateSchemaJson($this->rawJson);
            if (!$res['valid']) {
                $this->jsonError = $res['error'];
                session()->flash('error', 'Cannot save: ' . $res['error']);
                return;
            }
            $this->schema = $res['schema'];
        }

        $parsed = $validator->normalizeAndValidateSchema($this->schema);
        if (!$parsed['valid']) {
            session()->flash('error', 'Invalid form schema: ' . ($parsed['error'] ?? 'Check fields'));
            return;
        }

        $schemaData = $parsed['schema'];

        if ($this->form && $this->form->exists) {
            $this->form->update([
                'title' => $this->title,
                'description' => $this->description,
                'schema' => $schemaData,
                'settings' => $this->settings,
                'status' => $this->status,
                'version' => ($this->form->version ?? 1) + 1,
            ]);
        } else {
            $this->form = Form::create([
                'user_id' => auth()->id(),
                'title' => $this->title,
                'description' => $this->description,
                'schema' => $schemaData,
                'settings' => $this->settings,
                'status' => $this->status,
                'version' => 1,
            ]);
        }

        $this->syncSchemaToRawJson();

        session()->flash('success', 'Form saved successfully!');
    }

    public function render()
    {
        return view('livewire.forms.builder')
            ->layout('layouts.app');
    }
}