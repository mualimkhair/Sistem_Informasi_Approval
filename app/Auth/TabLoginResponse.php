<?php

namespace App\Auth;

use Filament\Facades\Filament;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;

class TabLoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $token = app()->has('tab_login_token') ? app('tab_login_token') : null;

        if ($token) {
            return redirect()->to(Filament::getUrl() . '?ctx=' . $token);
        }

        return redirect()->to(Filament::getUrl());
    }
}
