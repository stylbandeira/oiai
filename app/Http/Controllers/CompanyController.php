<?php

namespace App\Http\Controllers;

use App\Http\Resources\AdminCompanyResource;
use App\Http\Resources\ClientCompanyResource;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class CompanyController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Company::class, 'company');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Company::query();

        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                    ->orWhere('email', 'like', $searchTerm)
                    ->orWhere('cnpj', 'like', $searchTerm);
            });
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->user()->type === 'company') {
            $query->whereHas('owners', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            });
        }

        $perPage = $request->per_page ?? 10;

        if ($request->user()->type === 'admin') {

            //TO-DO - QUERO DEIXAR ISSO MAIS AUTOMÁTICO
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('plan')) {
                $query->where('plan', $request->plan);
            }
        }

        $companies = $query->with(['owners', 'products'])
            ->withCount('products')
            ->paginate($perPage);

        Log::alert($companies);

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
            ], 400);
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
            return new CompanyResource($company);
        }

        return new ClientCompanyResource($company);

        return response($company);
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
