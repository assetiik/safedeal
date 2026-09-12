<?php

namespace Tests\Feature;

use App\Enums\DealStatus;
use App\Enums\NotificationType;
use App\Models\AppNotification;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DemoPrepApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_does_not_spam_new_login_notification(): void
    {
        $user = User::factory()->create([
            'email' => 'buyer@test.kz',
            'password' => 'Password123',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'buyer@test.kz',
            'password' => 'Password123',
        ])->assertOk();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'buyer@test.kz',
            'password' => 'Password123',
        ])->assertOk();

        $this->assertSame(0, AppNotification::query()
            ->where('user_id', $user->id)
            ->where('type', NotificationType::AccountSecurity)
            ->where('title', 'Новый вход')
            ->count());
    }

    public function test_confirm_completion_sets_payout_completed(): void
    {
        $customer = User::factory()->create();
        $contractor = User::factory()->contractor()->create();

        $dealId = $this->authJson($customer, 'POST', '/api/v1/deals', [
            'executor_email' => $contractor->email,
            'title' => 'Работа',
            'description' => 'Описание',
            'amount_tenge' => 10000,
            'deadline' => now()->addMonth()->toDateString(),
            'terms' => 'Условия',
        ])->json('id');

        $this->authJson($contractor, 'POST', "/api/v1/deals/{$dealId}/actions/accept_invitation")->assertOk();
        $this->authJson($customer, 'POST', "/api/v1/deals/{$dealId}/contract/confirm", ['acknowledged' => true])->assertOk();
        $this->authJson($contractor, 'POST', "/api/v1/deals/{$dealId}/contract/confirm", ['acknowledged' => true])->assertOk();
        $this->authJson($customer, 'POST', "/api/v1/deals/{$dealId}/payment/reserve", [], [
            'Idempotency-Key' => 'pay-1',
        ])->assertOk();
        $this->authJson($contractor, 'POST', "/api/v1/deals/{$dealId}/actions/mark_work_completed")->assertOk();

        $this->authJson($customer, 'POST', "/api/v1/deals/{$dealId}/actions/confirm_completion")
            ->assertOk()
            ->assertJsonPath('status', DealStatus::PayoutCompleted->value);

        $history = $this->authJson($customer, 'GET', "/api/v1/deals/{$dealId}/history");
        $history->assertOk();
        $this->assertNotEmpty($history->json('data'));

        $payments = $this->authJson($customer, 'GET', '/api/v1/payments');
        $payments->assertOk();
        $this->assertGreaterThanOrEqual(2, count($payments->json('data')));
    }

    public function test_public_contract_placeholder_then_refresh_after_claim(): void
    {
        Mail::fake();

        $customer = User::factory()->create();
        $contractor = User::factory()->contractor()->create();

        $dealId = $this->authJson($customer, 'POST', '/api/v1/deals', [
            'visibility' => 'public',
            'specialty' => 'Разработка',
            'title' => 'Открытый заказ',
            'description' => 'Описание',
            'amount_tenge' => 50000,
            'deadline' => now()->addMonth()->toDateString(),
            'terms' => 'Условия',
        ])->json('id');

        $contract = $this->authJson($customer, 'GET', "/api/v1/deals/{$dealId}/contract");
        $contract->assertOk();
        $this->assertStringContainsString('определяется после отклика', $contract->json('body'));

        $this->authJson($contractor, 'POST', "/api/v1/deals/{$dealId}/actions/claim")->assertOk();

        $after = $this->authJson($customer, 'GET', "/api/v1/deals/{$dealId}/contract");
        $after->assertOk();
        $this->assertStringContainsString($contractor->displayName(), $after->json('body'));
        $this->assertStringNotContainsString('определяется после отклика', $after->json('body'));
    }

    public function test_demo_prep_deletes_junk_deals(): void
    {
        $customer = User::factory()->create();

        foreach ([
            ['deal_number' => 3, 'title' => 'sgagsgsgdgdg', 'amount_tenge' => 11111111],
            ['deal_number' => 4, 'title' => 'MVP', 'description' => 'desc', 'amount_tenge' => 120000],
            ['deal_number' => 5, 'title' => 'Keep me', 'amount_tenge' => 50000],
        ] as $attrs) {
            Deal::query()->create([
                'deal_number' => $attrs['deal_number'],
                'status' => DealStatus::AwaitingExecutor,
                'visibility' => 'private',
                'title' => $attrs['title'],
                'description' => $attrs['description'] ?? 'Описание',
                'amount_tenge' => $attrs['amount_tenge'],
                'currency' => 'KZT',
                'commission_rate_bps' => 0,
                'commission_amount_tenge' => 0,
                'deadline' => now()->addMonth()->toDateString(),
                'terms' => 'Условия',
                'required_documents' => [],
                'customer_user_id' => $customer->id,
                'contractor_invite_email' => 'contractor@test.kz',
            ]);
        }

        $this->artisan('demo:prep')->assertSuccessful();

        $this->assertDatabaseMissing('deals', ['deal_number' => 3]);
        $this->assertDatabaseMissing('deals', ['deal_number' => 4]);
        $this->assertDatabaseHas('deals', ['deal_number' => 5, 'title' => 'Keep me']);
    }
}
