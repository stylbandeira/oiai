<?php

namespace App\Actions\User;

use App\Http\Requests\User\IndexUserRequest;
use App\Http\Resources\AdminUserResource;
use App\Repositories\UserRepository;

class IndexUserAction
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function execute(IndexUserRequest $request)
    {
        $users = $this->userRepository->paginate(
            $request->validated(),
            [
                'with_trashed' => $request->user()->isAdmin(),
            ]
        );

        return AdminUserResource::collection($users);
    }
}
