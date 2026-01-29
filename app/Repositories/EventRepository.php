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
            'title' => 'product_insertion',
            'description' => ucwords(strtolower($user->name)) . ' adicionou ' . $quantity . ' produtos.',
            'where' => $company->name ?? '',
            'type' => 'product_insert',
            'points' => $quantity,
            'link' => $this->generateLink()
        ];

        try {
            $this->model->create($event);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    protected function generateLink()
    {
        return '';
    }
}
