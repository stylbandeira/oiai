<?php

namespace App\Jobs;

use App\Models\CompanyProducts;
use App\Models\Product;
use App\Models\UserAddedProducts;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AveragePriceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $job_constancy = Carbon::now()->subDays(Product::AVERAGE_PRICE_JOB_CONSTANCY_DAYS);
        $average_date_limit = Carbon::now()->subWeeks(Product::AVERAGE_PRICE_PURCHASE_DATE_LIMIT_WEEKS);
        $data = [];

        $products = Product::whereHas('userAddedProducts', function ($query) use ($job_constancy, $average_date_limit) {
            $query->where('created_at', '>', $job_constancy)
                ->where('purchase_date', '>', $average_date_limit)
                ->where('processed', false);
        })
            ->with('userAddedProducts', function ($query) {
                $query->withCount('product');
            })
            ->get();
        //Média do produto geral
        foreach ($products as $product) {

            $product_array = $product->toArray();

            $user_added_products = $product_array['user_added_products'];

            if (count($product_array['user_added_products']) > 1) {
                $company_product_price = [];

                foreach ($user_added_products as $user_added_product) {
                    $company_product_price[$user_added_product['company_id']][] = [
                        'price' => $user_added_product['price'],
                        'product_id' => $user_added_product['product_id']
                    ];
                }

                $average_price = array_sum(array_column($product_array['user_added_products'], 'price')) / count($product_array['user_added_products']);

                $data[] = [
                    'product_id' => $product_array['id'],
                    'company_product_ids' => array_column($product_array['user_added_products'], 'company_product_id'),
                    'product_prices_by_company_id' => $company_product_price,
                    'user_added_products' => $product_array['user_added_products'],
                    'average_price' => $average_price,
                    'product' => $product_array
                ];

                $product->average_price = ($product->average_price + $average_price) / 2;
                $product->save();
            }
        }

        //Média do produto na empresa
        foreach ($data as $product_data) {
            foreach ($product_data['product_prices_by_company_id'] as $company_id => $price) {

                $company_product_average_price = array_sum(array_column($price, 'price')) / count($price);

                CompanyProducts::updateOrCreate([
                    'company_id' => $company_id,
                    'product_id' => array_column($price, 'product_id')[0]
                ], [
                    'average_price' => $company_product_average_price
                ]);
            }
        }

        UserAddedProducts::where('created_at', '>', $job_constancy)
            ->where('processed', false)
            ->update([
                'processed' => true
            ]);

        Log::info('Average Price Job Proceeded With Sucess!');
    }

    public function setAveragePrice(array $added_products)
    {
        $product_ids = [];

        foreach ($added_products as $products) {
            if (count($products) > 1) {
                $prices = array_column($products, 'average_price');
                $sum = array_sum($prices);

                $average = $sum / count($products);

                $product_ids[] = [
                    'id' => $products[0]['id'],
                    'average_price' => $average
                ];
            } else {
                $average = $products[0]['average_price'];

                $product_ids[] = [
                    'id' => $products[0]['id'],
                    'average_price' => $average
                ];
            }
        }

        return $product_ids;
    }
}
