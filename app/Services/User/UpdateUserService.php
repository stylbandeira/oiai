<?php

namespace App\Services\User;

use App\Models\CompanyOwners;
use App\Models\User;
use App\Services\CompanyOwners\CompanyOwnerService;
use Illuminate\Support\Facades\Log;

class UpdateUserService
{
    public function __construct(
        private CompanyOwnerService $companyOwnerService
    ) {}

    public function execute(User $user, array $data, int $approvedBy): User
    {
        $user->update($data);

        if ($user->type === 'company') {
            Log::alert('sync');
            $this->companyOwnerService->sync($user, $data['companies'], $approvedBy);
        } else {
            $this->companyOwnerService->detach($user);
        }


        Log::alert('sync - out');

        return $user->load('companies');
    }
}
