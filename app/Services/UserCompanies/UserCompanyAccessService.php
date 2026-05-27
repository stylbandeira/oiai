<?php

namespace App\Services\UserCompanies;

use App\Models\User;

class UserCompanyAccessService
{
    public function sync(User $user, array $companies, ?int $approvedBy): void
    {
        $user->companies()->sync(
            $this->normalizeForSync($companies, $approvedBy)
        );
    }

    public function detach(User $user): void
    {
        $user->companies()->detach();
    }

    private function normalizeForSync(array $companies, ?int $approvedBy): array
    {
        return collect($companies)
            ->mapWithKeys(function (array $company) use ($approvedBy) {
                $status = $company['status'] ?? 'active';

                return [
                    (int) $company['id'] => [
                        'status' => $status,
                        'approved_at' => $status === 'active' ? now() : null,
                        'approved_by' => $status === 'active' ? $approvedBy : null,
                    ],
                ];
            })
            ->all();
    }
}
