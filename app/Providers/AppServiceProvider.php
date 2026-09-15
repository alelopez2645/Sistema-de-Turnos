<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
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
        // La app se maquetó con Bootstrap (no se carga Tailwind en el
        // layout), así que la paginación tiene que usar la vista de
        // Bootstrap 5 en vez de la vista por defecto de Laravel (que trae
        // clases de Tailwind y, sin su CSS cargado, se ve gigante y rota).
        Paginator::useBootstrapFive();
    }
}
