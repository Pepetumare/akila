<?php

namespace App\Enums;

enum UnidadComercial: string
{
    case Pieza = 'pieza';
    case HandRoll = 'hand_roll';
    case Unidad = 'unidad';

    public function label(int $quantity): string
    {
        return match ($this) {
            self::Pieza => $quantity === 1 ? 'pieza' : 'piezas',
            self::HandRoll => $quantity === 1 ? 'Hand Roll' : 'Hand Rolls',
            self::Unidad => $quantity === 1 ? 'unidad' : 'unidades',
        };
    }

    public function blockLabel(): string
    {
        return match ($this) {
            self::Pieza => 'Roll', self::HandRoll => 'Hand Roll', self::Unidad => 'Unidad',
        };
    }
}
