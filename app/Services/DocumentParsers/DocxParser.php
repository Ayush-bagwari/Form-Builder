<?php

namespace App\Services\DocumentParsers;

use PhpOffice\PhpWord\Element\Heading;
use PhpOffice\PhpWord\Element\Title;
use PhpOffice\PhpWord\Element\ListItem;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Str;

class DocxParser
{
    /**
     * Parse a .docx file into raw structured sections, fields, and unparseable blocks.
     */
    public function parse(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("Docx file does not exist: {$filePath}");
        }

        $previousXmlSetting = libxml_use_internal_errors(true);
        try {
            $phpWord = IOFactory::load($filePath);
        } finally {
            libxml_use_internal_errors($previousXmlSetting);
        }

        $sectionsData = [];
        $unparseableBlocks = [];

        $currentSection = [
            'id' => 'sec_' . uniqid(),
            'title' => 'General Information',
            'description' => '',
            'fields' => [],
        ];

        $currentField = null;

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $class = get_class($element);

                // Handle Titles & Headings (Section demarcations)
                if ($element instanceof Title || $element instanceof Heading) {
                    $text = trim(html_entity_decode(strip_tags($this->extractElementText($element))));
                    if (!empty($text)) {
                        if ($currentField) {
                            $currentSection['fields'][] = $currentField;
                            $currentField = null;
                        }
                        if (!empty($currentSection['fields']) || count($sectionsData) > 0 || $currentSection['title'] !== 'General Information') {
                            $sectionsData[] = $currentSection;
                        }

                        $currentSection = [
                            'id' => 'sec_' . uniqid(),
                            'title' => $text,
                            'description' => '',
                            'fields' => [],
                        ];
                    }
                }
                // Handle List items (Select/Radio options for preceding question)
                elseif ($element instanceof ListItem) {
                    $optionText = trim($this->extractElementText($element));
                    if (!empty($optionText)) {
                        if ($currentField) {
                            if (!in_array($currentField['type'], ['select', 'radio', 'checkbox'])) {
                                $currentField['type'] = 'select'; // Upgrade field type to select
                            }
                            $currentField['options'][] = [
                                'label' => $optionText,
                                'value' => Str::slug($optionText, '_') ?: 'opt_' . count($currentField['options']),
                            ];
                        } else {
                            $unparseableBlocks[] = "Orphan list item: {$optionText}";
                        }
                    }
                }
                // Handle Paragraph text / TextRun (Field questions/labels)
                else {
                    $text = trim($this->extractElementText($element));

                    if (empty($text)) {
                        continue;
                    }

                    // Check if text looks like a question or label (ends with ?, :, or starts with bold/number)
                    if ($this->isQuestionOrLabel($text)) {
                        if ($currentField) {
                            $currentSection['fields'][] = $currentField;
                        }

                        $cleanLabel = rtrim($text, ' :?');
                        $currentField = [
                            'id' => 'field_' . uniqid(),
                            'type' => $this->guessTypeFromLabel($cleanLabel),
                            'key' => Str::slug($cleanLabel, '_') ?: 'field_' . Str::random(6),
                            'label' => $cleanLabel,
                            'placeholder' => '',
                            'help_text' => '',
                            'required' => $this->isLikelyRequired($text),
                            'options' => [],
                            'validation' => [],
                        ];
                    } elseif ($currentField && empty($currentField['help_text'])) {
                        // If it follows a field and isn't a question, treat as help text
                        $currentField['help_text'] = $text;
                    } else {
                        $unparseableBlocks[] = $text;
                    }
                }
            }
        }

        if ($currentField) {
            $currentSection['fields'][] = $currentField;
        }

        if (!empty($currentSection['fields'])) {
            $sectionsData[] = $currentSection;
        }

        if (empty($sectionsData)) {
            $sectionsData[] = [
                'id' => 'sec_default_' . uniqid(),
                'title' => 'Imported Document',
                'description' => '',
                'fields' => [],
            ];
        }

        return [
            'title' => pathinfo($filePath, PATHINFO_FILENAME),
            'sections' => $sectionsData,
            'unparseable_blocks' => array_values(array_unique($unparseableBlocks)),
        ];
    }

    /**
     * Extract string text from various PhpWord element types.
     */
    protected function extractElementText($element): string
    {
        if (is_string($element)) return $element;

        if (method_exists($element, 'getTitle')) {
            $title = $element->getTitle();
            if (is_string($title)) return $title;
            if (is_object($title)) return $this->extractElementText($title);
        }

        if (method_exists($element, 'getText')) {
            $text = $element->getText();
            if (is_string($text)) return $text;
            if (is_object($text)) return $this->extractElementText($text);
        }

        if (method_exists($element, 'getElements')) {
            $combined = '';
            foreach ($element->getElements() as $child) {
                $combined .= $this->extractElementText($child) . ' ';
            }
            return trim($combined);
        }

        return '';
    }

    /**
     * Heuristic to identify if a text string is a field label or question.
     */
    protected function isQuestionOrLabel(string $text): bool
    {
        if (str_contains($text, '?') || str_contains($text, ':')) return true;
        if (preg_match('/^(?:[\d\.]+|Q\d+|Name|Email|Phone|Address|Date|Upload|Select|Rate|Signature)/i', $text)) return true;
        return strlen($text) < 120;
    }

    /**
     * Quick deterministic type guess based on label keywords.
     */
    protected function guessTypeFromLabel(string $label): string
    {
        $l = strtolower($label);
        if (str_contains($l, 'email')) return 'email';
        if (str_contains($l, 'phone') || str_contains($l, 'mobile') || str_contains($l, 'tel')) return 'phone';
        if (str_contains($l, 'date') || str_contains($l, 'dob') || str_contains($l, 'birth')) return 'date';
        if (str_contains($l, 'upload') || str_contains($l, 'resume') || str_contains($l, 'cv') || str_contains($l, 'file') || str_contains($l, 'attach')) return 'file';
        if (str_contains($l, 'rating') || str_contains($l, 'satisfaction') || str_contains($l, 'rate')) return 'rating';
        if (str_contains($l, 'signature') || str_contains($l, 'sign')) return 'signature';
        if (str_contains($l, 'describe') || str_contains($l, 'comment') || str_contains($l, 'summary') || str_contains($l, 'reason')) return 'textarea';
        if (str_contains($l, 'number') || str_contains($l, 'quantity') || str_contains($l, 'age')) return 'number';
        return 'text';
    }

    /**
     * Check if label hints required state (e.g. contains * or required).
     */
    protected function isLikelyRequired(string $text): bool
    {
        return str_contains($text, '*') || str_contains(strtolower($text), 'required');
    }
}
