<?php

namespace App\Services;

use App\Models\Ingrediente;
use App\Models\Producto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProductPricingService
{
    public const MAX_QUANTITY = 50;

    private ?Collection $ingredients = null;

    public function defaults(Producto $product): array
    {
        $product->loadMissing('ingredientes', 'swappables');
        $result = ['envoltura_id' => $product->ingredientes->where('tipo', 'envoltura')->first()?->id,
            'Proteínas' => [], 'vegetales' => [], 'cream_cheese' => false, 'scallions' => false];
        foreach (['proteina' => 'Proteínas', 'vegetal' => 'vegetales'] as $type => $key) {
            foreach ($product->ingredientes->where('tipo', $type) as $ingredient) {
                $quantity = (int) ($ingredient->pivot->cantidad_permitida ?? 1);
                // Legacy records express complete rolls as multiples of ten cuts.
                if ($quantity >= 10) {
                    $this->ensure($quantity % 10 === 0, 'La composición del producto necesita revisión.');
                    $quantity = intdiv($quantity, 10);
                }
                $this->ensure($quantity >= 0 && $quantity <= 100, 'Composición fuera de límites.');
                array_push($result[$key], ...array_fill(0, $quantity, (int) $ingredient->id));
            }
            $this->ensure(count($result[$key]) <= 100, 'Composición fuera de límites.');
        }

        return $result;
    }

    public function quote(Producto $product, array $input, int $quantity = 1): array
    {
        $this->ensure($quantity >= 1 && $quantity <= self::MAX_QUANTITY, 'Cantidad permitida: 1 a 50.');
        $menu = app(MenuConfigurationService::class);
        if ($menu->supports($product)) {
            return $menu->quote($product, $input, $quantity);
        }
        $data = Validator::make($input, [
            'envoltura_id' => 'nullable|integer', 'base_id' => 'nullable|integer',
            'Proteínas' => 'sometimes|array|max:100', 'Proteínas.*' => 'required|integer',
            'vegetales' => 'sometimes|array|max:100', 'vegetales.*' => 'required|integer',
            'cream_cheese' => 'sometimes|boolean', 'scallions' => 'sometimes|boolean',
        ])->validate();
        $defaults = $this->defaults($product);
        if (isset($data['base_id'], $data['envoltura_id'])) {
            $this->ensure((int) $data['base_id'] === (int) $data['envoltura_id'], 'Envolturas contradictorias.');
        }
        $config = array_replace($defaults, array_intersect_key($data, $defaults));
        $config['envoltura_id'] = isset($data['base_id']) ? (int) $data['base_id'] : ($config['envoltura_id'] ? (int) $config['envoltura_id'] : null);
        foreach (['Proteínas', 'vegetales'] as $key) {
            $config[$key] = array_map('intval', $config[$key]);
            sort($config[$key]);
            sort($defaults[$key]);
        }
        foreach (['cream_cheese', 'scallions'] as $key) {
            $config[$key] = (bool) $config[$key];
        }
        if (! $product->personalizable) {
            $this->ensure($config === $defaults, 'Este producto no permite personalización.');
        }
        $wrappers = $product->ingredientes->where('tipo', 'envoltura')->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->ensure($wrappers ? in_array($config['envoltura_id'], $wrappers, true) : $config['envoltura_id'] === null, 'Envoltura no permitida.');
        $all = $this->ingredients ??= Ingrediente::all()->keyBy('id');
        $surcharge = 0;
        foreach (['proteina' => 'Proteínas', 'vegetal' => 'vegetales'] as $type => $key) {
            $this->ensure(count($config[$key]) === count($defaults[$key]), 'Debes conservar la cantidad de ingredientes del producto.');
            $baseCounts = array_count_values($defaults[$key]);
            $allowed = $product->swappables->isNotEmpty()
                ? $product->ingredientes->merge($product->swappables)->where('tipo', $type)->pluck('id')->all()
                : $all->where('tipo', $type)->pluck('id')->all();
            foreach (array_count_values($config[$key]) as $id => $count) {
                $ingredient = $all->get($id);
                $this->ensure($ingredient && $ingredient->tipo === $type && in_array($id, $allowed), 'Ingrediente no permitido.');
                $limit = str_contains(mb_strtolower($ingredient->nombre), 'salm') ? 1 : 2;
                $this->ensure($count <= max($limit, $baseCounts[$id] ?? 0), 'Se excedió el límite de un ingrediente.');
                $surcharge += max(0, $count - ($baseCounts[$id] ?? 0)) * 1000;
            }
        }
        $base = (int) round((float) $product->precio * 100);
        $this->ensure($base >= 0, 'Precio de producto inválido.');
        $unit = ($base + $surcharge * 100) / 100;
        $hash = hash('sha256', json_encode([$product->id, $config], JSON_UNESCAPED_UNICODE));

        return [
            'hash' => $hash, 'producto_id' => $product->id, 'nombre' => $product->nombre,
            'unidades' => $quantity, 'precio_base' => $base / 100, 'recargo' => $surcharge,
            'precio_unit' => $unit, 'total' => round($unit * $quantity, 2), 'configuration' => $config,
            'detalle' => [
                'Base' => $all->get($config['envoltura_id'])?->nombre ?? 'Sin base',
                'Proteínas' => array_map(fn ($id) => $all->get($id)->nombre, $config['Proteínas']),
                'Vegetales' => array_map(fn ($id) => $all->get($id)->nombre, $config['vegetales']),
                'Sin queso' => $config['cream_cheese'], 'Sin cebollín' => $config['scallions'],
            ],
        ];
    }

    public function refreshCart(array $cart): array
    {
        $this->ensure(count($cart) <= 100, 'El carrito contiene demasiados productos.');
        $products = Producto::with(array_merge(['ingredientes', 'swappables'], MenuConfigurationService::RELATIONS))
            ->whereIn('id', array_column($cart, 'producto_id'))->get()->keyBy('id');
        $result = [];
        foreach ($cart as $line) {
            $this->ensure(isset($line['configuration']) && is_array($line['configuration']), 'Tu carrito anterior debe volver a agregarse para verificar sus opciones.');
            $product = $products->get($line['producto_id']);
            $this->ensure($product !== null, 'Un producto del carrito ya no está disponible.');
            $quote = $this->quote($product, $line['configuration'], (int) $line['unidades']);
            $result[$quote['hash']] = $quote;
        }

        return $result;
    }

    private function ensure(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['configuration' => $message]);
        }
    }
}
