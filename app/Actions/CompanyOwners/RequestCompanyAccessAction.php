<?php

namespace App\Actions\CompanyOwners;

use App\Models\Company;
use App\Models\CompanyOwners;
use App\Repositories\CompanyOwnerRepository;
use App\Repositories\EventRepository;
use Illuminate\Http\Request;

class RequestCompanyAccessAction
{
    public function __construct(
        private EventRepository $eventRepo,
        private CompanyOwnerRepository $companyOwnerRepository
    ) {}

    public function execute(Request $request, Company $company)
    {
        try {
            $this->companyOwnerRepository->create($company, $request->user());
        } catch (\Throwable $th) {
            return response([
                'error' => 'Não é possível solicitar novamente atribuição à mesma empresa.',
            ], 400);
        }

        $this->eventRepo->createOwnershipRequestEvent($request->user(), $company);

        return response([
            'message' => 'Solicitação feita com sucesso!',
        ]);
    }
}
