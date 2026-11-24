<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// FR-11: log and remove sessions idle for more than 15 minutes (wireframe 15).
Schedule::command('soc:expire-sessions')->everyMinute();
