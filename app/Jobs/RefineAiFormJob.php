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

class RefineAiFormJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Form $form;
    public string $instruction;
    public ?int $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(Form $form, string $instruction, ?int $userId = null)
    {
        $this->form = $form;
        $this->instruction = $instruction;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(AiFormGeneratorService $aiService): void
    {
        $this->form->update(['ai_status' => 'processing']);

        try {
            $existingSchema = $this->form->schema ?? [];
            $result = $aiService->refineFormSchema($existingSchema, $this->instruction);

            if ($result['success']) {
                $this->form->update([
                    'schema' => $result['schema'],
                    'version' => ($this->form->version ?? 1) + 1,
                    'ai_status' => 'completed',
                ]);

                // Record token usage & metrics log
                AiGenerationLog::create([
                    'form_id' => $this->form->id,
                    'user_id' => $this->userId,
                    'prompt' => $this->instruction,
                    'action_type' => 'refine',
                    'status' => isset($result['error']) ? 'repaired' : 'completed',
                    'model' => $result['model'] ?? 'gemini-1.5-flash',
                    'prompt_tokens' => $result['prompt_tokens'] ?? 0,
                    'completion_tokens' => $result['completion_tokens'] ?? 0,
                    'total_tokens' => $result['total_tokens'] ?? 0,
                    'latency_ms' => $result['latency_ms'] ?? 0,
                    'error_message' => $result['error'] ?? null,
                ]);
            } else {
                throw new \RuntimeException($result['error'] ?? 'Unknown AI refinement failure');
            }
        } catch (\Throwable $e) {
            Log::error("RefineAiFormJob failed for Form ID {$this->form->id}: " . $e->getMessage());

            $this->form->update(['ai_status' => 'failed']);

            AiGenerationLog::create([
                'form_id' => $this->form->id,
                'user_id' => $this->userId,
                'prompt' => $this->instruction,
                'action_type' => 'refine',
                'status' => 'failed',
                'model' => 'gemini-1.5-flash',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
