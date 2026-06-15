<?php

namespace App\Actions\CompanyOwners;

use App\Models\Company;
use App\Models\CompanyOwners;
use App\Repositories\EventRepository;
use Illuminate\Http\Request;

class RequestCompanyAccessAction
{
    public function __construct(private EventRepository $eventRepo)
    {
    }

    public function execute(Request $request, Company $company)
    {
        if ($request->user()->type !== 'company') {
            return response([
                'error' => 'Only company users can request access to companies.',
            ], 403);
        }

        try {
            CompanyOwners::create([
                'user_id' => $request->user()->id,
                'company_id' => $company->id,
            ]);
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
