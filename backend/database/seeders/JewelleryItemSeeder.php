<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\JewelleryItem;
use App\Models\Tax;
use Illuminate\Database\Seeder;

class JewelleryItemSeeder extends Seeder
{
    public function run(): void
    {
        $categoryIdByName = Category::pluck('id', 'name');
        $allTaxIds = Tax::pluck('id')->all();

        $items = [
            [
                'name' => 'Elegant Gold Necklace',
                'description' => 'A timeless 22K gold necklace with a traditional finish, perfect for weddings and festive occasions.',
                'category' => 'Necklaces',
                'metal_type' => 'gold_22k',
                'weight_grams' => 12.500,
                'making_charges' => 1500,
                'shipping_charges' => 200,
                'is_available' => true,
                'image_url' => 'https://images.unsplash.com/photo-1611591437281-460bfbe1220a?w=600',
            ],
            [
                'name' => 'Classic Solitaire Ring',
                'description' => '18K gold ring with a polished solitaire setting, sized for everyday wear.',
                'category' => 'Rings',
                'metal_type' => 'gold_18k',
                'weight_grams' => 4.200,
                'making_charges' => 800,
                'shipping_charges' => 100,
                'is_available' => true,
                'image_url' => 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=600',
            ],
            [
                'name' => 'Silver Drop Earrings',
                'description' => 'Lightweight sterling silver drop earrings with a hand-finished texture.',
                'category' => 'Earrings',
                'metal_type' => 'silver',
                'weight_grams' => 6.000,
                'making_charges' => 250,
                'shipping_charges' => 80,
                'is_available' => true,
                'image_url' => 'https://images.unsplash.com/photo-1620656798579-1984d9e87df7?w=600',
            ],
            [
                'name' => 'Platinum Wedding Band',
                'description' => 'A minimalist platinum band with a comfort-fit interior, built to last a lifetime.',
                'category' => 'Rings',
                'metal_type' => 'platinum',
                'weight_grams' => 5.800,
                'making_charges' => 1200,
                'shipping_charges' => 150,
                'is_available' => true,
                'image_url' => 'https://images.unsplash.com/photo-1602751584547-4a20b57d0298?w=600',
            ],
            [
                'name' => 'Bridal Gold Bangles (Set of 2)',
                'description' => '24K gold bangles with an engraved floral pattern, sold as a matching pair.',
                'category' => 'Bangles',
                'metal_type' => 'gold_24k',
                'weight_grams' => 22.000,
                'making_charges' => 2200,
                'shipping_charges' => 250,
                'is_available' => true,
                'image_url' => 'https://images.unsplash.com/photo-1596944924616-7b38e7cfac36?w=600',
            ],
            [
                'name' => 'Charm Bracelet',
                'description' => '22K gold bracelet with interchangeable charms, currently out of stock.',
                'category' => 'Bracelets',
                'metal_type' => 'gold_22k',
                'weight_grams' => 8.300,
                'making_charges' => 900,
                'shipping_charges' => 120,
                'is_available' => false,
                'image_url' => 'https://images.unsplash.com/photo-1611591437281-460bfbe1220a?w=600',
            ],
        ];

        foreach ($items as $itemData) {
            $categoryName = $itemData['category'];
            $imageUrl = $itemData['image_url'];
            unset($itemData['category'], $itemData['image_url']);
            $itemData['category_id'] = $categoryIdByName[$categoryName];

            $item = JewelleryItem::updateOrCreate(
                ['name' => $itemData['name']],
                $itemData
            );

            $item->taxes()->sync($allTaxIds);

            if ($item->images()->count() === 0) {
                $item->images()->create([
                    'path' => null,
                    'url' => $imageUrl,
                    'sort_order' => 0,
                ]);
            }
        }
    }
}
