<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Rules\PasswordPolicy;
use App\Services\PasswordService;
use App\Support\AuditEvent;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PasswordPolicyTest extends TestCase
{
    private function passes(string $password, ?User $user = null): bool
    {
        return Validator::make(
            ['password' => $password, 'password_confirmation' => $password],
            ['password' => PasswordPolicy::rules($user)]
        )->passes();
    }

    public function test_minimum_12_characters_and_no_composition_rules(): void
    {
        // FR-11 + NIST SP 800-63B-4: length matters, character mixes are not required.
        $this->assertFalse($this->passes('Short1!Aa'));           // 9 characters
        $this->assertFalse($this->passes('elevenchars'));          // 11 characters
        $this->assertTrue($this->passes('twelve chars'));          // 12, spaces allowed
        $this->assertTrue($this->passes('all lowercase passphrase'));
    }

    public function test_up_to_64_characters_allowed(): void
    {
        $this->assertTrue($this->passes(str_repeat('a', 64)));
        $this->assertFalse($this->passes(str_repeat('a', 65)));
    }

    public function test_confirmation_must_match(): void
    {
        $result = Validator::make(
            ['password' => 'twelve chars', 'password_confirmation' => 'something else'],
            ['password' => PasswordPolicy::rules()]
        );

        $this->assertTrue($result->fails());
    }

    public function test_last_five_passwords_cannot_be_reused(): void
    {
        $user = User::factory()->create();
        $pw = fn (int $i) => "rotating passphrase {$i}";

        foreach (range(1, 6) as $i) {
            app(PasswordService::class)->change($user, $pw($i));
        }

        $user = $user->fresh();
        $this->assertFalse($this->passes($pw(6), $user)); // current
        $this->assertFalse($this->passes($pw(2), $user)); // within the last 5
        $this->assertTrue($this->passes($pw(1), $user));  // 6th oldest is allowed again
    }

    public function test_password_change_is_logged_and_clears_forced_change(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        app(PasswordService::class)->change($user, 'a brand new passphrase');

        $user = $user->fresh();
        $this->assertFalse($user->must_change_password);
        $this->assertNotNull($user->password_changed_at);
        $this->assertDatabaseHas('activity_logs', [
            'event' => AuditEvent::AUTH_PASSWORD_CHANGED, 'user_id' => $user->id,
        ]);
    }
}
