<?php

namespace App\Actions\Company;

use App\Http\Resources\AdminCompanyResource;
use App\Http\Resources\ClientCompanyResource;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Repositories\CompanyRepository;
use Illuminate\Support\Facades\Auth;

class ShowCompanyAction
{
    public function __construct(private CompanyRepository $companyRepo)
    {
    }

    public function execute(Company $company)
    {
        $user = Auth::user();

        $company = $this->companyRepo->find($company->id);

        if ($user->type === 'admin') {
            return new AdminCompanyResource($company);
        }

        if ($user->type === 'company') {
            $company->load(['products']);
            return new CompanyResource($company);
        }

        return new ClientCompanyResource($company);
    }
}
