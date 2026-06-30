<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceProfileCompletion
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && !$user->is_profile_completed) {
            if (!$request->routeIs('filament.admin.pages.lengkapi-profil') && !$request->routeIs('filament.admin.auth.logout')) {
                return redirect()->route('filament.admin.pages.lengkapi-profil');
            }
        }

        return $next($request);
    }
}
