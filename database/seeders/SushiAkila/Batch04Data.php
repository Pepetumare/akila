<?php

namespace Database\Seeders\SushiAkila;

use App\Enums\TipoProducto;

/** Source: supplied transcription of photos 31–40, not an operational recipe. */
class Batch04Data
{
    public static function ingredients(): array
    {
        return ['Pollo apanado' => 'proteina', 'Camarón apanado' => 'proteina',
            'Salsa de soya' => 'salsa', 'Salsa agridulce' => 'salsa',
            'Jamón serrano' => 'envoltura', 'Almendras' => 'topping', 'Salmón ahumado' => 'proteina'];
    }

    public static function extras(): array
    {
        return array_fill_keys(['Camarón', 'Pollo', 'Kanikama', 'Choclo', 'Palmito', 'Palta', 'Champiñón'], '1000.00')
            + array_fill_keys(['Salmón', 'Pollo apanado', 'Camarón apanado', 'Atún'], '2000.00');
    }

    public static function sauces(int $pieces): ?array
    {
        return match (true) {
            $pieces === 20 => [1, 1],
            $pieces >= 30 && $pieces <= 50 => [2, 1],
            $pieces >= 60 && $pieces <= 70 => [2, 2],
            $pieces >= 80 && $pieces <= 90 => [4, 3],
            $pieces === 100 => [5, 3],
            default => null,
        };
    }

    public static function groups(): array
    {
        return [
            // An empty structural group is deliberately not assigned until options are confirmed.
            'envoltura-nikkei-pendiente' => ['nombre' => 'Envoltura pendiente de confirmación', 'opciones' => []],
            'envoltura-nikkei-almendras' => ['nombre' => 'Envoltura', 'opciones' => ['Almendras']],
            'relleno-akilaburger' => ['nombre' => 'Elección adicional', 'opciones' => ['Pollo', 'Kanikama', 'Salmón', 'Atún', 'Camarón', 'Choclo', 'Palmito', 'Palta', 'Champiñón']],
        ];
    }

    public static function products(): array
    {
        $prior = Batch01Data::products();
        $favorite = array_replace($prior['mi-favorita'], ['foto' => 32, 'orden' => 4,
            'envolturas_declaradas' => ['Queso crema', 'Palta', 'Sésamo'],
            'descripcion' => $prior['mi-favorita']['descripcion'].' Envolturas declaradas: queso crema, palta y sésamo.']);
        $goloso = array_replace($prior['goloso'], ['foto' => 38, 'orden' => 1]);

        return [
            'mi-favorita' => $favorite,
            'nikkei-jamon-serrano' => self::nikkei('Nikkei Jamón Serrano', 'Pollo apanado', 33, ['Jamón serrano']),
            'nikkei-pollo-crispy' => self::nikkei('Nikkei Pollo Crispy', 'Pollo apanado', 34),
            'nikkei-camaron-apanado' => self::nikkei('Nikkei Camarón Apanado', 'Camarón apanado', 35, ['Almendras']) + ['grupos_producto' => ['envoltura-nikkei-almendras']],
            'nikkei-salmon-ahumado' => self::nikkei('Nikkei Salmón Ahumado', 'Salmón ahumado', 36),
            'goloso' => $goloso,
            '20-piezas-a-eleccion' => [
                'nombre' => '20 Piezas a Elección', 'precio' => '10000.00', 'foto' => 39, 'foto_reglas' => 39, 'orden' => 39,
                'piezas' => 20, 'personalizable' => true, 'repeticiones' => true,
                'bloques' => array_fill(0, 2, ['a_eleccion' => true]), 'preparacion' => [],
                'grupo_relleno' => 'relleno-20-piezas-a-eleccion', 'rellenos_disponibles' => Batch01Data::CHOICE_FILLINGS,
                'grupo_envoltura' => 'envoltura-20-piezas-a-eleccion', 'envolturas_disponibles' => ['Frito', 'Queso crema', 'Palta'],
                'descripcion' => '2 rolls de 10 piezas. Una proteína o un vegetal por roll. Base de queso crema y cebollín. Envolturas disponibles: frito, queso crema y palta. Máximo dos repeticiones; salmón máximo una.',
            ],
            'akilaburger' => [
                'nombre' => 'AkilaBurger', 'precio' => '7500.00', 'foto' => 40, 'orden' => 40,
                'categoria' => 'Especialidades', 'categoria_orden' => 4, 'tipo' => TipoProducto::Simple,
                'unidad_tipo' => 'unidad', 'unidades' => 1, 'bloques' => [], 'preparacion' => [],
                'personalizable' => true, 'promocion' => false, 'cambios' => false, 'base_removible' => false,
                'composicion_fija' => ['Queso crema', 'Cebollín', 'Palta'], 'grupos_producto' => ['relleno-akilaburger'],
                'descripcion' => 'Base fija de queso crema, cebollín y palta, más una proteína o un vegetal a elección.',
            ],
        ];
    }

    private static function nikkei(string $name, string $protein, int $photo, array $wrappers = []): array
    {
        $pending = $wrappers === [];

        return ['nombre' => $name, 'precio' => '6990.00', 'foto' => $photo, 'orden' => $photo,
            'categoria' => 'Nikkei', 'categoria_orden' => 5, 'tipo' => TipoProducto::Simple,
            'piezas' => 10, 'bloques' => [], 'preparacion' => [], 'base' => ['Queso crema'],
            'composicion_fija' => array_merge([$protein, 'Palta', 'Queso crema'], $wrappers),
            'personalizable' => false, 'promocion' => false, 'cambios' => false, 'base_removible' => false,
            'descripcion' => '10 piezas de '.mb_strtolower($protein).', palta y queso crema. '.($pending
                ? 'Envoltura a elección: opciones pendientes de confirmación.' : 'Envoltura: '.implode(', ', $wrappers).'.')];
    }
}
