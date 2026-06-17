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

    public function list(User $user, array $data)
    {
        $query = $this->product->with(['category', 'unity', 'companies']);

        if (isset($data['search'])) {
            $searchTerm = '%' . $data['search'] . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                    ->orWhere('sku', 'like', $searchTerm);
            });
        }

        if (isset($data['validated']) && !$user->isClient()) {
            if ($data['validated'] === 'pendentes') {
                $query->where('validated', false);
            }

            if ($data['validated'] === 'validados') {
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

    public function paginate(User $user, array $data)
    {
        return $this->list($user, $data)->paginate($filters['paginate'] ?? 15);
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

    public function incrementListAdded(array $productsIds)
    {
        $this->product->whereIn('id', $productsIds)->increment('listAdded');
    }

    public function loadDefaultRelations(Product $product)
    {
        $product->with(['category', 'unity', 'companies']);
    }
}
