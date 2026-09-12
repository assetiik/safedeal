<?php

namespace Tests\Feature;

use App\Enums\DealStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HappyPathTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_deal_lifecycle_to_payout(): void
    {
        $customer = User::factory()->create(['email' => 'buyer@test.kz']);
        $contractor = User::factory()->contractor()->create(['email' => 'maker@test.kz']);

        $create = $this->authJson($customer, 'POST', '/api/v1/deals', [
            'executor_email' => $contractor->email,
            'title' => 'Разработка MVP',
            'description' => 'Сделать backend API',
            'amount_tenge' => 120000,
            'deadline' => now()->addMonth()->toDateString(),
            'terms' => 'Сдать API и документацию',
            'required_documents' => ['ТЗ'],
        ]);
        $create->assertCreated();
        $dealId = $create->json('id');
        $this->assertSame(DealStatus::AwaitingExecutor->value, $create->json('status'));

        $this->authJson($contractor, 'POST', "/api/v1/deals/{$dealId}/actions/accept_invitation")
            ->assertOk()
            ->assertJsonPath('status', DealStatus::ContractConfirmed->value);

        $this->authJson($customer, 'POST', "/api/v1/deals/{$dealId}/contract/confirm", ['acknowledged' => true])
            ->assertOk();
        $this->authJson($contractor, 'POST', "/api/v1/deals/{$dealId}/contract/confirm", ['acknowledged' => true])
            ->assertOk()
            ->assertJsonPath('status', DealStatus::AwaitingPayment->value);

        $pay = $this->authJson($customer, 'POST', "/api/v1/deals/{$dealId}/payment/reserve", [], [
            'Idempotency-Key' => 'test-reserve-1',
        ]);
        $pay->assertOk()->assertJsonPath('flow_status', 'success');
        $this->assertContains($pay->json('deal.status'), [
            DealStatus::MoneyReserved->value,
            DealStatus::InProgress->value,
        ]);

        $this->authJson($contractor, 'POST', "/api/v1/deals/{$dealId}/actions/mark_work_completed")
            ->assertOk()
            ->assertJsonPath('status', DealStatus::AwaitingCustomer->value);

        $this->authJson($customer, 'POST', "/api/v1/deals/{$dealId}/actions/confirm_completion")
            ->assertOk()
            ->assertJsonPath('status', DealStatus::PayoutCompleted->value);

        $this->authJson($customer, 'GET', '/api/v1/deals')
            ->assertOk()
            ->assertJsonPath('data.0.id', $dealId);

        $stranger = User::factory()->create();
        $this->authJson($stranger, 'GET', "/api/v1/deals/{$dealId}")
            ->assertNotFound()
            ->assertJsonPath('error.code', 'NOT_FOUND');
    }

    public function test_dispute_refund_flow(): void
    {
        $customer = User::factory()->create();
        $contractor = User::factory()->contractor()->create();
        $admin = User::factory()->admin()->create();

        $dealId = $this->authJson($customer, 'POST', '/api/v1/deals', [
            'executor_email' => $contractor->email,
            'title' => 'Спорная сделка',
            'description' => 'Описание',
            'amount_tenge' => 50000,
            'deadline' => now()->addMonth()->toDateString(),
            'terms' => 'Условия выполнения работы',
        ])->json('id');

        $this->authJson($contractor, 'POST', "/api/v1/deals/{$dealId}/actions/accept_invitation")->assertOk();
        $this->authJson($customer, 'POST', "/api/v1/deals/{$dealId}/contract/confirm", ['acknowledged' => true])->assertOk();
        $this->authJson($contractor, 'POST', "/api/v1/deals/{$dealId}/contract/confirm", ['acknowledged' => true])->assertOk();
        $this->authJson($customer, 'POST', "/api/v1/deals/{$dealId}/payment/reserve", [], [
            'Idempotency-Key' => 'dispute-reserve',
        ])->assertOk();

        $dispute = $this->authJson($customer, 'POST', "/api/v1/deals/{$dealId}/disputes", [
            'reason' => 'Работа не соответствует ТЗ и срокам',
        ]);
        $dispute->assertCreated();
        $disputeId = $dispute->json('id');

        $this->authJson($admin, 'POST', "/api/v1/admin/disputes/{$disputeId}/take")->assertOk();
        $this->authJson($admin, 'POST', "/api/v1/admin/disputes/{$disputeId}/resolve", [
            'resolution_type' => 'refund_customer',
            'resolution_note' => 'Возврат заказчику: работы не приняты',
        ])->assertOk()->assertJsonPath('status', 'resolved');

        $this->authJson($customer, 'GET', "/api/v1/deals/{$dealId}")
            ->assertOk()
            ->assertJsonPath('status', DealStatus::Refunded->value);
    }

    public function test_register_and_login(): void
    {
        $register = $this->postJson('/api/v1/auth/register', [
            'email' => 'new@company.kz',
            'password' => 'secret12',
            'role' => UserRole::Customer->value,
            'accepted_terms' => true,
        ]);
        $register->assertCreated()->assertJsonPath('user.role', 'customer');
        $this->assertNotEmpty($register->json('tokens.access_token'));

        $this->postJson('/api/v1/auth/login', [
            'email' => 'new@company.kz',
            'password' => 'secret12',
        ])->assertOk()->assertJsonPath('user.email', 'new@company.kz');
    }
}
