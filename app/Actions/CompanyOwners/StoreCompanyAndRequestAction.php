<?php

namespace App\Actions\CompanyOwners;

use App\Http\Requests\CompanyOwners\StoreCompanyAndRequestRequest;
use App\Models\Company;
use App\Models\CompanyOwners;
use App\Repositories\CompanyOwnerRepository;
use App\Repositories\CompanyRepository;
use App\Repositories\EventRepository;
use Illuminate\Support\Facades\DB;

class StoreCompanyAndRequestAction
{
    public function __construct(
        private EventRepository $eventRepo,
        private CompanyRepository $companyRepository,
        private CompanyOwnerRepository $companyOwnerRepository
    ) {}

    public function execute(StoreCompanyAndRequestRequest $request)
    {
        DB::transaction(function () use ($request) {
            $company = $this->companyRepository->firstOrCreateByCnpj($request->validated());

            $this->companyOwnerRepository->create($company, $request->user());

            $this->eventRepo->createOwnershipRequestEvent($request->user(), $company);
        });

        return response([
            'message' => 'Empresa cadastrada e solicitação enviada.',
        ]);
    }
}
