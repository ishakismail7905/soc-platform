<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Hash;

/** Rejects a password matching any of the user's last N passwords (FR-11). */
class NotRecentlyUsed implements ValidationRule
{
    public function __construct(private ?User $user, private int $depth) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->user === null || $this->depth < 1) {
            return;
        }

        $hashes = $this->user->passwordHistories()->latest('id')->limit($this->depth)->pluck('password');

        foreach ($hashes as $hash) {
            if (Hash::check((string) $value, $hash)) {
                $fail("You cannot reuse any of your last {$this->depth} passwords.");

                return;
            }
        }
    }
}