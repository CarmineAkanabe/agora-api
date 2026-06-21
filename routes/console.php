<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Schedule::command('requests:expire')->everyFiveMinutes();

Schedule::command('escrow:release')->hourly();
