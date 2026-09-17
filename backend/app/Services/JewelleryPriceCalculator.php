<?php

namespace App\Services;

use App\Models\JewelleryItem;
use App\Support\Decimal;

/**
 * Works out what a jewellery item actually costs today.
 *
 * The price is never stored on the item itself — it is recalculated on every
 * read from the item's weight and making/shipping charges plus whatever the
 * metal rate and tax rates currently are, so a change to today's gold rate or
 * a tax percentage is reflected immediately across the whole catalogue.
 *
 * Every step is done as decimal-string arithmetic via App\Support\Decimal
 * (backed by bcmath) — this never touches a PHP float, so there's no binary
 * rounding drift across the multiply/add/percentage chain. Values are only
 * rounded once, at the point each line item is finalised.
 *
 * Tax is charged on (metal cost + making charges + shipping charges) — the
 * full amount the customer pays before tax, matching how jewellers commonly
 * invoice.
 */
class JewelleryPriceCalculator
{
    public function calculate(JewelleryItem $item): array
    {
        $metalRatePerGram = (string) $item->metalPrice->price_per_gram;
        $weightGrams = (string) $item->weight_grams;
        $makingCharges = (string) $item->making_charges;
        $shippingCharges = (string) $item->shipping_charges;

        $metalCost = Decimal::round(Decimal::mul($weightGrams, $metalRatePerGram));
        $taxableAmount = Decimal::round(
            Decimal::add(Decimal::add($metalCost, $makingCharges), $shippingCharges)
        );

        $taxLines = $item->taxes->map(function ($tax) use ($taxableAmount) {
            $percentage = (string) $tax->percentage;
            $amount = Decimal::round(Decimal::div(Decimal::mul($taxableAmount, $percentage), '100'));

            return [
                'tax_id' => $tax->id,
                'name' => $tax->name,
                'percentage' => $percentage,
                'amount' => $amount,
            ];
        })->values();

        $taxTotal = Decimal::sum($taxLines->pluck('amount')->all());
        $finalPrice = Decimal::round(Decimal::add($taxableAmount, $taxTotal));

        return [
            'metal_rate_per_gram' => $metalRatePerGram,
            'metal_cost' => $metalCost,
            'making_charges' => Decimal::round($makingCharges),
            'shipping_charges' => Decimal::round($shippingCharges),
            'taxable_amount' => $taxableAmount,
            'tax_lines' => $taxLines,
            'tax_total' => $taxTotal,
            'final_price' => $finalPrice,
        ];
    }
}
