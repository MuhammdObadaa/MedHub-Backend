<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'http://127.0.0.1:8000/*',
        'http://f0fe-37-19-205-195.ngrok-free.app/*',
        'http://192.168.43.98:8000/*',
        'http://192.168.42.2:8000/*',
    ];
}
