<?php

namespace Database\Seeders\SushiAkila;

/** Only the supplied transcription of logical photos 11–20. */
class Batch02Data
{
    public static function products(): array
    {
        $hot = Batch01Data::products()['promo-hot'];
        $hot = array_replace($hot, ['foto' => 11, 'foto_reglas' => 11, 'precio' => '19990.00', 'orden' => 5, 'repeticiones' => true]);

        return [
            'promo-hot' => $hot,
            'el-ciclon' => self::fixed('El Ciclón', 12, 13, '24990.00', [
                'Pollo' => 10, 'Camarón' => 10, 'Kanikama' => 10, 'Choclo' => 10, 'Champiñón' => 10, 'Palmito' => 10,
            ], ['frito' => 30], ['Queso crema', 'Palta', 'Sésamo']),
            '60-piezas-a-eleccion' => self::choice(60, '25990.00', 13, 14, 30, ['Queso crema', 'Palta', 'Sésamo']),
            '50-piezas-a-eleccion' => self::choice(50, '20990.00', 14, 15, 20, ['Queso crema', 'Palta', 'Sésamo']),
            'noche-de-sushi' => self::fixed('Noche de Sushi', 15, 3, '17990.00', [
                'Pollo' => 20, 'Camarón' => 10, 'Palta' => 10,
            ], ['frito' => 20], ['Queso crema', 'Palta']) + ['reglas_superadas' => ['piezas-envuelto']],
            'tu-media-naranja' => [
                'nombre' => 'Tu Media Naranja', 'foto' => 16, 'orden' => 16, 'precio' => '17990.00', 'piezas' => 40,
                'descripcion' => '10 piezas de pollo fritas; 10 de camarón envueltas en palta; 10 de palta envueltas en queso crema; 10 de kanikama fritas. Base de queso crema y cebollín.',
                'bloques' => [
                    ['relleno' => ['Pollo'], 'envoltura' => 'Frito'],
                    ['relleno' => ['Camarón'], 'envoltura' => 'Palta'],
                    ['relleno' => ['Palta'], 'envoltura' => 'Queso crema'],
                    ['relleno' => ['Kanikama'], 'envoltura' => 'Frito'],
                ], 'preparacion' => ['frito' => 20, 'envuelto' => 20],
            ],
            '40-piezas-a-eleccion' => self::choice(40, '17900.00', 17, 17, 20, []),
            'el-emperador' => self::fixed('El Emperador', 18, 18, '49990.00', [
                'Pollo' => 30, 'Camarón' => 20, 'Kanikama' => 20, 'Choclo' => 20, 'Champiñón' => 20,
                'Salmón' => 10, 'Palmito' => 10, 'Palta' => 20,
            ], ['frio' => 60, 'frito' => 90], ['Queso crema', 'Palta', 'Sésamo', 'Ciboulette']),
            'para-la-familia' => self::fixed('Para la Familia', 19, 19, '36990.00', [
                'Pollo' => 20, 'Camarón' => 10, 'Kanikama' => 20, 'Choclo' => 10, 'Champiñón' => 10,
                'Salmón' => 10, 'Palmito' => 10, 'Palta' => 10,
            ], ['frio' => 40, 'frito' => 60], ['Queso crema', 'Palta', 'Sésamo', 'Ciboulette']),
            'de-las-que-no-sobran' => self::fixed('De las que no Sobran', 20, 20, '31990.00', [
                'Pollo' => 20, 'Camarón' => 10, 'Kanikama' => 20, 'Choclo' => 10, 'Champiñón' => 10,
                'Salmón' => 10, 'Palmito' => 10,
            ], ['frio' => 40, 'frito' => 50], ['Queso crema', 'Palta', 'Sésamo', 'Ciboulette']),
        ];
    }

    private static function fixed(string $name, int $photo, int $order, string $price, array $fillings, array $preparation, array $wrappers): array
    {
        $blocks = [];
        foreach ($fillings as $ingredient => $pieces) {
            if ($pieces <= 0 || $pieces % 10 !== 0) {
                throw new \RuntimeException('Composición comercial fuera de bloques de diez.');
            }
            array_push($blocks, ...array_fill(0, intdiv($pieces, 10), ['relleno' => [$ingredient]]));
        }
        $text = implode(', ', array_map(fn ($ingredient, $pieces) => "{$pieces} piezas de {$ingredient}", array_keys($fillings), $fillings));
        $counts = implode(', ', array_map(fn ($kind, $pieces) => $pieces.' '.($kind === 'frito' ? 'fritas' : 'frías'), array_keys($preparation), $preparation));

        return ['nombre' => $name, 'foto' => $photo, 'orden' => $order, 'precio' => $price,
            'piezas' => array_sum($fillings), 'bloques' => $blocks, 'preparacion' => $preparation,
            'envolturas_declaradas' => $wrappers,
            'descripcion' => $text.'. '.$counts.', sin distribución individual confirmada. Envolturas declaradas: '.implode(', ', $wrappers).'. Base de queso crema y cebollín.'];
    }

    private static function choice(int $pieces, string $price, int $photo, int $order, int $fried, array $wrappers): array
    {
        $data = [
            'nombre' => "{$pieces} Piezas a Elección", 'precio' => $price, 'foto' => $photo, 'foto_reglas' => $photo, 'orden' => $order,
            'piezas' => $pieces, 'personalizable' => true, 'repeticiones' => true,
            'bloques' => array_fill(0, intdiv($pieces, 10), ['a_eleccion' => true]),
            'preparacion' => ['frito' => $fried], 'grupo_relleno' => 'relleno-promociones-a-eleccion',
            'rellenos_disponibles' => ['Pollo', 'Kanikama', 'Camarón', 'Salmón', 'Choclo', 'Palmito', 'Champiñón', 'Palta'],
            'envolturas_disponibles' => $wrappers, 'envolturas_declaradas' => $wrappers,
            'descripcion' => intdiv($pieces, 10).' rolls de 10 piezas. Un ingrediente por roll: proteína o vegetal. Base de queso crema y cebollín, removible y no reemplazable. '.$fried.' piezas fritas en total, sin distribución individual confirmada.',
        ];
        if ($wrappers !== []) {
            $data['grupo_envoltura'] = 'envoltura-promociones-mixtas';
        }

        return $data;
    }
}
