<?php

use Illuminate\Foundation\Inspiring;
use App\Library\Log;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    (new Log())->info('schedule', 'Test Inspire Schedule');
})->describe('Display an inspiring quote');
