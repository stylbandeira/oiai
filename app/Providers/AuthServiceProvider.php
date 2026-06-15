<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;

use App\Models\Address;
use App\Models\Company;
use App\Models\CompanyOwners;
use App\Models\ItensList;
use App\Models\User;
use App\Policies\AddressPolicy;
use App\Policies\CompanyOwnersPolicy;
use App\Policies\CompanyPolicy;
use App\Policies\ItensListPolicy;
use App\Policies\UsersPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Address::class => AddressPolicy::class,
        User::class => UsersPolicy::class,
        ItensList::class => ItensListPolicy::class,
        Company::class => CompanyPolicy::class,
        CompanyOwners::class => CompanyOwnersPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}
