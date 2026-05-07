<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyOwners;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CompanyOwnersController extends Controller
{
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

        Log::alert($request->all());

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

        return response([
            'message' => 'Empresa cadastrada e solicitação enviada.'
        ]);
    }
}
