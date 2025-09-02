<?php

namespace App\Http\Controllers;

use App\Http\Resources\AdminCompanyResource;
use App\Http\Resources\ClientCompanyResource;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

        if ($request->user()->type === 'company') {
            $query->whereHas('owners', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            });
        }

        Log::info($request->all());

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
            ->paginate(10);

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
            'site' => 'string',
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

        if ($request->hasFile('img')) {
            $request['img'] = $request->file('img')->store('companies/images', 'public');
        }

        $company = Company::create($request->all());

        return response([
            'company' => $company
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function show(Company $company)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function destroy(Company $company)
    {
        //
    }
}
