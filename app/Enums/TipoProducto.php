<?php

namespace App\Enums;

enum TipoProducto: string
{
    case Simple = 'simple';
    case Promocion = 'promocion';
    case Combo = 'combo';
}
