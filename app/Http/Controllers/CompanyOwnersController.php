<?php

namespace App\Http\Controllers;

use App\Http\Resources\AdminCompanyResource;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Models\CompanyOwners;
use App\Models\User;
use App\Repositories\CompanyRepository;
use App\Repositories\EventRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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
            ->whereHas('owners', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
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
    public function storeCompanyAndRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'cnpj' => 'required|string|unique:company,cnpj',
            'img' => 'image',
            'website' => 'string',
            'email' => 'string|email',
            'status' => 'string',
            'phone' => 'string',
            'description' => 'string',
            'raw_address' => 'string',
        ]);

        if ($validator->fails()) {
            return response([
                'errors' => $validator->errors()
            ], 403);
        }

        $company = Company::firstOrCreate([
            'cnpj' => $request->cnpj
        ], [
            ...$request->all(),
            'status' => 'pending',
        ]);

        CompanyOwners::create([
            'user_id' => $request->user()->id,
            'company_id' => $company->id,
            'status' => 'pending',
        ]);

        $this->eventRepo->createOwnershipRequestEvent($request->user(), $company);

        return response([
            'message' => 'Empresa cadastrada e solicitação enviada.'
        ]);
    }

    /**
     * Replace the companies owned by an user.
     *
     * @param Request $request
     * @param User $user
     * @return void
     */
    public function updateUserCompanies(Request $request, User $user)
    {
        if ($request->user()->type !== 'admin') {
            return response([
                'error' => 'Only admin users can update company ownership.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'company_ids' => 'required|array',
            'company_ids.*' => 'integer|distinct|exists:company,id',
        ]);

        if ($validator->fails()) {
            return response([
                'errors' => $validator->errors()
            ], 403);
        }

        $companyIds = collect($request->company_ids)->map(fn($id) => (int) $id)->values();

        $allowedCompanyIds = DB::transaction(function () use ($request, $user, $companyIds) {
            $currentRelationships = CompanyOwners::where('user_id', $user->id)
                ->get()
                ->keyBy('company_id');

            CompanyOwners::where('user_id', $user->id)
                ->whereNotIn('company_id', $companyIds)
                ->delete();

            $allowedCompanyIds = [];

            foreach ($companyIds as $companyId) {
                $currentRelationship = $currentRelationships->get($companyId);

                CompanyOwners::updateOrCreate([
                    'user_id' => $user->id,
                    'company_id' => $companyId,
                ], [
                    'status' => 'active',
                    'approved_at' => now(),
                    'approved_by' => $request->user()->id,
                ]);

                if (!$currentRelationship || $currentRelationship->status !== 'active') {
                    $allowedCompanyIds[] = $companyId;
                }
            }

            return $allowedCompanyIds;
        });

        Company::whereIn('id', $allowedCompanyIds)
            ->get()
            ->each(fn(Company $company) => $this->eventRepo->createOwnershipAllowedEvent($user, $company));

        return response([
            'message' => 'Empresas do usuário atualizadas com sucesso!'
        ]);
    }
}
