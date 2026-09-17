<?php

namespace Tests\Unit;

use App\Models\JewelleryItem;
use App\Models\JewelleryItemImage;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JewelleryItemImageCleanupTest extends TestCase
{
    public function test_deleting_an_item_removes_its_image_files_from_storage(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('items/one.jpg', 'fake-jpeg-bytes');
        Storage::disk('public')->put('items/two.jpg', 'fake-jpeg-bytes');

        $item = new JewelleryItem;
        $item->setRelation('images', collect([
            new JewelleryItemImage(['path' => 'items/one.jpg', 'url' => 'http://example.test/items/one.jpg']),
            new JewelleryItemImage(['path' => 'items/two.jpg', 'url' => 'http://example.test/items/two.jpg']),
        ]));

        $item->deleteStoredImages();

        Storage::disk('public')->assertMissing('items/one.jpg');
        Storage::disk('public')->assertMissing('items/two.jpg');
    }

    public function test_an_image_with_no_stored_path_is_skipped_without_error(): void
    {
        Storage::fake('public');

        $item = new JewelleryItem;
        $item->setRelation('images', collect([
            new JewelleryItemImage(['path' => null, 'url' => 'http://example.test/items/external.jpg']),
        ]));

        $item->deleteStoredImages();

        $this->addToAssertionCount(1); // reaching here without an exception is the assertion
    }
}
