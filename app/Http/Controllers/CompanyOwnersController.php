<?php

namespace App\Http\Controllers;

use App\Actions\CompanyOwners\IndexCompanyOwnersAction;
use App\Actions\CompanyOwners\RequestCompanyAccessAction;
use App\Actions\CompanyOwners\StoreCompanyAndRequestAction;
use App\Http\Requests\CompanyOwners\StoreCompanyAndRequestRequest;
use App\Models\Company;
use Illuminate\Http\Request;

class CompanyOwnersController extends Controller
{
    /**
     * Lista empresas dos usuários.
     *
     * @param Request $request
     * @return void
     */
    public function index(Request $request, IndexCompanyOwnersAction $action)
    {
        return $action->execute($request);
    }

    /**
     * Request access to company data
     *
     * @param Request $request
     * @param Company $company
     * @return void
     */
    public function requestAccess(Request $request, Company $company, RequestCompanyAccessAction $action)
    {
        return $action->execute($request, $company);
    }

    /**
     * Request access and create an company
     *
     * @param Request $request
     * @return void
     */
    public function storeCompanyAndRequest(StoreCompanyAndRequestRequest $request, StoreCompanyAndRequestAction $action)
    {
        return $action->execute($request);
    }
}
