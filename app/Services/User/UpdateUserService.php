<?php

namespace App\Services\User;

use App\Models\User;
use App\Services\UserCompanies\UserCompanyAccessService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateUserService
{
    public function __construct(
        private UserCompanyAccessService $companyAccessService
    ) {}

    public function execute(User $user, array $data, int $approvedBy): User
    {
        $user->update($data);

        if ($user->type === 'company') {
            $this->companyAccessService->sync($user, $data['companies'], $approvedBy);
        } else {
            $this->companyAccessService->detach($user);
        }

        return $user->load('companies');
    }
}
