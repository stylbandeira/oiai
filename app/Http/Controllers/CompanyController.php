<?php

namespace App\Http\Controllers;

use App\Http\Resources\AdminCompanyResource;
use App\Http\Resources\ClientCompanyResource;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Repositories\CompanyRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class CompanyController extends Controller
{
    protected CompanyRepository $companyRepo;

    public function __construct(CompanyRepository $companyRepo)
    {
        $this->companyRepo = $companyRepo;
        $this->authorizeResource(Company::class, 'company');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = $this->companyRepo->list($request);

        $perPage = $request->per_page ?? 10;

        $companies = $query->with(['owners', 'products'])
            ->withCount('products')
            ->paginate($perPage);

        if ($request->user()->type === 'admin') {
            return AdminCompanyResource::collection($companies);
        }

        if ($request->user()->type === 'company') {
            return CompanyResource::collection($companies);
        }

        return ClientCompanyResource::collection($companies);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // Inertia::render('Welcome', []);
        return Inertia::render('Company/CreateCompany', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
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
            ], 422);
        }

        $validatedData = $request->all();

        if ($request->hasFile('img')) {
            $imgPath = $request->file('img')->store('companies/images', 'public');
            $validatedData['img'] = $imgPath;
        }

        $company = Company::create($validatedData);

        return response([
            'company' => $company
        ]);
    }

    /**
     * Display the specified company.
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function show(Company $company)
    {
        $user = Auth::user();

        if ($user->type === 'admin') {
            return new AdminCompanyResource($company);
        }

        if ($user->type === 'company') {
            $company->load(['products']);
            return new CompanyResource($company);
        }

        return new ClientCompanyResource($company);
    }

    public function dashboardData(Request $request, Company $company)
    {
        $user = Auth::user();

        if (!in_array($company->id, $user->activeCompanies->pluck('id')->toArray())) {
            return response([
                'error' => "User don't have access to this company"
            ], 403);
        }


        // // TODO - VERIFICAR SE O COMPANY QUE VIRÁ NA FUNÇÃO É A MESMA QUE O USUÁRIO TEM ACESSO
        // $user_companies_ids = $request->user()->companies->pluck('id')->toArray();
        // $company = $request->user()->activeCompanies[0] ?? false;

        // if (
        //     $request->user()->type !== 'company' ||
        //     // !in_array($company->id, $user_companies_ids)
        //     $user_companies_ids < 1 ||
        //     !$company
        // ) {
        //     return response([
        //         'error' => 'Apenas empresas podem ter acessos aos seus respectivos dados'
        //     ], 403);
        // }

        // TODO - ATUALIZAR DADOS
        return response([
            'company' => new CompanyResource($company),
            'totalProducts' => count($company->products) ?? 0,
            'activeWebhooks' => 0,
            'monthlyUpdates' => 0,
            'userEngagement' => 0
        ]);
    }

    // TODO
    public function submit(Request $request)
    {
        Log::alert($request->all());
        return response([
            'message' => 'Solicitação feita com sucesso!'
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function edit(Company $company)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Company $company)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string',
            'cnpj' => 'string|unique:company,cnpj,' . $company->id,
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
            ], 400);
        }

        $validatedData = $request->all();

        if ($request->hasFile('img')) {

            if ($company->img && Storage::disk('public')->exists($company->img)) {
                Storage::disk('public')->delete($company->img);
            }

            $imgPath = $request->file('img')->store('companies/images', 'public');
            $validatedData['img'] = $imgPath;
        }

        $company->update($validatedData);

        return response([
            'company' => $company
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function destroy(Company $company)
    {
        $company->delete();

        return response([
            'message' => 'Empresa deletada com sucesso!'
        ]);
    }
}
