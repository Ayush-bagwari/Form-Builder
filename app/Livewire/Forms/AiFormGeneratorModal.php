<?php

namespace App\Livewire\Forms;

use App\Jobs\GenerateAiFormJob;
use App\Jobs\RefineAiFormJob;
use App\Models\Form;
use Livewire\Component;

class AiFormGeneratorModal extends Component
{
    public bool $showModal = false;
    public string $prompt = '';
    public ?int $formId = null;
    public string $actionType = 'create'; // 'create' or 'refine'

    protected $listeners = [
        'openAiModal' => 'openModal',
        'openAiRefineModal' => 'openRefineModal',
    ];

    public function openModal()
    {
        $this->reset(['prompt', 'formId']);
        $this->actionType = 'create';
        $this->showModal = true;
    }

    public function openRefineModal(int $formId)
    {
        $this->reset(['prompt']);
        $this->formId = $formId;
        $this->actionType = 'refine';
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
    }

    public function selectPreset(string $preset)
    {
        $this->prompt = $preset;
    }

    public function submit()
    {
        $this->validate([
            'prompt' => 'required|string|min:3|max:1000',
        ]);

        if ($this->actionType === 'create') {
            $form = Form::create([
                'user_id' => auth()->id(),
                'title' => 'Generating with AI...',
                'description' => 'Prompt: ' . $this->prompt,
                'schema' => [
                    'version' => 1,
                    'sections' => []
                ],
                'status' => 'draft',
                'ai_status' => 'queued',
                'ai_prompt' => $this->prompt,
            ]);

            GenerateAiFormJob::dispatch($form, $this->prompt, auth()->id());

            session()->flash('success', 'AI Form generation started in background! Check real-time progress below.');
            $this->closeModal();
            return redirect()->route('dashboard');

        } else if ($this->actionType === 'refine' && $this->formId) {
            $form = Form::findOrFail($this->formId);
            $form->update([
                'ai_status' => 'queued',
                'ai_prompt' => $this->prompt,
            ]);

            RefineAiFormJob::dispatch($form, $this->prompt, auth()->id());

            session()->flash('success', 'AI Form modification started in background!');
            $this->closeModal();
            $this->dispatch('aiRefineQueued');
        }
    }

    public function render()
    {
        return view('livewire.forms.ai-form-generator-modal');
    }
}
