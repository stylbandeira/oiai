<?php

namespace App\Console;

use App\Jobs\AveragePriceJob;
use App\Jobs\GeocodeScheduleJob;
use App\Jobs\ProcessInvoiceJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Versão 1: Com log detalhado
        $schedule->job(app(ProcessInvoiceJob::class))
            ->everyMinute()
            ->before(function () {
                Log::info('Antes de despachar ProcessInvoiceJob', [
                    'memory' => memory_get_usage(),
                    'time' => now()
                ]);
            })
            ->after(function () {
                Log::info('Depois de despachar ProcessInvoiceJob');
            })
            ->onSuccess(function () {
                Log::info('ProcessInvoiceJob despachado com sucesso');
            })
            ->onFailure(function () {
                Log::error('Falha ao despachar ProcessInvoiceJob');
            });

        $schedule->job(new AveragePriceJob())
            ->everyFourHours()
            ->onFailure(function () {
                Log::error('Falha ao despachar AveragePriceJob');
            });

        // Seu heartbeat original
        $schedule->call(function () {
            Log::info('Scheduler heartbeat: ' . now());
        })->everyMinute();

        $schedule->job(new GeocodeScheduleJob())
            ->everyMinute()
            ->onFailure(function () {
                Log::alert("Erro no Job de Geolocalização");
            });
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
