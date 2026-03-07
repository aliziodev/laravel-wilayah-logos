<?php

namespace Aliziodev\WilayahLogos\Commands;

use Aliziodev\WilayahLogos\Support\LogoAssetManager;
use Illuminate\Console\Command;

class LogosPublishCommand extends Command
{
    protected $signature = 'logos:publish
                              {--force : Overwrite files yang sudah ada}';

    protected $description = 'Publish asset logo/lambang daerah ke direktori public.';

    public function __construct(private readonly LogoAssetManager $assetManager)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('📸 Publishing logo/lambang daerah...');

        $publicPath = $this->assetManager->publishToPublic((bool) $this->option('force'));

        $this->info("✅ Logo berhasil di-publish ke {$publicPath}");
        $this->comment('Akses via: asset("vendor/wilayah/logos/prov/img/11.png")');
        $this->comment('Atau via model: $province->logoUrl() / $regency->logoUrl()');

        return self::SUCCESS;
    }
}
