<?php

use App\Console\CheckOrderStatuses;
use Illuminate\Support\Facades\Schedule;

Schedule::call(new CheckOrderStatuses)
    ->everyMinute()
    ->name('orders:check-status')
    ->withoutOverlapping(2);
