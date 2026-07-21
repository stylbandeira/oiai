<?php

namespace App\Providers;

use App\Contracts\ListDataAssembler;
use App\Services\ExportService;
use App\Services\Lists\EloquentListDataAssembler;
use App\Services\NFCe\PernambucoNFCeProvider;
use App\Services\NFCe\SaoPauloNFCeProvider;
use App\Services\NFCeScraperService;
use App\Services\NFCeXMLParserService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(ListDataAssembler::class, EloquentListDataAssembler::class);

        $this->app->singleton(ExportService::class, function () {
            return new ExportService();
        });

        $this->app->singleton(NFCeXMLParserService::class, function ($app) {
            return new NFCeXMLParserService();
        });

        $this->app->singleton(NFCeScraperService::class, function ($app) {
            return new NFCeScraperService(
                [
                    $app->make(SaoPauloNFCeProvider::class),
                    $app->make(PernambucoNFCeProvider::class),
                ],
            );
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
