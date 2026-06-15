<?php

namespace App\Actions\CompanyOwners;

use App\Http\Resources\AdminCompanyResource;
use App\Http\Resources\CompanyResource;
use App\Models\CompanyOwners;
use App\Repositories\CompanyRepository;
use Illuminate\Http\Request;

class IndexCompanyOwnersAction
{
    public function __construct(private CompanyRepository $companyRepo)
    {
    }

    public function execute(Request $request)
    {
        $user = $request->user();

        if ($request->user()->type === 'client') {
            return response([
                'error' => 'Não permitido para clientes comuns.',
            ], 403);
        }

        $query = $this->companyRepo->list($request);

        $companies = $query->with(['owners', 'products'])
            ->when($request->user()->type === 'company', function ($query) use ($request) {
                $query->whereHas('owners', function ($query) use ($request) {
                    $query->where('user_id', $request->user()->id)
                        ->where('company_owners.status', CompanyOwners::STATUS_ACTIVE);
                });
            })
            ->with(['ownerRelationship' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->withCount('products')
            ->paginate($request->per_page ?? 10);

        if ($request->user()->type === 'admin') {
            return AdminCompanyResource::collection($companies);
        }

        if ($request->user()->type === 'company') {
            return CompanyResource::collection($companies);
        }
    }
}
