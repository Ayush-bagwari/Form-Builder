<?php

namespace App\Jobs;

use App\Models\AiGenerationLog;
use App\Models\Form;
use App\Services\AiFormGeneratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateAiFormJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Form $form;
    public string $prompt;
    public ?int $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(Form $form, string $prompt, ?int $userId = null)
    {
        $this->form = $form;
        $this->prompt = $prompt;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(AiFormGeneratorService $aiService): void
    {
        $this->form->update(['ai_status' => 'processing']);

        try {
            $result = $aiService->generateFromPrompt($this->prompt);

            if ($result['success']) {
                $this->form->update([
                    'title' => $result['title'] ?? $this->form->title,
                    'description' => $result['description'] ?? $this->form->description,
                    'schema' => $result['schema'],
                    'status' => 'published',
                    'ai_status' => 'completed',
                ]);

                // Record token usage & metrics log
                AiGenerationLog::create([
                    'form_id' => $this->form->id,
                    'user_id' => $this->userId,
                    'prompt' => $this->prompt,
                    'action_type' => 'create',
                    'status' => isset($result['error']) ? 'repaired' : 'completed',
                    'model' => $result['model'] ?? 'gemini-1.5-flash',
                    'prompt_tokens' => $result['prompt_tokens'] ?? 0,
                    'completion_tokens' => $result['completion_tokens'] ?? 0,
                    'total_tokens' => $result['total_tokens'] ?? 0,
                    'latency_ms' => $result['latency_ms'] ?? 0,
                    'error_message' => $result['error'] ?? null,
                ]);
            } else {
                throw new \RuntimeException($result['error'] ?? 'Unknown AI generation failure');
            }
        } catch (\Throwable $e) {
            Log::error("GenerateAiFormJob failed for Form ID {$this->form->id}: " . $e->getMessage());

            $this->form->update(['ai_status' => 'failed']);

            AiGenerationLog::create([
                'form_id' => $this->form->id,
                'user_id' => $this->userId,
                'prompt' => $this->prompt,
                'action_type' => 'create',
                'status' => 'failed',
                'model' => 'gemini-1.5-flash',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
