<?php

namespace App\Services\DocumentParsers;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Str;

class XlsxParser
{
    /**
     * Parse an .xlsx file into raw structured sections, fields, and unparseable blocks.
     */
    public function parse(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("Excel file does not exist: {$filePath}");
        }

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        if (empty($rows)) {
            return [
                'title' => pathinfo($filePath, PATHINFO_FILENAME),
                'sections' => [],
                'unparseable_blocks' => ['The uploaded Excel sheet is empty.'],
            ];
        }

        $unparseableBlocks = [];
        $headerMap = [];

        // Check first non-empty row for header mapping
        $headerRow = null;
        $headerRowIndex = 1;

        foreach ($rows as $index => $row) {
            $nonEmpty = array_filter($row);
            if (!empty($nonEmpty)) {
                $headerRow = $row;
                $headerRowIndex = $index;
                break;
            }
        }

        if (!$headerRow) {
            return [
                'title' => pathinfo($filePath, PATHINFO_FILENAME),
                'sections' => [],
                'unparseable_blocks' => ['No valid rows found in sheet.'],
            ];
        }

        // Map column headers
        foreach ($headerRow as $col => $val) {
            if (!$val) continue;
            $lower = strtolower(trim((string)$val));
            if (str_contains($lower, 'sec')) $headerMap['section'] = $col;
            elseif (str_contains($lower, 'label') || str_contains($lower, 'question') || str_contains($lower, 'field')) $headerMap['label'] = $col;
            elseif (str_contains($lower, 'type')) $headerMap['type'] = $col;
            elseif (str_contains($lower, 'opt')) $headerMap['options'] = $col;
            elseif (str_contains($lower, 'req')) $headerMap['required'] = $col;
            elseif (str_contains($lower, 'place')) $headerMap['placeholder'] = $col;
        }

        // If no explicit 'label' header was detected, fallback to Col A = Section, Col B = Label, Col C = Type
        if (!isset($headerMap['label'])) {
            $headerMap['section'] = 'A';
            $headerMap['label'] = 'B';
            $headerMap['type'] = 'C';
            $headerMap['options'] = 'D';
            $headerMap['required'] = 'E';
        }

        $sectionsByTitle = [];

        foreach ($rows as $index => $row) {
            // Skip the header row itself
            if ($index <= $headerRowIndex) {
                continue;
            }

            $label = trim((string)($row[$headerMap['label']] ?? ''));
            $sectionTitle = trim((string)($row[$headerMap['section']] ?? 'General Information'));
            if (empty($sectionTitle)) $sectionTitle = 'General Information';

            if (empty($label)) {
                $rawRow = implode(' | ', array_filter($row));
                if (!empty($rawRow)) {
                    $unparseableBlocks[] = "Row {$index}: {$rawRow}";
                }
                continue;
            }

            $type = strtolower(trim((string)($row[$headerMap['type']] ?? '')));
            if (empty($type)) {
                $type = $this->guessTypeFromLabel($label);
            }

            $rawOptions = (string)($row[$headerMap['options']] ?? '');
            $options = $this->parseOptions($rawOptions);

            $requiredVal = (string)($row[$headerMap['required']] ?? '');
            $required = in_array(strtolower($requiredVal), ['1', 'true', 'yes', 'y', 'required']);

            $placeholder = (string)($row[$headerMap['placeholder']] ?? '');

            if (!isset($sectionsByTitle[$sectionTitle])) {
                $sectionsByTitle[$sectionTitle] = [
                    'id' => 'sec_' . uniqid(),
                    'title' => $sectionTitle,
                    'description' => '',
                    'fields' => [],
                ];
            }

            $sectionsByTitle[$sectionTitle]['fields'][] = [
                'id' => 'field_' . uniqid(),
                'type' => $type,
                'key' => Str::slug($label, '_') ?: 'field_' . Str::random(6),
                'label' => $label,
                'placeholder' => $placeholder,
                'help_text' => '',
                'required' => $required,
                'options' => $options,
                'validation' => [],
            ];
        }

        return [
            'title' => pathinfo($filePath, PATHINFO_FILENAME),
            'sections' => array_values($sectionsByTitle),
            'unparseable_blocks' => array_values(array_unique($unparseableBlocks)),
        ];
    }

    /**
     * Parse option strings separated by |, comma, or semicolon.
     */
    protected function parseOptions(string $rawOptions): array
    {
        if (empty(trim($rawOptions))) return [];

        $delimiters = ['|', ';', ','];
        $chosenDelimiter = '|';

        foreach ($delimiters as $delim) {
            if (str_contains($rawOptions, $delim)) {
                $chosenDelimiter = $delim;
                break;
            }
        }

        $parts = explode($chosenDelimiter, $rawOptions);
        $options = [];

        foreach ($parts as $p) {
            $trimmed = trim($p);
            if (!empty($trimmed)) {
                $options[] = [
                    'label' => $trimmed,
                    'value' => Str::slug($trimmed, '_') ?: 'opt_' . count($options),
                ];
            }
        }

        return $options;
    }

    /**
     * Quick type guess based on label.
     */
    protected function guessTypeFromLabel(string $label): string
    {
        $l = strtolower($label);
        if (str_contains($l, 'email')) return 'email';
        if (str_contains($l, 'phone') || str_contains($l, 'mobile')) return 'phone';
        if (str_contains($l, 'date')) return 'date';
        if (str_contains($l, 'upload') || str_contains($l, 'file') || str_contains($l, 'resume')) return 'file';
        if (str_contains($l, 'rating') || str_contains($l, 'satisfaction')) return 'rating';
        if (str_contains($l, 'comment') || str_contains($l, 'description')) return 'textarea';
        return 'text';
    }
}
