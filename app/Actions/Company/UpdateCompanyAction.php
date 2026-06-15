<?php

namespace App\Actions\Company;

use App\Http\Requests\Company\CompanyUpdateRequest;
use App\Models\Company;
use Illuminate\Support\Facades\Storage;

class UpdateCompanyAction
{
    public function execute(CompanyUpdateRequest $request, Company $company)
    {
        $validatedData = $request->validated();

        if ($request->hasFile('img')) {
            if ($company->img && Storage::disk('public')->exists($company->img)) {
                Storage::disk('public')->delete($company->img);
            }

            $imgPath = $request->file('img')->store('companies/images', 'public');
            $validatedData['img'] = $imgPath;
        }

        $company->update($validatedData);

        return response([
            'company' => $company,
        ]);
    }
}
