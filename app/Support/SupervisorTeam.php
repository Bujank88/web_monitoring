<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class SupervisorTeam
{
    public static function members(): Builder
    {
        abort_unless(auth()->user()?->role === 'Supervisor', 403);

        return User::where('role', 'cvsr')->where('supervisor_id', auth()->id());
    }

    public static function ids(): Builder
    {
        return self::members()->select('id');
    }

    // SOF lama hanya menyimpan nama PIC. Nama ambigu tidak boleh membuka data tim lain.
    public static function uniqueNames(): Builder
    {
        return self::members()->whereIn('name', User::select('name')->groupBy('name')->havingRaw('COUNT(*) = 1'))->select('name');
    }
}
