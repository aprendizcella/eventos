<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PromoCodeType;
use App\Models\PromoCode;

final class PriceCalculator
{
    /**
     * Calcula el subtotal, el descuento aplicado y el total final de una compra.
     *
     * @param  float  $price  Precio unitario del producto.
     * @param  int  $quantity  Cantidad de unidades.
     * @param  PromoCode|null  $promoCode  Código promocional a aplicar.
     * @return array{
     *     subtotal: float,
     *     discount: float,
     *     total: float
     * }
     */
    public function calculate(float $price, int $quantity, ?PromoCode $promoCode = null): array
    {
        $subtotal = $price * $quantity;
        $discount = 0.0;

        if ($promoCode instanceof PromoCode && $promoCode->status === 'active') {
            if ($promoCode->type === PromoCodeType::Percentage) {
                $discount = ($subtotal * $promoCode->value) / 100.0;
            } elseif ($promoCode->type === PromoCodeType::Fixed) {
                $discount = $promoCode->value;
            }
        }

        // El descuento no puede exceder el subtotal
        if ($discount > $subtotal) {
            $discount = $subtotal;
        }

        $total = $subtotal - $discount;

        return [
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'total' => round(max(0.0, $total), 2),
        ];
    }
}
