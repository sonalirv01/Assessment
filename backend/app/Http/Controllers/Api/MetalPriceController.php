<?php

namespace App\Http\Controllers\Api;

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
        $metalPrice->update($request->validated());

        return new MetalPriceResource($metalPrice);
    }
}
