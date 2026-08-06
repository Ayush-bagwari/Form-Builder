<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiGenerationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'form_id',
        'user_id',
        'prompt',
        'action_type',
        'status',
        'model',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'latency_ms',
        'error_message',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
