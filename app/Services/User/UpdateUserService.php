<?php

namespace App\Services\User;

use App\Models\User;
use App\Services\CompanyOwners\CompanyOwnerService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateUserService
{
    public function __construct(
        private CompanyOwnerService $companyAccessService
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
