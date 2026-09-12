<?php

namespace Tests;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

trait AuthenticatesAsTab
{
    protected function actingAsTab(User $user, ?string $role = null): static
    {
        $this->actingAs($user, 'web');

        Auth::guard('tab')->setUser($user);

        return $this;
    }
}
