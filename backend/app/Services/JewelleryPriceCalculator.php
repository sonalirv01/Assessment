<?php

namespace App\Services;

use App\Models\JewelleryItem;

/**
 * Works out what a jewellery item actually costs today.
 *
 * The price is never stored on the item itself — it is recalculated on every
 * read from the item's weight and making/shipping charges plus whatever the
 * metal rate and tax rates currently are, so a change to today's gold rate or
 * a tax percentage is reflected immediately across the whole catalogue.
 *
 * Tax is charged on (metal cost + making charges), matching how jewellers
 * commonly invoice — shipping is added afterwards, untaxed.
 */
class JewelleryPriceCalculator
{
    public function calculate(JewelleryItem $item): array
    {
        $metalRatePerGram = (float) $item->metalPrice->price_per_gram;
        $weightGrams = (float) $item->weight_grams;
        $makingCharges = (float) $item->making_charges;
        $shippingCharges = (float) $item->shipping_charges;

        $metalCost = round($weightGrams * $metalRatePerGram, 2);
        $taxableAmount = round($metalCost + $makingCharges, 2);

        $taxLines = $item->taxes->map(function ($tax) use ($taxableAmount) {
            $amount = round($taxableAmount * ((float) $tax->percentage / 100), 2);

            return [
                'tax_id' => $tax->id,
                'name' => $tax->name,
                'percentage' => (float) $tax->percentage,
                'amount' => $amount,
            ];
        })->values();

        $taxTotal = round($taxLines->sum('amount'), 2);
        $finalPrice = round($taxableAmount + $taxTotal + $shippingCharges, 2);

        return [
            'metal_rate_per_gram' => $metalRatePerGram,
            'metal_cost' => $metalCost,
            'making_charges' => $makingCharges,
            'taxable_amount' => $taxableAmount,
            'tax_lines' => $taxLines,
            'tax_total' => $taxTotal,
            'shipping_charges' => $shippingCharges,
            'final_price' => $finalPrice,
        ];
    }
}
