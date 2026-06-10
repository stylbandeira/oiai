<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class ProductRepository
{
    protected Product $product;

    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    public function all()
    {
        return $this->product->all();
    }

    /**
     * Returns a list of products
     *
     * @param string $search
     * @param array $with
     * @return Product
     */
    public function list(User $user, Request $request, array $with)
    {
        $query = $this->product->with($with);

        if ($request->has('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                    ->orWhere('sku', 'like', $searchTerm);
            });
        }

        if ($request->has('validated') && !$user->isClient()) {
            if ($request->validated === 'pendentes') {
                $query->where('validated', false);
            }

            if ($request->validated === 'validados') {
                $query->where('validated', true);
            }
        }

        if ($user->isClient()) {
            $query->where('validated', true)
                ->with(['userFavorites' => function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                }]);
        }

        return $query->withCount('companies as sum_companies')
            ->orderBy('sum_companies', 'desc')
            ->orderBy('mentioned_quantity', 'desc')
            ->orderBy('listAdded', 'desc')
            ->orderBy('name', 'asc')
            ->limit(1500);
    }

    public function find($id)
    {
        return $this->product->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->product->create($data);
    }

    public function update($id, array $data)
    {
        $record = $this->find($id);
        $record->update($data);
        return $record;
    }

    public function delete($id)
    {
        return $this->product->destroy($id);
    }
}
