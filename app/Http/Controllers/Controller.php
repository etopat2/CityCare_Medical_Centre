<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Base controller shared by application endpoints for common HTTP behavior.
 */
abstract class Controller
{
    use AuthorizesRequests;
}
