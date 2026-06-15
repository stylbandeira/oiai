<?php

namespace App\Actions\Company;

use App\Http\Requests\Company\CompanyStoreRequest;
use App\Models\Company;

class StoreCompanyAction
{
    public function execute(CompanyStoreRequest $request)
    {
        $validatedData = $request->validated();

        if ($request->hasFile('img')) {
            $imgPath = $request->file('img')->store('companies/images', 'public');
            $validatedData['img'] = $imgPath;
        }

        $company = Company::create($validatedData);

        return response([
            'company' => $company,
        ]);
    }
}
