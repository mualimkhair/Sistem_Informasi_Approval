<?php

namespace App\Auth;

use Filament\Facades\Filament;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;

class TabLoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $token = session('tab_login_token');

        if ($token) {
            session()->forget('tab_login_token');
            return redirect()->to(Filament::getUrl() . '?ctx=' . $token);
        }

        return redirect()->to(Filament::getUrl());
    }
}
