<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Documents\DocumentService;
use App\Enums\DocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UploadDocumentRequest;
use App\Http\Resources\Api\V1\DocumentResource;
use App\Http\Support\ApiDate;
use App\Http\Support\ApiPaginator;
use App\Models\Deal;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentService $documents,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Document::query()
            ->with(['deal', 'uploadedBy.profile'])
            ->whereHas('deal', fn ($q) => $q->forUser($request->user()))
            ->orderByDesc('created_at');

        if ($request->filled('deal_id')) {
            $query->where('deal_id', $request->string('deal_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        $q = trim($request->string('q')->toString());
        if ($q !== '') {
            $query->where(function ($inner) use ($q) {
                $inner->where('title', 'like', '%'.$q.'%')
                    ->orWhere('file_name', 'like', '%'.$q.'%');
            });
        }

        return ApiPaginator::make(
            $query->paginate(ApiPaginator::pageSize($request)),
            DocumentResource::class,
            $request,
        );
    }

    public function forDeal(Request $request, Deal $deal): JsonResponse
    {
        $docs = $deal->documents()->with(['deal', 'uploadedBy.profile'])->orderByDesc('created_at')->get();

        return response()->json([
            'data' => DocumentResource::collection($docs)->resolve($request),
        ]);
    }

    public function store(UploadDocumentRequest $request, Deal $deal): JsonResponse
    {
        $document = $this->documents->storeUpload(
            $deal,
            $request->user(),
            $request->file('file'),
            DocumentType::from($request->string('type')->toString()),
            $request->string('title')->toString(),
        );

        return response()->json(
            (new DocumentResource($document->load(['deal', 'uploadedBy.profile'])))->resolve($request),
            201,
        );
    }

    public function show(Request $request, Document $document): JsonResponse
    {
        $payload = (new DocumentResource($document))->resolve($request);

        if (str_starts_with($document->mime_type, 'text/')) {
            $payload['preview'] = $this->documents->contents($document);
        }

        return response()->json($payload);
    }

    public function download(Request $request, Document $document): JsonResponse
    {
        $minutes = (int) config('escrow.documents.download_ttl_minutes', 5);
        $expires = now()->addMinutes($minutes);

        // Use the host the client called, not APP_URL (localhost breaks Flutter on device/LAN).
        $previousRoot = config('app.url');
        URL::forceRootUrl(rtrim($request->root(), '/'));

        try {
            $url = URL::temporarySignedRoute('documents.file', $expires, [
                'document' => $document->id,
            ]);
        } finally {
            URL::forceRootUrl($previousRoot);
        }

        return response()->json([
            'url' => $url,
            'expires_at' => ApiDate::iso($expires),
        ]);
    }

    public function file(Document $document): StreamedResponse
    {
        $disk = (string) config('escrow.documents.disk', 'documents');

        return response()->streamDownload(function () use ($document, $disk) {
            echo \Illuminate\Support\Facades\Storage::disk($disk)->get($document->storage_key);
        }, $document->file_name, [
            'Content-Type' => $document->mime_type,
        ]);
    }
}
