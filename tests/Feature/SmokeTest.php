<?php

namespace Tests\Feature;

use Tests\TestCase;

class SmokeTest extends TestCase
{
    public function test_health_route_responds(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_app_runs_in_malaysia_time_with_15_minute_sessions(): void
    {
        // FR-2 (UTC+8) and FR-11 (15-minute idle logout).
        $this->assertSame('Asia/Kuala_Lumpur', config('app.timezone'));
        $this->assertSame(15, (int) config('session.lifetime'));
    }

    public function test_frontend_uses_bootstrap_not_tailwind(): void
    {
        // Report Table 3.5: Bootstrap is the frontend framework.
        $package = json_decode(file_get_contents(base_path('package.json')), true);
        $deps = array_merge($package['dependencies'] ?? [], $package['devDependencies'] ?? []);

        $this->assertArrayHasKey('bootstrap', $deps);
        $this->assertArrayNotHasKey('tailwindcss', $deps);
    }
}