<?php

namespace Database\Seeders\SushiAkila;

/** Only the logical photos 1–10 transcribed in the Batch01 specification. */
class Batch01Data
{
    public const PROTEINS = ['Pollo', 'Kanikama', 'Salmón', 'Atún', 'Camarón'];

    public const VEGETABLES = ['Choclo', 'Palmito', 'Palta', 'Champiñón', 'Morrón'];

    public const BASE = ['Queso crema', 'Cebollín'];

    public const WRAPPERS = ['Queso crema', 'Palta', 'Sésamo', 'Ciboulette', 'Frito'];

    public const CHOICE_FILLINGS = ['Pollo', 'Camarón', 'Kanikama', 'Salmón', 'Palmito', 'Champiñón', 'Choclo', 'Palta'];

    public static function ingredients(): array
    {
        return array_merge(
            array_fill_keys(self::PROTEINS, 'proteina'),
            array_fill_keys(self::VEGETABLES, 'vegetal'),
            array_fill_keys(self::BASE, 'base'),
            ['Sésamo' => 'topping', 'Ciboulette' => 'topping'],
        );
    }

    public static function products(): array
    {
        // Block order is a stable import identity, not an inferred preparation order.
        return [
            'goloso' => [
                'nombre' => 'Goloso', 'precio' => '10000.00', 'piezas' => 20, 'foto' => 1,
                'descripcion' => '20 piezas de pollo y palta fritas. Base de queso crema y cebollín.',
                'bloques' => array_fill(0, 2, ['relleno' => ['Pollo', 'Palta'], 'envoltura' => 'Frito']),
                'preparacion' => ['frito' => 20],
            ],
            'proteinas' => [
                'nombre' => 'Proteínas', 'precio' => '14990.00', 'piezas' => 30, 'foto' => 4,
                'descripcion' => '30 piezas de pollo fritas. Base de queso crema y cebollín.',
                'bloques' => array_fill(0, 3, ['relleno' => ['Pollo'], 'envoltura' => 'Frito']),
                'preparacion' => ['frito' => 30],
            ],
            'noche-de-sushi' => [
                'nombre' => 'Noche de Sushi', 'precio' => '17990.00', 'piezas' => 40, 'foto' => 1,
                'descripcion' => '20 piezas de pollo, 10 de camarón y 10 de palta. 20 envueltas y 20 fritas, sin distribución individual confirmada. Base de queso crema y cebollín.',
                'bloques' => self::fillings(['Pollo', 'Pollo', 'Camarón', 'Palta']),
                'preparacion' => ['envuelto' => 20, 'frito' => 20],
            ],
            'mi-favorita' => [
                'nombre' => 'Mi Favorita', 'precio' => '18990.00', 'piezas' => 50, 'foto' => 1,
                'descripcion' => '20 piezas de pollo, 10 de camarón, 10 de kanikama y 10 de choclo. 30 envueltas y 20 fritas, sin distribución individual confirmada. Base de queso crema y cebollín.',
                'bloques' => self::fillings(['Pollo', 'Pollo', 'Camarón', 'Kanikama', 'Choclo']),
                'preparacion' => ['envuelto' => 30, 'frito' => 20],
            ],
            'promo-hot' => [
                'nombre' => 'Promo Hot', 'precio' => '20990.00', 'piezas' => 60, 'foto' => 1,
                'descripcion' => '20 piezas de pollo, 10 de camarón, 10 de kanikama, 10 de choclo y 10 de champiñón. 60 piezas fritas. Base de queso crema y cebollín.',
                'bloques' => array_map(fn ($block) => $block + ['envoltura' => 'Frito'], self::fillings(['Pollo', 'Pollo', 'Camarón', 'Kanikama', 'Choclo', 'Champiñón'])),
                'preparacion' => ['frito' => 60],
            ],
            'lo-nuevo' => [
                'nombre' => 'Lo Nuevo', 'precio' => '25990.00', 'piezas' => 70, 'foto' => 10,
                'descripcion' => '20 piezas de pollo, 10 de camarón, 20 de kanikama, 10 de choclo y 10 de champiñón. 30 frías y 40 fritas, sin distribución individual confirmada. Base de queso crema y cebollín.',
                'bloques' => self::fillings(['Pollo', 'Pollo', 'Camarón', 'Kanikama', 'Kanikama', 'Choclo', 'Champiñón']),
                'preparacion' => ['frio' => 30, 'frito' => 40],
            ],
            '30-piezas-a-eleccion' => [
                'nombre' => '30 Piezas a Elección', 'precio' => '14900.00', 'piezas' => 30, 'foto' => 3,
                'descripcion' => '3 rolls de 10 piezas. Un ingrediente por roll: pollo, camarón, kanikama, salmón, palmito, champiñón, choclo o palta. Base de queso crema y cebollín, removible y no reemplazable.',
                'personalizable' => true,
                'bloques' => array_fill(0, 3, ['a_eleccion' => true]),
                'preparacion' => [],
            ],
            'costanera' => self::fixed('Costanera', 5, [
                ['relleno' => ['Pollo'], 'envoltura' => 'Frito'],
                ['relleno' => ['Camarón'], 'envoltura' => 'Palta'],
                ['relleno' => ['Palta'], 'envoltura' => 'Queso crema'],
            ]),
            'monumento' => self::fixed('Monumento', 6, [
                ['relleno' => ['Pollo'], 'envoltura' => 'Frito'],
                ['relleno' => ['Champiñón'], 'envoltura' => 'Palta'],
                ['relleno' => ['Palta'], 'envoltura' => 'Queso crema'],
            ]),
            'las-ruinas' => self::fixed('Las Ruinas', 7, [
                ['relleno' => ['Pollo'], 'envoltura' => 'Frito'],
                ['relleno' => ['Kanikama'], 'envoltura' => 'Palta'],
                ['relleno' => ['Palta'], 'envoltura' => 'Queso crema'],
            ]),
            'la-playa' => self::fixed('La Playa', 8, [
                ['relleno' => ['Camarón'], 'envoltura' => 'Frito'],
                ['relleno' => ['Kanikama'], 'envoltura' => 'Queso crema'],
                ['relleno' => ['Salmón'], 'envoltura' => 'Palta'],
            ]),
            'codigo-verde' => self::fixed('Código Verde', 9, [
                ['relleno' => ['Champiñón'], 'envoltura' => 'Frito'],
                ['relleno' => ['Choclo'], 'envoltura' => 'Queso crema'],
                ['relleno' => ['Palmito'], 'envoltura' => 'Palta'],
            ]),
        ];
    }

    private static function fillings(array $names): array
    {
        return array_map(fn ($name) => ['relleno' => [$name]], $names);
    }

    private static function fixed(string $name, int $photo, array $blocks): array
    {
        return [
            'nombre' => $name, 'precio' => '14900.00', 'piezas' => 30, 'foto' => $photo,
            'descripcion' => implode('; ', array_map(fn ($b) => '10 piezas de '.implode(' y ', $b['relleno']).($b['envoltura'] === 'Frito' ? ' fritas' : ' envueltas en '.$b['envoltura']), $blocks)).'. Base de queso crema y cebollín.',
            'bloques' => $blocks, 'preparacion' => ['frito' => 10, 'envuelto' => 20],
        ];
    }
}
