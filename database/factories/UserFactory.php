<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'password' => 'Password123',
            'role' => UserRole::Customer,
            'status' => UserStatus::Active,
            'email_verified_at' => now(),
            'accepted_terms_at' => now(),
        ];
    }

    public function contractor(): static
    {
        return $this->state(fn () => ['role' => UserRole::Contractor]);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => UserRole::Admin]);
    }

    public function blocked(): static
    {
        return $this->state(fn () => ['status' => UserStatus::Blocked]);
    }
}
