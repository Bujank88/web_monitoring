<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CalendarParticipant extends Model
{
    protected $fillable = ['name', 'color'];

    public static function options(): Collection
    {
        return static::groupedOptions()->collapse()->sortKeys(SORT_NATURAL | SORT_FLAG_CASE);
    }

    public static function groupedOptions(): Collection
    {
        $savedColors = static::query()->pluck('color', 'name');
        $userRoles = User::query()
            ->whereRaw('UPPER(role) IN (?, ?)', ['PH', 'MPCC'])
            ->orderBy('role')
            ->pluck('role', 'name')
            ->map(fn ($role) => strtoupper($role));

        $groups = collect([
            'PH' => collect(),
            'MPCC' => collect(),
            'Nama tambahan' => collect(),
        ]);

        $names = $savedColors->keys()
            ->merge($userRoles->keys())
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE);

        foreach ($names as $name) {
            $role = $userRoles->get($name, 'Nama tambahan');
            $groups[$role]->put($name, $savedColors->get($name) ?: static::defaultColor($name));
        }

        return $groups->filter(fn ($members) => $members->isNotEmpty());
    }

    public static function defaultColor(string $name): string
    {
        // Keep generated colors dark enough for white calendar labels.
        $hash = md5($name);
        $channels = array_map(
            fn ($offset) => 48 + (hexdec(substr($hash, $offset, 2)) % 112),
            [0, 2, 4]
        );

        return sprintf('#%02x%02x%02x', ...$channels);
    }
}
