<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class HeadersTest extends TestCase
{
    public function test_security_headers_are_on_every_page(): void
    {
        config(['soc.csp_enabled' => true]);

        $response = $this->get('/login');

        $response->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("script-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
    }

    public function test_headers_are_also_on_pages_that_do_not_exist(): void
    {
        $this->get('/no-such-page')->assertNotFound()->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_csp_can_be_switched_off_for_local_debugging(): void
    {
        config(['soc.csp_enabled' => false]);

        $this->get('/login')->assertHeaderMissing('Content-Security-Policy');
    }

    public function test_hsts_is_sent_only_over_https(): void
    {
        $this->get('/login')->assertHeaderMissing('Strict-Transport-Security');

        $this->get('https://localhost/login')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_session_cookie_is_locked_down(): void
    {
        $this->assertTrue(config('session.encrypt'));
        $this->assertTrue(config('session.http_only'));
        $this->assertSame('strict', config('session.same_site'));
        $this->assertTrue(config('session.expire_on_close'));
        $this->assertSame(15, (int) config('session.lifetime'));
    }
}