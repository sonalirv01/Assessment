<?php

namespace App\Http\Controllers\Api;

use App\Events\MetalPriceUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMetalPriceRequest;
use App\Http\Resources\MetalPriceResource;
use App\Models\MetalPrice;

class MetalPriceController extends Controller
{
    public function index()
    {
        return MetalPriceResource::collection(MetalPrice::orderBy('label')->get());
    }

    public function update(UpdateMetalPriceRequest $request, MetalPrice $metalPrice)
    {
        $this->authorize('update', $metalPrice);

        $metalPrice->update($request->validated());

        broadcast(new MetalPriceUpdated($metalPrice));

        return new MetalPriceResource($metalPrice);
    }
}
