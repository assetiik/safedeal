<?php

namespace Tests\Unit;

use App\Domain\Deals\DealStateMachine;
use App\Enums\DealAction;
use App\Enums\DealStatus;
use App\Exceptions\ApiException;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DealStateMachineTest extends TestCase
{
    use RefreshDatabase;

    public function test_contractor_cannot_confirm_completion(): void
    {
        $customer = User::factory()->create();
        $contractor = User::factory()->contractor()->create();
        $deal = $this->deal($customer, $contractor, DealStatus::AwaitingCustomer);

        $this->expectException(ApiException::class);
        app(DealStateMachine::class)->assertCan($deal, $contractor, DealAction::ConfirmCompletion);
    }

    public function test_customer_cannot_mark_work_completed(): void
    {
        $customer = User::factory()->create();
        $contractor = User::factory()->contractor()->create();
        $deal = $this->deal($customer, $contractor, DealStatus::InProgress);

        $this->expectException(ApiException::class);
        app(DealStateMachine::class)->assertCan($deal, $customer, DealAction::MarkWorkCompleted);
    }

    public function test_cannot_act_on_completed_deal(): void
    {
        $customer = User::factory()->create();
        $contractor = User::factory()->contractor()->create();
        $deal = $this->deal($customer, $contractor, DealStatus::Completed);

        $this->assertFalse(
            app(DealStateMachine::class)->can($deal, $customer, DealAction::OpenDispute),
        );
    }

    public function test_invitee_can_accept(): void
    {
        $customer = User::factory()->create();
        $contractor = User::factory()->contractor()->create();
        $deal = $this->deal($customer, $contractor, DealStatus::AwaitingExecutor);

        app(DealStateMachine::class)->assertCan($deal, $contractor, DealAction::AcceptInvitation);
        $this->assertTrue(true);
    }

    private function deal(User $customer, User $contractor, DealStatus $status): Deal
    {
        return Deal::query()->create([
            'deal_number' => (int) (Deal::query()->max('deal_number') ?: 0) + 1,
            'status' => $status,
            'title' => 'Test',
            'description' => 'Desc',
            'amount_tenge' => 1000,
            'currency' => 'KZT',
            'deadline' => now()->addMonth()->toDateString(),
            'terms' => 'Terms',
            'customer_user_id' => $customer->id,
            'contractor_user_id' => $contractor->id,
            'contractor_invite_email' => $contractor->email,
        ]);
    }
}
