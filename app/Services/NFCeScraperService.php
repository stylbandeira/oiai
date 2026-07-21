<?php

namespace App\Services;

use App\Contracts\NFCe\StateNFCeProvider;
use Illuminate\Support\Facades\Log;

class NFCeScraperService
{
    /** @param array<StateNFCeProvider> $stateProviders */
    public function __construct(private array $stateProviders) {}

    public function scrapeFromQRCode(string $qrData): array
    {
        foreach ($this->stateProviders as $provider) {
            if (! $provider->supports($qrData)) {
                continue;
            }

            try {
                return $provider->scrapeFromQRCode($qrData);
            } catch (\Throwable $exception) {
                Log::error('Erro ao processar NFCe no provider estadual.', [
                    'provider' => $provider::class,
                    'qr_data' => $qrData,
                    'error' => $exception->getMessage(),
                ]);

                return [
                    'status' => 'error',
                    'error' => $exception->getMessage(),
                    'qr_data' => $qrData,
                ];
            }
        }

        return [
            'status' => 'error',
            'error' => 'Não existe provider cadastrado para a UF informada.',
            'qr_data' => $qrData,
        ];
    }
}
