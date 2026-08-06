<?php

namespace App\Livewire\Forms;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Services\FormSchemaValidator;
use Livewire\Component;
use Livewire\WithFileUploads;

class PublicForm extends Component
{
    use WithFileUploads;

    public Form $form;
    public array $answers = [];
    public bool $submitted = false;

    public function mount(string $slug)
    {
        $this->form = Form::where('slug', $slug)->firstOrFail();

        if ($this->form->status !== 'published' && auth()->id() !== $this->form->user_id) {
            abort(404, 'Form is not currently published.');
        }

        // Initialize default answer structures
        foreach ($this->form->schema['sections'] ?? [] as $section) {
            foreach ($section['fields'] ?? [] as $field) {
                if ($field['type'] === 'checkbox') {
                    $this->answers[$field['key']] = [];
                } else {
                    $this->answers[$field['key']] = $field['default'] ?? '';
                }
            }
        }
    }

    public function submit()
    {
        $validator = app(FormSchemaValidator::class);
        $rulesData = $validator->buildValidationRules($this->form->schema);

        // Server-Side Schema Validation
        $validatedData = $this->validate(
            $rulesData['rules'],
            $rulesData['messages'],
            $rulesData['attributes']
        );

        $answers = $validatedData['answers'] ?? [];

        // Handle uploaded files
        foreach ($this->form->schema['sections'] ?? [] as $section) {
            foreach ($section['fields'] ?? [] as $field) {
                if ($field['type'] === 'file' && !empty($answers[$field['key']])) {
                    $file = $answers[$field['key']];
                    if (is_object($file) && method_exists($file, 'store')) {
                        $path = $file->store('submissions/' . $this->form->id, 'public');
                        $answers[$field['key']] = [
                            'original_name' => $file->getClientOriginalName(),
                            'path' => $path,
                            'size' => $file->getSize(),
                        ];
                    }
                }
            }
        }

        // Store Submission in Database
        FormSubmission::create([
            'form_id' => $this->form->id,
            'data' => $answers,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.forms.public-form')
            ->layout('layouts.app');
    }
}
