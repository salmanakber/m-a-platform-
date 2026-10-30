<?php

namespace App\Providers;

use App\Models\ArticleCategory;
use App\Services\Settings\SettingsService;
use App\Support\SettingKey;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
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
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::defaultView('vendor.pagination.ne');
        Paginator::defaultSimpleView('vendor.pagination.ne');

        View::composer('layouts.public', function ($view) {
            $settings = app(SettingsService::class);
            $categories = collect();
            try {
                if (Schema::hasTable('article_categories')) {
                    $categories = ArticleCategory::query()->orderBy('sort_order')->orderBy('name_de')->get();
                }
            } catch (\Throwable $e) {
                $categories = collect();
            }

            $view->with([
                'navArticleCategories' => $categories,
                'footerContactLines' => $settings->get(
                    SettingKey::FOOTER_CONTACT_LINES,
                    "nachfolge-experten.ch\nBrünigstrasse 144\n6060 Sarnen"
                ),
                'footerContactEmail' => $settings->get(SettingKey::FOOTER_CONTACT_EMAIL, 'kontakt@nachfolge-experten.ch'),
            ]);
        });
    }
}
