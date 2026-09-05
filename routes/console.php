<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// A held tutor or seat is unavailable to everyone else, so holds are released
// on a schedule rather than waiting for someone to notice.
Schedule::command('marketplace:expire')->hourly();
