<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::query()
            ->with(['actor.profile', 'deal'])
            ->latest('created_at');

        $days = (int) $request->integer('days', 7);
        if ($days > 0) {
            $query->where('created_at', '>=', now()->subDays($days));
        }

        $actor = $request->string('actor', 'all')->toString();
        match ($actor) {
            'system' => $query->whereNull('actor_user_id'),
            'admin', 'customer', 'contractor' => $query->where('actor_role', $actor),
            default => null,
        };

        if ($request->filled('action')) {
            $query->where('action', $request->string('action'));
        }

        $q = trim($request->string('q')->toString());
        if ($q !== '') {
            $query->where(function ($inner) use ($q) {
                $inner->where('action', 'like', '%'.$q.'%')
                    ->orWhere('entity_type', 'like', '%'.$q.'%')
                    ->orWhere('ip', 'like', '%'.$q.'%')
                    ->orWhereHas('actor', fn ($a) => $a->where('email', 'like', '%'.$q.'%')
                        ->orWhereHas('profile', fn ($p) => $p->where('display_name', 'like', '%'.$q.'%')));
            });
        }

        return view('admin.audit.index', [
            'logs' => $query->paginate(25)->withQueryString(),
            'days' => $days,
            'q' => $q,
            'actor' => $actor,
            'action' => $request->string('action')->toString(),
            'actions' => AuditLog::query()->select('action')->distinct()->orderBy('action')->pluck('action'),
        ]);
    }
}
