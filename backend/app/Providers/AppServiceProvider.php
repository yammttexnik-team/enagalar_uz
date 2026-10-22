<?php

namespace App\Providers;

use App\Models\NannyProfile;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Sevimlilarda to'liq klass nomi o'rniga qisqa tur saqlanadi.
        Relation::morphMap(['nanny' => NannyProfile::class, 'vacancy' => Vacancy::class]);
    }
}
