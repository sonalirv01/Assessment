<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJewelleryItemRequest;
use App\Http\Requests\UpdateJewelleryItemRequest;
use App\Http\Resources\JewelleryItemResource;
use App\Models\JewelleryItem;
use App\Services\JewelleryPriceCalculator;
use Illuminate\Http\Request;

class JewelleryItemController extends Controller
{


    public function index(Request $request, JewelleryPriceCalculator $calculator)
    {
        $perPage = max((int) $request->input('per_page', 12), 1);
        $page = max((int) $request->input('page', 1), 1);

        $items = JewelleryItem::with(['category', 'metalPrice', 'taxes', 'images'])
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->input('search').'%'))
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->input('category_id')))
            ->when($request->filled('metal_type'), fn ($query) => $query->where('metal_type', $request->input('metal_type')))
            ->when($request->filled('is_available'), fn ($query) => $query->where('is_available', $request->boolean('is_available')))
            ->get();

        // The final price isn't a database column, it's calculated live from the
        // current metal rate and tax rates, so price filtering/sorting has to
        // happen here in memory once we know what today's price actually is.
        // Fine at the scale of a jewellery catalogue; a much larger store would
        // want to cache the computed price instead of recalculating every read.
        $withPrices = $items->map(fn (JewelleryItem $item) => [
            'item' => $item,
            'final_price' => $calculator->calculate($item)['final_price'],
        ]);

        if ($request->filled('min_price')) {
            $minPrice = (float) $request->input('min_price');
            $withPrices = $withPrices->filter(fn ($entry) => $entry['final_price'] >= $minPrice);
        }

        if ($request->filled('max_price')) {
            $maxPrice = (float) $request->input('max_price');
            $withPrices = $withPrices->filter(fn ($entry) => $entry['final_price'] <= $maxPrice);
        }

        $sortBy = $request->input('sort_by', 'name');
        $sortDescending = $request->input('sort_dir', 'asc') === 'desc';

        $sorted = $withPrices
            ->sortBy(
                fn ($entry) => $sortBy === 'price' ? $entry['final_price'] : strtolower($entry['item']->name),
                SORT_REGULAR,
                $sortDescending
            )
            ->values();

        $total = $sorted->count();
        $pageOfItems = $sorted->slice(($page - 1) * $perPage, $perPage)->pluck('item')->values();

        return response()->json([
            'data' => JewelleryItemResource::collection($pageOfItems),
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => max((int) ceil($total / $perPage), 1),
            ],
        ]);
    }

    public function show(JewelleryItem $item)
    {
        $item->load(['category', 'metalPrice', 'taxes', 'images']);

        return new JewelleryItemResource($item);
    }

    public function store(StoreJewelleryItemRequest $request)
    {
        $data = $request->validated();
        $taxIds = $data['tax_ids'] ?? [];
        unset($data['tax_ids']);

        $item = JewelleryItem::create($data);
        $item->taxes()->sync($taxIds);
        $item->load(['category', 'metalPrice', 'taxes', 'images']);

        return (new JewelleryItemResource($item))->response()->setStatusCode(201);
    }

    public function update(UpdateJewelleryItemRequest $request, JewelleryItem $item)
    {
        $data = $request->validated();
        $taxIds = $data['tax_ids'] ?? [];
        unset($data['tax_ids']);

        $item->update($data);
        $item->taxes()->sync($taxIds);
        $item->load(['category', 'metalPrice', 'taxes', 'images']);

        return new JewelleryItemResource($item);
    }

    public function destroy(JewelleryItem $item)
    {
        $item->delete();

        return response()->json([
            'message' => 'Item deleted successfully.',
        ]);
    }
}
