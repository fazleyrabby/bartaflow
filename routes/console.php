<?php

declare(strict_types=1);

use App\Console\Commands\DispatchDueSchedulesCommand;
use App\Console\Commands\WhatsAppHealthCheckCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(WhatsAppHealthCheckCommand::class)->hourly();

// Dispatch due scheduled messages every minute; never overlap concurrent runs.
Schedule::command(DispatchDueSchedulesCommand::class)->everyMinute()->withoutOverlapping();
