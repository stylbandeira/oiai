<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Event;
use App\Models\User;

class NotificationService
{

    /**
     * TODO - Criar notificação para um usuário
     *
     * @return void
     */
    public function sendUserNotification(): void {}

    public function userOwnershipRequestActivated(User $user, Company $company)
    {
        Event::create([
            'user_id' => $user->id,
            'target_type' => 'company',
            'title' => 'company_ownership_active',
            'description' => 'Sua requisição para administrar a empresa ' . $company->name . ' foi  aceita.',
            'where' => '',
            'type' => 'user',
            'points' => 0,
            'link' => 'company_ownership_active',
            'entity_type' => 'user',
            'entity_id' => $user->id,
        ]);
    }
}
