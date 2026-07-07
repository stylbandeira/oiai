<?php

namespace App\Services\Product;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductDataService
{
    protected string $accessKey;
    protected string $url;

    public function __construct()
    {
        $this->accessKey = env('COSMOS_API_TOKEN');
        $this->url = env('COSMOS_API_URL');
    }

    /**
     * Recupera detalhes do produto atráves do GTIN/EAN informado.
     */
    public function getProductData(string $ean)
    {
        $productData = [];
        $url = $this->url . '/gtins/' . $ean . 'json';
        $agent = 'Cosmos-API-Request';
        $headers = array(
            "Content-Type: application/json",
            "X-Cosmos-Token: wS3W6JJz4WF8DmBFAGRHMw"
        );

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_USERAGENT, $agent);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FAILONERROR, true);

        $data = curl_exec($curl);
        if ($data === false || $data == NULL) {
            var_dump(curl_error($curl));
            return [];
        } else {
            $object = json_decode($data);
            Log::info((array)$object);

            $productData['name'] = $object->description ?? '';
            $productData['image_url'] = $object->gtins->barcode_image ?? $object->thumbnail ?? '';
            $productData['category'] = $object->category->description ?? '';

            return $productData;
        }

        curl_close($curl);
    }
}
