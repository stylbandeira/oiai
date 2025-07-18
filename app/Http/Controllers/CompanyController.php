<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
            'company.name' => 'required|string',
            'company.cnpj' => 'required|string',
            'company.img' => 'required|image',

            'address.country' => 'required|string',
            'address.area' => 'required|string',
            'address.city' => 'required|string',
            'address.street' => 'required|string',
            'address.number' => 'required|string',
            'address.complement' => 'string',
        ]);

        if ($validator->fails()) {
            return response([
                'errors' => $validator->errors()
            ], 400);
        }

        DB::beginTransaction();

        try {
            $address = Address::create($request->address);
            $companyData = $request->company;
            $companyData['address_id'] = $address->id;
            $company = Company::create($companyData);

            dd('Entrou no try');

            DB::commit();
        } catch (\Throwable $th) {
            dd('Entrou no catch');
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
