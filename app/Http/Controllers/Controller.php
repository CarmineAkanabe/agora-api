<?php

namespace App\Http\Controllers;

use Laravel\Telescope\AuthorizesRequests;

abstract class Controller
{
    //
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;
}
