<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\User;
use App\Services\DocumentImportService;
use App\Services\DocumentParsers\DocxParser;
use App\Services\DocumentParsers\XlsxParser;
use App\Services\AiFormGeneratorService;
use App\Services\FormSchemaValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_docx_parser_extracts_headings_fields_and_options(): void
    {
        $docxPath = storage_path('app/samples/sample_job_application.docx');
        $this->assertFileExists($docxPath);

        $parser = new DocxParser();
        $result = $parser->parse($docxPath);

        $this->assertNotEmpty($result['sections']);
        $this->assertEquals('sample_job_application', $result['title']);
    }

    public function test_xlsx_parser_extracts_header_row_layout(): void
    {
        $xlsxPath = storage_path('app/samples/sample_customer_survey.xlsx');
        $this->assertFileExists($xlsxPath);

        $parser = new XlsxParser();
        $result = $parser->parse($xlsxPath);

        $this->assertNotEmpty($result['sections']);
        $this->assertEquals('sample_customer_survey', $result['title']);

        $allFields = [];
        foreach ($result['sections'] as $sec) {
            foreach ($sec['fields'] as $f) {
                $allFields[] = $f['label'];
            }
        }

        $this->assertContains('Full Name', $allFields);
        $this->assertContains('Overall Satisfaction Rating', $allFields);
    }

    public function test_document_import_service_hybrid_enrichment(): void
    {
        $validator = new FormSchemaValidator();
        $aiService = new AiFormGeneratorService($validator);
        $docxParser = new DocxParser();
        $xlsxParser = new XlsxParser();

        $service = new DocumentImportService($docxParser, $xlsxParser, $aiService, $validator);
        $docxPath = storage_path('app/samples/sample_job_application.docx');

        $result = $service->importDocument($docxPath, 'sample_job_application.docx');

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['schema']['sections']);
    }

    public function test_livewire_document_import_modal_flow(): void
    {
        $user = User::factory()->create();
        $docxPath = storage_path('app/samples/sample_job_application.docx');

        $service = app(DocumentImportService::class);
        $result = $service->importDocument($docxPath, 'sample_job_application.docx');

        Livewire::actingAs($user)
            ->test(\App\Livewire\Forms\DocumentImportModal::class)
            ->set('title', $result['title'])
            ->set('sections', $result['schema']['sections'])
            ->set('step', 2)
            ->call('commitImport')
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('forms', [
            'user_id' => $user->id,
            'title' => $result['title'],
        ]);
    }
}
