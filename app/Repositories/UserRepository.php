<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    protected $model;

    public function __construct(User $model)
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

    /**
     * Add points to an user
     *
     * @param [type] $id
     * @param [type] $points
     * @return void
     */
    public function addPoints($id, $points)
    {
        $user = $this->find($id);
        return $this->update($id, [
            User::POINTS => $user->points + $points
        ]);
    }
}
