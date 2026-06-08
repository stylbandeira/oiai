<?php

namespace Tests\Feature\Integration\ListController;

use App\Models\Company;
use App\Models\CompanyProducts;
use App\Models\ItensList;
use App\Models\ListProducts;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListOptimizeTest extends TestCase
{
    use RefreshDatabase;

    public function test_not_found_returns_404(): void
    {
        $user = User::factory()->client()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/lists/999999/optimize');

        $response->assertStatus(404);
    }

    public function test_company_product_id_is_filled_with_cheapest_or_only_company_product(): void
    {
        $user = User::factory()->client()->create();
        $list = $this->createList($user);
        $firstProduct = Product::factory()->create(['name' => 'Arroz']);
        $secondProduct = Product::factory()->create(['name' => 'Leite']);
        $this->createListProduct($list, $firstProduct, 1);
        $this->createListProduct($list, $secondProduct, 1);

        $expensiveCompanyProduct = $this->createCompanyProduct($firstProduct, 12.9);
        $cheapCompanyProduct = $this->createCompanyProduct($firstProduct, 9.9);
        $onlyCompanyProduct = $this->createCompanyProduct($secondProduct, 6.5);

        $response = $this->actingAs($user)
            ->postJson('/api/lists/' . $list->id . '/optimize');

        $response
            ->assertStatus(200)
            ->assertJsonStructure(['list']);

        $this->assertDatabaseHas('list_products', [
            'list_id' => $list->id,
            'product_id' => $firstProduct->id,
            'company_product_id' => $cheapCompanyProduct->id,
        ]);
        $this->assertDatabaseMissing('list_products', [
            'list_id' => $list->id,
            'product_id' => $firstProduct->id,
            'company_product_id' => $expensiveCompanyProduct->id,
        ]);
        $this->assertDatabaseHas('list_products', [
            'list_id' => $list->id,
            'product_id' => $secondProduct->id,
            'company_product_id' => $onlyCompanyProduct->id,
        ]);
        $this->assertDatabaseHas('list', [
            'id' => $list->id,
            'optimized' => true,
        ]);
    }

    private function createList(User $user): ItensList
    {
        return ItensList::create([
            'user_id' => $user->id,
            'name' => 'Lista',
            'favorite' => false,
            'total' => 0,
        ]);
    }

    private function createListProduct(ItensList $list, Product $product, int $quantity): ListProducts
    {
        return ListProducts::unguarded(fn() => ListProducts::create([
            'list_id' => $list->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
        ]));
    }

    private function createCompanyProduct(Product $product, float $price): CompanyProducts
    {
        $company = Company::factory()->create();

        return CompanyProducts::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'average_price' => $price,
        ]);
    }
}
