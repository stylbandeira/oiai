<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class ClientUserResource extends JsonResource
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
            'email' => $this->email,
            'notifications' => $this->notifications,
            'notificationList' => $this->whenLoaded('events', function () {
                return ClientEventResource::collection($this->events);
            }),
            'name' => $this->name,
            'points' => $this->points,
            'token' => $this->token,
            'type' => $this->type,
            'email_verified' => $this->hasVerifiedEmail()
        ];
    }
}
