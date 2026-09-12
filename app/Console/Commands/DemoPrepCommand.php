<?php

namespace App\Console\Commands;

use App\Enums\DealStatus;
use App\Enums\DisputeResolutionType;
use App\Enums\DisputeStatus;
use App\Enums\NotificationType;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\AppNotification;
use App\Models\Deal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DemoPrepCommand extends Command
{
    protected $signature = 'demo:prep
                            {--dry-run : Only show what would change}';

    protected $description = 'Safe demo cleanup: delete junk deals #3/#4, fix stuck dispute deals, purge login spam, normalize payout statuses';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $this->cleanupLoginSpam($dry);
        $this->deleteJunkDeals($dry);
        $this->syncResolvedDisputes($dry);
        $this->normalizePayoutStatuses($dry);
        $this->refreshOpenPublicContracts($dry);

        $this->info($dry ? 'Dry-run complete (no changes written).' : 'Demo prep complete.');

        return self::SUCCESS;
    }

    private function cleanupLoginSpam(bool $dry): void
    {
        $query = AppNotification::query()
            ->where('type', NotificationType::AccountSecurity)
            ->where('title', 'Новый вход');

        $count = $query->count();
        $this->line("Login spam notifications: {$count}");

        if (! $dry && $count > 0) {
            $query->delete();
        }
    }

    private function deleteJunkDeals(bool $dry): void
    {
        $deals = Deal::query()
            ->whereIn('deal_number', [3, 4])
            ->get();

        foreach ($deals as $deal) {
            $this->line("Delete deal #{$deal->deal_number} «{$deal->title}» ({$deal->id})");

            if ($dry) {
                continue;
            }

            DB::transaction(function () use ($deal): void {
                $disk = (string) config('escrow.documents.disk', 'documents');
                foreach ($deal->documents as $document) {
                    Storage::disk($disk)->delete($document->storage_key);
                }

                $deal->delete();
            });
        }
    }

    private function syncResolvedDisputes(bool $dry): void
    {
        $deals = Deal::query()
            ->where('status', DealStatus::Dispute)
            ->whereHas('dispute', fn ($q) => $q->where('status', DisputeStatus::Resolved))
            ->with('dispute')
            ->get();

        foreach ($deals as $deal) {
            $dispute = $deal->dispute;
            $target = match ($dispute->resolution_type) {
                DisputeResolutionType::RefundCustomer => DealStatus::Refunded,
                DisputeResolutionType::PayoutContractor => DealStatus::PayoutCompleted,
                DisputeResolutionType::Partial => ($dispute->contractor_amount_tenge ?? 0) > 0
                    ? DealStatus::PayoutCompleted
                    : DealStatus::Refunded,
                default => DealStatus::Refunded,
            };

            $this->line("Sync deal #{$deal->deal_number}: dispute → {$target->value}");

            if (! $dry) {
                $deal->update([
                    'status' => $target,
                    'funds_frozen' => false,
                ]);
            }
        }

        // Stuck dispute with successful refund payment but deal still in dispute
        $stuck = Deal::query()
            ->where('status', DealStatus::Dispute)
            ->whereHas('payments', fn ($q) => $q
                ->whereIn('type', [\App\Enums\PaymentType::Refund, \App\Enums\PaymentType::PartialRefund])
                ->where('status', \App\Enums\PaymentStatus::Succeeded))
            ->get();

        foreach ($stuck as $deal) {
            $this->line("Unstick deal #{$deal->deal_number}: dispute + refund → refunded");
            if (! $dry) {
                $deal->update(['status' => DealStatus::Refunded, 'funds_frozen' => false]);
                if ($deal->dispute && $deal->dispute->status->isActive()) {
                    $deal->dispute->update([
                        'status' => DisputeStatus::Resolved,
                        'resolution_type' => DisputeResolutionType::RefundCustomer,
                        'resolution_note' => $deal->dispute->resolution_note ?: 'Автосинхронизация для демо',
                        'resolved_at' => now(),
                    ]);
                }
            }
        }
    }

    private function normalizePayoutStatuses(bool $dry): void
    {
        $deals = Deal::query()
            ->where('status', DealStatus::Completed)
            ->whereHas('payments', fn ($q) => $q
                ->whereIn('type', [PaymentType::Payout, PaymentType::PartialPayout])
                ->where('status', PaymentStatus::Succeeded))
            ->get();

        foreach ($deals as $deal) {
            $this->line("Normalize deal #{$deal->deal_number}: completed → payout_completed");

            if (! $dry) {
                $deal->update(['status' => DealStatus::PayoutCompleted]);
            }
        }
    }

    private function refreshOpenPublicContracts(bool $dry): void
    {
        $generator = app(\App\Domain\Contracts\ContractGenerator::class);

        $deals = Deal::query()
            ->with(['customer.profile', 'contractor.profile', 'contract'])
            ->where('visibility', 'public')
            ->whereNull('contractor_user_id')
            ->where('status', DealStatus::AwaitingExecutor)
            ->get();

        foreach ($deals as $deal) {
            if (! $deal->contract) {
                continue;
            }

            $this->line("Refresh contract body for open deal #{$deal->deal_number}");

            if (! $dry) {
                $deal->contract->update(['body_snapshot' => $generator->render($deal)]);
            }
        }
    }
}
