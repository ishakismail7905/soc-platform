<?php

namespace Tests\Feature\Ui;

use App\Enums\Role;
use App\Models\Client;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ForbiddenPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // A stand-in admin page until Task 8 builds the real one.
        Route::middleware(['web', 'auth', 'can:users.manage'])
            ->get('/_test/admin-only', fn () => 'top secret admin page');
    }

    public function test_forbidden_page_matches_wireframe_20_and_leaks_nothing(): void
    {
        $this->actingAsRole(Role::ClientViewer, [Client::factory()->create()]);

        $this->get('/_test/admin-only')
            ->assertForbidden()
            ->assertSee("You don't have access to this page", false)
            ->assertSee('Back to my dashboard')
            ->assertDontSee('top secret admin page')
            ->assertDontSee('This action is unauthorized');
    }
}