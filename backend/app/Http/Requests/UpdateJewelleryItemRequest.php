<?php

namespace App\Http\Requests;

class UpdateJewelleryItemRequest extends StoreJewelleryItemRequest
{
    // Updating an item takes the exact same shape as creating one (a full
    // replace via PUT), so the validation rules are inherited unchanged.
}
