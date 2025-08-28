<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddedProducts;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class DashboardService
{
    public function getSystemStats(): array
    {
        return [
            'totalUsers' => User::count(),
            'totalCompanies' => Company::count(),
            'totalProducts' => Product::count(),
            'systemHealth' => $this->calculateSystemHealth()
        ];
    }

    public function getTopUsers(int $limit = 3): Collection
    {
        return User::orderBy('points', 'desc')
            ->take($limit)
            ->get(['id', 'name', 'points', 'reputation']);
    }

    /**
     * Função a ser finalizada
     *
     * @return Collection
     */
    public function getTopMentionedStores(): array
    {
        return [];
    }

    public function getTopMentionedProducts(): array
    {
        $top_mentioned_products = Product::withCount(['userAddedProducts as registrations'])->get();

        if (count($top_mentioned_products)) {
            return [
                'id' => $top_mentioned_products->id,
                'name' => $top_mentioned_products->name,
                'registrations' => $top_mentioned_products->registrations,
            ];
        }

        return [];
    }

    private function calculateSystemHealth(): float
    {
        //INSERIR LÓGICA PARA CALCULAR A SAÚDE DO SISTEMA
        return 99.5;
    }
}
