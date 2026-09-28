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

        $isExempt = $user->hasRole(['super_admin', 'admin']);
        if ($user && !$isExempt && (!$user->is_profile_completed || is_null($user->jenis_kelamin))) {
            if (!$request->routeIs('filament.admin.pages.lengkapi-profil') && !$request->routeIs('filament.admin.auth.logout') && !$request->routeIs('tab.auth.logout')) {
                return redirect()->route('filament.admin.pages.lengkapi-profil');
            }
        }

        return $next($request);
    }
}
