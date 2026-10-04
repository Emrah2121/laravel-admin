<?php

namespace App\Providers;

use App\Models\Admin;
use App\Models\Blog;
use App\Services\DatabaseTranslationLoader;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
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
        $this->app->removeDeferredServices(['translator', 'translation.loader']);

        $this->app->singleton('translation.loader', function ($app) {
            return new DatabaseTranslationLoader($app['files'], $app->langPath());
        });

        $this->app->singleton('translator', function ($app) {
            $trans = new \Illuminate\Translation\Translator($app['translation.loader'], $app->getLocale());
            $trans->setFallback($app->getFallbackLocale());

            return $trans;
        });

        Gate::before(function ($user) {
            if ($user instanceof Admin && $user->isDeveloper()) {
                return true;
            }

            return null;
        });

        // The manipulationable_type column on documents stores these short
        // aliases (see UploadDocumentService callers), not the FQCN, so the
        // morphMany relations on Admin/Blog need a matching morph map.
        Relation::morphMap([
            'admin' => Admin::class,
            'blog' => Blog::class,
        ]);
    }
}
