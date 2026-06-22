<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Schedule::command('request:expire')->everyFiveMinutes();

Schedule::command('escrow:release')->hourly();
