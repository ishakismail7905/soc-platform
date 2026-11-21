<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Cookie;

/**
 * A small cookie meaning "this browser was signed in". If a request later arrives
 * with the cookie but no valid session, the session timed out (wireframe 20).
 * Laravel encrypts it with APP_KEY, so it can't be forged, and it holds nothing sensitive.
 */
class TrackSignedInBrowser
{
    public const COOKIE = 'soc_was_signed_in';

    public function handleLogin(Login $event): void
    {
        Cookie::queue(self::COOKIE, '1', 60 * 24 * 7); // minutes: 7 days
    }

    public function handleLogout(Logout $event): void
    {
        Cookie::queue(Cookie::forget(self::COOKIE)); // a real logout is not a timeout
    }
}