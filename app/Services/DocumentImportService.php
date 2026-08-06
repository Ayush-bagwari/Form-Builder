<?php

namespace App\Services;

use App\Services\DocumentParsers\DocxParser;
use App\Services\DocumentParsers\XlsxParser;

class DocumentImportService
{
    protected DocxParser $docxParser;
    protected XlsxParser $xlsxParser;
    protected AiFormGeneratorService $aiService;
    protected FormSchemaValidator $validator;

    public function __construct(
        DocxParser $docxParser,
        XlsxParser $xlsxParser,
        AiFormGeneratorService $aiService,
        FormSchemaValidator $validator
    ) {
        $this->docxParser = $docxParser;
        $this->xlsxParser = $xlsxParser;
        $this->aiService = $aiService;
        $this->validator = $validator;
    }

    /**
     * Import a document (.docx or .xlsx) using a hybrid deterministic + AI approach.
     */
    public function importDocument(string $filePath, string $originalFilename = ''): array
    {
        $extension = strtolower(pathinfo($originalFilename ?: $filePath, PATHINFO_EXTENSION));

        // Step 1: Deterministic Parsing
        if (in_array($extension, ['docx', 'doc'])) {
            $parsed = $this->docxParser->parse($filePath);
        } elseif (in_array($extension, ['xlsx', 'xls', 'csv'])) {
            $parsed = $this->xlsxParser->parse($filePath);
        } else {
            throw new \InvalidArgumentException("Unsupported document format '.{$extension}'. Please upload a .docx or .xlsx file.");
        }

        // Step 2: AI Type Enrichment for Ambiguous Fields
        $enrichedSections = $this->enrichFieldsWithAi($parsed['sections']);

        // Step 3: Schema Normalization
        $schema = [
            'version' => 1,
            'sections' => $enrichedSections,
        ];

        $normalized = $this->validator->normalizeAndValidateSchema($schema);

        // Extract clean title from original filename
        $rawFilename = $originalFilename ?: basename($filePath);
        $titleWithoutExt = pathinfo($rawFilename, PATHINFO_FILENAME);
        if (str_contains($titleWithoutExt, '-meta')) {
            $parts = explode('-meta', $titleWithoutExt);
            $titleWithoutExt = end($parts);
            if (base64_decode($titleWithoutExt, true) !== false) {
                $decoded = base64_decode($titleWithoutExt);
                if (!empty($decoded)) $titleWithoutExt = $decoded;
            }
        }
        $formattedTitle = ucwords(str_replace(['_', '-'], ' ', pathinfo($titleWithoutExt, PATHINFO_FILENAME)));
        $formTitle = !empty(trim($formattedTitle)) ? trim($formattedTitle) : 'Imported Form';

        return [
            'success' => true,
            'title' => $formTitle,
            'schema' => $normalized['schema'],
            'unparseable_blocks' => $parsed['unparseable_blocks'] ?? [],
        ];
    }

    /**
     * AI Enrichment: Uses Gemini API (or smart heuristics fallback) to infer optimal field types and validations for ambiguous document fields.
     */
    protected function enrichFieldsWithAi(array $sections): array
    {
        // 1. Local Heuristic Pre-Pass (Instant keyword matching for explicit labels)
        foreach ($sections as &$section) {
            if (isset($section['fields']) && is_array($section['fields'])) {
                foreach ($section['fields'] as &$field) {
                    $label = strtolower($field['label'] ?? '');
                    
                    if (($field['type'] ?? 'text') === 'text') {
                        if (str_contains($label, 'email')) {
                            $field['type'] = 'email';
                            $field['validation']['email'] = true;
                        } elseif (str_contains($label, 'phone') || str_contains($label, 'mobile') || str_contains($label, 'contact number')) {
                            $field['type'] = 'phone';
                        } elseif (str_contains($label, 'resume') || str_contains($label, 'upload') || str_contains($label, 'cv') || str_contains($label, 'file')) {
                            $field['type'] = 'file';
                            $field['help_text'] = 'Upload file attachment';
                        } elseif (str_contains($label, 'date') || str_contains($label, 'birth') || str_contains($label, 'when')) {
                            $field['type'] = 'date';
                        } elseif (str_contains($label, 'rating') || str_contains($label, 'score') || str_contains($label, 'satisfaction')) {
                            $field['type'] = 'rating';
                        } elseif (str_contains($label, 'describe') || str_contains($label, 'comment') || str_contains($label, 'address') || str_contains($label, 'reason')) {
                            $field['type'] = 'textarea';
                        }
                    }
                }
            }
        }

        // 2. Real Gemini AI Model Pass (if API key is configured)
        $apiKey = config('services.gemini.api_key') ?: config('services.openai.api_key');
        if (!empty($apiKey)) {
            try {
                $draftSchema = ['version' => 1, 'sections' => $sections];
                $aiResult = $this->aiService->refineFormSchema(
                    $draftSchema,
                    "Analyze all fields extracted from this imported document. Infer optimal field types (text, email, textarea, number, select, radio, checkbox, file, date, phone, rating, signature), sensible placeholders, choices for dropdowns/radios, and validation rules for any ambiguous fields. Preserve all existing section titles and field labels."
                );

                if (!empty($aiResult['schema']['sections'])) {
                    return $aiResult['schema']['sections'];
                }
            } catch (\Throwable $e) {
                // Graceful fallback to heuristic sections if AI request fails
            }
        }

        return $sections;
    }
}
