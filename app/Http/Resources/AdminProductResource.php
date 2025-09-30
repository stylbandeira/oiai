<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AdminProductResource extends JsonResource
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
            'img' => $this->img ? config('app.url') . '/storage/' . $this->img : null,
            'average_price' => $this->average_price,

            //Only for Admin
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,

            'unity' => $this->whenLoaded('unity', $this->unity->name),
            'quantity' => $this->whenLoaded('unity', $this->quantity),
            'category' => $this->whenLoaded('category', $this->category->name),
        ];
    }
}
