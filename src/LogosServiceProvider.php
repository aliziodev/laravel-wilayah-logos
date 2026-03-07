<?php

namespace Aliziodev\WilayahLogos;

use Aliziodev\WilayahLogos\Commands\LogosPublishCommand;
use Aliziodev\WilayahLogos\Support\LogoAssetManager;
use Illuminate\Support\ServiceProvider;

class LogosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/logos.php', 'logos');

        $this->app->singleton(LogoAssetManager::class, fn () => new LogoAssetManager);
    }

    public function boot(): void
    {
        $this->injectLogoMacros();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/logos.php' => config_path('logos.php'),
            ], 'wilayah-logos-config');

            $this->commands([LogosPublishCommand::class]);
        }
    }

    protected function injectLogoMacros(): void
    {
        $models = [
            config('wilayah.models.province'),
            config('wilayah.models.regency'),
        ];

        foreach ($models as $model) {
            if (! $model || ! class_exists($model)) {
                continue;
            }

            $model::macro('logoPath', function (string $variant = 'img'): string {
                $folder = $variant === 'thumb' ? 'thumbs' : 'img';
                $basePath = rtrim((string) config('logos.public_path', public_path('vendor/wilayah/logos')), DIRECTORY_SEPARATOR);

                if (str_contains($this->code, '.')) {
                    $provinceCode = substr((string) $this->code, 0, 2);

                    return $basePath.DIRECTORY_SEPARATOR.'kab'.DIRECTORY_SEPARATOR.$provinceCode.DIRECTORY_SEPARATOR.$folder.DIRECTORY_SEPARATOR.$this->code.'.png';
                }

                return $basePath.DIRECTORY_SEPARATOR.'prov'.DIRECTORY_SEPARATOR.$folder.DIRECTORY_SEPARATOR.$this->code.'.png';
            });

            $model::macro('logoUrl', function (string $variant = 'img'): ?string {
                $path = $this->logoPath($variant);
                if (! file_exists($path)) {
                    return null;
                }

                $relative = str_replace(DIRECTORY_SEPARATOR, '/', str_replace(public_path().DIRECTORY_SEPARATOR, '', $path));

                return asset($relative);
            });
        }
    }
}
