<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminChange extends Model
{
    protected $guarded = [];

    protected $casts = ['changes' => 'array'];

    public static function record(Model $entity, string $action, array $changes): void
    {
        if ($changes !== []) {
            self::create(['user_id' => auth()->id(), 'entity_type' => $entity->getMorphClass(),
                'entity_id' => $entity->getKey(), 'action' => $action, 'changes' => $changes]);
        }
    }
}
