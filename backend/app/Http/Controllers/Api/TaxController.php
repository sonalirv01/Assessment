<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaxRequest;
use App\Http\Requests\UpdateTaxRequest;
use App\Http\Resources\TaxResource;
use App\Models\Tax;

class TaxController extends Controller
{
    public function index()
    {
        return TaxResource::collection(Tax::orderBy('name')->get());
    }

    public function store(StoreTaxRequest $request)
    {
        $this->authorize('create', Tax::class);

        $tax = Tax::create($request->validated());

        return (new TaxResource($tax))->response()->setStatusCode(201);
    }

    public function update(UpdateTaxRequest $request, Tax $tax)
    {
        $this->authorize('update', $tax);

        $tax->update($request->validated());

        return new TaxResource($tax);
    }

    public function destroy(Tax $tax)
    {
        $this->authorize('delete', $tax);

        $tax->delete();

        return response()->json([
            'message' => 'Tax deleted successfully.',
        ]);
    }
}
