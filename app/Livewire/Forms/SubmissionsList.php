<?php

namespace App\Livewire\Forms;

use App\Models\Form;
use App\Models\FormSubmission;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Response;

class SubmissionsList extends Component
{
    use WithPagination;

    public Form $form;
    public string $search = '';
    public ?int $selectedSubmissionId = null;

    public function mount(Form $form)
    {
        $this->form = $form;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function viewSubmission(int $id)
    {
        $this->selectedSubmissionId = $id;
    }

    public function closeModal()
    {
        $this->selectedSubmissionId = null;
    }

    public function downloadCsv()
    {
        $submissions = $this->form->submissions()->latest()->get();

        // Extract headers (Field labels)
        $headers = ['Submission ID', 'Submitted At', 'IP Address'];
        $fieldMap = []; // key => label

        foreach ($this->form->schema['sections'] ?? [] as $section) {
            foreach ($section['fields'] ?? [] as $field) {
                if ($field['type'] === 'section_heading') continue;
                $headers[] = $field['label'];
                $fieldMap[$field['key']] = $field['label'];
            }
        }

        $callback = function () use ($submissions, $headers, $fieldMap) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);

            foreach ($submissions as $sub) {
                $row = [
                    $sub->id,
                    $sub->created_at->format('Y-m-d H:i:s'),
                    $sub->ip_address ?? 'N/A',
                ];

                $data = $sub->data ?? [];
                foreach ($fieldMap as $key => $label) {
                    $val = $data[$key] ?? '';
                    if (is_array($val)) {
                        if (isset($val['original_name'])) {
                            $val = $val['original_name'] . ' (' . asset('storage/' . $val['path']) . ')';
                        } else {
                            $val = implode(', ', $val);
                        }
                    }
                    $row[] = $val;
                }

                fputcsv($file, $row);
            }

            fclose($file);
        };

        $filename = 'submissions_' . $this->form->slug . '_' . date('Y-m-d_His') . '.csv';

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function render()
    {
        $query = $this->form->submissions()->latest();

        if (!empty(trim($this->search))) {
            $searchTerm = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('data', 'like', $searchTerm)
                  ->orWhere('ip_address', 'like', $searchTerm);
            });
        }

        $submissions = $query->paginate(15);

        $selectedSubmission = $this->selectedSubmissionId 
            ? FormSubmission::find($this->selectedSubmissionId)
            : null;

        return view('livewire.forms.submissions-list', [
            'submissions' => $submissions,
            'selectedSubmission' => $selectedSubmission,
        ])->layout('layouts.app');
    }
}
