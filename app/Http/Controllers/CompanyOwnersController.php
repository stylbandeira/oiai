<?php

namespace App\Http\Controllers;

use App\Http\Resources\AdminCompanyResource;
use App\Http\Resources\CompanyResource;
use App\Http\Requests\CompanyOwners\StoreCompanyAndRequestRequest;
use App\Models\Company;
use App\Models\CompanyOwners;
use App\Repositories\CompanyRepository;
use App\Repositories\EventRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompanyOwnersController extends Controller
{
    protected EventRepository $eventRepo;
    protected CompanyRepository $companyRepo;

    public function __construct(EventRepository $eventRepo, CompanyRepository $companyRepo)
    {
        $this->eventRepo = $eventRepo;
        $this->companyRepo = $companyRepo;
    }

    /**
     * Lista empresas dos usuários.
     *
     * @param Request $request
     * @return void
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($request->user()->type === 'client') {
            return response([
                'error' => 'Não permitido para clientes comuns.'
            ], 403);
        }

        $query = $this->companyRepo->list($request);

        $perPage = $request->per_page ?? 10;

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
            ->paginate($perPage);

        if ($request->user()->type === 'admin') {
            return AdminCompanyResource::collection($companies);
        }

        if ($request->user()->type === 'company') {
            return CompanyResource::collection($companies);
        }
    }

    /**
     * Request access to company data
     *
     * @param Request $request
     * @param Company $company
     * @return void
     */
    public function requestAccess(Request $request, Company $company)
    {
        if ($request->user()->type !== 'company') {
            return response([
                'error' => 'Only company users can request access to companies.'
            ], 403);
        }

        try {
            $access = CompanyOwners::create([
                'user_id' => $request->user()->id,
                'company_id' => $company->id
            ]);
        } catch (\Throwable $th) {
            return response([
                'error' => 'Não é possível solicitar novamente atribuição à mesma empresa.'
            ], 400);
        }

        $this->eventRepo->createOwnershipRequestEvent($request->user(), $company);

        return response([
            'message' => 'Solicitação feita com sucesso!'
        ]);
    }

    /**
     * Request access and create an company
     *
     * @param Request $request
     * @return void
     */
    public function storeCompanyAndRequest(StoreCompanyAndRequestRequest $request)
    {
        if ($request->user()->type !== 'company') {
            return response([
                'error' => 'Only company users can request access to companies.'
            ], 403);
        }

        DB::transaction(function () use ($request) {
            $company = Company::firstOrCreate([
                'cnpj' => $request->cnpj
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
            'message' => 'Empresa cadastrada e solicitação enviada.'
        ]);
    }
}
