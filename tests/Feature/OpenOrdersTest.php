<?php

namespace Tests\Feature;

use App\Enums\DealStatus;
use App\Enums\DealVisibility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpenOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_deal_create_without_executor_email(): void
    {
        $customer = User::factory()->create();

        $response = $this->authJson($customer, 'POST', '/api/v1/deals', [
            'visibility' => 'public',
            'is_open' => true,
            'specialty' => 'Разработка',
            'title' => 'Разработка MVP',
            'description' => 'Описание работы',
            'amount_tenge' => 120000,
            'deadline' => now()->addMonths(3)->toDateString(),
            'terms' => 'Условия выполнения',
            'additional_terms' => '',
            'required_documents' => ['Обходной лист.docx', 'file.pdf'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('visibility', 'public')
            ->assertJsonPath('specialty', 'Разработка')
            ->assertJsonPath('status', DealStatus::AwaitingExecutor->value)
            ->assertJsonPath('contractor_user_id', null);
    }

    public function test_private_deal_without_executor_email_fails(): void
    {
        $customer = User::factory()->create();

        $this->authJson($customer, 'POST', '/api/v1/deals', [
            'visibility' => 'private',
            'title' => 'Личная сделка',
            'description' => 'Описание',
            'amount_tenge' => 50000,
            'deadline' => now()->addMonth()->toDateString(),
            'terms' => 'Условия',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonPath('error.details.executor_email.0', 'The executor email field is required.');
    }

    public function test_private_deal_with_executor_email_succeeds(): void
    {
        $customer = User::factory()->create();
        $contractor = User::factory()->contractor()->create();

        $this->authJson($customer, 'POST', '/api/v1/deals', [
            'visibility' => 'private',
            'executor_email' => $contractor->email,
            'title' => 'Личная сделка',
            'description' => 'Описание',
            'amount_tenge' => 50000,
            'deadline' => now()->addMonth()->toDateString(),
            'terms' => 'Условия',
        ])
            ->assertCreated()
            ->assertJsonPath('visibility', 'private')
            ->assertJsonPath('status', DealStatus::AwaitingExecutor->value);
    }

    public function test_open_orders_feed_and_claim(): void
    {
        $customer = User::factory()->create(['email' => 'customer@safedeal.test']);
        $contractor = User::factory()->contractor()->create(['email' => 'contractor@safedeal.test']);
        $other = User::factory()->contractor()->create();

        $dealId = $this->authJson($customer, 'POST', '/api/v1/deals', [
            'visibility' => 'public',
            'specialty' => 'Разработка',
            'title' => 'Разработка MVP',
            'description' => 'Описание работы',
            'amount_tenge' => 120000,
            'deadline' => now()->addMonths(3)->toDateString(),
            'terms' => 'Условия выполнения',
        ])->json('id');

        $open = $this->authJson(
            $contractor,
            'GET',
            '/api/v1/orders/open?'.http_build_query(['specialty' => 'Разработка']),
        );
        $open->assertOk()
            ->assertJsonPath('data.0.id', $dealId)
            ->assertJsonPath('data.0.specialty', 'Разработка')
            ->assertJsonPath('data.0.city', 'Онлайн');

        $fallback = $this->authJson($contractor, 'GET', '/api/v1/deals?filter=open&visibility=public');
        $fallback->assertOk()->assertJsonPath('data.0.id', $dealId);

        $claim = $this->authJson($contractor, 'POST', "/api/v1/deals/{$dealId}/actions/claim");
        $claim->assertOk()
            ->assertJsonPath('status', DealStatus::ContractConfirmed->value)
            ->assertJsonPath('visibility', DealVisibility::Public->value)
            ->assertJsonPath('contractor_user_id', $contractor->id)
            ->assertJsonPath('contractor_invite_email', $contractor->email);

        $this->authJson($contractor, 'GET', '/api/v1/orders/open')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->authJson($other, 'POST', "/api/v1/deals/{$dealId}/actions/claim")
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'DEAL_ALREADY_CLAIMED');
    }

    public function test_deal_details_expose_visibility_and_specialty(): void
    {
        $customer = User::factory()->create();

        $dealId = $this->authJson($customer, 'POST', '/api/v1/deals', [
            'visibility' => 'public',
            'specialty' => 'Маркетинг',
            'title' => 'Кампания',
            'description' => 'Описание',
            'amount_tenge' => 80000,
            'deadline' => now()->addMonth()->toDateString(),
            'terms' => 'Условия',
        ])->json('id');

        $this->authJson($customer, 'GET', "/api/v1/deals/{$dealId}")
            ->assertOk()
            ->assertJsonPath('visibility', 'public')
            ->assertJsonPath('specialty', 'Маркетинг');

        $this->authJson($customer, 'GET', '/api/v1/deals')
            ->assertOk()
            ->assertJsonPath('data.0.visibility', 'public')
            ->assertJsonPath('data.0.specialty', 'Маркетинг');
    }
}
