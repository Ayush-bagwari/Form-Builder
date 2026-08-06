<?php

namespace App\Notifications;

use App\Models\Form;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FormSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(public Form $form, public array $submissionData)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'submission',
            'icon'       => 'inbox',
            'title'      => 'New Form Submission',
            'message'    => "New response received on '{$this->form->title}'",
            'form_id'    => $this->form->id,
            'action_url' => route('dashboard', ['form' => $this->form->id]),
        ];
    }
}
