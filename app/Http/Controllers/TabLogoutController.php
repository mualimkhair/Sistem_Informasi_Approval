<?php

namespace App\Http\Controllers;

use App\Models\TabContext;
use Illuminate\Http\Request;

class TabLogoutController
{
    public function logout(Request $request)
    {
        $token = $request->header('X-Tab-Token') ?? $request->query('ctx');

        if ($token && is_string($token)) {
            $hash = TabContext::hashToken($token);
            TabContext::where('token_hash', $hash)->delete();
        }

        return redirect()->route('filament.admin.auth.login');
    }
}
