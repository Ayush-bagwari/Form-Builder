<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiFormGeneratorService
{
    protected FormSchemaValidator $validator;
    protected ?string $apiKey;
    protected string $model;

    public function __construct(FormSchemaValidator $validator)
    {
        $this->validator = $validator;
        $this->apiKey = config('services.gemini.api_key') ?: config('services.openai.api_key');
        $this->model = config('services.gemini.api_key') ? config('services.gemini.model', 'gemini-1.5-flash') : config('services.openai.model', 'gpt-4o-mini');
    }

    /**
     * Generate a complete form schema from a natural-language prompt.
     */
    public function generateFromPrompt(string $prompt): array
    {
        $startTime = microtime(true);

        if (empty($this->apiKey)) {
            return $this->generateMockFallback($prompt);
        }

        $systemPrompt = $this->getSystemPrompt();
        $userPrompt = "Create a complete, sensible form schema based on this prompt: \"{$prompt}\"";

        return $this->executeLlmWithRetries($systemPrompt, $userPrompt, $prompt, 'create', $startTime);
    }

    /**
     * Refine or modify an existing form schema based on a user instruction.
     */
    public function refineFormSchema(array $existingSchema, string $instruction): array
    {
        $startTime = microtime(true);

        if (empty($this->apiKey)) {
            return $this->refineMockFallback($existingSchema, $instruction);
        }

        $systemPrompt = $this->getSystemPrompt();
        $schemaJson = json_encode($existingSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $userPrompt = "Here is the existing form schema:\n\n{$schemaJson}\n\nApply the following modification instruction: \"{$instruction}\"";

        return $this->executeLlmWithRetries($systemPrompt, $userPrompt, $instruction, 'refine', $startTime);
    }

    /**
     * Execute LLM request via Gemini API with automatic retries and JSON repair.
     */
    protected function executeLlmWithRetries(string $systemPrompt, string $userPrompt, string $originalPrompt, string $actionType, float $startTime): array
    {
        $maxAttempts = 3;
        $lastError = null;
        $currentUserPrompt = $userPrompt;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                // Determine API type: Gemini vs OpenAI
                if (config('services.gemini.api_key')) {
                    $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";
                    
                    $response = Http::timeout(30)->post($endpoint, [
                        'system_instruction' => [
                            'parts' => [['text' => $systemPrompt]]
                        ],
                        'contents' => [
                            [
                                'parts' => [['text' => $currentUserPrompt]]
                            ]
                        ],
                        'generationConfig' => [
                            'temperature' => 0.4,
                            'responseMimeType' => 'application/json',
                        ]
                    ]);

                    if (!$response->successful()) {
                        throw new \RuntimeException("Gemini API returned HTTP status {$response->status()}: {$response->body()}");
                    }

                    $data = $response->json();
                    $rawContent = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    $usage = $data['usageMetadata'] ?? [];

                    $promptTokens = $usage['promptTokenCount'] ?? 0;
                    $completionTokens = $usage['candidatesTokenCount'] ?? 0;
                    $totalTokens = $usage['totalTokenCount'] ?? 0;

                } else {
                    // Fallback to OpenAI API structure if OpenAI key is present
                    $response = Http::withToken($this->apiKey)
                        ->timeout(30)
                        ->post('https://api.openai.com/v1/chat/completions', [
                            'model' => $this->model,
                            'messages' => [
                                ['role' => 'system', 'content' => $systemPrompt],
                                ['role' => 'user', 'content' => $currentUserPrompt],
                            ],
                            'temperature' => 0.4,
                            'response_format' => ['type' => 'json_object'],
                        ]);

                    if (!$response->successful()) {
                        throw new \RuntimeException("OpenAI API returned HTTP status {$response->status()}: {$response->body()}");
                    }

                    $data = $response->json();
                    $rawContent = $data['choices'][0]['message']['content'] ?? '';
                    $usage = $data['usage'] ?? [];

                    $promptTokens = $usage['prompt_tokens'] ?? 0;
                    $completionTokens = $usage['completion_tokens'] ?? 0;
                    $totalTokens = $usage['total_tokens'] ?? 0;
                }

                $parsedJson = $this->sanitizeAndParseJson($rawContent);
                $normalized = $this->validator->normalizeAndValidateSchema($parsedJson);

                if (!$normalized['valid']) {
                    throw new \InvalidArgumentException("Schema validation failed: " . ($normalized['error'] ?? 'Invalid schema structure'));
                }

                $latencyMs = (int)round((microtime(true) - $startTime) * 1000);

                return [
                    'success' => true,
                    'schema' => $normalized['schema'],
                    'title' => $parsedJson['title'] ?? 'AI Generated Form',
                    'description' => $parsedJson['description'] ?? '',
                    'model' => $this->model,
                    'prompt_tokens' => $promptTokens,
                    'completion_tokens' => $completionTokens,
                    'total_tokens' => $totalTokens,
                    'latency_ms' => $latencyMs,
                    'attempts' => $attempt,
                ];

            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                Log::warning("AI Form Generation attempt {$attempt} failed: {$lastError}");

                if ($attempt < $maxAttempts) {
                    $currentUserPrompt = $userPrompt . "\n\nCRITICAL FIX: Your previous response was invalid JSON or failed validation: {$lastError}. Please output STRICTLY valid JSON matching the required schema contract.";
                }
            }
        }

        // Fall back to mock generator on total API failure
        Log::error("AI Form Generation failed after {$maxAttempts} attempts. Falling back to intelligent mock generator.");
        $fallback = ($actionType === 'refine') 
            ? $this->refineMockFallback($userPrompt, $originalPrompt)
            : $this->generateMockFallback($originalPrompt);
            
        $fallback['error'] = "Primary LLM call failed ({$lastError}). Fallback schema applied successfully.";
        return $fallback;
    }

    /**
     * Sanitize and parse JSON response from LLM (repair markdown backticks, trailing commas).
     */
    public function sanitizeAndParseJson(string $rawContent): array
    {
        // Remove markdown backticks if present
        $clean = preg_replace('/^```(?:json)?\s*/i', '', trim($rawContent));
        $clean = preg_replace('/\s*```$/', '', $clean);

        // Remove trailing commas before closing braces/brackets
        $clean = preg_replace('/,(\s*[\}\]])/', '$1', $clean);

        $decoded = json_decode($clean, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException("JSON syntax error: " . json_last_error_msg());
        }

        return $this->mapHallucinatedTypes($decoded);
    }

    /**
     * Map hallucinated or non-standard field types to supported standard types.
     */
    protected function mapHallucinatedTypes(array $schema): array
    {
        $typeMapping = [
            'dropdown' => 'select',
            'multiselect' => 'checkbox',
            'check' => 'checkbox',
            'checkboxes' => 'checkbox',
            'radio-group' => 'radio',
            'radios' => 'radio',
            'tel' => 'phone',
            'telephone' => 'phone',
            'star' => 'rating',
            'stars' => 'rating',
            'starRating' => 'rating',
            'attach' => 'file',
            'attachment' => 'file',
            'upload' => 'file',
            'header' => 'text',
            'heading' => 'text',
        ];

        if (isset($schema['sections']) && is_array($schema['sections'])) {
            foreach ($schema['sections'] as &$section) {
                if (isset($section['fields']) && is_array($section['fields'])) {
                    foreach ($section['fields'] as &$field) {
                        $rawType = strtolower($field['type'] ?? 'text');
                        if (isset($typeMapping[$rawType])) {
                            $field['type'] = $typeMapping[$rawType];
                        }
                    }
                }
            }
        }

        return $schema;
    }

    /**
     * System prompt with strict JSON output contract.
     */
    protected function getSystemPrompt(): string
    {
        return <<<PROMPT
You are an expert AI Form Architect. Your task is to generate complete, sensible, user-friendly form schemas based on natural language prompts or modification instructions.

STRICT OUTPUT CONTRACT:
You MUST respond strictly with a single valid JSON object. Do NOT include any markdown formatting outside of JSON, explanation, or conversational text.

Required JSON Structure:
{
  "title": "Clear Form Title",
  "description": "Helpful description for applicants or submitters",
  "version": 1,
  "sections": [
    {
      "id": "sec_1",
      "title": "Section Title",
      "description": "Optional section instructions",
      "fields": [
        {
          "id": "field_1",
          "type": "text|email|textarea|number|select|radio|checkbox|file|date|phone|rating|signature",
          "key": "snake_case_key",
          "label": "User-facing Field Label",
          "placeholder": "Helpful placeholder",
          "help_text": "Guidance text",
          "required": true,
          "options": [
            {"label": "Option 1", "value": "option_1"},
            {"label": "Option 2", "value": "option_2"}
          ],
          "validation": {
            "min_length": null,
            "max_length": 255,
            "email": false,
            "regex": null
          }
        }
      ]
    }
  ]
}

ALLOWED FIELD TYPES ONLY:
- "text", "email", "textarea", "number", "select", "radio", "checkbox", "file", "date", "phone", "rating", "signature"

RULES:
1. Always generate logical sections (e.g. Personal Info, Qualifications, Uploads).
2. Choose appropriate field types (e.g., use "file" for resume/documents, "email" for email, "phone" for telephone, "rating" for feedback).
3. Provide realistic option values for "select", "radio", or "checkbox" fields.
4. Set required=true for essential identity fields.
5. All field keys MUST be unique snake_case strings.
PROMPT;
    }

    /**
     * Intelligent Mock Generator for offline/testing use without an API key.
     */
    public function generateMockFallback(string $prompt): array
    {
        $lower = strtolower($prompt);
        $title = "Form: " . Str::title($prompt);
        $description = "Generated form based on prompt: \"{$prompt}\"";

        $sections = [];

        // Section 1: General Info
        $fields1 = [
            [
                'id' => 'field_' . uniqid(),
                'type' => 'text',
                'key' => 'full_name',
                'label' => 'Full Name',
                'placeholder' => 'e.g. Jane Doe',
                'help_text' => 'Enter your official full name',
                'required' => true,
                'options' => [],
                'validation' => ['max_length' => 100],
            ],
            [
                'id' => 'field_' . uniqid(),
                'type' => 'email',
                'key' => 'email_address',
                'label' => 'Email Address',
                'placeholder' => 'jane@example.com',
                'help_text' => 'We will send confirmations to this email',
                'required' => true,
                'options' => [],
                'validation' => ['email' => true],
            ],
            [
                'id' => 'field_' . uniqid(),
                'type' => 'phone',
                'key' => 'phone_number',
                'label' => 'Phone Number',
                'placeholder' => '+1 (555) 000-0000',
                'help_text' => '',
                'required' => false,
                'options' => [],
                'validation' => [],
            ],
        ];

        $sections[] = [
            'id' => 'sec_gen_' . uniqid(),
            'title' => 'Personal & Contact Information',
            'description' => 'Please provide your basic contact details.',
            'fields' => $fields1,
        ];

        // Section 2: Domain Specific Fields
        if (str_contains($lower, 'intern') || str_contains($lower, 'job') || str_contains($lower, 'apply') || str_contains($lower, 'resume')) {
            $title = "Application Form";
            $fields2 = [
                [
                    'id' => 'field_' . uniqid(),
                    'type' => 'select',
                    'key' => 'education_level',
                    'label' => 'Highest Education Level',
                    'placeholder' => 'Select education level',
                    'help_text' => '',
                    'required' => true,
                    'options' => [
                        ['label' => 'High School', 'value' => 'high_school'],
                        ['label' => 'Bachelors Degree', 'value' => 'bachelors'],
                        ['label' => 'Masters / PhD', 'value' => 'masters_phd'],
                    ],
                    'validation' => [],
                ],
                [
                    'id' => 'field_' . uniqid(),
                    'type' => 'textarea',
                    'key' => 'key_skills',
                    'label' => 'Key Skills & Experience',
                    'placeholder' => 'List your primary technical & professional skills...',
                    'help_text' => '',
                    'required' => true,
                    'options' => [],
                    'validation' => ['max_length' => 1000],
                ],
                [
                    'id' => 'field_' . uniqid(),
                    'type' => 'file',
                    'key' => 'resume_upload',
                    'label' => 'Upload Resume / CV',
                    'placeholder' => '',
                    'help_text' => 'Accepted formats: PDF, DOCX (Max 5MB)',
                    'required' => true,
                    'options' => [],
                    'validation' => [],
                ],
            ];

            $sections[] = [
                'id' => 'sec_qual_' . uniqid(),
                'title' => 'Education & Qualifications',
                'description' => 'Detail your background and attach supporting documents.',
                'fields' => $fields2,
            ];
        } else {
            // Default Feedback / General Form Section
            $fields2 = [
                [
                    'id' => 'field_' . uniqid(),
                    'type' => 'rating',
                    'key' => 'overall_rating',
                    'label' => 'Overall Satisfaction',
                    'placeholder' => '',
                    'help_text' => 'Rate from 1 to 5 stars',
                    'required' => true,
                    'options' => [],
                    'validation' => [],
                ],
                [
                    'id' => 'field_' . uniqid(),
                    'type' => 'textarea',
                    'key' => 'additional_comments',
                    'label' => 'Additional Comments & Feedback',
                    'placeholder' => 'Share your thoughts...',
                    'help_text' => '',
                    'required' => false,
                    'options' => [],
                    'validation' => ['max_length' => 500],
                ],
            ];

            $sections[] = [
                'id' => 'sec_feed_' . uniqid(),
                'title' => 'Feedback & Details',
                'description' => 'Help us understand your requirements.',
                'fields' => $fields2,
            ];
        }

        $schema = [
            'version' => 1,
            'sections' => $sections,
        ];

        $normalized = $this->validator->normalizeAndValidateSchema($schema);

        return [
            'success' => true,
            'schema' => $normalized['schema'],
            'title' => $title,
            'description' => $description,
            'model' => 'gemini-1.5-flash (mock)',
            'prompt_tokens' => 150,
            'completion_tokens' => 280,
            'total_tokens' => 430,
            'latency_ms' => 320,
            'attempts' => 1,
        ];
    }

    /**
     * Refine Mock Generator for editing an existing form offline.
     */
    protected function refineMockFallback($existingSchema, string $instruction): array
    {
        $schema = is_array($existingSchema) ? $existingSchema : [];
        $lower = strtolower($instruction);

        if (!isset($schema['sections']) || !is_array($schema['sections'])) {
            return $this->generateMockFallback($instruction);
        }

        // Emergency Contact modification
        if (str_contains($lower, 'emergency') || str_contains($lower, 'contact')) {
            $schema['sections'][] = [
                'id' => 'sec_emerg_' . uniqid(),
                'title' => 'Emergency Contact Information',
                'description' => 'Please provide emergency contact details.',
                'fields' => [
                    [
                        'id' => 'field_' . uniqid(),
                        'type' => 'text',
                        'key' => 'emergency_contact_name',
                        'label' => 'Emergency Contact Name',
                        'placeholder' => 'e.g. John Smith',
                        'required' => true,
                        'options' => [],
                        'validation' => [],
                    ],
                    [
                        'id' => 'field_' . uniqid(),
                        'type' => 'phone',
                        'key' => 'emergency_contact_phone',
                        'label' => 'Emergency Contact Phone',
                        'placeholder' => '+1 (555) 000-0000',
                        'required' => true,
                        'options' => [],
                        'validation' => [],
                    ],
                ]
            ];
        } 
        // Hindi Translation instruction
        else if (str_contains($lower, 'hindi') || str_contains($lower, 'translate')) {
            foreach ($schema['sections'] as &$section) {
                $section['title'] = $section['title'] . ' (विवरण)';
                foreach ($section['fields'] as &$field) {
                    $field['label'] = $field['label'] . ' (विवरण)';
                }
            }
        } 
        // Require Phone instruction
        else if (str_contains($lower, 'phone') && str_contains($lower, 'required')) {
            foreach ($schema['sections'] as &$section) {
                foreach ($section['fields'] as &$field) {
                    if (str_contains(strtolower($field['key'] ?? ''), 'phone') || strtolower($field['type'] ?? '') === 'phone') {
                        $field['required'] = true;
                    }
                }
            }
        }

        $normalized = $this->validator->normalizeAndValidateSchema($schema);

        return [
            'success' => true,
            'schema' => $normalized['schema'],
            'title' => $schema['title'] ?? 'Updated Form',
            'description' => $schema['description'] ?? '',
            'model' => 'gemini-1.5-flash (mock)',
            'prompt_tokens' => 210,
            'completion_tokens' => 310,
            'total_tokens' => 520,
            'latency_ms' => 280,
            'attempts' => 1,
        ];
    }
}
