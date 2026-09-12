<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReferrerPolicyNoReferrer
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('Referrer-Policy', 'no-referrer');

        if ($request->query->has('ctx')) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
