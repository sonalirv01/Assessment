<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJewelleryItemImagesRequest;
use App\Http\Resources\JewelleryItemImageResource;
use App\Models\JewelleryItem;
use App\Models\JewelleryItemImage;
use Illuminate\Support\Facades\Storage;

class JewelleryItemImageController extends Controller
{
    public function store(StoreJewelleryItemImagesRequest $request, JewelleryItem $item)
    {
        $this->authorize('create', JewelleryItemImage::class);

        $nextSortOrder = ($item->images()->max('sort_order') ?? -1) + 1;

        // seperate row per photo so a single one can be deleted later without
        // touching the rest
        foreach ($request->file('images') as $index => $photo) {
            $path = $photo->store('items', 'public');

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

        // TODO: JewelleryItem::destroy() doesn't currently loop over images to
        // clean up their files before the cascade delete removes the rows —
        // fine for now, but worth fixing before this touches real storage.
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
