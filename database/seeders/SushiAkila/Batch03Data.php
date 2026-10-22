<?php

namespace Database\Seeders\SushiAkila;

use App\Enums\TipoProducto;

/** Confirmed transcription of photos 21–30, including repeated identities. */
class Batch03Data
{
    public const PROTEINS = ['Pollo', 'Kanikama', 'Camarón', 'Salmón', 'Atún'];

    public const VEGETABLES = ['Palta', 'Champiñón', 'Palmito', 'Choclo', 'Pepino', 'Morrón'];

    public static function groups(): array
    {
        $fillings = array_merge(self::PROTEINS, self::VEGETABLES);

        return [
            'vegetal' => ['nombre' => 'Vegetal', 'opciones' => self::VEGETABLES],
            'relleno-hand-roll' => ['nombre' => 'Relleno', 'opciones' => $fillings],
            'relleno-muchupleto' => ['nombre' => 'Elección adicional', 'opciones' => array_merge(self::PROTEINS, ['Choclo', 'Palmito', 'Palta', 'Champiñón'])],
            'segundo-ingrediente' => ['nombre' => 'Segundo ingrediente', 'opciones' => $fillings, 'recargo' => '1000.00'],
        ];
    }

    public static function products(): array
    {
        $fifty = Batch02Data::products()['50-piezas-a-eleccion'];
        $fifty['foto'] = 30;
        $fifty['foto_reglas'] = 30;
        $fixedFillings = ['Pollo', 'Pollo', 'Camarón', 'Kanikama', 'Choclo', 'Champiñón', 'Salmón', 'Palmito'];

        return [
            'las-mismas-de-siempre' => [
                'nombre' => 'Las Mismas de Siempre', 'precio' => '27900.00', 'foto' => 21, 'orden' => 21,
                'piezas' => 80, 'bloques' => array_map(fn ($name) => ['relleno' => [$name]], $fixedFillings),
                'preparacion' => ['frio' => 40, 'frito' => 40],
                'envolturas_declaradas' => ['Queso crema', 'Palta', 'Sésamo', 'Ciboulette'],
                'descripcion' => '80 piezas: 20 pollo; 10 camarón, kanikama, choclo, champiñón, salmón y palmito. 40 frías y 40 fritas, sin distribución individual confirmada. Envolturas declaradas: queso crema, palta, sésamo y ciboulette. Base de queso crema y cebollín.',
            ],
            'muchupleto' => array_replace(self::individual('Muchupleto', '6500.00', 27, 22), [
                'categoria' => 'Especialidades', 'categoria_orden' => 4, 'unidad_tipo' => 'unidad',
                'composicion_fija' => ['Queso crema', 'Cebollín', 'Palta', 'Pollo'],
                'grupos_producto' => ['relleno-muchupleto'],
                'descripcion' => 'Base fija de queso crema, cebollín, palta y pollo, más una proteína o un vegetal a elección.',
            ]),
            'hand-roll-tradicional' => self::individual('Hand Roll Tradicional', '3990.00', 28, 23) + [
                'grupos_producto' => ['relleno-hand-roll'], 'foto_reglas' => 23,
                'recargos_ingredientes' => ['Salmón' => '1000.00', 'Atún' => '1000.00'],
            ],
            'hand-roll-relleno-doble' => self::individual('Hand Roll Relleno Doble', '4990.00', 28, 24) + [
                'grupos_producto' => ['proteina', 'vegetal'],
            ],
            '3-hand-rolls' => self::combo(3, '11000.00', 26, 25),
            '4-hand-rolls' => self::combo(4, '14990.00', 25, 26),
            '5-hand-rolls' => self::combo(5, '18000.00', 29, 27) + ['segundo_ingrediente' => 'segundo-ingrediente'],
            '4-hand-rolls-pollo-palta' => array_replace(self::combo(4, '18000.00', 24, 28), [
                'nombre' => '4 Hand Rolls Pollo Palta', 'personalizable' => false, 'repeticiones' => false,
                'composicion_fija' => ['Queso crema', 'Cebollín', 'Pollo', 'Palta'],
                'bloques' => array_fill(0, 4, ['relleno' => ['Pollo', 'Palta']]),
                'descripcion' => '4 Hand Rolls de composición fija: queso crema, cebollín, pollo y palta por unidad.',
            ]),
            '50-piezas-a-eleccion' => $fifty,
        ];
    }

    private static function individual(string $name, string $price, int $photo, int $order): array
    {
        return ['nombre' => $name, 'precio' => $price, 'foto' => $photo, 'orden' => $order,
            'categoria' => 'Hand Rolls', 'categoria_orden' => 3, 'unidad_tipo' => 'hand_roll',
            'unidades' => 1, 'bloques' => [], 'preparacion' => [], 'tipo' => TipoProducto::Simple,
            'promocion' => false, 'personalizable' => true, 'cambios' => false, 'base_removible' => false,
            'descripcion' => $name.'. Base de queso crema y cebollín. '.($name === 'Hand Roll Relleno Doble' ? 'Una proteína y un vegetal obligatorios.' : 'Una proteína o un vegetal a elección.')];
    }

    private static function combo(int $units, string $price, int $photo, int $order): array
    {
        return ['nombre' => $units.' Hand Rolls', 'precio' => $price, 'foto' => $photo, 'orden' => $order,
            'categoria' => 'Hand Rolls', 'categoria_orden' => 3, 'unidad_tipo' => 'hand_roll', 'unidades' => $units,
            'tipo' => TipoProducto::Combo, 'personalizable' => true, 'preparacion' => [],
            'bloques' => array_fill(0, $units, ['a_eleccion' => true]),
            'grupo_relleno' => 'relleno-hand-roll', 'rellenos_disponibles' => array_merge(self::PROTEINS, self::VEGETABLES),
            'envolturas_disponibles' => [], 'repeticiones' => true, 'limites_repeticion' => ['Salmón' => 1, 'Atún' => 1],
            'cambios' => false, 'base_removible' => false,
            'descripcion' => $units.' Hand Rolls. Base de queso crema y cebollín, más una proteína o un vegetal por unidad. Máximo dos repeticiones; salmón y atún máximo una vez.'];
    }
}
