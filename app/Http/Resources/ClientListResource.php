<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClientListResource extends JsonResource
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
            'user_id' => $this->user_id,
            'name' => $this->name,
            'favorite' => boolval($this->favorite),
            'status' => boolval($this->status),
            'total' => floatval($this->total),
            'products' => $this->whenLoaded('products', $this->products),
            'productsQuantity' => $this->whenLoaded('products', $this->products->count()),
        ];
    }
}
