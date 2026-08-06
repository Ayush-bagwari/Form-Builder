<?php

namespace App\Services;

class FormSchemaValidator
{
    public const SUPPORTED_TYPES = [
        'text',
        'textarea',
        'number',
        'email',
        'phone',
        'dropdown',
        'radio',
        'checkbox',
        'date',
        'file',
        'rating',
        'section_heading'
    ];

    /**
     * Validate and parse a raw JSON schema string (supports jQuery formBuilder JSON & canonical format).
     */
    public function validateSchemaJson(string $jsonString): array
    {
        $jsonString = trim($jsonString);

        if (empty($jsonString) || $jsonString === '[]') {
            return [
                'valid' => true,
                'schema' => [
                    'version' => 1,
                    'sections' => [
                        [
                            'id' => 'sec_' . uniqid(),
                            'title' => 'General Information',
                            'description' => '',
                            'fields' => []
                        ]
                    ]
                ]
            ];
        }

        $decoded = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'valid' => false,
                'error' => 'Invalid JSON format: ' . json_last_error_msg()
            ];
        }

        return $this->normalizeAndValidateSchema($decoded);
    }

    /**
     * Normalize and validate schema array.
     */
    public function normalizeAndValidateSchema($data): array
    {
        if (!is_array($data)) {
            return [
                'valid' => false,
                'error' => 'Schema root must be an array or JSON object.'
            ];
        }

        // Handle flat array of fields (standard jQuery formBuilder format)
        if (isset($data[0]) && is_array($data[0])) {
            $data = [
                'version' => 1,
                'sections' => [
                    [
                        'id' => 'sec_default',
                        'title' => 'Form Fields',
                        'description' => '',
                        'fields' => $data
                    ]
                ]
            ];
        }

        $sections = $data['sections'] ?? [];

        if (empty($sections) && isset($data['fields'])) {
            $sections = [
                [
                    'id' => 'sec_default',
                    'title' => $data['title'] ?? 'General',
                    'description' => $data['description'] ?? '',
                    'fields' => $data['fields']
                ]
            ];
        }

        $normalizedSections = [];
        $fieldKeysSeen = [];

        foreach ($sections as $sIdx => $section) {
            $secTitle = $section['title'] ?? ('Section ' . ($sIdx + 1));
            $secFields = $section['fields'] ?? [];

            $normalizedFields = [];

            foreach ($secFields as $fIdx => $field) {
                if (!is_array($field)) {
                    continue;
                }

                $type = strtolower($field['type'] ?? 'text');
                $subtype = strtolower($field['subtype'] ?? '');

                // Map jQuery formBuilder types to canonical types
                if (in_array($type, ['header', 'paragraph'])) {
                    $type = 'section_heading';
                } elseif ($type === 'select') {
                    $type = 'dropdown';
                } elseif ($type === 'radio-group') {
                    $type = 'radio';
                } elseif ($type === 'checkbox-group') {
                    $type = 'checkbox';
                } elseif ($type === 'starrating' || $type === 'star-rating') {
                    $type = 'rating';
                } elseif ($type === 'text') {
                    if ($subtype === 'email') $type = 'email';
                    elseif ($subtype === 'tel' || $subtype === 'phone') $type = 'phone';
                }

                if (!in_array($type, self::SUPPORTED_TYPES)) {
                    $type = 'text'; // Fallback
                }

                $label = $field['label'] ?? ($field['name'] ?? ('Field ' . ($fIdx + 1)));
                $key = $field['name'] ?? ($field['key'] ?? ('field_' . strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $label))));
                
                // Unique key sanitization
                $baseKey = Str_slug_key($key);
                $key = $baseKey;
                $kCount = 1;
                while (in_array($key, $fieldKeysSeen)) {
                    $key = $baseKey . '_' . $kCount++;
                }
                $fieldKeysSeen[] = $key;

                $rawOptions = $field['values'] ?? ($field['options'] ?? []);

                $normalizedField = [
                    'id' => $field['id'] ?? ('field_' . uniqid()),
                    'type' => $type,
                    'key' => $key,
                    'label' => $label,
                    'placeholder' => $field['placeholder'] ?? '',
                    'help_text' => $field['description'] ?? ($field['help_text'] ?? ''),
                    'default' => $field['default'] ?? ($field['value'] ?? ''),
                    'required' => (bool) ($field['required'] ?? false),
                    'options' => $this->normalizeOptions($rawOptions),
                    'validation' => [
                        'min' => $field['validation']['min'] ?? ($field['min'] ?? null),
                        'max' => $field['validation']['max'] ?? ($field['max'] ?? null),
                        'min_length' => $field['validation']['min_length'] ?? ($field['min_length'] ?? null),
                        'max_length' => $field['validation']['max_length'] ?? ($field['max_length'] ?? null),
                        'email' => (bool) ($field['validation']['email'] ?? ($type === 'email')),
                        'regex' => $field['validation']['regex'] ?? ($field['regex'] ?? null),
                        'file_types' => $field['validation']['file_types'] ?? ($field['file_types'] ?? ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg']),
                        'max_size_kb' => $field['validation']['max_size_kb'] ?? ($field['max_size_kb'] ?? 5120),
                    ]
                ];

                $normalizedFields[] = $normalizedField;
            }

            $normalizedSections[] = [
                'id' => $section['id'] ?? ('sec_' . uniqid()),
                'title' => $secTitle,
                'description' => $section['description'] ?? '',
                'fields' => $normalizedFields
            ];
        }

        $schema = [
            'version' => 1,
            'sections' => $normalizedSections
        ];

        return [
            'valid' => true,
            'schema' => $schema
        ];
    }

    private function normalizeOptions($options): array
    {
        if (is_string($options)) {
            $lines = explode("\n", $options);
            $options = array_map('trim', $lines);
        }

        if (!is_array($options)) {
            return [];
        }

        $result = [];
        foreach ($options as $opt) {
            if (is_array($opt)) {
                $lbl = $opt['label'] ?? ($opt['text'] ?? ($opt['value'] ?? ''));
                $val = $opt['value'] ?? ($opt['label'] ?? '');
                $result[] = ['label' => (string)$lbl, 'value' => (string)$val];
            } else {
                $str = (string)$opt;
                if (!empty($str)) {
                    $result[] = ['label' => $str, 'value' => $str];
                }
            }
        }
        return $result;
    }

    /**
     * Build Laravel validation rules from schema array for server-side form submission.
     */
    public function buildValidationRules(array $schema): array
    {
        $rules = [];
        $attributes = [];
        $messages = [];

        $sections = $schema['sections'] ?? [];

        foreach ($sections as $section) {
            foreach ($section['fields'] ?? [] as $field) {
                $type = $field['type'] ?? 'text';
                if ($type === 'section_heading') {
                    continue;
                }

                $key = 'answers.' . $field['key'];
                $fieldRules = [];

                if (!empty($field['required'])) {
                    $fieldRules[] = 'required';
                } else {
                    $fieldRules[] = 'nullable';
                }

                switch ($type) {
                    case 'email':
                        $fieldRules[] = 'email';
                        break;

                    case 'number':
                        $fieldRules[] = 'numeric';
                        if (isset($field['validation']['min']) && is_numeric($field['validation']['min'])) {
                            $fieldRules[] = 'min:' . $field['validation']['min'];
                        }
                        if (isset($field['validation']['max']) && is_numeric($field['validation']['max'])) {
                            $fieldRules[] = 'max:' . $field['validation']['max'];
                        }
                        break;

                    case 'rating':
                        $fieldRules[] = 'integer';
                        $min = $field['validation']['min'] ?? 1;
                        $max = $field['validation']['max'] ?? 5;
                        $fieldRules[] = "between:$min,$max";
                        break;

                    case 'date':
                        $fieldRules[] = 'date';
                        break;

                    case 'checkbox':
                        if (!empty($field['required'])) {
                            $fieldRules[] = 'array';
                            $fieldRules[] = 'min:1';
                        } else {
                            $fieldRules[] = 'array';
                        }
                        break;

                    case 'file':
                        if ($field['required']) {
                            $fieldRules[] = 'file';
                        }
                        if (!empty($field['validation']['file_types']) && is_array($field['validation']['file_types'])) {
                            $mimes = implode(',', $field['validation']['file_types']);
                            $fieldRules[] = 'mimes:' . $mimes;
                        }
                        if (!empty($field['validation']['max_size_kb'])) {
                            $fieldRules[] = 'max:' . $field['validation']['max_size_kb'];
                        }
                        break;

                    case 'text':
                    case 'textarea':
                    case 'phone':
                    default:
                        $fieldRules[] = 'string';
                        if (isset($field['validation']['min_length']) && is_numeric($field['validation']['min_length'])) {
                            $fieldRules[] = 'min:' . $field['validation']['min_length'];
                        }
                        if (isset($field['validation']['max_length']) && is_numeric($field['validation']['max_length'])) {
                            $fieldRules[] = 'max:' . $field['validation']['max_length'];
                        }
                        if (!empty($field['validation']['regex'])) {
                            $fieldRules[] = 'regex:' . $field['validation']['regex'];
                        }
                        break;
                }

                $rules[$key] = $fieldRules;
                $attributes[$key] = $field['label'];
            }
        }

        return [
            'rules' => $rules,
            'attributes' => $attributes,
            'messages' => $messages,
        ];
    }
}

function Str_slug_key($string) {
    $clean = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $string));
    $clean = trim(preg_replace('/_+/', '_', $clean), '_');
    return $clean ?: 'field';
}
