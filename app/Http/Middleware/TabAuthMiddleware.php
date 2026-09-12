<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Filament\Facades\Filament;
use Symfony\Component\HttpFoundation\Response;

class TabAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('tab');

        if (!$guard->check()) {
            $this->unauthenticated($request);
        }

        $user = $guard->user();

        $panel = Filament::getCurrentOrDefaultPanel();

        if (method_exists($user, 'canAccessPanel') && !$user->canAccessPanel($panel)) {
            abort(403);
        }

        return $next($request);
    }

    protected function unauthenticated(Request $request): void
    {
        $loginUrl = Filament::getLoginUrl();

        if ($request->expectsJson() || $request->is('livewire/*')) {
            abort(401);
        }

        redirect()->guest($loginUrl)->throwResponse();
    }
}
