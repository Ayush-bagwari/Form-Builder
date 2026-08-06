<?php

namespace App\Notifications;

use App\Models\Form;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DocumentImportedNotification extends Notification
{
    use Queueable;

    public function __construct(public Form $form, public string $filename)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'document_imported',
            'icon'       => 'document',
            'title'      => 'Document Imported',
            'message'    => "Successfully imported '{$this->filename}' into form '{$this->form->title}'.",
            'form_id'    => $this->form->id,
            'action_url' => route('builder', ['form' => $this->form->id]),
        ];
    }
}
