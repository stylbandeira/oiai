<?php

namespace Tests\Feature\Integration\ListController;

use App\Models\CompletedList;
use App\Models\ItensList;
use App\Models\ListProducts;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompletedListSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_completing_a_list_stores_its_json_snapshot(): void
    {
        [$user, $list, $product] = $this->createListWithProduct();

        $this->actingAs($user)
            ->putJson('/api/lists/' . $list->id, ['status' => ItensList::STATUS_COMPLETED])
            ->assertOk()
            ->assertJsonPath('list.status', ItensList::STATUS_COMPLETED);

        $snapshot = CompletedList::query()->where('list_id', $list->id)->firstOrFail();

        $this->assertSame('1.0', $snapshot->version);
        $this->assertSame($list->id, $snapshot->list_data['id']);
        $this->assertSame('Arroz', $snapshot->list_data['products'][0]['name']);
        $this->assertSame(2, $snapshot->list_data['products'][0]['quantity']);
        $this->assertSame(ItensList::STATUS_COMPLETED, $snapshot->list_data['status']);
    }

    public function test_show_returns_snapshot_for_completed_list(): void
    {
        [$user, $list, $product] = $this->createListWithProduct();

        $this->actingAs($user)
            ->putJson('/api/lists/' . $list->id, ['status' => ItensList::STATUS_COMPLETED])
            ->assertOk();

        $product->update(['name' => 'Nome alterado depois da conclusão']);

        $this->actingAs($user)
            ->getJson('/api/lists/' . $list->id)
            ->assertOk()
            ->assertJsonPath('list.products.0.name', 'Arroz')
            ->assertJsonPath('list.status', ItensList::STATUS_COMPLETED);
    }

    public function test_snapshot_is_not_overwritten_after_list_is_completed(): void
    {
        [$user, $list] = $this->createListWithProduct();

        $this->actingAs($user)
            ->putJson('/api/lists/' . $list->id, ['status' => ItensList::STATUS_COMPLETED])
            ->assertOk();

        $this->actingAs($user)
            ->putJson('/api/lists/' . $list->id, ['name' => 'Nome posterior'])
            ->assertOk();

        $this->actingAs($user)
            ->getJson('/api/lists/' . $list->id)
            ->assertOk()
            ->assertJsonPath('list.name', 'Compras');
    }

    public function test_show_keeps_using_live_data_for_active_list(): void
    {
        [$user, $list, $product] = $this->createListWithProduct();
        $product->update(['name' => 'Arroz integral']);

        $this->actingAs($user)
            ->getJson('/api/lists/' . $list->id)
            ->assertOk()
            ->assertJsonPath('list.products.0.name', 'Arroz integral');

        $this->assertDatabaseCount('completed_lists', 0);
    }

    public function test_updating_only_status_does_not_remove_list_items(): void
    {
        [$user, $list] = $this->createListWithProduct();

        $this->actingAs($user)
            ->putJson('/api/lists/' . $list->id, ['status' => ItensList::STATUS_COMPLETED])
            ->assertOk();

        $this->assertDatabaseCount('list_products', 1);
    }

    private function createListWithProduct(): array
    {
        $user = User::factory()->client()->create();
        $product = Product::factory()->create(['name' => 'Arroz']);
        $list = ItensList::create([
            'user_id' => $user->id,
            'name' => 'Compras',
            'favorite' => false,
            'total' => 12.5,
        ]);

        ListProducts::unguarded(fn () => ListProducts::create([
            'list_id' => $list->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'completed' => false,
        ]));

        return [$user, $list, $product];
    }
}
