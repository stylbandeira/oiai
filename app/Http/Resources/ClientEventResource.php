<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class ClientEventResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'where' => $this->where,
            'type' => $this->type,
            'points' => $this->points,
            'link' => $this->link,
            'is_new' => Carbon::now()->subDays(3) < $this->created_at,
            'created_at' => $this->created_at,
        ];
    }
}
