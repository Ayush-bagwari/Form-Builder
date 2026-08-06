<?php

namespace App\Livewire\Forms;

use App\Models\Form;
use Livewire\Component;
use Livewire\WithPagination;

class Dashboard extends Component
{
    use WithPagination;

    public string $search = '';

    public function deleteForm(int $formId)
    {
        $form = Form::findOrFail($formId);
        $form->delete();
        session()->flash('success', 'Form deleted successfully.');
    }

    public function render()
    {
        $query = Form::withCount('submissions')->latest();

        if (!empty(trim($this->search))) {
            $query->where('title', 'like', '%' . trim($this->search) . '%');
        }

        $forms = $query->paginate(9);

        return view('livewire.forms.dashboard', [
            'forms' => $forms,
        ])->layout('layouts.app');
    }
}
