<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Deals\DealService;
use App\Enums\DealAction;
use App\Enums\DealStatus;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateDealRequest;
use App\Http\Resources\Api\V1\DealListResource;
use App\Http\Resources\Api\V1\DealResource;
use App\Http\Support\ApiDate;
use App\Http\Support\ApiPaginator;
use App\Models\Deal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealController extends Controller
{
    public function __construct(
        private readonly DealService $deals,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Deal::query()
            ->with(['customer.profile', 'contractor.profile'])
            ->forUser($user)
            ->orderByDesc('updated_at');

        $filter = $request->string('filter')->toString();
        match ($filter) {
            'active' => $query->whereNotIn('status', [DealStatus::Completed, DealStatus::Draft, DealStatus::Refunded, DealStatus::PayoutCompleted]),
            'completed' => $query->whereIn('status', [DealStatus::Completed, DealStatus::PayoutCompleted]),
            'dispute' => $query->where('status', DealStatus::Dispute),
            default => null,
        };

        $q = trim($request->string('q')->toString());
        if ($q !== '') {
            $query->where(function ($inner) use ($q) {
                $inner->where('title', 'like', '%'.$q.'%')
                    ->orWhere('contractor_invite_email', 'like', '%'.$q.'%')
                    ->orWhereHas('customer.profile', fn ($p) => $p->where('display_name', 'like', '%'.$q.'%'))
                    ->orWhereHas('contractor.profile', fn ($p) => $p->where('display_name', 'like', '%'.$q.'%'));

                if (ctype_digit($q)) {
                    $inner->orWhere('deal_number', (int) $q);
                }
            });
        }

        return ApiPaginator::make(
            $query->paginate(ApiPaginator::pageSize($request)),
            DealListResource::class,
            $request,
        );
    }

    public function store(CreateDealRequest $request): JsonResponse
    {
        $deal = $this->deals->create($request->user(), $request->validated());

        return response()->json(
            (new DealResource($deal->load(['customer.profile', 'contractor.profile', 'payments'])))->resolve($request),
            201,
        );
    }

    public function show(Request $request, Deal $deal): JsonResponse
    {
        $deal->load(['customer.profile', 'contractor.profile', 'payments', 'contract']);

        return response()->json((new DealResource($deal))->resolve($request));
    }

    public function action(Request $request, Deal $deal, string $action): JsonResponse
    {
        $enum = DealAction::tryFrom($action);
        if ($enum === null || $enum === DealAction::OpenDispute) {
            throw new ApiException('UNKNOWN_ACTION', 'Неизвестное действие', 400, ['action' => $action]);
        }

        $deal = $this->deals->applyAction($deal, $request->user(), $enum);

        return response()->json(
            (new DealResource($deal->load(['customer.profile', 'contractor.profile', 'payments'])))->resolve($request),
        );
    }

    public function contract(Request $request, Deal $deal): JsonResponse
    {
        $payload = $this->deals->contractPayload($deal->load(['customer.profile', 'contractor.profile', 'contract']), $request->user());

        return response()->json([
            'deal_id' => $payload['deal_id'],
            'template_version' => $payload['template_version'],
            'body' => $payload['body'],
            'signature_type' => $payload['signature_type'],
            'customer_confirmed' => $payload['customer_confirmed'],
            'contractor_confirmed' => $payload['contractor_confirmed'],
            'customer_confirmed_at' => ApiDate::iso($payload['customer_confirmed_at']),
            'contractor_confirmed_at' => ApiDate::iso($payload['contractor_confirmed_at']),
            'can_confirm' => $payload['can_confirm'],
        ]);
    }

    public function confirmContract(Request $request, Deal $deal): JsonResponse
    {
        $acknowledged = $request->boolean('acknowledged', true);
        $deal = $this->deals->confirmContract($deal, $request->user(), $acknowledged);

        return response()->json(
            (new DealResource($deal->load(['customer.profile', 'contractor.profile', 'payments'])))->resolve($request),
        );
    }
}
