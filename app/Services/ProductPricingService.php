<?php

namespace App\Services;

use App\Commerce\Compatibility\LegacyQuotePresenter;
use App\Commerce\Compatibility\LegacySushiConfigurationAdapter;
use App\Commerce\Compatibility\SushiConfigurationAdapter;
use App\Commerce\Configuration\ProductConfigurationInput;
use App\Commerce\Configuration\ProductConfigurationService;
use App\Commerce\Contracts\ProductCatalog;
use App\Commerce\Exceptions\ConfigurationException;
use App\Commerce\Infrastructure\EloquentProductCatalog;
use App\Commerce\Pricing\PricingService;
use App\Commerce\Pricing\ProductQuote;
use App\Models\Producto;
use Illuminate\Validation\ValidationException;

/** Application entry point. Existing array API remains a compatibility boundary. */
class ProductPricingService
{
    public const MAX_QUANTITY = PricingService::MAX_QUANTITY;

    public function __construct(
        private ProductCatalog $catalog,
        private ProductConfigurationService $configuration,
        private SushiConfigurationAdapter $presentation,
        private LegacySushiConfigurationAdapter $legacy,
        private LegacyQuotePresenter $presenter,
    ) {}

    public function defaults(Producto $product): array
    {
        return $this->legacy->defaults($product);
    }

    public function usesLegacyConfiguration(Producto $product): bool
    {
        $product->loadMissing('bloques', 'gruposOpciones', 'ingredientes');

        return $product->bloques->isEmpty() && $product->gruposOpciones->isEmpty()
            && $product->ingredientes->isNotEmpty()
            && ! $product->ingredientes->contains(fn ($i) => $i->pivot->metadata['composicion_fija'] ?? false);
    }

    public function quoteResult(Producto $product, array|ProductConfigurationInput $input, int $quantity = 1): ProductQuote
    {
        if ($this->usesLegacyConfiguration($product)) {
            if (! is_array($input)) {
                throw new ConfigurationException('invalid_option');
            }

            return $this->legacy->quote($product, $input, $quantity);
        }
        try {
            $definition = $this->catalog->definition($product);
            $choices = is_array($input) ? $this->presentation->input($input) : $input;
            $quote = $this->configuration->quote($definition, $choices, $quantity);

            return $this->presentation->present($product, $quote);
        } catch (ConfigurationException $e) {
            $this->presentation->translate($e);
        }
    }

    public function quote(Producto $product, array $input, int $quantity = 1): array
    {
        return $this->toArray($this->quoteResult($product, $input, $quantity));
    }

    public function toArray(ProductQuote $quote): array
    {
        return $this->presenter->toArray($quote, ! isset($quote->configuration['version']));
    }

    public function refreshCart(array $cart): array
    {
        $this->ensure(count($cart) <= 100, 'El carrito contiene demasiados productos.');
        $products = Producto::with(array_merge(['swappables'], EloquentProductCatalog::RELATIONS))
            ->whereIn('id', array_column($cart, 'producto_id'))->get()->keyBy('id');
        $result = [];
        foreach ($cart as $line) {
            $this->ensure(isset($line['configuration']) && is_array($line['configuration']), 'Tu carrito anterior debe volver a agregarse para verificar sus opciones.');
            $product = $products->get($line['producto_id']);
            $this->ensure($product !== null, 'Un producto del carrito ya no está disponible.');
            $quote = $this->quoteResult($product, $line['configuration'], (int) $line['unidades']);
            $result[$quote->hash()] = $this->toArray($quote);
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
