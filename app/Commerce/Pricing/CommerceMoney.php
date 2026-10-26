<?php

namespace App\Commerce\Pricing;

use Brick\Math\BigDecimal;
use Illuminate\Validation\ValidationException;

class CommerceMoney
{
    public static function cents(int|float|string $value): int
    {
        try {
            $amount = BigDecimal::of((string) $value)->toScale(2)->getUnscaledValue()->toInt();
        } catch (\Throwable) {
            throw ValidationException::withMessages(['configuration' => 'El precio configurado necesita revisión.']);
        }
        if ($amount < 0) {
            throw ValidationException::withMessages(['configuration' => 'El precio configurado necesita revisión.']);
        }

        return $amount;
    }

    public static function decimal(int $cents): string
    {
        return (string) BigDecimal::ofUnscaledValue($cents, 2);
    }
}
