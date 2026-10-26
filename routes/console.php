<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily automatic backup of database at 11:00 PM
Schedule::command('backup:run --only-db --disable-notifications')->dailyAt('23:00');

// Clean up old backups based on retention policy at 11:30 PM
Schedule::command('backup:clean --disable-notifications')->dailyAt('23:30');
