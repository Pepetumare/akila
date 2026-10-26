<?php

namespace App\Enums;

enum TipoReglaPromocion: string
{
    case MaxRepeticiones = 'max_repeticiones';
    case Excluir = 'excluir';
    case Recargo = 'recargo';
    case MinPiezas = 'min_piezas';
    case CantidadIncluida = 'cantidad_incluida';
    case Incompatible = 'incompatible';
}
