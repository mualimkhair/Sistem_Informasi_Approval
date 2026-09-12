<?php

namespace App\Http\Middleware;

use App\Models\TabContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TabContextMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Tab-Token') ?? $request->query('ctx');

        if (is_string($token) && strlen($token) >= 32) {
            $userAgent = $request->userAgent();
            $context = TabContext::resolve($token, $userAgent);

            if ($context) {
                $user = $context->user;
                if ($user) {
                    Auth::guard('tab')->setUser($user);
                    Auth::shouldUse('tab');
                }
            }
        }

        $response = $next($request);

        if ($token && is_string($token) && $response->isRedirection()) {
            $location = $response->headers->get('Location');

            $locationPath = $location ? parse_url($location, PHP_URL_PATH) : null;
            $locationHost = $location ? parse_url($location, PHP_URL_HOST) : null;
            $isSameOrigin = str_starts_with($location, '/') || $locationHost === $request->getHost();

            if (
                $location
                && $isSameOrigin
                && ! in_array($locationPath, ['/login', '/auth/logout'], true)
                && ! str_contains($location, 'ctx=')
            ) {
                $separator = str_contains($location, '?') ? '&' : '?';
                $response->headers->set('Location', $location . $separator . 'ctx=' . rawurlencode($token));
            }
        }

        return $response;
    }
}
