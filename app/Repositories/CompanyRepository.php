<?php

namespace App\Repositories;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CompanyRepository
{
    protected Company $company;

    public function __construct(Company $company)
    {
        $this->company = $company;
    }

    public function all()
    {
        return $this->company->all();
    }

    public function find($id)
    {
        return $this->company->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->company->create($data);
    }

    public function update($id, array $data)
    {
        $record = $this->find($id);
        $record->update($data);
        return $record;
    }

    public function delete($id)
    {
        return $this->company->destroy($id);
    }

    public function list(Request $request)
    {
        $query = Company::query();

        if ($request->has('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($query) use ($searchTerm) {
                $query->where('name', 'like', $searchTerm)
                    ->orWhere('email', 'like', $searchTerm)
                    ->orWhere('cnpj', 'like', $searchTerm);
            });
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->user()->type === 'company') {
            $query->with('ownerRelationship')
                ->whereHas('ownerRelationship', function ($query) use ($request) {
                    $query->where('user_id', $request->user()->id);
                });
        }

        if ($request->user()->type === 'admin') {

            $query->withTrashed();

            //TO-DO - QUERO DEIXAR ISSO MAIS AUTOMÁTICO
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
        }

        return $query;
    }
}
