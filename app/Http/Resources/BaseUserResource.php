<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BaseUserResource extends JsonResource
{
    /**
     * Campos comuns para TODOS os tipos de usuário
     */
    protected function getCommonFields(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'cpf' => $this->cpf,
            'status' => $this->status,
            'type' => $this->type,
            'email' => $this->email,
            'points' => $this->points,
            'notifications' => $this->notifications,
            'notificationList' => $this->whenLoaded('events', function () {
                return BaseEventResource::collection($this->events);
            }),
            'token' => $this->token,
            'email_verified' => $this->hasVerifiedEmail()
        ];
    }

    /**
     * Method to be overwritten by child
     */
    protected function getUserSpecificFields(): array
    {
        return [];
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return array_merge(
            $this->getCommonFields(),
            $this->getUserSpecificFields()
        );
    }
}
