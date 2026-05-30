<?php

namespace App\Services\CompanyOwners;

use App\Models\Company;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class CompanyOwnerService
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function sync(User $user, array $companies, ?int $approvedBy): void
    {
        $user->companies()->sync(
            $this->normalizeForSync($user, $companies, $approvedBy)
        );
    }

    public function detach(User $user): void
    {
        $user->companies()->detach();
    }

    private function normalizeForSync(User $user, array $companies, ?int $approvedBy): array
    {
        return collect($companies)
            ->mapWithKeys(function (array $company) use ($user, $approvedBy) {
                $status = $company['status'] ?? 'active';

                try {
                    $this->notificationService->userOwnershipRequestActivated($user, Company::find($company['id']));
                } catch (\Throwable $th) {
                    Log::error('Não foi possível criar notificação para a empresa', [
                        'error' => $th->getMessage()
                    ]);
                }

                return [
                    (int) $company['id'] => $this->buildPivotData(
                        $status,
                        $approvedBy
                    ),
                ];
            })
            ->all();
    }

    private function buildPivotData(string $status, ?int $approvedBy): array
    {
        return [
            'status' => $status,
            'approved_at' => $status === 'active' ? now() : null,
            'approved_by' => $status === 'active' ? $approvedBy : null,
        ];
    }
}
