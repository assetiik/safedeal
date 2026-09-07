<?php

namespace Tests\Feature;

use App\Enums\DealStatus;
use App\Enums\DocumentType;
use App\Models\Deal;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_download_url_uses_request_host_not_app_url(): void
    {
        config(['app.url' => 'http://localhost:8000']);

        $customer = User::factory()->create();
        $contractor = User::factory()->contractor()->create();

        $deal = $this->makeDeal($customer, $contractor, ['title' => 'Тест документов', 'required_documents' => ['ТЗ']]);

        Storage::disk('documents')->put('deals/test/file.txt', 'hello');

        $document = Document::query()->create([
            'deal_id' => $deal->id,
            'type' => DocumentType::Other,
            'title' => 'Файл',
            'file_name' => 'file.txt',
            'mime_type' => 'text/plain',
            'size_bytes' => 5,
            'storage_key' => 'deals/test/file.txt',
            'uploaded_by_user_id' => $customer->id,
        ]);

        $response = $this->authJson(
            $customer,
            'GET',
            "http://192.168.1.10:8000/api/v1/documents/{$document->id}/download",
        );

        $response->assertOk();
        $url = $response->json('url');
        $this->assertStringContainsString('192.168.1.10:8000', $url);
        $this->assertStringNotContainsString('localhost', $url);
    }

    public function test_stranger_cannot_list_or_download_document(): void
    {
        $customer = User::factory()->create();
        $contractor = User::factory()->contractor()->create();
        $stranger = User::factory()->create();

        $deal = $this->makeDeal($customer, $contractor, ['title' => 'Чужая сделка']);

        Storage::disk('documents')->put('deals/test/secret.txt', 'secret');

        $document = Document::query()->create([
            'deal_id' => $deal->id,
            'type' => DocumentType::Other,
            'title' => 'Секрет',
            'file_name' => 'secret.txt',
            'mime_type' => 'text/plain',
            'size_bytes' => 6,
            'storage_key' => 'deals/test/secret.txt',
            'uploaded_by_user_id' => $customer->id,
        ]);

        $this->authJson($stranger, 'GET', "/api/v1/documents/{$document->id}")
            ->assertNotFound();

        $this->authJson($stranger, 'GET', "/api/v1/documents/{$document->id}/download")
            ->assertNotFound();

        $this->authJson($stranger, 'GET', "/api/v1/deals/{$deal->id}/documents")
            ->assertNotFound();
    }

    public function test_participant_can_upload_and_open_signed_file(): void
    {
        $customer = User::factory()->create();
        $contractor = User::factory()->contractor()->create();

        $deal = $this->makeDeal($customer, $contractor, ['title' => 'Загрузка']);

        $upload = $this->actingAs($customer, 'sanctum')
            ->post("/api/v1/deals/{$deal->id}/documents", [
                'type' => DocumentType::Technical->value,
                'title' => 'ТЗ',
                'file' => UploadedFile::fake()->create('tz.pdf', 100, 'application/pdf'),
            ], ['Accept' => 'application/json']);

        $upload->assertCreated();
        $documentId = $upload->json('id');

        $download = $this->authJson($customer, 'GET', "/api/v1/documents/{$documentId}/download");
        $download->assertOk();

        $this->get($download->json('url'))->assertOk();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeDeal(User $customer, User $contractor, array $overrides = []): Deal
    {
        static $n = 9000;

        return Deal::query()->create(array_merge([
            'deal_number' => ++$n,
            'customer_user_id' => $customer->id,
            'contractor_user_id' => $contractor->id,
            'contractor_invite_email' => $contractor->email,
            'title' => 'Сделка',
            'description' => 'Описание',
            'amount_tenge' => 10000,
            'deadline' => now()->addMonth()->toDateString(),
            'terms' => 'Условия',
            'required_documents' => [],
            'status' => DealStatus::InProgress,
        ], $overrides));
    }
}
