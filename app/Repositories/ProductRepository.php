<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ProductRepository
{
    protected $product;

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
    public function list(User $user, String $search = '', array $with)
    {
        $query = $this->product->with($with);

        if ($search != '') {
            $searchTerm = '%' . $search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                    ->orWhere('sku', 'like', $searchTerm);
            });
        }

        if ($user->type === 'client') {
            $query->where('validated', true);
        }

        return $query->orderBy('mentioned_quantity', 'desc')
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
