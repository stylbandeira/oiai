<?php

namespace App\Jobs;

use App\Models\Address;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodeScheduleJob implements ShouldQueue
{
    use Queueable;

    private int $limit;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->limit = 30;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $items = Address::query()
            ->whereNull('latitude')
            ->whereNull('longitude')
            ->where('geocode_status', 'pending')
            ->limit((int) $this->limit)
            ->get();

        foreach ($items as $item) {
            $address = collect([
                $item->street,
                $item->number,
                $item->area,
                $item->city,
                $item->state,
                $item->cep,
                'Brasil',
            ])->filter()->implode(', ');

            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'SeuSistema/1.0 seu-email@dominio.com'
                ])->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $address,
                    'format' => 'json',
                    'limit' => 1,
                    'countrycodes' => 'br',
                    'addressdetails' => 1,
                ]);

                if ($response->successful() && count($response->json()) > 0) {
                    $result = $response->json()[0];

                    $item->update([
                        'latitude' => $result['lat'],
                        'longitude' => $result['lon'],
                        'geocode_status' => 'done',
                        'geocode_error' => null,
                        'geocoded_at' => now(),
                    ]);
                } else {
                    $item->update([
                        'geocode_status' => 'not_found',
                        'geocode_error' => 'Endereço não encontrado',
                    ]);
                }
            } catch (\Throwable $e) {
                $item->update([
                    'geocode_status' => 'error',
                    'geocode_error' => $e->getMessage(),
                ]);
            }

            sleep(1); // respeita o limite do Nominatim público
        }

        Log::alert([
            'Message' => count($items) . ' endereços cadastrados'
        ]);
    }
}
