<?php

namespace App\Repositories;

use App\Models\Company;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class EventRepository
{
    protected $model;

    public function __construct(Event $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->all();
    }

    public function find($id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        $record = $this->find($id);
        $record->update($data);
        return $record;
    }

    public function delete($id)
    {
        return $this->model->destroy($id);
    }

    public function createProductInsertionEvent(User $user, Int $quantity, Company $company)
    {
        $event = [];

        $event = [
            'user_id' => $user->id,
            'title' => Event::TYPE_PRODUCT_INSERT,
            'description' => ucwords(strtolower($user->name)) . ' adicionou ' . $quantity . ' produtos.',
            'where' => $company->name ?? '',
            'type' => 'client',
            'points' => $quantity,
            'link' => Event::TYPE_PRODUCT_INSERT,
        ];

        try {
            $this->model->create($event);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Create a event from when an user requests an access to an company account.
     *
     * @param User $user
     * @param Company $company
     * @return void
     */
    public function createOwnershipRequestEvent(User $user, Company $company)
    {
        $event = [];

        $event = [
            'user_id' => $user->id,
            'title' => Event::TYPE_COMPANY_OWNER_REQUEST,
            'description' => ucwords(strtolower($user->name)) . ' solicitou acesso de admin à empresa: ' . $company->name,
            'where' => $company->name ?? '',
            'type' => 'admin',
            'entity_type' => 'user',
            'entity_id' => $user->id,
            'points' => 0,
            'link' => Event::TYPE_COMPANY_OWNER_REQUEST,
            'target_type' => 'admin',
        ];

        try {
            $this->model->create($event);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Create an event when a user is allowed as owner of a company.
     *
     * @param User $user
     * @param Company $company
     * @return void
     */
    public function createOwnershipAllowedEvent(User $user, Company $company)
    {
        $event = [
            'user_id' => $user->id,
            'title' => Event::TYPE_COMPANY_OWNER_ALLOWED,
            'description' => 'Você foi autorizado como proprietário da empresa: ' . $company->name,
            'where' => $company->name ?? '',
            'type' => 'company',
            'entity_type' => 'company',
            'entity_id' => $company->id,
            'points' => 0,
            'link' => Event::TYPE_COMPANY_OWNER_ALLOWED,
            'target_type' => 'user',
        ];

        try {
            $this->model->create($event);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
