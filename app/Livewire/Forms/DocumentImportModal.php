<?php

namespace App\Livewire\Forms;

use App\Models\Form;
use App\Services\DocumentImportService;
use Livewire\Component;
use Livewire\WithFileUploads;

class DocumentImportModal extends Component
{
    use WithFileUploads;

    public bool $showModal = false;
    public $documentFile = null;
    public int $step = 1; // 1 = Upload, 2 = Preview & Mapping

    // Parsed Data State
    public string $title = 'Imported Form';
    public array $sections = [];
    public array $unparseableBlocks = [];
    public ?string $errorMessage = null;

    protected $listeners = [
        'openDocumentImportModal' => 'openModal',
    ];

    public function openModal()
    {
        $this->reset(['documentFile', 'step', 'sections', 'unparseableBlocks', 'errorMessage']);
        $this->title = 'Imported Form';
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
    }

    public function processUpload(DocumentImportService $importService)
    {
        $this->validate([
            'documentFile' => 'required|file|max:10240', // Max 10MB
        ]);

        $this->errorMessage = null;

        try {
            $path = $this->documentFile->getRealPath() ?: $this->documentFile->path();
            $originalName = method_exists($this->documentFile, 'getClientOriginalName') ? $this->documentFile->getClientOriginalName() : 'imported_doc.docx';

            $result = $importService->importDocument($path, $originalName);

            $this->title = $result['title'] ?? 'Imported Form';
            $this->sections = $result['schema']['sections'] ?? [];
            $this->unparseableBlocks = $result['unparseable_blocks'] ?? [];
            $this->step = 2; // Move to Preview & Mapping screen

        } catch (\Throwable $e) {
            $this->errorMessage = 'Failed to parse document: ' . $e->getMessage();
        }
    }

    public function addFieldToSection(int $sectionIndex)
    {
        if (isset($this->sections[$sectionIndex])) {
            $this->sections[$sectionIndex]['fields'][] = [
                'id' => 'field_' . uniqid(),
                'type' => 'text',
                'key' => 'new_field_' . Str::random(4),
                'label' => 'New Field',
                'placeholder' => '',
                'help_text' => '',
                'required' => false,
                'options' => [],
                'validation' => [],
            ];
        }
    }

    public function removeField(int $sectionIndex, int $fieldIndex)
    {
        if (isset($this->sections[$sectionIndex]['fields'][$fieldIndex])) {
            array_splice($this->sections[$sectionIndex]['fields'], $fieldIndex, 1);
        }
    }

    public function convertUnparseableToField(int $blockIndex)
    {
        if (isset($this->unparseableBlocks[$blockIndex])) {
            $text = $this->unparseableBlocks[$blockIndex];

            if (empty($this->sections)) {
                $this->sections[] = [
                    'id' => 'sec_' . uniqid(),
                    'title' => 'General Information',
                    'description' => '',
                    'fields' => [],
                ];
            }

            $this->sections[0]['fields'][] = [
                'id' => 'field_' . uniqid(),
                'type' => 'text',
                'key' => 'field_' . Str::random(6),
                'label' => rtrim($text, ' :?'),
                'placeholder' => '',
                'help_text' => '',
                'required' => false,
                'options' => [],
                'validation' => [],
            ];

            unset($this->unparseableBlocks[$blockIndex]);
            $this->unparseableBlocks = array_values($this->unparseableBlocks);
        }
    }

    public function commitImport()
    {
        if (empty(trim($this->title))) {
            $this->addError('title', 'Please provide a form title.');
            return;
        }

        $schema = [
            'version' => 1,
            'sections' => $this->sections,
        ];

        $form = Form::create([
            'user_id' => auth()->id(),
            'title' => $this->title,
            'description' => 'Imported from document',
            'schema' => $schema,
            'status' => 'published',
            'version' => 1,
        ]);

        if (auth()->user()) {
            $filename = is_object($this->documentFile) && method_exists($this->documentFile, 'getClientOriginalName') ? $this->documentFile->getClientOriginalName() : 'Document';
            auth()->user()->notify(new \App\Notifications\DocumentImportedNotification($form, $filename));
        }

        session()->flash('success', 'Form "' . $this->title . '" imported successfully!');
        $this->closeModal();

        return redirect()->route('dashboard');
    }

    public function render()
    {
        return view('livewire.forms.document-import-modal');
    }
}
