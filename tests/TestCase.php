<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('documents');
    }

    protected function authJson(
        User $user,
        string $method,
        string $uri,
        array $data = [],
        array $headers = [],
    ): TestResponse {
        Sanctum::actingAs($user, ['*']);

        return $this->withHeaders(array_merge([
            'Accept' => 'application/json',
        ], $headers))->json($method, $uri, $data);
    }
}
