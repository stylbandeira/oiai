<?php

namespace App\Jobs;

use App\Helpers\ImageFromUrl;
use App\Models\Product;
use App\Repositories\ProductCategoryRepository;
use App\Services\Product\ProductDataService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProductDataSearchJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private ProductDataService $product_data_service,
        private ProductCategoryRepository $product_category_repository,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $products = Product::where('refined', 0)
            ->whereNotNull('ean')
            ->limit(env('COSMOS_API_DAILY_PRODUCT_COUNT'))
            ->get();

        foreach ($products as $product) {
            $productData = $this->product_data_service->getProductData($product->ean);

            if (!count($productData)) {
                continue;
            }

            $img = '';

            if (isset($productData['image_url']) && $productData['image_url'] !== '') {
                $img = app(ImageFromUrl::class)->saveImageFromUrl($productData['image_url'] ?? '');
            }

            $product->img = $img !== '' ? $img : $product->img;
            $product->refined = true;
            $product->name = isset($productData['name']) ? $productData['name'] : $product->name;
            $product->save();

            if (isset($productData['category']) && $productData['category'] !== '') {
                $this->product_category_repository->firstOrNew($productData['category']);
            }
        }
    }
}
