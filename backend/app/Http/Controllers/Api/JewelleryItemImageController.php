<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJewelleryItemImagesRequest;
use App\Http\Resources\JewelleryItemImageResource;
use App\Models\JewelleryItem;
use App\Models\JewelleryItemImage;
use App\Services\ImageReencoder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class JewelleryItemImageController extends Controller
{
    public function store(StoreJewelleryItemImagesRequest $request, JewelleryItem $item, ImageReencoder $reencoder)
    {
        $this->authorize('create', JewelleryItemImage::class);

        // Re-encode every file up front and fail the whole request before
        // writing anything, rather than persisting some images and
        // rejecting others from the same batch.
        $reencoded = [];
        foreach ($request->file('images') as $index => $photo) {
            try {
                $reencoded[$index] = $reencoder->reencode($photo);
            } catch (RuntimeException $e) {
                throw ValidationException::withMessages([
                    "images.{$index}" => [$e->getMessage()],
                ]);
            }
        }

        $nextSortOrder = ($item->images()->max('sort_order') ?? -1) + 1;

        // seperate row per photo so a single one can be deleted later without
        // touching the rest
        foreach ($reencoded as $index => $file) {
            $path = 'items/'.Str::uuid().'.'.$file['extension'];
            Storage::disk('public')->put($path, $file['contents']);

            $item->images()->create([
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
                'sort_order' => $nextSortOrder + $index,
            ]);
        }

        return JewelleryItemImageResource::collection($item->images()->get())
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(JewelleryItem $item, JewelleryItemImage $image)
    {
        $this->authorize('delete', $image);

        abort_unless($image->jewellery_item_id === $item->id, 404);

        if ($image->path) {
            Storage::disk('public')->delete($image->path);
        }

        $image->delete();

        return response()->json([
            'message' => 'Image deleted successfully.',
        ]);
    }
}
