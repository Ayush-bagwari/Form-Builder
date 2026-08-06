<?php

namespace App\Notifications;

use App\Models\Form;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FormGeneratedNotification extends Notification
{
    use Queueable;

    public function __construct(public Form $form, public string $actionType = 'generated')
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $actionTitle = $this->actionType === 'refined' ? 'AI Form Refined' : 'AI Form Generated';

        return [
            'type'       => 'ai_generated',
            'icon'       => 'sparkles',
            'title'      => $actionTitle,
            'message'    => "'{$this->form->title}' was successfully {$this->actionType} by Gemini AI.",
            'form_id'    => $this->form->id,
            'action_url' => route('builder', ['form' => $this->form->id]),
        ];
    }
}
