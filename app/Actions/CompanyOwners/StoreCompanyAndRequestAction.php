<?php

namespace App\Actions\CompanyOwners;

use App\Http\Requests\CompanyOwners\StoreCompanyAndRequestRequest;
use App\Models\Company;
use App\Models\CompanyOwners;
use App\Repositories\EventRepository;
use Illuminate\Support\Facades\DB;

class StoreCompanyAndRequestAction
{
    public function __construct(private EventRepository $eventRepo)
    {
    }

    public function execute(StoreCompanyAndRequestRequest $request)
    {
        if ($request->user()->type !== 'company') {
            return response([
                'error' => 'Only company users can request access to companies.',
            ], 403);
        }

        DB::transaction(function () use ($request) {
            $company = Company::firstOrCreate([
                'cnpj' => $request->cnpj,
            ], [
                ...$request->validated(),
                'status' => Company::STATUS_PENDING,
            ]);

            CompanyOwners::create([
                'user_id' => $request->user()->id,
                'company_id' => $company->id,
                'status' => CompanyOwners::STATUS_PENDING,
            ]);

            $this->eventRepo->createOwnershipRequestEvent($request->user(), $company);
        });

        return response([
            'message' => 'Empresa cadastrada e solicitação enviada.',
        ]);
    }
}
