<?php

namespace App\Observers;

use App\Models\User;
use App\Models\UnitKerja;
use App\Models\Seksi;
use Illuminate\Support\Facades\DB;

class UserObserver
{
    public function deleting(User $user): void
    {
        $user->roles()->detach();
    }
}
