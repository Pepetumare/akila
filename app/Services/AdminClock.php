<?php

namespace App\Services;

use Carbon\Carbon;

class AdminClock
{
    public static function now(): Carbon
    {
        return now(config('admin.timezone', 'America/Santiago'));
    }

    public static function dayBounds(): array
    {
        return [self::now()->startOfDay()->utc(), self::now()->endOfDay()->utc()];
    }
}
