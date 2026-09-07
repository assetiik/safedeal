<?php

namespace App\Http\Controllers\Admin\Web;

use App\Domain\Audit\AuditLogger;
use App\Enums\AuditAction;
use App\Enums\DealStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request): View
    {
        $base = User::query()
            ->with('profile')
            ->whereIn('role', [UserRole::Customer, UserRole::Contractor]);

        $role = $request->string('role', 'all')->toString();
        if (in_array($role, ['customer', 'contractor'], true)) {
            $base->where('role', $role);
        }

        $q = trim($request->string('q')->toString());
        if ($q !== '') {
            $base->where(function ($inner) use ($q) {
                $inner->where('email', 'like', '%'.$q.'%')
                    ->orWhereHas('profile', fn ($p) => $p->where('display_name', 'like', '%'.$q.'%')
                        ->orWhere('tax_id', 'like', '%'.$q.'%'));
            });
        }

        $sort = $request->string('sort', 'newest')->toString();
        if (! in_array($sort, ['newest', 'oldest', 'name'], true)) {
            $sort = 'newest';
        }

        $filteredTotal = (clone $base)->count();

        $query = clone $base;
        match ($sort) {
            'oldest' => $query->orderBy('created_at'),
            'name' => $query->orderBy(
                \App\Models\Profile::query()
                    ->select('display_name')
                    ->whereColumn('profiles.user_id', 'users.id')
                    ->limit(1)
            )->orderBy('email'),
            default => $query->latest(),
        };

        return view('admin.users.index', [
            'users' => $query->paginate(15)->withQueryString(),
            'role' => $role,
            'q' => $q,
            'sort' => $sort,
            'total' => $filteredTotal,
            'allTotal' => User::query()->whereIn('role', [UserRole::Customer, UserRole::Contractor])->count(),
        ]);
    }

    public function show(User $user): View
    {
        abort_if($user->isAdmin(), 404);

        $user->load('profile');

        $dealsQuery = Deal::query()
            ->with(['customer.profile', 'contractor.profile'])
            ->where(function ($q) use ($user) {
                $q->where('customer_user_id', $user->id)
                    ->orWhere('contractor_user_id', $user->id);
            });

        $deals = (clone $dealsQuery)->latest('updated_at')->limit(5)->get();

        $stats = [
            'total' => (clone $dealsQuery)->count(),
            'pending' => (clone $dealsQuery)->whereIn('status', [
                DealStatus::AwaitingExecutor, DealStatus::ContractConfirmed, DealStatus::AwaitingPayment, DealStatus::AwaitingCustomer,
            ])->count(),
            'in_progress' => (clone $dealsQuery)->whereIn('status', [
                DealStatus::MoneyReserved, DealStatus::InProgress, DealStatus::WorkCompleted,
            ])->count(),
            'completed' => (clone $dealsQuery)->whereIn('status', [
                DealStatus::Completed, DealStatus::PayoutCompleted,
            ])->count(),
        ];

        return view('admin.users.show', compact('user', 'deals', 'stats'));
    }

    public function block(Request $request, User $user): RedirectResponse
    {
        abort_if($user->isAdmin(), 403);

        $user->update(['status' => UserStatus::Blocked]);
        $user->tokens()->delete();
        $this->audit->record(AuditAction::UserBlocked, $user, $request->user());

        return back()->with('success', 'Пользователь заблокирован');
    }

    public function unblock(Request $request, User $user): RedirectResponse
    {
        $user->update(['status' => UserStatus::Active]);
        $this->audit->record(AuditAction::UserUnblocked, $user, $request->user());

        return back()->with('success', 'Пользователь разблокирован');
    }
}
