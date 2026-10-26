<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class OperationSettings
{
    public const DEFAULTS = [
        'is_open' => true, 'online_orders' => true, 'delivery_enabled' => true,
        'estimate_min' => 30, 'estimate_max' => 45, 'overdue_minutes' => 45,
        'load_medium' => 150, 'load_high' => 350, 'message' => '', 'hours' => [],
    ];

    public function get(): array
    {
        $row = DB::table('operation_settings')->where('key', 'operation')->first();

        return array_replace(self::DEFAULTS, $row ? json_decode($row->value, true, flags: JSON_THROW_ON_ERROR) : []);
    }

    public function save(array $values): void
    {
        DB::table('operation_settings')->updateOrInsert(['key' => 'operation'],
            ['value' => json_encode($values, JSON_THROW_ON_ERROR), 'updated_at' => now(), 'created_at' => now()]);
    }
}
