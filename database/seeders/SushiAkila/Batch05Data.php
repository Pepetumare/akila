<?php

namespace Database\Seeders\SushiAkila;

use App\Enums\TipoProducto;

/** Supplied transcription of photos 41–42. No inferred operational recipes. */
class Batch05Data
{
    public const GOHAN_BASE = ['Arroz', 'Queso crema', 'Ciboulette', 'Palta', 'Sésamo'];

    public const GOHAN_PENDING = 'Pendiente de confirmar ingrediente/proteína diferencial';

    public static function groups(): array
    {
        return ['sabor-arrollados' => ['nombre' => 'Sabores', 'tipo_seleccion' => 'multiple',
            'min_selections' => 1, 'max_selections' => 2, 'opciones' => ['Primavera', 'Jamón Queso']]];
    }

    public static function products(): array
    {
        $common = ['tipo' => TipoProducto::Simple, 'unidad_tipo' => 'unidad', 'bloques' => [],
            'preparacion' => [], 'promocion' => false, 'cambios' => false, 'base_removible' => false];

        return [
            'arrollados' => $common + [
                'nombre' => 'Arrollados', 'precio' => '5000.00', 'foto' => 41, 'orden' => 41,
                'categoria' => 'Acompañamientos', 'categoria_orden' => 6, 'unidades' => 8,
                'personalizable' => true, 'base' => [], 'grupos_producto' => ['sabor-arrollados'],
                'descripcion' => '8 unidades. Sabores Primavera y Jamón Queso. Se pueden pedir mixtos, sin recargo. La proporción por sabor se coordina al preparar; no se establece una distribución fija.',
            ],
            'gohan-flecha' => $common + self::gohan('Gohan Flecha'),
            'gohan-orion' => $common + self::gohan('Gohan Orion'),
        ];
    }

    private static function gohan(string $name): array
    {
        return ['nombre' => $name, 'precio' => '7500.00', 'foto' => 42, 'orden' => 42,
            'categoria' => 'Gohan', 'categoria_orden' => 7, 'unidades' => 1, 'personalizable' => false,
            'activo' => false, 'base' => self::GOHAN_BASE, 'composicion_fija' => self::GOHAN_BASE,
            'descripcion' => 'Base confirmada: arroz, queso crema, ciboulette, palta y sésamo. '.self::GOHAN_PENDING.'. Disponible opción vegetariana; pendiente de definición operativa, sin sustitución ni recargo configurados.'];
    }
}
