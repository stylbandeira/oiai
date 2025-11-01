<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CompanyUserResource extends JsonResource
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
            'type' => $this->type,
            // 'img' => $this->img ? config('app.url') . '/storage/' . $this->img : null,
            'email' => $this->email,
            'points' => $this->points,
            'reputation' => $this->reputation,
            'cpf' => $this->cpf,
            'status' => $this->status,

            'companies' => $this->whenLoaded('companies', $this->companies)
        ];
    }
}
