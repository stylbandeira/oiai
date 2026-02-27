<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ClientProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'img' => $this->img,
            'ean' => $this->ean,
            'average_price' => floatval($this->average_price),
            'unity' => $this->whenLoaded('unity', $this->unity->abbreviation),
            'unity_id' => $this->whenLoaded('unity', $this->unity->id),
            'unity_quantity' => $this->whenLoaded('unity', $this->quantity),
            'category' => $this->whenLoaded('category', $this->category->name),
            'mentioned_quantity' => $this->mentioned_quantity,
            'mentioned_quantity_variant' => $this->mentioned_quantity_variant
        ];
    }
}
