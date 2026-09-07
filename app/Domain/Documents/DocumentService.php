<?php

namespace App\Domain\Documents;

use App\Domain\Audit\AuditLogger;
use App\Enums\AuditAction;
use App\Enums\DocumentType;
use App\Models\Deal;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class DocumentService
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    public function storeUpload(Deal $deal, User $user, UploadedFile $file, DocumentType $type, string $title): Document
    {
        $disk = (string) config('escrow.documents.disk', 'documents');
        $key = 'deals/'.$deal->id.'/'.Str::uuid().'_'.$this->safeName($file->getClientOriginalName());

        Storage::disk($disk)->put($key, $file->getContent());

        $document = Document::query()->create([
            'deal_id' => $deal->id,
            'type' => $type,
            'title' => $title,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size_bytes' => $file->getSize(),
            'storage_key' => $key,
            'uploaded_by_user_id' => $user->id,
        ]);

        $this->audit->record(AuditAction::DocumentUploaded, $document, $user, $deal->id, [
            'title' => $title,
            'type' => $type->value,
        ]);

        return $document;
    }

    public function storeGenerated(Deal $deal, string $contents, string $fileName, string $title, DocumentType $type, ?User $actor = null): Document
    {
        $disk = (string) config('escrow.documents.disk', 'documents');
        $key = 'deals/'.$deal->id.'/'.Str::uuid().'_'.$this->safeName($fileName);

        Storage::disk($disk)->put($key, $contents);

        $document = Document::query()->create([
            'deal_id' => $deal->id,
            'type' => $type,
            'title' => $title,
            'file_name' => $fileName,
            'mime_type' => 'text/plain; charset=UTF-8',
            'size_bytes' => strlen($contents),
            'storage_key' => $key,
            'uploaded_by_user_id' => $actor?->id,
        ]);

        $this->audit->record(AuditAction::DocumentGenerated, $document, $actor, $deal->id, [
            'title' => $title,
        ]);

        return $document;
    }

    public function contents(Document $document): string
    {
        return Storage::disk((string) config('escrow.documents.disk', 'documents'))
            ->get($document->storage_key);
    }

    private function safeName(string $name): string
    {
        return preg_replace('/[^A-Za-z0-9._-]+/', '_', $name) ?: 'file';
    }
}
