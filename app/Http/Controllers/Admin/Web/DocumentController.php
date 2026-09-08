<?php

namespace App\Http\Controllers\Admin\Web;

use App\Domain\Documents\DocumentService;
use App\Enums\DocumentType;
use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentService $documents,
    ) {}

    public function index(Request $request): View
    {
        $query = Document::query()->with('deal')->latest();

        $type = $request->string('type', 'all')->toString();
        if (in_array($type, ['contract', 'act', 'technical', 'other'], true)) {
            $query->where('type', $type);
        }

        $dealId = $request->string('deal_id')->toString();
        if ($dealId !== '') {
            $query->where('deal_id', $dealId);
        }

        $q = trim($request->string('q')->toString());
        if ($q !== '') {
            $query->where(function ($inner) use ($q) {
                $inner->where('title', 'like', '%'.$q.'%')
                    ->orWhere('file_name', 'like', '%'.$q.'%');

                $number = ltrim($q, '#');
                if (ctype_digit($number)) {
                    $inner->orWhereHas('deal', fn ($d) => $d->where('deal_number', (int) $number));
                }
            });
        }

        return view('admin.documents.index', [
            'documents' => $query->paginate(20)->withQueryString(),
            'type' => $type,
            'q' => $q,
            'dealId' => $dealId,
            'filteredDeal' => $dealId !== '' ? Deal::query()->find($dealId) : null,
            'deals' => Deal::query()->latest()->limit(50)->get(['id', 'deal_number', 'title']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'deal_id' => ['required', 'uuid', 'exists:deals,id'],
            'type' => ['required', 'in:contract,technical,act,other'],
            'title' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:20480'],
        ]);

        $deal = Deal::query()->findOrFail($data['deal_id']);
        $this->documents->storeUpload(
            $deal,
            $request->user(),
            $request->file('file'),
            DocumentType::from($data['type']),
            $data['title'],
        );

        return back()->with('success', 'Документ загружен');
    }

    public function download(Document $document): StreamedResponse
    {
        $disk = (string) config('escrow.documents.disk', 'documents');

        return Storage::disk($disk)->download($document->storage_key, $document->file_name);
    }
}
