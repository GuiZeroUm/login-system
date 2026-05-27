<?php

namespace Tests;

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function inertiaHeaders(): array
    {
        $version = app(HandleInertiaRequests::class)->version($this->app['request']);

        return [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $version ?? '',
        ];
    }
}
