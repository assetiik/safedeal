<?php

namespace Database\Seeders;

use App\Domain\Deals\DealService;
use App\Domain\Deals\DisputeService;
use App\Domain\Payments\PaymentService;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'email' => 'admin@safedeal.test',
                'role' => UserRole::Admin,
                'display_name' => 'Администратор',
            ],
            [
                'email' => 'customer@safedeal.test',
                'role' => UserRole::Customer,
                'display_name' => 'ТОО Альфа',
            ],
            [
                'email' => 'contractor@safedeal.test',
                'role' => UserRole::Contractor,
                'display_name' => 'ИП Соколова',
            ],
        ];

        foreach ($users as $data) {
            $user = User::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'password' => 'Password123',
                    'role' => $data['role'],
                    'status' => UserStatus::Active,
                    'email_verified_at' => now(),
                    'accepted_terms_at' => now(),
                ],
            );

            $user->profile()->update([
                'display_name' => $data['display_name'],
                'tax_id' => $user->isCustomer() ? '190740012345' : ($user->isContractor() ? '010203500123' : null),
                'phone' => '+77011234567',
                'bank_details' => 'KZ123456789012345678',
                'legal_address' => $user->isCustomer() ? 'г. Алматы' : null,
            ]);
        }

        $customer = User::query()->where('email', 'customer@safedeal.test')->first();
        $contractor = User::query()->where('email', 'contractor@safedeal.test')->first();
        $admin = User::query()->where('email', 'admin@safedeal.test')->first();

        /** @var DealService $deals */
        $deals = app(DealService::class);
        /** @var PaymentService $payments */
        $payments = app(PaymentService::class);
        /** @var DisputeService $disputes */
        $disputes = app(DisputeService::class);

        $deal = $deals->create($customer, [
            'executor_email' => $contractor->email,
            'title' => 'Разработка лендинга для компании',
            'description' => 'Адаптивный лендинг и базовая админка',
            'amount_tenge' => 750000,
            'deadline' => now()->addMonth()->toDateString(),
            'terms' => 'Срок 30 дней. Правки — 2 раунда.',
            'required_documents' => ['ТЗ', 'Макеты'],
        ]);

        $deals->accept($deal, $contractor);
        $deals->confirmContract($deal->fresh(), $customer);
        $deals->confirmContract($deal->fresh(), $contractor);
        $payments->reserve($deal->fresh(), $customer, (string) Str::uuid());

        $deal2 = $deals->create($customer, [
            'executor_email' => $contractor->email,
            'title' => 'SEO-продвижение сайта',
            'description' => 'Аудит и контент-план',
            'amount_tenge' => 250000,
            'deadline' => now()->addWeeks(6)->toDateString(),
            'terms' => 'Ежемесячный отчёт.',
            'required_documents' => ['Отчёт'],
        ]);
        $deals->accept($deal2, $contractor);
        $deals->confirmContract($deal2->fresh(), $customer);
        $deals->confirmContract($deal2->fresh(), $contractor);
        $payments->reserve($deal2->fresh(), $customer, (string) Str::uuid());
        $disputes->open($deal2->fresh(), $customer, 'Работа не соответствует техническому заданию и срокам сдачи');

        unset($admin);
    }
}
