<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeTokenLogs
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->query('ctx');
        if ($token && is_string($token)) {
            $request->server->set('REQUEST_URI',
                preg_replace('/([?&])ctx=[^&]*/', '$1ctx=[REDACTED]', $request->server->get('REQUEST_URI', ''))
            );
        }

        return $next($request);
    }
}
