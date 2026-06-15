<?php

namespace App\Actions\Address;

use App\Http\Requests\Address\AddressStoreRequest;
use App\Models\Address;

class StoreAddressAction
{
    public function execute(AddressStoreRequest $request)
    {
        $address = Address::create($request->validated());

        return response([
            'address' => $address,
        ]);
    }
}
